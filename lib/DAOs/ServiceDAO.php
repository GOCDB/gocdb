<?php
include_once __DIR__ . '/AbstractDAO.php';
/**
 * All public methods are non-transactional - they queue changes to the DB with
 * EntityManager remove(), merge() but do not call commit() or rollback().
 * They should always be called from within a transaction.
 * Authorisation and validation is not carried out here
 * and should take place in another layer.
 *
 * @author George Ryall
 * @author David Meredith
 * @author John Casson
 */
class ServiceDAO extends AbstractDAO{

    /**
     * Queues a service for removal from GOCDB. In doing so, also removes
     * any downtimes that will be orphaned.
     * <p>
     * This method deletes downtimes that are ONLY associated with this
     * service and no others - if another servcie or service's EndpointLocation links to a
     * downtime, the downtime is not removed - there is no cascade delete from EL-to-DT).
     * <p>
     * No authorisation or validation occurs here.
     * Non-transactional - invokes remove() and merge() of the EntityManager
     * but does not call flush(), commit() or rollback().
     *
     * @param \Service $s service to be removed
     * @param \User $user user doing the removal (for audit table)
     */
    public function removeService(\Service $s){
        //Sort downtimes into those for deletion and those that only need their
        //link with $service and $service->endpoint breaking

        //Collate service's Reachable downtimes
        $downTimes= array();

        // Add downtimes Reachable through service endpoints
        $eLs= $s->getEndpointLocations();
        foreach($eLs as $eL){
            foreach($eL->getDowntimes() as $dt){
                if(!in_array($dt, $downTimes)){
                    $downTimes[]=$dt;
                }
            }
        }
        // Add downtimes Reachable through service
        foreach($s->getDowntimes() as $dt){
            if(!in_array($dt, $downTimes)){
                $downTimes[]=$dt;
            }
        }

        // Store downtimes that will be orphaned
        $downTimesForRemoval = array();

        // Iterate each reachable downtime and check if each references another
        // service (or another service's endpoint). If not in both cases,
        // remove the DT relationships (between DT<-->EL and DT<-->SE).
        foreach($downTimes as $reachableDT){
            $removeDT = true;

            // Is dt linked to another service's endpoint ?
            foreach($reachableDT->getEndpointLocations() as $el){
                if($el->getService() != $s){
                    // dt is linked to a different service's endpoint so
                    // we can't delete this downtime, and we must also
                    // maintain the link between $reachableDT and $el
                    $removeDT = false;
                } else {
                    // dt is linked to the targetService's endpoint, so we
                    // must delete the relationship
                    $reachableDT->removeEndpointLocation($el);
                }
            }
            // Is dt linked to another service ?
            foreach($reachableDT->getServices() as $ser){
                if($ser != $s){
                   // yes, dt is linked to a different service so
                   // we can't delete this downtime, and we must also
                   // maintain the link between $reachableDT and $ser.
                   $removeDT = false;
                } else {
                    // dt is linked to the targetService, so we
                    // must delete the relationship
                    $reachableDT->removeService($ser); // unlinks on both sides
                    //$ser->getDowntimes()->removeElement($reachableDT);
                    //$reachableDT->getServices()->removeElement($ser);
                }
            }

            // this dt is not linked to any other service or service's endpoint
            // so we can store it for subsequent deletion.
            if($removeDT){
                $downTimesForRemoval[]=$reachableDT;
            }
        }

        //remove downtimes that would be orphaned
        foreach($downTimesForRemoval as $dt){
            $this->em->remove($dt);
        }

        // Remove Endpoints
        // Impt: When deleting a service, we can't rely solely on the DB level FK
        // 'onDelete=CASCADE' defined on the EndpointLocation's 'service' association
        // (the @JoinColumn annotation) to correctly cascade-delete the EndpointLocation.
        // This is because Downtimes can also be linked to the EL, meaning a FK integrity
        // constraint violation exception would be thrown because the onDelete=cascade
        // does not remove this association in 'DOWNTIMES_ENDPOINTLOCATIONS.ENDPOINTLOCATION_ID' FK column.
        // To solve this, we also need to do an ORM level remove of the EL
        // (either by adding cascade="remove" or manually invoking em->remove($el) on each EL)
        // to ensure the ORM Unit-of-work flags the EL to be removed which subsequently causes
        // Doctrine to automatically delete the relevant row(s) in 'DOWNTIMES_ENDPOINTLOCATIONS'
        // join table!
        // See: http://docs.doctrine-project.org/en/2.0.x/reference/working-with-objects.html#removing-entities
        // See: http://stackoverflow.com/questions/6328535/on-delete-cascade-with-doctrine2
        foreach($eLs as $el){
            $this->em->remove($el);
        }

        //remove service
        $this->em->remove($s);
    }

    /**
     * Queues an EndpointLocation for removal by unlinking its associations
     * with Service and Downtime.
     * <p>
     * No authorisation or validation occurs here.
     * Non-transactional - invokes remove() of the EntityManager
     * but does not call flush(), commit() or rollback().
     *
     * @param \EndpointLocation $endpoint
     */
    public function removeEndpoint(\EndpointLocation $endpoint) {
        $service = $endpoint->getService();
        // unset relation on both sides (SE<-->EL)
        $service->getEndpointLocations()->removeElement($endpoint);
        $endpoint->setServiceDoJoin(NULL);

        // unset relation on both sides (EL<-->DT)
        foreach ($endpoint->getDowntimes() as $dt) {
            $endpoint->getDowntimes()->removeElement($dt);
            $dt->getEndpointLocations()->removeElement($endpoint);
        }
        // Once relationships are removed delete the actual element
        $this->em->remove($endpoint);
    }

    /**
     * Creates an entry in the service archive table, to enable auditing
     * of deletion.
     * @param \Service $service
     * @param \User $user
     */
    public function addServiceToArchive(\Service $service, \User $user){
        $archievedService = new \ArchivedService;
        $archievedService->setDeletedBy($user->getCertificateDn());
        $archievedService->setHostName($service->getHostName());
        $archievedService->setServiceType($service->getServiceType()->getName());
        $archievedService->setOriginalCreationDate($service->getCreationDate());
        $archievedService->setParentSite($service->getParentSite()->getShortName());
        $archievedService->setScopes($service->getScopeNamesAsString());
        $archievedService->setMonitored($service->getMonitored());
        $archievedService->setBeta($service->getBeta());
        $archievedService->setProduction($service->getProduction());

        $this->em->persist($archievedService);
    }
}
