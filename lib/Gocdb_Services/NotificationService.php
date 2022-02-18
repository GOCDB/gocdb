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
     * @param OwnedEntity $entity An instance of Site,Service,NGI,Project or other OwnedEntity.
     * @return void
     */
    public function roleRequest ($roleRequested, $requestingUser, $entity) {
        $project = null;
        $authorisingUserIds = [];
        $projectIds = null;

        // For each role the entity has.
        foreach ($entity->getRoles() as $role) {
            // Get the corresponding user.
            $user = $role->getUser();

            // Determine if that user has roles that can approve role requests,
            // this role may not be the same as the one currently in the $role
            // variable.
            $enablingRoles = \Factory::getRoleActionAuthorisationService()->authoriseAction(\Action::GRANT_ROLE, $entity, $user)->getGrantingRoles();

            // If they can, add their user id to the list of authorising user
            // ids.
            if (count($enablingRoles) > 0) {
                $authorisingUserIds [] = $user->getId();
            }
        }

        // If there are no users that are able to grant the role over this entity,
        // we will email the parent entity for approval.
        if (count($authorisingUserIds) == 0) {
            if ($entity instanceof \Site) {
                // Sites can only have a single parent NGI.
                $this->roleRequest ( $roleRequested, $requestingUser, $entity->getNgi () ); // Recursivly call this function to send email to the NGI users
            } else if ($entity instanceof \NGI) {
                /*
                 * NGIs can belong to multiple Projects.
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
                // Also send email to GOCDB Admins
                $this->sendGocdbAdminsEmail($roleRequested, $requestingUser, $entity->getName());
        } else {
            // If the entity has valid users who can approve the role then send the email notification.

            // Remove duplicate user ids from array
            $authorisingUserIds = array_unique ( $authorisingUserIds );

            // Send email to all users who can approve this role request
           foreach ( $authorisingUserIds as $userId ) {
                $approvingUser = \Factory::getUserService()->getUser($userId);
                $this->sendEmail($roleRequested, $requestingUser, $entity->getName(), $approvingUser);
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
                $this->roleRequest ( $roleRequested, $requestingUser, $project );

            }
        }
    }


    /**
    * Return the PortalURL to enable an accurate link to the role approval view to be created
    */
    private function getWebPortalURL() {
        return \Factory::getConfigService()->GetPortalURL();
    }

    private function sendEmail($roleRequested, $requestingUser, $entityName, $approvingUser) {
        $subject = sprintf(
            'GOCDB: A Role request from %1$s %2$s over %3$s requires your attention',
            $requestingUser->getForename(),
            $requestingUser->getSurname(),
            $roleRequested->getOwnedEntity()->getName()
        );

        $body = sprintf(
            implode("\n", array(
                'Dear %1$s,',
                '',
                '%2$s %3$s requested the "%4$s" role over %5$s which requires your attention.',
                '',
                'You can approve or deny the request here:',
                '    %6$s/index.php?Page_Type=Role_Requests',
                '',
                'Note: This role could already have been approved or denied by another GOCDB User',
            )),
            $approvingUser->getForename(),
            $requestingUser->getForename(),
            $requestingUser->getSurname(),
            $roleRequested->getRoleType()->getName(),
            $roleRequested->getOwnedEntity()->getName(),
            $this->getWebPortalURL()
        );

        $emailAddress = $approvingUser->getEmail();
        $headers = "From: GOCDB <gocdb-admins@mailman.egi.eu>";

        \Factory::getEmailService()->send($emailAddress, $subject, $body, $headers);
    }

    private function sendGocdbAdminsEmail($roleRequested, $requestingUser) {
        $subject = sprintf(
            'GOCDB: A Role request from %1$s %2$s over %3$s has no approving users',
            $requestingUser->getForename(),
            $requestingUser->getSurname(),
            $roleRequested->getOwnedEntity()->getName()
        );

        $body = sprintf(
            implode("\n", array(
                'Dear GOCDB Admins,',
                '',
                '%1$s %2$s requested the "%3$s" role over %4$s, and there are no '.
                'approving users available for the request.',
                '',
                'You can approve or deny the request here:',
                '    %5$s/index.php?Page_Type=Role_Requests',
            )),
            $requestingUser->getForename(),
            $requestingUser->getSurname(),
            $roleRequested->getRoleType()->getName(),
            $roleRequested->getOwnedEntity()->getName(),
            $this->getWebPortalURL()
        );

        $emailAddress = \Factory::getConfigService()->getGocdbAdminsEmail();
        $headers = "From: GOCDB";

        \Factory::getEmailService()->send($emailAddress, $subject, $body, $headers);
    }
}
