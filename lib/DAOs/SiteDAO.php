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
class SiteDAO extends AbstractDAO{

    /**
     * Non-transactional. Queues a site for removal from GOCDB with remove() but
     * does not call commit() or rollback(). No authorisation or validation occours here.
     * @param \Site $s Site to be deleted
     */
    public function removeSite(\Site $s){

//        //remove the sites subgrid, if it has one and it is the only member.
//        if(!is_null($s->getSubGrid())){
//            //echo "Hello World";
//            //die();
//
////            $subGrid = $s->getSubGrid();/
///           $s->setSubGrid(null);
////            $subGrid->getSites()->removeElement($s);
////
////            $this->em->merge($subGrid);
//////
//////            if(sizeof($subGrid->getSites())==0){
////                $this->em->remove($subGrid);
////            //}
//        }

        //remove site
        $this->em->remove($s);

    }

    /**
     * Creates an entry in the site archive table, to enable auditing of
     * deletion. Authorisation must have already been performed and the values come from
     * an existing object and so are assumed to be valid. May not work if unregistered
     * users are ever allowed to delete things.
     * @param \Site $site
     * @param \User $user
     */
    public function addSiteToArchive(\Site $site, \User $user){
        $archievedSite = new \ArchivedSite();
        $archievedSite->setCertStatus($site->getCertificationStatus()->getName());
        $archievedSite->setCountry($site->getCountry()->getName());
        $archievedSite->setDeletedBy($user->getCertificateDn());
        $archievedSite->setName($site->getName());
        $archievedSite->setOriginalCreationDate($site->getCreationDate());
        $archievedSite->setParentNgi($site->getNgi()->getName());
        $archievedSite->setScopes($site->getScopeNamesAsString());
        $archievedSite->setV4PrimaryKey($site->getPrimaryKey());
        $archievedSite->setInfrastructure($site->getInfrastructure()->getName());

        $this->em->persist($archievedSite);
    }
}
