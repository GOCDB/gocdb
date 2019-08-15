<?php

namespace org\gocdb\services;
/*
 * Copyright ? 2011 STFC Licensed under the Apache License, Version 2.0 (the "License"); you may not use this file except in compliance with the License. You may obtain a copy of the License at http://www.apache.org/licenses/LICENSE-2.0 Unless required by applicable law or agreed to in writing, software distributed under the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the License for the specific language governing permissions and limitations under the License.
 */
require_once __DIR__ . '/AbstractEntityService.php';
require_once __DIR__ . '/Factory.php';
require_once __DIR__ . '/User.php';

/**
 * A service layer to aid the sending of notification emails
 *
 * @author James McCarthy
 */
class NotificationService extends AbstractEntityService {

    /**
     * This class will take an entity of either site, service group, NGI or Project.
     * It will then get the roles from the entity
     * and then get the users for each of those roles. Then using the authoriseAction function it will
     * ascertain if a given user has the permission to grant a role. If they do there email address is added to an array. This array
     * of email addresses will then be sent a notification that they have a pending role request they can approve.
     *
     * If a site or NGI has no users with roles attached to it due to being newly created then this method will get the parent NGI and
     * send an email to those users to approve. It does this by passing the parent entity back into this method recursively.
     *
     *
     * @param Site/ServiceGroup/NGI/Project $entity
     */
    public function roleRequest ($role_requested, $requesting_user, $entity) {
        $project = null;
        $authorising_user_ids = null;
        $projectIds = null;

        // Get the roles from the entity
        foreach ( $entity->getRoles () as $role ) {
            $roles [] = $role;
        }

        // Now for each role get the user
        foreach ( $roles as $role ) {
            $enablingRoles = \Factory::getRoleActionAuthorisationService()->authoriseAction(\Action::GRANT_ROLE, $entity, $role->getUser())->getGrantingRoles();
            if ($entity instanceof \Site) {
                // If the site has no site adminstrators to approve the role request then send an email to the parent NGI users to approve the request
                if ($roles == null) {
                    $this->roleRequest ( $role_requested, $requesting_user, $entity->getNgi () ); // Recursivly call this function to send email to the NGI users
                }
            } else if ($entity instanceof \NGI) {
                $projects = $entity->getProjects (); // set project with the NGI's parent project and later recurse with this

                // Only send emails to Project users if there are no users with grant_roles over the NGI
                if ($roles == null) {
                    // Get the ID's of each project so we can remove duplicates
                    foreach ( $projects as $project ) {
                        $projectIds [] = $project->getId ();
                    }
                    $projectIds = array_unique ( $projectIds );
                }
            }

            // Get the user id and add it to the array if they have an enabling role
            if (count ( $enablingRoles ) > 0) {
                $authorising_user_ids [] = $role->getUser ()->getId ();
            }
        }

        /*
         * No users are able to grant the role or there are no users over this entity. In this case we will email the parent entity for approval
         */
        if ($authorising_user_ids == null || count($authorising_user_ids) == 0) {
            if ($entity instanceof \Site) {
                $this->roleRequest ( $role_requested, $requesting_user, $entity->getNgi () ); // Recursivly call this function to send email to the NGI users
            } else if ($entity instanceof \NGI) {
                /*
                 * It is important to remove duplicate projects here otherwise we will spam the same addresses as we recursively call this method.
                 */
                $projects = $entity->getProjects (); // set project with the NGI's parent project and later recurse with this
                $projectIds = array();
                // Get the ID's of each project so we can remove duplicates
                foreach ( $projects as $project ) {
                    $projectIds [] = $project->getId ();
                }
                $projectIds = array_unique ( $projectIds );
            }
        } else {
            // If the entity has valid users who can approve the role then send the email notification.

            // Remove duplicate user ids from array
            $authorising_user_ids = array_unique ( $authorising_user_ids );

            // Send email to all users who can approve this role request
            if ($authorising_user_ids != null) {
                foreach ( $authorising_user_ids as $user_id ) {
                    $approving_user = \Factory::getUserService()->getUser($user_id);
                    $this->send_email($role_requested, $requesting_user, $entity->getName(), $approving_user);
                }
            }
        }

        /**
         * For each project ID get the entity and run this function again for each entity so
         * that for each NGI the email notification is sent to all users who hold roles over the parent
         * NGI(s).
         */
        if ($projectIds != null) {
            foreach ( $projectIds as $pid ) {
                $project = \Factory::getOwnedEntityService ()->getOwnedEntityById ( $pid );
                if(sendMail){
                    $this->roleRequest ( $role_requested, $requesting_user, $project );
                } else {
                    echo $project->getName () . "<br>";
                }
            }
        }
    }


    /**
    * Return the PortalURL to enable an accurate link to the role approval view to be created
    */
    private function get_webPortalURL() {
        $localInfoXML = simplexml_load_file(__DIR__ . "/../../config/local_info.xml");
        return $localInfoXML->local_info->web_portal_url;
    }


    /**
    * Return whether send_email is enabled in the config file
    */
    private function get_config_send_email() {
        $localInfoXML = simplexml_load_file(__DIR__ . "/../../config/local_info.xml");
        return strtolower($localInfoXML->local_info->send_email) === 'true';
    }


    private function mock_mail($to, $subject, $message, $additional_headers = "", $additional_parameters = "") {
        echo "<!--\n";
        echo "Sending mail disabled, but would have sent:\n";
        echo "$additional_headers\n";
        echo "To: $to\n";
        echo "Subject: $subject\n";
        echo "\n$message\n";
        echo "\nAdditional Parameters: $additional_parameters\n";
        echo "-->\n";
        return True;
    }


    private function send_email($role_requested, $requesting_user, $entity_name, $approving_user) {
        $subject = sprintf(
            'GocDB: A Role request from %1$s over %2$s requires your attention',
            $requesting_user->getForename(),
            $entity_name
        );

        $body = sprintf(
            implode("\n", array(
                'Dear %1$s,',
                '%2$s requested %3$s on %4$s which requires your attention.',
                '',
                'You can approve or deny the request here:',
                '    %5$s/index.php?Page_Type=Role_Requests',
                '',
                'Note: This role could already have been approved or denied by another GocDB User',
            )),
            $approving_user->getForename(),
            $requesting_user->getForename(),
            $role_requested->getRoleType()->getName(),
            $role_requested->getOwnedEntity()->getName(),
            $this->get_webPortalURL()
        );

        $email = $approving_user->getEmail();
        $headers = "From: no-reply@goc.egi.eu";

        if ($this->get_config_send_email()) {
            mail($email, $subject, $body, $headers);
        } else {
            $this->mock_mail($email, $subject, $body, $headers);
        }
    }
}
