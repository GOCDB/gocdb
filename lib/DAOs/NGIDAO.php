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
class NGIDAO extends AbstractDAO{

    /**
     * Non-transactional. Removes a NGI  from GOCDB. No authorisation
     * or validation occours here.
     * @param \NGI $n NGI to be deleted
     */
    public function removeNGI(\NGI $n){
        //remove any subgrids related to this ngi


        //remove ngi
        $this->em->remove($n);

    }
     /**
     * Creates an entry in the NGI archieve table, to enable auditing of
     * deletion. This code is designed to be run from within the try/catch
     * block within the single transaction of the delete NGI function.
     * Authorisation must have already been performed and the values come from
     * an existing object and so are assumed valid. May not work if unregistered
     * users are ever allowed to delete things.
     * @param \NGI $ngi
     * @param \User $user
     */
    public function addNGIToArchive(\NGI $ngi, \User $user){
        $archievedNgi = new \ArchivedNGI;
        $serv = \Factory::getUserService();
        $archievedNgi->setDeletedBy($serv->getDefaultIdString($user));
        $archievedNgi->setName($ngi->getName());
        $archievedNgi->setOriginalCreationDate($ngi->getCreationDate());
        $archievedNgi->setScopes($ngi->getScopeNamesAsString());

        $projectNamesAsArray = array();
        foreach ($ngi->getProjects() AS $p){
            $projectNamesAsArray[] = $p->getName();
        }
        $projectNamesAsString = implode(", ", $projectNamesAsArray);
        $archievedNgi->setParentProjects($projectNamesAsString);

        $this->em->persist($archievedNgi);
    }
}