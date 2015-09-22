<?php

namespace org\gocdb\services;
/*
 * Copyright ? 2011 STFC Licensed under the Apache License, Version 2.0 (the "License"); you may not use this file except in compliance with the License. You may obtain a copy of the License at http://www.apache.org/licenses/LICENSE-2.0 Unless required by applicable law or agreed to in writing, software distributed under the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the License for the specific language governing permissions and limitations under the License.
 */
require_once __DIR__ . '/AbstractEntityService.php';
require_once __DIR__ . '/Factory.php';

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
    public function roleRequest($entity) {
        $project = null;
        $emails = null;
        $projectIds = null;
        
        // Get the roles from the entity
        foreach ( $entity->getRoles () as $role ) {
            $roles [] = $role;
        }
        
        // Now for each role get the user
        foreach ( $roles as $role ) {
            $enablingRoles = \Factory::getRoleActionAuthorisationService()->authoriseAction(\Action::GRANT_ROLE, $entity, $role->getUser()); 
            if ($entity instanceof \Site) {
                //$enablingRoles = \Factory::getSiteService ()->authorize Action ( \Action::GRANT_ROLE, $entity, $role->getUser () );
                
                // If the site has no site adminstrators to approve the role request then send an email to the parent NGI users to approve the request
                if ($roles == null) {
                    $this->roleRequest ( $entity->getNgi () ); // Recursivly call this function to send email to the NGI users
                }
            } else if ($entity instanceof \ServiceGroup) {
                //$enablingRoles = \Factory::getServiceGroupService ()->authorize Action ( \Action::GRANT_ROLE, $entity, $role->getUser () );
            } else if ($entity instanceof \Project) {
                //$enablingRoles = \Factory::getProjectService ()->authorize Action ( \Action::GRANT_ROLE, $entity, $role->getUser () );
            } else if ($entity instanceof \NGI) {
                //$enablingRoles = \Factory::getNgiService ()->authorize Action ( \Action::GRANT_ROLE, $entity, $role->getUser () );
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
            
            // remove admin from enabling roles
            /*$position = array_search ( 'GOCDB_ADMIN', $enablingRoles );
            if ($position != null) {
                unset ( $enablingRoles [$position] );
            }*/
            // Get the users email and add it to the array if they have an enabling role
            if (count ( $enablingRoles ) > 0) {
                $emails [] = $role->getUser ()->getEmail ();
            }
        }
        
        /*
         * No users are able to grant the role or there are no users over this entity. In this case we will email the parent entity for approval
         */
        if ($emails == null || count($emails) == 0) {
            if ($entity instanceof \Site) {
                $this->roleRequest ( $entity->getNgi () ); // Recursivly call this function to send email to the NGI users
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
            
            // Remove duplicate emails from array
            $emails = array_unique ( $emails );
            
            // Get the PortalURL to create an accurate link to the role approval view
            $localInfoLocation = __DIR__ . "/../../config/local_info.xml";
            $localInfoXML = simplexml_load_file ( $localInfoLocation );
            $webPortalURL = $localInfoXML->local_info->web_portal_url;
            
            // Email content
            $headers = "From: no-reply@goc.egi.eu";
            $subject = "GocDB: A Role request requires attention";
            
            $body = "Dear GOCDB User,\n\n" . "A user has requested a role that requires attention.\n\n" . 
                    "You can approve or deny this request here:\n\n" . $webPortalURL . "/index.php?Page_Type=Role_Requests\n\n" . 
                    "Note: This role may already have been approved or denied by another GocDB User";
        
            $sendMail = TRUE; 
            // Send email to all users who can approve this role request
            if ($emails != null) {
                foreach ( $emails as $email ) {
                    if($sendMail){ 
                       mail($email, $subject, $body, $headers);
                    } else {
                       echo "Email: " . $email . "<br>";
                       echo "Subject: " . $subject . "<br>";
                       echo "Body: " . $body . "<br>";
                    }
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
                    $this->roleRequest ( $project );
                } else {
                    echo $project->getName () . "<br>";
                }
            }
        }
    }
}
