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
 * GOCDB Stateless service facade (business routnes) for certification status entities.
 * The public API methods are transactional.
 *
 * @author John Casson
 * @author David Meredith
 */
class CertificationStatus extends AbstractEntityService{

    /**
     * @param int Certification status ID
     * @return \CertificationStatus
     */
    public function getCertificationStatus($id) {
        $dql = "SELECT cs FROM CertificationStatus cs WHERE cs.id = :id";
        $status = $this->em->createQuery($dql)
                       ->setParameter('id', $id)
                       ->getSingleResult();
        return $status;
    }

    /**
     * Returns all cert statuses
     * @return array Of \CertificationStatus objects
     */
    public function getCertificationStatuses() {
        $dql = "SELECT cs FROM CertificationStatus cs";
        $statuses = $this->em->createQuery($dql)->getResult();
        return $statuses;
    }

    /**
     * @param \Site $site
     * @param \CertificationStatus $certStatus
     * @param \User $user
     * @param string $reason The reason for this change, max 300 char. 
     * @throws \Exception If access is denied or the change is invalid
     */
    public function editCertificationStatus(\Site $site, \CertificationStatus $newCertStatus, \User $user, $reason) {
        //$this->editAuthorization($site, $user);
        require_once __DIR__ . '/Site.php'; 
        $siteService = new \org\gocdb\services\Site(); 
        $siteService->setEntityManager($this->em);
        if(count($siteService->authorizeAction(\Action::SITE_EDIT_CERT_STATUS, $site, $user))==0 ){
           throw new \Exception('You do not have permission to change site certification status'); 
        }
        // TODO use validate service 
        if(empty($reason) ){
           throw new LogicException('A reason is required');     
        }
        if(strlen($reason) > 300){
            throw new LogicException('Invalid reason - 300 char max'); 
        } 
        // Admins can do any cert status change, e.g. to undo mistakes.
        if(!$user->isAdmin()){
          $this->isChangeValid($site, $newCertStatus);
        }
        $oldStatusString = $site->getCertificationStatus()->getName(); 
        try {
            $this->em->beginTransaction();
            $now = new \DateTime('now', new \DateTimeZone('UTC')); 
            
            // create a new CertStatusLog 
            $certLog = new \CertificationStatusLog(); 
            $certLog->setAddedBy($user->getCertificateDn()); 
            $certLog->setNewStatus($newCertStatus->getName()); 
            $certLog->setOldStatus($oldStatusString); 
            $certLog->setAddedDate($now); 
            $certLog->setReason($reason); 
            $this->em->persist($certLog); 
            
            // update our site  
            $site->addCertificationStatusLog($certLog); 
            $site->setCertificationStatus($newCertStatus);
            $site->setCertificationStatusChangeDate($now); 
            
            $this->em->merge($site);
            $this->em->flush();
            $this->em->getConnection()->commit();
        } catch(\Exception $ex){
            $this->em->getConnection()->rollback();
            $this->em->close();
            throw $ex;
        }
    }

    /**
     * Check for invalid cert status changes.
     * The following transitions are valid:
     * <ul>
     *   <li>candidate -> uncertified</li>
     *   <li>candidate -> closed</li>
     *   <li>uncertified -> certified</li>
     *   <li>certified -> suspended</li>
     *   <li>certified -> closed (on site request)</li>
     *   <li>suspended -> uncertified</li>
     *   <li>suspended -> closed</li>
     * </ul>
     * The following are forbidden:
     * <ul>
     *   <li>suspended -> certified</li>
     *   <li>candidate -> something else but uncertified and closed</li>
     *   <li>closed -> anything else</li>
     * </ul>
     * https://wiki.egi.eu/wiki/GOCDB/Input_System_User_Documentation#Changing_Site_Certification_Status
     * @param \Site $site
     * @param \CertificationStatus $newCertStatus
     * @throws \Exception if the change is invalid
     */
    public function isChangeValid(\Site $site, \CertificationStatus $newCertStatus) {
        $oldStatus = $site->getCertificationStatus()->getName();
        $newStatus = $newCertStatus->getName();

        // This exception is only thrown if the transition is invalid
        $error = "A Certification Status can't transition from $oldStatus to $newStatus.";
        $e = new \Exception($error);

        if ($oldStatus === 'Suspended' && $newStatus === 'Certified') {
            throw $e;
        }

        if ($oldStatus === 'Closed') {
            throw $e;
        }
        // forbidden: candidate -> any other status except uncertified and closed
        if ($oldStatus == 'Candidate') {
            if ($newStatus !== 'Uncertified' && $newStatus !== 'Closed') {
                throw $e;
            }
        }
    }
}
?>