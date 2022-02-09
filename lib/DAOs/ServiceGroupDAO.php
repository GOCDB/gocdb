<?php
include_once __DIR__ . '/AbstractDAO.php';
/**
 * All public methods are non transacional. They should always be called from
 * within a transaction. Authorisation and validation is not carried out here
 * and should take place in the service layer.
 *
 * @author George Ryall
 * @author David Meredith
 * @author John Casson
 */
class ServiceGroupDAO extends AbstractDAO{

    /**
     * Non-transactional. Removes a service group from GOCDB. No authorisation
     * or validation occours here.
     * @param \Service $sg service group to be removed
     * @param \User $user user doing the removal (for audit table)
     */
    public function removeServiceGroup(\ServiceGroup $sg){
        $this->em->remove($sg);
    }


    /**
     * Creates an entry in the servicegroup archive table, to enable auditing
     * of deletion.
     * @param \ServiceGroup $sg
     * @param \User $user
     */
    public function addServiceGroupToArchive(\ServiceGroup $sg, \User $user){
        $archievedSG = new \ArchivedServiceGroup;
        $serv = \Factory::getUserService();
        $archievedSG->setDeletedBy($serv->getDefaultIdString($user));
        $archievedSG->setName($sg->getName());
        $archievedSG->setOriginalCreationDate($sg->getCreationDate());
        $archievedSG->setScopes($sg->getScopeNamesAsString());

        $serviceNamesAsArray = array();
        foreach ($sg->getServices() AS $s){
            $serviceNamesAsArray[] = $s->getHostName() . "(" . $s->getServiceType()->getName() . ")";
        }
        $serviceNamesAsString = implode(", ", $serviceNamesAsArray);
        $archievedSG->setServices($serviceNamesAsString);

        $this->em->persist($archievedSG);
    }
}
