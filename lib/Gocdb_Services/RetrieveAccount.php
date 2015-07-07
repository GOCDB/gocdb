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
require_once __DIR__ . '/AbstractEntityService.php';

/**
 * GOCDB Stateless service facade (business routnes) for DN Change operations.
 * The public API methods are transactional.
 *
 * @author John Casson
 * @author David Meredith
 */
class RetrieveAccount extends AbstractEntityService {
     
    /**
     * Validates an account retrieval request
     * @param string $oldDn
     * @param string $email
     */
    public function validate($oldDn, $email) {
        require_once __DIR__ . '/User.php'; 
        $userService = new \org\gocdb\services\User(); 
        $userService->setEntityManager($this->em); 
        $user = $userService->getUserByPrinciple($oldDn); 
        if($user == null) {
            throw new \Exception("Can't find user with DN $oldDn");
        }

        if(strcasecmp($user->getEmail(), $email)) {
            throw new \Exception("E-mail address doesn't match DN");
        }
    }

    /**
     * Processes an account retrieval request for the passed user
     * @param \User $user
     */
    public function newRetrieveAccountRequest($currentDn, $givenEmail, $oldDn) {


        //get user from old dn and throw exception if they don't exist
        require_once __DIR__ . '/User.php'; 
        $userService = new \org\gocdb\services\User(); 
        $userService->setEntityManager($this->em); 
        $user = $userService->getUserByPrinciple($oldDn); 
        if($user == null) {
            throw new \Exception("Can't find user with DN $oldDn");
        }
        
        //check the given email address matches the one given
        if(strcasecmp($user->getEmail(), $givenEmail)) {
            throw new \Exception("E-mail address doesn't match DN");
        }

        //Check the portal is not in read only mode, throws exception if it is. If portal is read only, but the user whos DN is being changed is an admin, we will still be able to proceed.
        $this->checkPortalIsNotReadOnlyOrUserIsAdmin($user);
        
        //check if there has already been a request for this dn, if there has, 
        //remove it. This must be in a seperate try catch block to the new one, 
        //to prevent constraint violations
        $previousRequest = $this->getRetrieveAccountRequestByUserId($user->getId());
        if(!is_null($previousRequest)){
            try{
                $this->em->getConnection()->beginTransaction();
                $this->em->remove($previousRequest);
                $this->em->flush();
                $this->em->getConnection()->commit();
            } catch(\Exception $e){
                $this->em->getConnection()->rollback();
                $this->em->close();
                throw $e;
            }  
        }
        
        //Generate confirmation code
        $code = $this->generateConfirmationCode($user->getCertificateDn());
        
        $retrieveAccountReq = new \RetrieveAccountRequest($user, $code, $currentDn);
        //die('code ['.$code.']');
        
        //apply change
        try {
            $this->em->getConnection()->beginTransaction();
            $this->em->persist($retrieveAccountReq);
            $this->em->flush();
            
            //send email (before commit - if it fails we'll need a rollback)
            $this->sendConfirmationEmail($user, $code, $currentDn);
            
            $this->em->getConnection()->commit();
        } catch(\Exception $ex){
            $this->em->getConnection()->rollback();
            $this->em->close();
            throw $ex;
        }
        
    }

    private function generateConfirmationCode($userdn){
        $confirm_code = rand(1, 10000000);
        $confirm_hash = sha1($userdn.$confirm_code);
        return $confirm_hash;
    }
    
    /**
     * Gets a retrieve account request from the database based on userid
     * @param integer $userId userid of the request to be retrieved
     * @return arraycollection
     */
    public function getRetrieveAccountRequestByUserId($userId){
        $dql = "SELECT r
                FROM RetrieveAccountRequest r
                JOIN r.user u 
                WHERE u.id = :id";
        
        $request = $this->em
	    	->createQuery($dql)
	    	->setParameter('id', $userId)
	    	->getOneOrNullResult();
        
        return $request;
    }
    
    /**
     * Gets a retrieve account request from the database based on the conformation code
     * @param string $code confirmation code of the request being retrieved
     * @return arraycollection
     */
    public function getRetrieveAccountRequestByConfirmationCode($code){
        $dql = "SELECT r
                FROM RetrieveAccountRequest r
                WHERE r.confirmCode = :code";
        
        $request = $this->em
	    	->createQuery($dql)
	    	->setParameter('code', $code)
	    	->getOneOrNullResult();
        
        return $request;
    }
    
    /**
     * sends a confimation email to the user
     * 
     * @param \User $user user who's dn is being changed
     * @param type $confirmationCode genersted confirmation code 
     * @param type $newDn new dn for $user
     * @throws \Exception
     */
    private function sendConfirmationEmail(\User $user, $confirmationCode, $newDn){
        $portal_url = \Factory::getConfigService()->GetPortalURL();
        //echo $portal_url; 
       //die();
        
        $link = $portal_url."/index.php?Page_Type=User_Validate_DN_Change&c=".$confirmationCode;
        $subject = "Validation of changes on your GOCDB account";
        $body = "Dear GOCDB User,\n\n"
            ."A request to retrieve and associate your GOCDB account and privileges with a "
                . "new account ID has just been made on GOCDB (e.g. you have a new certificate with a different DN)."
            ."\n\nThe new account ID is: $newDn"
            ."\n\nIf you wish to associate your GOCDB account with this account ID, please validate your request by clicking on the link below:\n"
            ."$link".
            "\n\nIf you did not create this request in GOCDB, please immediately contact gocdb-admins@mailman.egi.eu" ;
            ;
        //If "sendmail_from" is set in php.ini, use second line ($headers = '';):
        $headers = "From: no-reply@goc.egi.eu";           
        //$headers = "";
        
        //mail command returns boolean. False if message not accepted for delivery.
        if (!mail($user->getEmail(), $subject, $body, $headers)){
            throw new \Exception("Unable to send email message");
        }
        
        //echo $body;
    }
    
    public function confirmAccountRetrieval ($code, $currentDn){
        //get the request
        $request = $this->getRetrieveAccountRequestByConfirmationCode($code);
        
        //check there is a result
        if(is_null($request)){
            throw new \Exception("Confirmation URL invalid. If you have submitted multiple requests for the same account, please ensure you have used the link in the most recent email");
        }
        
        $user = $request->getUser();
        
        //Check the portal is not in read only mode, throws exception if it is. If portal is read only, but the user whos DN is being changed is an admin, we will still be able to proceed.
        $this->checkPortalIsNotReadOnlyOrUserIsAdmin($user);
        
        //check the DN currently being used by the user is the one they want their account changed to
        if($currentDn != $request->getNewDn()){
            throw new Exception("Your current certificate DN does not match the one to which it was requested to link the user account. The link will only work once, if you have refreshed the page or clicked the link a second time you will see this messaage"); //TODO: reword
        }
        
        //update user, remove request from table
        try{
            $user->setCertificateDn($request->getNewDn()); 
            $this->em->getConnection()->beginTransaction();
            $this->em->merge($user);
            $this->em->remove($request);
            $this->em->flush();
            $this->em->getConnection()->commit();
        } catch(\Exception $e){
            $this->em->getConnection()->rollback();
            $this->em->close();
            throw $e;
        }
        
    }
}