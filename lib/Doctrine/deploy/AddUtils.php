<?php


// Finds one or more services by hostname and service type
function findSEs($hostName, $serviceType) {
    $dql = "SELECT s FROM Service s JOIN s.serviceType st"
            . " WHERE s.hostName = :hostName AND st.name = :serviceType";

    $services = $GLOBALS['entityManager']
    ->createQuery($dql)
    ->setParameter(":hostName", $hostName)
    ->setParameter(":serviceType", $serviceType)
    ->getResult();

    return $services;
}

/* Is the passed site invalid?
 * Uses a list of known bad sites */
function isBad($site) {
    /* Roles over these sites are in the production data but can't be inserted
     * into v5 because they don't have an NGI or a domain. v5 doesn't import sites without an NGI
     * or domain   
     * 
     * Sites ignored becuase of no parent NGI:
     * Australia-UNIMELB-LCG2, GUP-JKU,UNIBAS, FZK-PPS, MA-01-CNRST,  All sites under
     * ROC_IGALC which was closed Apr-2013, all NGI_IE sites closed Jul-2013.
     * PPS-CNAF is a broken site with a production status of pps
     * 15.08.13: Removed 'UFRJ-IF',  from this list as it appears to be fixed (??)
     * 15.08.13: Removed 'EELA-UNLP',  from this list as it appears to be fixed (??)
     * 15.08.13: Removed 'ULA-MERIDA', from this list as it appears to be fixed (??)
     * 15.08.13: Removed 'ZA-MERAKA', 'ZA-WITS-CORE', 'ZA-UJ', 'ZA-CHPC' as NGI_ZA has been reinstated
     */
    $badSites = array('Australia-UNIMELB-LCG2', 'PPS-CNAF', 'GUP-JKU', 'UNIBAS', 'FZK-PPS'
            , 'MA-01-CNRST', 'EELA-UC', 'CEFET-RJ', 'CMM-UChile', 'CPTEC-INPE'
            , 'CUBAENERGIA', 'EPN', 'FING', 'GRID-CEDIA', 'GRyDs-USB'
            , 'INCOR-HCFMUSP', 'UFCG-LSD', 'UIS-BUCARAMANGA',   'UTP-PANAMA', 'ITWM-PPS' , 'HU-BERLIN', 'SCAI-PPS'
            , 'FZK-SC', 'FZK-Test', 'FZK-Test', 'GRIDOPS-GRIDVIEW', 'GSI-LCG2-PPS' 
            // next lot is from NGI_IE which was deleted. 
            , 'csTCDie', 'mpUCDie', 'giNUIMie', 'cpDIASie', 'csQUBuk', 'csUCCie' 
            , 'scgNUIGie', 'giITTAie', 'obsARMuk', 'giHECie', 'giRCSIie'
            , 'giDCUie');

    if(sizeof(array_intersect(array($site), $badSites)) == 0) {
        return false;
    } else {
        return true;
    }
}