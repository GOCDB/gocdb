<?php
namespace org\gocdb\services;

/* Copyright (c) 2011 STFC
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/**
 * Parent class to those service classes that deal with entities, containing 
 * those methods required by all classes.
 *
 * @author John Casson
 * @author David Meredith
 * @author George Ryall
 */

abstract class AbstractEntityService {
    protected $em;

    public function __construct() {
    }

    /**
     * Set the EntityManager instance used by all service methods. 
     * @param \Doctrine\ORM\EntityManager $em
     */
    public function setEntityManager(\Doctrine\ORM\EntityManager $em){
        $this->em = $em;  
    }
    
    /**
     * @return \Doctrine\ORM\EntityManager 
     */
    public function getEntityManager(){
        return $this->em; 
    }
    
     /**
     * Checks witht the config service if the portal is in read only mode and if 
     * it is throws an exception unless the user is a GOCDB admin
     * 
     * @param \User $user user
     * @throws \Exception
     */
    protected function checkPortalIsNotReadOnlyOrUserIsAdmin(\User $user = NULL){
        require_once __DIR__ . '/Config.php';
        
        //this block is required to deal with unregistered users (where $user is null)
        $userIsAdmin = false;
        if(!is_null($user)){
            if($user->isAdmin()){ //sub query required becauser ->isAdmin can't be called on null
                $userIsAdmin = true;
            }
        }
        
        $configServ = new \org\gocdb\services\Config();     
        if ($configServ->IsPortalReadOnly() and !$userIsAdmin){
            throw new \Exception("The portal is currently in read only mode, so this action is not permitted");
        }
    }
    
    /**
     * Checks if a user is an administrator and throws an exception if they are 
     * not. Used in functions that make changes that only GOCDB admins should be
     * able to make
     * 
     * @param \User $user User making the change
     * @throws \Exception
     */
    protected function checkUserIsAdmin(\User $user = null) {
        //isAdmin can't be called on null, so first check that the user is registered
        if (is_null($user)) {
            throw new \Exception("Unregistered users may not make changes");
        }
        //Check the user is an administrator, if not throw an exception
        if (!$user->isAdmin()) {
            throw new \Exception("Only GOCDB admins may perform this action");
        }
    }
}
