<?php
namespace org\gocdb\services;

/* Copyright © 2011 STFC
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
require_once __DIR__ . '/RoleConstants.php'; 
require_once __DIR__ . '/Config.php';
require_once __DIR__ . '/OwnedEntity.php';
require_once __DIR__.'/../Doctrine/bootstrap.php';
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * GOCDB Stateless service facade (business routnes) for PI queries.
 * The public API methods are transactional.
 *
 * @author John Casson
 * @author David Meredith
 * @author George Ryall
 * @author James McCarthy
 */
class PI extends AbstractEntityService{
    /*
     * All the public service methods in a service facade are typically atomic -
     * they demarcate the tx boundary at the start and end of the method
     * (getConnection/commit/rollback). A service facade should not be too 'chatty,'
     * ie where the client is required to make multiple calls to the service in
     * order to fetch/update/delete data. Inevitably, this usually means having
     * to refactor the service facade as the business requirements evolve.
     *
     * If the tx needs to be propagated across different service methods,
     * consider refactoring those calls into a new transactional service method.
     * Note, we can always call out to private helper methods to build up a
     * 'composite' service method. In doing so, we must access the same DB
     * connection (thus maintaining the atomicity of the service method).
     */



    /**
     * Return an XML document that encodes Site Certification status dates.
     * Optionally provide an associative array of query parameters with values 
     * used to restrict the results. Only known parameters are honoured while 
     * unknown produce and error doc. Parmeter array keys include:
     * <pre>
     * 'roc', 'certification_status' 
     * </pre>
     * @param array $parameters Associative array of parameters to narrow the query
     * @return string XML result string
     * @throws \Exception
     */
     public function getCertStatusDate($parameters){
        $supportedQueryParams = array('certification_status', 'roc'/*, 'scope', 'scope_match'*/);
        $this->validateParams($supportedQueryParams, $parameters);

        if (isset($parameters['roc'])) {
            $roc = $parameters['roc'];
        } else {
            $roc = '%%';
        }

        if (isset($parameters['certification_status'])) {
            $certification_status = $parameters['certification_status'];
        } else {
            $certification_status = '%%';
        }

        if(isset($parameters['scope'])) {
            $scopeArray = $this->convertScopesToArray($parameters['scope']);
        } else {
            $scopeArray = $this->convertScopesToArray($this->defaultScope());
        }
        
        /*$scopeClause = "";
        if($this->containsValidScope($scopeArray)){
            $scopeClause = $this->scopeClauseFromScopeArray($scopeArray, "sc", 
                    $this->allScopesMustMatch($parameters), 'Site', 's') . " 
                AND";
        } 
        */
        // Can easily re-enable the scoping (scoping is not supported for this
        // method in the v4 PI). 
        $scopeClause = "";
        
        $dql = "SELECT s 
            FROM Site s 
            JOIN s.certificationStatus cs
            JOIN s.ngi n
            LEFT JOIN s.scopes sc
            JOIN s.infrastructure i
            WHERE (n.name LIKE :roc)
            AND " . $scopeClause // AND clause is already appended to $scopeClause where relevant.
            . " (i.name = 'Production')
            AND (cs.name LIKE :certStatus)"; 
        
        $q = $this->em->createQuery($dql)
            ->setParameter('roc', $roc)
            ->setParameter('certStatus', $certification_status);
        
        // Can easily re-enable the scoping (scoping is not supported for this
        // method in the v4 PI). 
//        $q = $this->setScopeBindParameters($scopeArray, $q);
        
        $allSites = $q->getResult();
       
        // Ensure all dates are in UTC
        date_default_timezone_set("UTC");
       
        $xml = new \SimpleXMLElement("<results />");
        foreach ($allSites as $site) {
            $xmlSite = $xml->addChild('site');
            $xmlSite->addChild('name', $site->getShortName());
            $xmlSite->addChild('cert_status', $site->getCertificationStatus()->getName());
            // TODO fix import BUG: The below IF statement should not really be needed. 
            // However, the Site.xml seed file for v5 currently (erroneously) 
            // contains decommissioned sites e.g. GRIDOPS-CICPORTAL. These 
            // decommissioned sites aren't listed  in the 
            // CertStatusDate.xml file which is used to seed the 
            // site.certificationStatusChangeDate value. Therefore, this value 
            // is null for these decomissioned sites. 
            if($site->getCertificationStatusChangeDate() != null){
               // e.g. <cert_date>29-JAN-13 05.13.08 PM</cert_date>
               $xmlSite->addChild('cert_date', $site->getCertificationStatusChangeDate()->format('d-M-y H.i.s A')); 
            }
        }
        $dom_sxe = dom_import_simplexml($xml);
        $dom = new \DOMDocument('1.0');
        $dom->encoding='UTF-8';
        $dom_sxe = $dom->importNode($dom_sxe, true);
        $dom_sxe = $dom->appendChild($dom_sxe);
        $dom->formatOutput = true;
        $xmlString = $dom->saveXML();
        return $xmlString;
     } 


    /**
     * Return an XML document that encodes Site CertificationStatusLog entities.
     * Optionally provide an associative array of query parameters with values 
     * used to restrict the results. Only known parameters are honoured while 
     * unknown produce and error doc. Parmeter array keys include:
     * <pre>
     * 'site', 'startdate', 'enddate' 
     * </pre>
     * @param array $parameters Associative array of parameters to narrow the query
     * @return string XML result string
     * @throws \Exception
     */
     public function getCertStatusChanges($parameters){
        $supportedQueryParams = array('site', 'startdate', 'enddate');
        $this->validateParams($supportedQueryParams, $parameters);

        if (isset($parameters['site'])) {
            $site = $parameters['site'];
        } else {
            $site = '%%';
        }
         if (isset($parameters['startdate'])) {
            $startDate = new \DateTime($parameters['startdate']);
        } else {
            $startDate = null;
        }
        if (isset($parameters['enddate'])) {
            $endDate = new \DateTime($parameters['enddate']);
        } else {
            $endDate = null;
        }

        $dql = "SELECT log, s 
            FROM CertificationStatusLog log 
            JOIN log.parentSite s 
            JOIN s.certificationStatus cs
            LEFT JOIN s.scopes sc
            JOIN s.infrastructure i
            WHERE 
            (s.shortName LIKE :site) 
            
            AND (
                :startDate IS null 
                OR log.addedDate > :startDate
            )
            
            AND (
                :endDate IS null 
                OR log.addedDate < :endDate
            )"; 
        
        $q = $this->em->createQuery($dql)
            ->setParameter('site', $site)
            //->setParameter('certStatus', $certification_status)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate); 
         $allLogs = $q->getResult();
       
        // Ensure all dates are in UTC
        date_default_timezone_set("UTC");
        
        $xml = new \SimpleXMLElement("<results />");
        foreach ($allLogs as $log) {
            $xmlLog = $xml->addChild('result');
            if ($log->getAddedDate() != null) {
                // e.g. <TIME>02-JUL-2013 12.51.58</TIME>
                $xmlLog->addChild('TIME', $log->getAddedDate()->format('d-M-Y H.i.s'));
                // e.g. <UNIX_TIME>1372769518</UNIX_TIME>
                $xmlLog->addChild('UNIX_TIME', $log->getAddedDate()->format('U'));
            }
            $xmlLog->addChild('SITE', $log->getParentSite()->getShortName());
            $xmlLog->addChild('OLD_STATUS', $log->getOldStatus());
            $xmlLog->addChild('NEW_STATUS', $log->getNewStatus());
            $xmlLog->addChild('CHANGED_BY', $log->getAddedBy());
            $xmlLog->addChild('COMMENT', $log->getReason());
        }
       
        $dom_sxe = dom_import_simplexml($xml);
        $dom = new \DOMDocument('1.0');
        $dom->encoding='UTF-8';
        $dom_sxe = $dom->importNode($dom_sxe, true);
        $dom_sxe = $dom->appendChild($dom_sxe);
        $dom->formatOutput = true;
        $xmlString = $dom->saveXML();
        return $xmlString;
     } 


    /**
     * Return an XML document that encodes the Site entities. 
     * Optionally provide an associative array of query parameters with values 
     * used to restrict the results. Only known parameters are honoured while 
     * unknown produce and error doc. Parmeter array keys include:
     * <pre>
     * 'sitename', 'roc', 'country', 'certification_status', 
     * 'exclude_certification_status', 'production_status', 'scope', 'scope_match'
     * (where scope refers to Site scope) 
     * </pre>
     * Uses the addIfNotEmpty method to duplicate the behavior of the Oracle XML 
     * module that rendered this query in GOCDBv4.
     * @param array $parameters Associative array of parameters to narrow the query
     * @return string XML result string
     * @throws \Exception
     */ 
    public function getSite($parameters){
        // Define supported parameters and validate given params (die if an unsupported param is given)
        $supportedQueryParams = array('sitename', 'roc', 'country'
            , 'certification_status', 'exclude_certification_status'
            , 'production_status', 'scope', 'scope_match');
        $this->validateParams($supportedQueryParams, $parameters);

        if(isset($parameters['sitename'])) {
            $site = $parameters['sitename'];
        } else {
            $site = '%%';
        }

        if(isset($parameters['roc'])) {
            $ngi = $parameters['roc'];
        } else {
            $ngi = '%%';
        }

        if(isset($parameters['country'])) {
            $country = $parameters['country'];
        } else {
            $country = '%%';
        }

        if(isset($parameters['certification_status'])) {
            $certStatus = $parameters['certification_status'];
        } else {
            $certStatus = '%%';
        }

        if(isset($parameters['exclude_certification_status'])) {
            $excludeCertStatus = $parameters['exclude_certification_status'];
        } else {
            $excludeCertStatus = '%%';
        }

        if(isset($parameters['production_status'])) {
            $prodStatus = $parameters['production_status'];
        } else {
            $prodStatus = '%%';
        }

        if(isset($parameters['scope'])) {
            $scopeArray = $this->convertScopesToArray($parameters['scope']);
        } else {
            $scopeArray = $this->convertScopesToArray($this->defaultScope());
        }
        
        $scopeClause = "";
        if($this->containsValidScope($scopeArray)){
            $scopeClause = $this->scopeClauseFromScopeArray($scopeArray, "sc", 
                    $this->allScopesMustMatch($parameters), 'Site', 's') . " 
                AND";
        }
        
        $dql = "select s, sc 
            FROM Site s
            LEFT JOIN s.scopes sc
            JOIN s.ngi n
            JOIN s.country c
            JOIN s.certificationStatus cs
            JOIN s.infrastructure i
            WHERE " 
            . $scopeClause  // AND clause is already appended to $scopeClause where relevant.
            . " (s.shortName LIKE :site)
            AND (n.name LIKE :ngi)
            AND (c.name LIKE :country)
            AND (cs.name LIKE :certStatus)
            AND (:excludeCertStatus = '%%' or cs.name not LIKE :excludeCertStatus)
            AND (i.name LIKE :prodStatus)
            ORDER BY s.shortName";
        
        $q = $this->em->createQuery($dql)
            ->setParameter('site', $site)
            ->setParameter('ngi', $ngi)
            ->setParameter('country', $country)
            ->setParameter('certStatus', $certStatus)
            ->setParameter('excludeCertStatus', $excludeCertStatus)
            ->setParameter('prodStatus', $prodStatus);

        $q = $this->setScopeBindParameters($scopeArray, $q);
            
        $sites = $q->getResult();

        $xml = new \SimpleXMLElement("<results />");
        foreach($sites as $site) {
            $xmlSite = $xml->addChild('SITE');
            $xmlSite->addAttribute('ID', $site->getId());
            $xmlSite->addAttribute('PRIMARY_KEY', $site->getPrimaryKey());
            $xmlSite->addAttribute('NAME', $site->getShortName());
            $this->addIfNotEmpty($xmlSite, 'PRIMARY_KEY', $site->getPrimaryKey());
            $this->addIfNotEmpty($xmlSite, 'SHORT_NAME', $site->getShortName());
            $this->addIfNotEmpty($xmlSite, 'OFFICIAL_NAME', htmlspecialchars($site->getOfficialName()));
            $this->addIfNotEmpty($xmlSite, 'SITE_DESCRIPTION', htmlspecialchars($site->getDescription()));
            $portalUrl = '#GOCDB_BASE_PORTAL_URL#/index.php?Page_Type=Site&id=' . $site->getId();
            $portalUrl = htmlspecialchars($portalUrl);
            $this->addIfNotEmpty($xmlSite, 'GOCDB_PORTAL_URL', $portalUrl);
            $this->addIfNotEmpty($xmlSite, 'HOME_URL', htmlspecialchars($site->getHomeUrl()));
            $this->addIfNotEmpty($xmlSite, 'CONTACT_EMAIL', $site->getEmail());
            $this->addIfNotEmpty($xmlSite, 'CONTACT_TEL', $site->getTelephone());
            $this->addIfNotEmpty($xmlSite, 'ALARM_EMAIL', $site->getAlarmEmail());
            $this->addIfNotEmpty($xmlSite, 'GIIS_URL', htmlspecialchars($site->getGiisUrl()));
            // Tier is an optional parameter
            if($site->getTier() != null) {
                $this->addIfNotEmpty($xmlSite, 'TIER', $site->getTier()->getName());
            }
            $this->addIfNotEmpty($xmlSite, 'COUNTRY_CODE', $site->getCountry()->getCode());
            $this->addIfNotEmpty($xmlSite, 'COUNTRY', $site->getCountry()->getName());
            $this->addIfNotEmpty($xmlSite, 'ROC', $site->getNgi()->getName());
            // SubGrid is an optional parameter
            if($site->getSubGrid() != null) {
                $this->addIfNotEmpty($xmlSite, 'SUBGRID', $site->getSubGrid()->getName());
            }
            $this->addIfNotEmpty($xmlSite, 'PRODUCTION_INFRASTRUCTURE', $site->getInfrastructure()->getName());
            $this->addIfNotEmpty($xmlSite, 'CERTIFICATION_STATUS', $site->getCertificationStatus()->getName());
            $this->addIfNotEmpty($xmlSite, 'TIMEZONE', $site->getTimezoneId());
            $this->addIfNotEmpty($xmlSite, 'LATITUDE', $site->getLatitude());
            $this->addIfNotEmpty($xmlSite, 'LONGITUDE', $site->getLongitude());
            $this->addIfNotEmpty($xmlSite, 'CSIRT_EMAIL', $site->getCsirtEmail());
            $domain = $xmlSite->addChild('DOMAIN');
            $this->addIfNotEmpty($domain, 'DOMAIN_NAME', $site->getDomain());

            // IF we need to nest a site's scope tags within the XML results: 
            //$xmlScopeTag = $xmlSite->addChild('SCOPE_TAGS'); 
            //foreach($site->getScopes() as $siteScope){
            //   $xmlScopeTag->addChild('SCOPE_TAG', $siteScope->getName());  
            //}
        }

        $dom_sxe = dom_import_simplexml($xml);
        $dom = new \DOMDocument('1.0');
        $dom->encoding='UTF-8';
        $dom_sxe = $dom->importNode($dom_sxe, true);
        $dom_sxe = $dom->appendChild($dom_sxe);
        $dom->formatOutput = true;
        $xmlString = $dom->saveXML();
        return $xmlString;
    }

    /**
     * Return an XML document that encodes a Site list.
     * Optionally provide an associative array of query parameters with values 
     * used to restrict the results. Only known parameters are honoured while 
     * unknown params produce an error doc. Parmeter array keys include:
     * <pre>
     * 'sitename', 'roc', 'country', 'certification_status', 
     * 'exclude_certification_status', 'production_status', 'scope', 'scope_match'
     * (where scope refers to Site scope) 
     * </pre>
     * Uses the addIfNotEmpty method to duplicate the behavior of the Oracle XML module that
     * rendered this query in GOCDBv4.
     * @param array $parameters Associative array of parameters to narrow the query
     * @return string XML result string
     * @throws \Exception
     */
    public function getSiteList($parameters){
        // Define supported parameters and validate given params (die if an unsupported param is given)
        $supportedQueryParams = array('sitename', 'roc', 'country'
                , 'certification_status', 'exclude_certification_status'
                , 'production_status', 'scope', 'scope_match');
        $this->validateParams($supportedQueryParams, $parameters);

        if(isset($parameters['sitename'])) {
            $site = $parameters['sitename'];
        } else {
            $site = '%%';
        }

        if(isset($parameters['roc'])) {
            $ngi = $parameters['roc'];
        } else {
            $ngi = '%%';
        }

        if(isset($parameters['country'])) {
            $country = $parameters['country'];
        } else {
            $country = '%%';
        }

        if(isset($parameters['certification_status'])) {
            $certStatus = $parameters['certification_status'];
        } else {
            $certStatus = '%%';
        }

        if(isset($parameters['exclude_certification_status'])) {
            $excludeCertStatus = $parameters['exclude_certification_status'];
        } else {
            $excludeCertStatus = '%%';
        }

        if(isset($parameters['production_status'])) {
            $prodStatus = $parameters['production_status'];
        } else {
            $prodStatus = '%%';
        }

        if(isset($parameters['scope'])) {
            $scopeArray = $this->convertScopesToArray($parameters['scope']);
        } else {
            $scopeArray = $this->convertScopesToArray($this->defaultScope());
        }
        
        $scopeClause = "";
        if($this->containsValidScope($scopeArray)){
            $scopeClause = $this->scopeClauseFromScopeArray($scopeArray, "sc", 
                    $this->allScopesMustMatch($parameters), 'Site', 's') . " 
                AND";
        } 
        
        $dql = "select s FROM Site s
            LEFT JOIN s.scopes sc
            JOIN s.ngi n
            JOIN s.country c
            JOIN s.certificationStatus cs
            JOIN s.infrastructure i
            WHERE " . $scopeClause  // AND clause is already appended to $scopeClause where relevant.
            . " (s.shortName LIKE :site)
            AND (n.name LIKE :ngi)
            AND (c.name LIKE :country)
            AND (cs.name LIKE :certStatus)
            AND (:excludeCertStatus = '%%' or cs.name not LIKE :excludeCertStatus)
            AND (i.name LIKE :prodStatus)
            ORDER BY s.shortName";
        
        $q = $this->em->createQuery($dql)
                ->setParameter('site', $site)
                ->setParameter('ngi', $ngi)
                ->setParameter('country', $country)
                ->setParameter('certStatus', $certStatus)
                ->setParameter('excludeCertStatus', $excludeCertStatus)
                ->setParameter('prodStatus', $prodStatus);

        $q = $this->setScopeBindParameters($scopeArray, $q);
        
        $sites = $q->getResult();

        $xml = new \SimpleXMLElement("<results />");
        foreach($sites as $site) {
            $xmlSite = $xml->addChild('SITE');
            $xmlSite->addAttribute('ID', $site->getId() /*. "G0"*/);
            $xmlSite->addAttribute('PRIMARY_KEY', $site->getPrimaryKey());
            $xmlSite->addAttribute('NAME', $site->getShortName());
            $xmlSite->addAttribute('COUNTRY', $site->getCountry()->getName());
            $xmlSite->addAttribute('COUNTRY_CODE', $site->getCountry()->getCode());
            $xmlSite->addAttribute('ROC', $site->getNgi()->getName());
            $subGrid = $site->getSubGrid();
            if($subGrid != null) {
                $subGrid = $subGrid->getName();
            }
            $xmlSite->addAttribute('SUBGRID', $subGrid);
            $xmlSite->addAttribute('GIIS_URL', $site->getGiisUrl());
        }

        $dom_sxe = dom_import_simplexml($xml);
        $dom = new \DOMDocument('1.0');
        $dom->encoding='UTF-8';
        $dom_sxe = $dom->importNode($dom_sxe, true);
        $dom_sxe = $dom->appendChild($dom_sxe);
        $dom->formatOutput = true;

        $xmlString = $dom->saveXML();

        return $xmlString;
    }

    /**
     * Return an XML document that encodes the site contacts.
     * Optionally provide an associative array of query parameters with values 
     * used to restrict the results. Only known parameters are honoured while 
     * unknown params produce an error doc.
     * <pre>
     * 'sitename', 'roc', 'country', 'roletype', 'scope', 'scope_match' 
     * (where scope refers to Site scope) 
     * </pre>
     * @param array $parameters Associative array of parameters to narrow the query
     * @return string XML result string
     * @throws \Exception
     */
    public function getSiteContacts($parameters){
        // Define supported parameters and validate given params (die if an unsupported param is given)
        $supportedQueryParams = array('sitename', 'roc', 'country'
                , 'roletype', 'scope', 'scope_match');
        $this->validateParams($supportedQueryParams, $parameters);

        if(isset($parameters['sitename'])) {
            $site = $parameters['sitename'];
        } else {
            $site = '%%';
        }

        if(isset($parameters['roc'])) {
            $ngi = $parameters['roc'];
        } else {
            $ngi = '%%';
        }

        if(isset($parameters['country'])) {
            $country = $parameters['country'];
        } else {
            $country = '%%';
        }

        if(isset($parameters['roletype'])) {
            $roleType = $parameters['roletype'];
        } else {
            $roleType = '%%';
        }

        if(isset($parameters['scope'])) {
            $scopeArray = $this->convertScopesToArray($parameters['scope']);
        } else {
            $scopeArray = $this->convertScopesToArray($this->defaultScope());
        }

        $scopeClause = "";
        if($this->containsValidScope($scopeArray)){
            $scopeClause = $this->scopeClauseFromScopeArray($scopeArray, "sc", 
                    $this->allScopesMustMatch($parameters), 'Site', 's') . " 
                AND";
        } 
        
       $dql = "select s FROM Site s
            LEFT JOIN s.scopes sc
            JOIN s.ngi n
            JOIN s.country c
            WHERE " . $scopeClause  // AND clause is already appended to $scopeClause where relevant.
            . " (s.shortName LIKE :site)
            AND (n.name LIKE :ngi)
            AND (c.name LIKE :country)            
            ORDER BY s.shortName";
        
        $q = $this->em->createQuery($dql)
                ->setParameter('site', $site)
                ->setParameter('ngi', $ngi)
                ->setParameter('country', $country); 

        $q = $this->setScopeBindParameters($scopeArray, $q);
        
        $sites = $q->getResult();
        $xml = new \SimpleXMLElement ( "<results />" );
        foreach ( $sites as $site ) {
            $xmlSite = $xml->addChild ( 'SITE' );
            $xmlSite->addAttribute ( 'ID', $site->getId () . "G0" );
            $xmlSite->addAttribute ( 'PRIMARY_KEY', $site->getPrimaryKey () );
            $xmlSite->addAttribute ( 'NAME', $site->getShortName () );
            
            $xmlSite->addChild ( 'PRIMARY_KEY', $site->getPrimaryKey () );
            $xmlSite->addChild ( 'SHORT_NAME', $site->getShortName () );
            foreach ( $site->getRoles () as $role ) {
                if ($role->getStatus() == "STATUS_GRANTED") {   //Only show users who are granted the role, not pending
                    $rtype = $role->getRoleType ()->getName ();
                    if ($roleType == '%%' || $rtype == $roleType) {
                        $user = $role->getUser ();
                        $xmlContact = $xmlSite->addChild ( 'CONTACT' );
                        $xmlContact->addAttribute ( 'USER_ID', $user->getId () . "G0" );
                        $xmlContact->addAttribute ( 'PRIMARY_KEY', $user->getId () . "G0" );
                        $xmlContact->addChild ( 'FORENAME', $user->getForename () );
                        $xmlContact->addChild ( 'SURNAME', $user->getSurname () );
                        $xmlContact->addChild ( 'TITLE', $user->getTitle () );
                        $xmlContact->addChild ( 'EMAIL', $user->getEmail () );
                        $xmlContact->addChild ( 'TEL', $user->getTelephone () );
                        $xmlContact->addChild ( 'CERTDN', $user->getCertificateDn () );
                        $xmlContact->addChild ( 'ROLE_NAME', $role->getRoleType ()->getName () );
                    }
                }
            }
        }

        $dom_sxe = dom_import_simplexml($xml);
        $dom = new \DOMDocument('1.0');
        $dom->encoding='UTF-8';
        $dom_sxe = $dom->importNode($dom_sxe, true);
        $dom_sxe = $dom->appendChild($dom_sxe);
        $dom->formatOutput = true;
        $xmlString = $dom->saveXML();
        return $xmlString;
    }

    /**
     * Return an XML document that encodes the site security info from the DB.
     * Optionally provide an associative array of query parameters with values used to restrict the results.
     * Only known parameters are honoured while unknown params are ignored.
     * Implemented with Doctrine.
     * @param array $parameters Associative array of parameters with results used to narrow the
     *  results.
     * @return string XML result string
     * @throws \Exception
     */
    public function getSiteSecurityInfo($parameters){
        $supportedQueryParams = array('sitename', 'roc', 'country'
                , 'certification_status', 'exclude_certification_status'
                , 'production_status', 'scope', 'scope_match');
        $this->validateParams($supportedQueryParams, $parameters);

        if(isset($parameters['sitename'])) {
            $site = $parameters['sitename'];
        } else {
            $site = '%%';
        }

        if(isset($parameters['roc'])) {
            $ngi = $parameters['roc'];
        } else {
            $ngi = '%%';
        }

        if(isset($parameters['country'])) {
            $country = $parameters['country'];
        } else {
            $country = '%%';
        }

        if(isset($parameters['certification_status'])) {
            $certStatus = $parameters['certification_status'];
        } else {
            $certStatus = '%%';
        }

        if(isset($parameters['exclude_certification_status'])) {
            $excludeCertStatus = $parameters['exclude_certification_status'];
        } else {
            $excludeCertStatus = '%%';
        }

        if(isset($parameters['production_status'])) {
            $prodStatus = $parameters['production_status'];
        } else {
            $prodStatus = '%%';
        }

        if(isset($parameters['scope'])) {
            $scopeArray = $this->convertScopesToArray($parameters['scope']);
        } else {
            $scopeArray = $this->convertScopesToArray($this->defaultScope());
        }    
        
        $scopeClause = "";
        if($this->containsValidScope($scopeArray)){
            $scopeClause = $this->scopeClauseFromScopeArray($scopeArray, "sc", 
                    $this->allScopesMustMatch($parameters), 'Site', 's') . " 
                AND";
        } 
        
        $dql = "
            select s FROM Site s
            LEFT JOIN s.scopes sc
            JOIN s.ngi n
            JOIN s.country c
            JOIN s.certificationStatus cs
            JOIN s.infrastructure i
            WHERE " . $scopeClause  // AND clause is already appended to $scopeClause where relevant.
            . " (s.shortName LIKE :site)
            AND (n.name LIKE :ngi)
            AND (c.name LIKE :country)
            AND (cs.name LIKE :certStatus)
            AND (:excludeCertStatus = '%%' or cs.name not LIKE :excludeCertStatus)
            AND (i.name LIKE :prodStatus)
            ORDER BY s.shortName";
        
        $q = $this->em->createQuery($dql)
                ->setParameter('site', $site)
                ->setParameter('ngi', $ngi)
                ->setParameter('country', $country)
                ->setParameter('certStatus', $certStatus)
                ->setParameter('excludeCertStatus', $excludeCertStatus)
                ->setParameter('prodStatus', $prodStatus);

        $q = $this->setScopeBindParameters($scopeArray, $q);
        
        $sites = $q->getResult();

        $xml = new \SimpleXMLElement("<results />");
        foreach($sites as $site) {
            $xmlSite = $xml->addChild('SITE');
            $xmlSite->addAttribute('ID', $site->getId() . "G0");
            $xmlSite->addAttribute('PRIMARY_KEY', $site->getPrimaryKey());
            $xmlSite->addAttribute('NAME', $site->getShortName());

            $xmlSite->addChild('PRIMARY_KEY', $site->getPrimaryKey());
            $xmlSite->addChild('SHORT_NAME', $site->getShortName());
            $xmlSite->addChild('CSIRT_EMAIL', $site->getCsirtEmail());
            $xmlSite->addChild('CSIRT_TEL', $site->getCsirtTel());
        }

        $dom_sxe = dom_import_simplexml($xml);
        $dom = new \DOMDocument('1.0');
        $dom->encoding='UTF-8';
        $dom_sxe = $dom->importNode($dom_sxe, true);
        $dom_sxe = $dom->appendChild($dom_sxe);
        $dom->formatOutput = true;

        $xmlString = $dom->saveXML();

        return $xmlString;
    }

    /**
     * Return an XML document that encodes the ROC (NGI) info from the DB.
     * Optionally provide an associative array of query parameters with values used to restrict the results.
     * Only known parameters are honoured while unknown params are ignored.
     * Implemented with Doctrine.
     * @param array $parameters Associative array of parameters with results used to narrow the
     *  results.
     * @return string XML result string
     * @throws \Exception
     */
    public function getRocList($parameters){
        // Define supported parameters and validate given params (die if an unsupported param is given)
        $supportedQueryParams = array('roc');
        $this->validateParams($supportedQueryParams, $parameters);

        if(isset($parameters['roc'])) {
            $ngi = $parameters['roc'];
        } else {
            $ngi = '%%';
        }

        $q = $this->em->createQuery("
            SELECT n FROM NGI n
            WHERE n.name LIKE :ngi")
                ->setParameter('ngi', $ngi);

        $ngis = $q->getResult();

        $xml = new \SimpleXMLElement("<results />");
        foreach($ngis as $ngi) {
            $xmlNgi = $xml->addChild('ROC');
            $xmlNgi->addAttribute('PRIMARY_KEY', $ngi->getId() . "G0");
            $xmlNgi->addAttribute('ROC_NAME', $ngi->getName());
        }

        $dom_sxe = dom_import_simplexml($xml);
        $dom = new \DOMDocument('1.0');
        $dom->encoding='UTF-8';
        $dom_sxe = $dom->importNode($dom_sxe, true);
        $dom_sxe = $dom->appendChild($dom_sxe);
        $dom->formatOutput = true;

        $xmlString = $dom->saveXML();

        return $xmlString;
    }

    /**
     * Return an XML document that encodes the ROC (NGI) info from the DB.
     * Optionally provide an associative array of query parameters with values used to restrict the results.
     * Only known parameters are honoured while unknown params are ignored.
     * Implemented with Doctrine.
     * @param array $parameters Associative array of parameters with results used to narrow the
     *  results.
     * @return string XML result string
     * @throws \Exception
     */
    public function getSubGridList($parameters){
        // Define supported parameters and validate given params (die if an unsupported param is given)
        $supportedQueryParams = array('subgrid');
        $this->validateParams($supportedQueryParams, $parameters);

        if(isset($parameters['subgrid'])) {
            $subGrid = $parameters['subgrid'];
        } else {
            $subGrid = '%%';
        }

        $q = $this->em->createQuery("
            SELECT s FROM SubGrid s
            WHERE s.name LIKE :subGrid")
                ->setParameter('subGrid', $subGrid);

        $subGrids = $q->getResult();

        $xml = new \SimpleXMLElement("<results />");
        foreach($subGrids as $subGrid) {
            $xmlSubGrid = $xml->addChild('SUBGRID');
            $xmlSubGrid->addAttribute('PRIMARY_KEY', $subGrid->getId() . "G0");
            $xmlSubGrid->addAttribute('SUBGRID_NAME', $subGrid->getName());
            $xmlSubGrid->addAttribute('PARENT_ROC', $subGrid->getNgi()->getName());
        }

        $dom_sxe = dom_import_simplexml($xml);
        $dom = new \DOMDocument('1.0');
        $dom->encoding='UTF-8';
        $dom_sxe = $dom->importNode($dom_sxe, true);
        $dom_sxe = $dom->appendChild($dom_sxe);
        $dom->formatOutput = true;

        $xmlString = $dom->saveXML();

        return $xmlString;
    }

    /**
     * Return an XML document that encodes the NGI contacts selected from the DB.
     * Optionally provide an associative array of query parameters with values used to restrict the results.
     * Only known parameters are honoured while unknown params are ignored.
     * Implemented with Doctrine.
     * @param array $parameters Associative array of parameters with results used to narrow the
     *  results.
     * @return string XML result string
     * @throws \Exception
     */
    public function getRocContacts($parameters){
        // Define supported parameters and validate given params (die if an unsupported param is given)
        $supportedQueryParams = array('roc', 'roletype');
        $this->validateParams($supportedQueryParams, $parameters);

        if(isset($parameters['roc'])) {
            $ngi = $parameters['roc'];
        } else {
            $ngi = '%%';
        }

        if(isset($parameters['roletype'])) {
            $roleType = $parameters['roletype'];
        } else {
            $roleType = '%%';
        }

        $q = $this->em->createQuery("
            SELECT n FROM NGI n
            WHERE n.name LIKE :ngi")
                ->setParameter('ngi', $ngi);

        $ngis = $q->getResult();

        $xml = new \SimpleXMLElement("<results />");
        foreach($ngis as $ngi) {
            $xmlNgi = $xml->addChild('ROC');
            $xmlNgi->addAttribute('ROC_NAME', $ngi->getName());
            $xmlNgi->addChild('ROCNAME', $ngi->getName());
            $xmlNgi->addChild('MAIL_CONTACT', $ngi->getEmail());
            $portalUrl = '#GOCDB_BASE_PORTAL_URL#/index.php?Page_Type=NGI&id=' . $ngi->getId ();
            $portalUrl = htmlspecialchars ( $portalUrl );
            $this->addIfNotEmpty ( $xmlNgi, 'GOCDB_PORTAL_URL', $portalUrl );
            foreach($ngi->getRoles() as $role) {
                if ($role->getStatus() == "STATUS_GRANTED") {   //Only show users who are granted the role, not pending				
                    $rtype = $role->getRoleType()->getName(); 
                    if($roleType == '%%' || $rtype == $roleType) {
                        $user = $role->getUser();
                        $xmlContact = $xmlNgi->addChild('CONTACT');
                        $xmlContact->addAttribute('USER_ID', $user->getId() . "G0");
                        $xmlContact->addAttribute('PRIMARY_KEY', $user->getId() . "G0");
                        $xmlContact->addChild('FORENAME', $user->getForename());
                        $xmlContact->addChild('SURNAME', $user->getSurname());
                        $xmlContact->addChild('TITLE', $user->getTitle());
                        $xmlContact->addChild('EMAIL', $user->getEmail());
                        $xmlContact->addChild('TEL', $user->getTelephone());
                        $xmlContact->addChild('CERTDN', $user->getCertificateDn());
                        
                        $roleName = $role->getRoleType()->getName();  
                        $xmlContact->addChild('ROLE_NAME', $roleName);
                        
                        //$roleClass = \RoleTypeName::getRoleTypeClass($roleName); 
                        //if($roleClass != null){
                        //    $xmlContact->addChild('ROLE_TYPE', $roleClass);  
                        //}
                    }
                }	
            }
        }

        $dom_sxe = dom_import_simplexml($xml);
        $dom = new \DOMDocument('1.0');
        $dom->encoding='UTF-8';
        $dom_sxe = $dom->importNode($dom_sxe, true);
        $dom_sxe = $dom->appendChild($dom_sxe);
        $dom->formatOutput = true;

        $xmlString = $dom->saveXML();

        return $xmlString;
    }

    /**
     * Return an XML document that encodes the project contacts selected from the DB.
     * Supported params: 
     * <pre>'project'</pre>
     *  
     * @param array $parameters Associative array of parameters and values used to narrow results.  
     * @return string XML result string
     * @throws \Exception
     */
    public function getProjectContacts($parameters){
        // Define supported parameters and validate given params (die if an unsupported param is given)
        $supportedQueryParams = array('project');
        $this->validateParams($supportedQueryParams, $parameters);
        
        //Delete the following line to allow this method to work for user supplied project name
        //$parameters['project'] =  'EGI';
            
        $qb = $this->em->createQueryBuilder();
        $qb->select('p')
        ->from('project', 'p');
        //Add each parameter to query if it is set
        if (isset ( $parameters ['project'] )) {
            $qb	->where($qb->expr()->like('p.name', ':projectName'))
                ->setParameter('projectName', $parameters['project']);
        }
                    
        $query = $qb->getQuery();		
        $projects = $query->execute();
        
        $xml = new \SimpleXMLElement("<results />");	
        
        foreach($projects as $project){			
            $xmlProjUser = $xml->addChild('Project');
            $xmlProjUser->addAttribute('NAME', $project->getName());
            
            foreach($project->getRoles() as $role){
                if($role->getStatus() == \RoleStatus::GRANTED &&
                $role->getRoleType()->getName() != \RoleTypeName::CIC_STAFF){
            
                        //$rtype = $role->getRoleType()->getName();
                        $user = $role->getUser();
                        $xmlContact = $xmlProjUser->addChild('CONTACT');
                        $xmlContact->addAttribute('USER_ID', $user->getId() . "G0");
                        $xmlContact->addAttribute('PRIMARY_KEY', $user->getId() . "G0");
                        $xmlContact->addChild('FORENAME', $user->getForename());
                        $xmlContact->addChild('SURNAME', $user->getSurname());
                        $xmlContact->addChild('TITLE', $user->getTitle());
                        $xmlContact->addChild('EMAIL', $user->getEmail());
                        $xmlContact->addChild('TEL', $user->getTelephone());
                        $xmlContact->addChild ( 'WORKING_HOURS_START', $user->getWorkingHoursStart () );
                        $xmlContact->addChild ( 'WORKING_HOURS_END', $user->getWorkingHoursEnd () );
                        $xmlContact->addChild('CERTDN', $user->getCertificateDn());
                         
                        $roleName = $role->getRoleType()->getName();
                        $xmlContact->addChild('ROLE_NAME', $roleName);
                    
                }
            }
        }

        $dom_sxe = dom_import_simplexml($xml);
        $dom = new \DOMDocument('1.0');
        $dom->encoding='UTF-8';
        $dom_sxe = $dom->importNode($dom_sxe, true);
        $dom_sxe = $dom->appendChild($dom_sxe);
        $dom->formatOutput = true;

        $xmlString = $dom->saveXML();

        return $xmlString;
    }
    
    /**
     * A legacy method from V4 that was depreciated. This is a backup method 
     * to be put into production should it be required by users on short notice.
     * Return an XML document that encodes the project contacts selected from the DB.
     * Supported params:
     * None
     *
     * @param array $parameters Associative array of parameters and values used to narrow results.
     * @return string XML result string
     * @throws \Exception
     */
    public function getEgeeContacts(){
        $qb = $this->em->createQueryBuilder();
        $qb->select('p')
        ->from('project', 'p')
            ->where($qb->expr()->like('p.name', ':projectName'))
            ->setParameter('projectName', 'EGI');
            
        $query = $qb->getQuery();
        $projects = $query->execute();
    
        $xml = new \SimpleXMLElement("<results />");
    
        foreach($projects as $project){
            
            foreach($project->getRoles() as $role){
                if($role->getStatus() == \RoleStatus::GRANTED &&
                    $role->getRoleType()->getName() != \RoleTypeName::CIC_STAFF){
                        
                    $user = $role->getUser();
                    $xmlContact = $xml->addChild('CONTACT');
                    $xmlContact->addAttribute('USER_ID', $user->getId() . "G0");
                    $xmlContact->addAttribute('PRIMARY_KEY', $user->getId() . "G0");
                    $xmlContact->addChild('FORENAME', $user->getForename());
                    $xmlContact->addChild('SURNAME', $user->getSurname());
                    $xmlContact->addChild('TITLE', $user->getTitle());
                    $xmlContact->addChild('EMAIL', $user->getEmail());
                    $xmlContact->addChild('TEL', $user->getTelephone());
                    $xmlContact->addChild ( 'WORKING_HOURS_START', $user->getWorkingHoursStart () );
                    $xmlContact->addChild ( 'WORKING_HOURS_END', $user->getWorkingHoursEnd () );
                    $xmlContact->addChild('CERTDN', $user->getCertificateDn());
                        
                    $roleName = $role->getRoleType()->getName();
                    $xmlContact->addChild('ROLE_NAME', $roleName);
                        
                }
            }
        }
    
        $dom_sxe = dom_import_simplexml($xml);
        $dom = new \DOMDocument('1.0');
        $dom->encoding='UTF-8';
        $dom_sxe = $dom->importNode($dom_sxe, true);
        $dom_sxe = $dom->appendChild($dom_sxe);
        $dom->formatOutput = true;
    
        $xmlString = $dom->saveXML();
    
        return $xmlString;
    }

    /**
     * Return an XML document that encodes the NGI contacts selected from the DB.
     * Optionally provide an associative array of query parameters with values used to restrict the results.
     * Only known parameters are honoured while unknown params are ignored.
     * Implemented with Doctrine.
     * @param array $parameters Associative array of parameters with results used to narrow the
     *  results.
     * @return string XML result string
     * @throws \Exception
     */
    public function getServiceTypes($parameters){
        // Define supported parameters and validate given params (die if an unsupported param is given)
        $supportedQueryParams = array('');
        $this->validateParams($supportedQueryParams, $parameters);

        $q = $this->em->createQuery("
            SELECT st FROM ServiceType st");

        // Service types
        $sts = $q->getResult();

        $xml = new \SimpleXMLElement("<results />");
        foreach($sts as $st) {
            $xmlServiceType = $xml->addChild('SERVICE_TYPE');
            $xmlServiceType->addAttribute('TYPE_ID', $st->getId() . "G0");
            $xmlServiceType->addAttribute('PRIMARY_KEY', $st->getId() . "G0");
            $xmlServiceType->addChild('SERVICE_TYPE_NAME', $st->getName());
            $xmlServiceType->addChild('SERVICE_TYPE_DESC', $st->getDescription());
        }

        $dom_sxe = dom_import_simplexml($xml);
        $dom = new \DOMDocument('1.0');
        $dom->encoding='UTF-8';
        $dom_sxe = $dom->importNode($dom_sxe, true);
        $dom_sxe = $dom->appendChild($dom_sxe);
        $dom->formatOutput = true;

        $xmlString = $dom->saveXML();

        return $xmlString;
    }

    /**
     * Return an XML document that encodes the services.
     * Optionally provide an associative array of query parameters with values to restrict the results.
     * Only known parameters are honoured while unknown params produce an error doc. 
     * Parmeter array keys include:
     * <pre>
     * 'hostname', 'sitename', 'roc', 'country', 'service_type', 'monitored', 
     * 'scope', 'scope_match' (where scope refers to Service scope) 
     * </pre>
     * Uses the addIfNotEmpty method to duplicate the behavior of the Oracle 
     * XML module thatrendered this query in GOCDBv4.
     * @param array $parameters Associative array of parameters to narrow the query
     * @return string XML result string
     * @throws \Exception
     */
    public function getServiceEndpoint($parameters){
        // TODO: In testing this is 3 times slower than the PROM equivalent. (SQLite vs Oracle)
        // Options: Use SQL instead of DQL, Query caching, other DBs.
        // Define supported parameters and validate given params (die if an unsupported param is given)
        $supportedQueryParams = array('hostname', 'sitename', 'roc', 'country'
                , 'service_type', 'monitored', 'scope', 'scope_match');
        $this->validateParams($supportedQueryParams, $parameters);

        if(isset($parameters['hostname'])) {
            $hostName = $parameters['hostname'];
        } else {
            $hostName = '%%';
        }

        if(isset($parameters['sitename'])) {
            $site = $parameters['sitename'];
        } else {
            $site = '%%';
        }

        if(isset($parameters['roc'])) {
            $ngi = $parameters['roc'];
        } else {
            $ngi = '%%';
        }

        if(isset($parameters['country'])) {
            $country = $parameters['country'];
        } else {
            $country = '%%';
        }

        if(isset($parameters['service_type'])) {
            $serviceType = $parameters['service_type'];
        } else {
            $serviceType = '%%';
        }

        if(isset($parameters['monitored'])) {
            switch($parameters['monitored']) {
                case "Y":
                    $monitored = 1;
                    break;
                case "N":
                    $monitored = 0;
                    break;
                default:
                    $monitored = '%%';
                    break;
            }
        } else {
            $monitored = '%%';
        }

        if(isset($parameters['scope'])) {
            $scopeArray = $this->convertScopesToArray($parameters['scope']);
        } else {
            $scopeArray = $this->convertScopesToArray($this->defaultScope());
        }
        
        $scopeClause = "";
        if($this->containsValidScope($scopeArray)){
            $scopeClause = $this->scopeClauseFromScopeArray($scopeArray, "sc", 
                    $this->allScopesMustMatch($parameters), 'Service', 'se') . " 
                AND";
        } 
        
        /* For performance reasons, we name the fields in the select query.
         * By SELECTing e.g. s, sc, el etc they'll already be hydrated
         * in the result set. So if we were to call Service->getParentSite()
         * there won't be an additional database query as the data has already been
         * loaded and hydrated. [1]
         *
         * To witness the performance impact, try taking s, sc, el, c and n
         * out of the SELECT statement. Using the production data on my test instance
         * the query time jumps from 6 seconds to 11 seconds. (SQLite)
         *
         * [1] http://docs.doctrine-project.org/en/latest/reference/dql-doctrine-query-language.html
         */
        $dql= " SELECT se, s, sc, el, c, n, st FROM Service se
                JOIN se.parentSite s
                JOIN s.certificationStatus cs
                LEFT JOIN se.scopes sc
                JOIN se.endpointLocations el
                JOIN s.country c
                JOIN s.ngi n
                JOIN se.serviceType st
                WHERE " . $scopeClause   // AND clause is already appended to $scopeClause where relevant.
                . "  cs.name != 'Closed'
                AND se.hostName LIKE :hostName
                AND s.shortName LIKE :siteName
                AND n.name LIKE :ngi
                AND c.name LIKE :country
                AND st.name LIKE :serviceType
                AND se.monitored LIKE :monitored";

        $q = $this->em->createQuery($dql)
                ->setParameter('hostName', $hostName)
                ->setParameter('siteName', $site)
                ->setParameter('ngi', $ngi)
                ->setParameter('country', $country)
                ->setParameter('serviceType', $serviceType)
                ->setParameter('monitored', $monitored);
        
        $q = $this->setScopeBindParameters($scopeArray, $q);
        
        $ses = $q->getResult();

        $xml = new \SimpleXMLElement("<results />");
        foreach($ses as $se) {
            $xmlSe = $xml->addChild('SERVICE_ENDPOINT');
            $xmlSe->addAttribute("PRIMARY_KEY", $se->getId() . "G0");
            $this->addIfNotEmpty($xmlSe, 'PRIMARY_KEY', $se->getId() . "G0");
            $this->addIfNotEmpty($xmlSe, 'HOSTNAME', $se->getHostName());
            $portalUrl = '#GOCDB_BASE_PORTAL_URL#/index.php?Page_Type=Service&id=' . $se->getId();
            $portalUrl = htmlspecialchars($portalUrl);
            $this->addIfNotEmpty($xmlSe, 'GOCDB_PORTAL_URL', $portalUrl);
            $this->addIfNotEmpty($xmlSe, 'HOSTDN', $se->getDn());
            $this->addIfNotEmpty($xmlSe, 'HOST_OS', $se->getOperatingSystem());
            /* JC: I'm not sure why I put this in earlier. The production output
             * doesn't include DESCRIPTION for SEs. Commenting for now.
             */
            // $description = htmlspecialchars($se->getDescription());
            // $this->addIfNotEmpty($xmlSe, 'DESCRIPTION', $description);
            $this->addIfNotEmpty($xmlSe, 'HOST_ARCH', $se->getArchitecture());

            if($se->getBeta()) {
                $beta = "Y";
            } else {
                $beta = "N";
            }
            $xmlSe->addChild('BETA', $beta);

            $this->addIfNotEmpty($xmlSe, 'SERVICE_TYPE', $se->getServiceType()->getName());
            $this->addIfNotEmpty($xmlSe, 'HOST_IP', $se->getIpAddress());
            $xmlSe->addChild("CORE", "");

            if($se->getProduction()) {
                $prod = "Y";
            } else {
                $prod = "N";
            }
            $xmlSe->addChild('IN_PRODUCTION', $prod);


            if($se->getMonitored()) {
                $mon = "Y";
            } else {
                $mon = "N";
            }
            $xmlSe->addChild('NODE_MONITORED', $mon);
            $site = $se->getParentSite();
            $this->addIfNotEmpty($xmlSe, "SITENAME", $site->getShortName());
            $this->addIfNotEmpty($xmlSe, "COUNTRY_NAME", $site->getCountry()->getName());
            $this->addIfNotEmpty($xmlSe, "COUNTRY_CODE", $site->getCountry()->getCode());
            $this->addIfNotEmpty($xmlSe, "ROC_NAME", $site->getNGI()->getName());
            $endpointLocation = htmlspecialchars($se->getEndpointLocations()->first()->getUrl());
            $xmlSe->addChild("URL", $endpointLocation);
        }

        $dom_sxe = dom_import_simplexml($xml);
        $dom = new \DOMDocument('1.0');
        $dom->encoding='UTF-8';
        $dom_sxe = $dom->importNode($dom_sxe, true);
        $dom_sxe = $dom->appendChild($dom_sxe);
        $dom->formatOutput = true;
        $xmlString = $dom->saveXML();
        return $xmlString;
    }

    public function getDowntime($parameters){
        return $this->getDowntimeHelper($parameters, false);
    }
    
    public function getDowntimeShort($parameters){
        return $this->getDowntimeHelper($parameters, true);
    }
    
    /**
     * Return an XML document that encodes the downtimes.	 	 
     * Optionally provide an associative array of query parameters with values to restrict the results.
     * Only known parameters are honoured while unknown params produce an error doc.
     * Parmeter array keys include:
     * <pre>
     * 'topentity', 'ongoing_only' , 'startdate', 'enddate', 'windowstart', 'windowend',
     * 'scope', 'scope_match', 'page' (where scope refers to Service scope)
     * </pre>
     * Uses the addIfNotEmpty method to duplicate the behavior of the Oracle XML
     * module that rendered this query in GOCDBv4.
     * 
     * @param array $parameters
     *        	Associative array of parameters to narrow the query
     * @return string XML result string
     * @throws \Exception
     */
     private function getDowntimeHelper($parameters, $isShort){
        // Define supported parameters and validate given params (die if an unsupported param is given)
        /** Short is a programitcally set parameter not a user supplied limiter **/
        $supportedQueryParams = array('topentity', 'ongoing_only'
                , 'startdate', 'enddate', 'windowstart',
                'windowend', 'scope', 'scope_match', 'page', 'all_lastmonth');
        $this->validateParams($supportedQueryParams, $parameters);
        
        // Ensure all dates are in UTC
        date_default_timezone_set("UTC");
        define('DATE_FORMAT', 'Y-m-d H:i');

        // Validate page parameter
        if (isset($parameters['page'])) {
            if ($this->is_whole_number($parameters['page']) && (int) $parameters['page'] > 0) {
                $page = (int) $parameters['page'];
            } else {
                echo "<error>Invalid 'page' parameter - must be a whole number greater than zero</error>";
                die();
            }
        }
       
        if(isset($parameters['topentity'])) {
            $topEntity = $parameters['topentity'];
        } else {
            $topEntity = '%%';
        }

        if(isset($parameters['ongoing_only'])) {
            $onGoingOnly = $parameters['ongoing_only'];
            if($onGoingOnly == 'yes'){
                if(isset($parameters['enddate']) || isset($parameters['startdate'])){
                    echo "<error>Invalid parameter combination - do not specify startdate or enddate with ongoing_only</error>"; 
                    die(); 
                }
            } else if($onGoingOnly == 'no'){
               // else do nothing 
            } else {
                echo "<error>Invalid ongoing_only value - must be 'yes' or 'no'</error>"; 
                die(); 
            }
        } else {
            $onGoingOnly = 'no';
        }

        if(isset($parameters['startdate'])) {
            $startDate = new \DateTime($parameters['startdate']);
        } else {
            $startDate = null;
        }

        if(isset($parameters['enddate'])) {
            $endDate = new \DateTime($parameters['enddate']);
        } else {
            $endDate = null;
        }

        if(isset($parameters['windowstart'])) {
            $windowStart = new \DateTime($parameters['windowstart']);
        } else {
            $windowStart = null;
        }

        if(isset($parameters['windowend'])) {
            $windowEnd = new \DateTime($parameters['windowend']);
            // Add 1 day to windowend so that downtimes that start on
            // e.g. 2012-02-01 16:00 are included if the windowend is set 
            // to 2012-02-01. This is to mirror the GOCDBv4 behaviour
            $windowEnd->add(new \DateInterval('P1D'));
            // DM: Adding one day can also be done using DQL with the DATE_ADD
            // method used within the query, but I can't get it to work with Oracle 
            // so add the day using $windowEnd->add(dateInterval) instead.  
            // DATE_ADD(:windowEnd, 1, 'DAY') 
        } else {
            $windowEnd = null;
        }

        if(isset($parameters['scope'])) {
            $scopeArray = $this->convertScopesToArray($parameters['scope']);
        } else {
            $scopeArray = $this->convertScopesToArray($this->defaultScope());
        }
        
        $scopeClause = "";
        if($this->containsValidScope($scopeArray)){
            $scopeClause = $this->scopeClauseFromScopeArray($scopeArray, "sc", 
                    $this->allScopesMustMatch($parameters), 'Service', 'se') . " 
                AND";
        }  

        // Special parameter added for ATP who require all downtimes starting 
        // from one month ago (including current and future DTs) to be generated
        // in one page result. We don't want to allow the disabling of paging (using $page=-1)  
        // for other getDowntime() queries as this circumvents paging as a saftey parameter. 
        // It is ok if we are only returning DTs for the last month as the number
        // of DTs will probably be less than the page limit anyway.  
        if(isset($parameters['all_lastmonth'])){ 
            if(isset($parameters['page']) || 
                    isset($parameters['ongoing_only']) || 
                    isset($parameters['startdate']) || 
                    isset($parameters['enddate']) || 
                    isset($parameters['windowstart']) || 
                    isset($parameters['windowend'])){
               echo "<error>Invalid parameters - only scope, scope_match, 
                   topentity params allowed when specifying all_lastmonth</error>";
               die(); 
            }
            // current date with 1 month and 1 day subtracted. 
            $startDate = new \DateTime();
            $startDate->sub(new \DateInterval('P1M'));
            $startDate->sub(new \DateInterval('P1D'));
        }
        
        //See note 1 at bottom of class
        $dql =  "SELECT DISTINCT d, els, se, s, sc, st 
                FROM Downtime d
                JOIN d.endpointLocations els
                JOIN els.service se
                JOIN se.parentSite s
                LEFT JOIN se.scopes sc
                JOIN s.ngi n
                JOIN s.country c
                JOIN se.serviceType st

                WHERE " . $scopeClause //AND clause is specified in string
                . " (se.hostName LIKE :topEntity
                    OR s.shortName LIKE :topEntity
                    OR n.name LIKE :topEntity
                    OR c.name LIKE :topEntity
                )
                
                AND (
                        :onGoingOnly = 'no'
                        OR
                        (:onGoingOnly = 'yes'
                        AND d.startDate < :now
                        AND d.endDate > :now)
                )
                
                AND (
                    :startDate IS null 
                    OR d.startDate > :startDate
                )

                AND (
                    :endDate IS null 
                    OR d.endDate < :endDate
                )

                AND (
                    :windowStart IS null
                    OR d.endDate > :windowStart
                )

                AND (
                    :windowEnd IS null
                    OR d.startDate < :windowEnd 
                ) 
                ORDER BY d.startDate DESC
                ";

        //$offset = 0; $maxResults = null;
        $q = $this->em->createQuery($dql)
                    ->setParameter('topEntity', $topEntity)
                    ->setParameter('onGoingOnly', $onGoingOnly)
                    ->setParameter('now', new \DateTime())
                    ->setParameter('startDate', $startDate)
                    ->setParameter('endDate', $endDate)
                    ->setParameter('windowStart', $windowStart)
                    ->setParameter('windowEnd', $windowEnd);
        
        $q = $this->setScopeBindParameters($scopeArray, $q);
        
        // If the page parameter is specified, paginate the results
        if(isset($parameters['page'])) {
            // The maxResults we want to render in one page (todo, lookup from config)
            // Note, don't set $maxResults to more than 1000, if >1000 is needed, you 
            // can't use the Doctrine paginator due to issues described below. 
            $maxResults = 1000;
             
            if($page == 1){
                $offset = 0; // offset is zero-offset (starts from 0 not 1)
            } elseif($page > 1) {
                $offset = (($page-1)*$maxResults);
            } else {
                throw new LogicException('Coding error - invalid page ['.$page.']');
            }
            //See note 2 at bottom of class            
            $q->setFirstResult($offset)->setMaxResults($maxResults);
            $results = new Paginator($q, $fetchJoinCollection = true);
            //If short downtime has been called return XML without repeating service endpoints 
            if($isShort){
                return $this->getDowntimeXML($results, 1);  
            }else{
                return $this->getDowntimeXML($results, 2);
            }          
        } else {
            $results = $q->getArrayResult();        	 
            //If short downtime has been called return XML without repeating service endpoints        	     
            if($isShort){
                return $this->getDowntimeXML($results, 3);  
            }else{
                return $this->getDowntimeXML($results, 4);
            }          
        }
    }
    
    
    /** 2 Different formats of XML can be returned. A short format downtime which doesn't have services
     * repeating within a downtime. And the original longer format downtime. Both long and short format 
     * have a method which uses pagination and get result and a method which uses get array instead of get result.
     * 
     * @param ResultSet $results
     * @param int $type Type of downtime to render to user, standard, nested and paginated version of both
     * @return XMLString
     * @throws \Exception
     */
    private function getDowntimeXML($results, $type){
        
        switch($type){			
            case 1:
                //Get result method for SHORT format downtime requests with page parameter
                $xml = new \SimpleXMLElement("<results />");
                foreach($results as $downtime) {
                    $xmlDowntime = $xml->addChild('DOWNTIME');
                    // ID is the internal object id/sequence
                    $xmlDowntime->addAttribute("ID", $downtime->getId());
                    // Note, we are preserving the v4 primary keys here.
                    //$xmlDowntime->addAttribute("PRIMARY_KEY", $downtime->getId() . "G0");
                    $xmlDowntime->addAttribute("PRIMARY_KEY", $downtime->getPrimaryKey());
                    
                    $xmlDowntime->addAttribute("CLASSIFICATION", $downtime->getClassification());
                    
                    $portalUrl = '#GOCDB_BASE_PORTAL_URL#/index.php?Page_Type=Downtime&id=' . $downtime->getId();
                    $portalUrl = htmlspecialchars($portalUrl);
                    
                    $this->addIfNotEmpty($xmlDowntime, 'SEVERITY', $downtime->getSeverity());
                    $description = htmlspecialchars($downtime->getDescription());
                    $this->addIfNotEmpty($xmlDowntime, 'DESCRIPTION', $description);
                    $this->addIfNotEmpty($xmlDowntime, 'INSERT_DATE', $downtime->getInsertDate()->getTimestamp());
                    $this->addIfNotEmpty($xmlDowntime, 'START_DATE', $downtime->getStartDate()->getTimestamp());
                    $this->addIfNotEmpty($xmlDowntime, 'END_DATE', $downtime->getEndDate()->getTimestamp());
                    $this->addIfNotEmpty($xmlDowntime, 'FORMATED_START_DATE', $downtime->getStartDate()->format(DATE_FORMAT));
                    $this->addIfNotEmpty($xmlDowntime, 'FORMATED_END_DATE', $downtime->getEndDate()->format(DATE_FORMAT));
                    $this->addIfNotEmpty($xmlDowntime, 'GOCDB_PORTAL_URL', $portalUrl);
                    
                    $xmlImpactedSE = $xmlDowntime->addChild('SERVICES');						
                    foreach($downtime->getEndpointLocations() as $els){
                        $se = $els->getService();
                        $xmlServices = $xmlImpactedSE->addChild('SERVICE');
                        
                        $this->addIfNotEmpty($xmlServices, 'PRIMARY_KEY', $se->getId());				
                        $this->addIfNotEmpty($xmlServices, 'HOSTNAME', $se->getHostName());
                        $this->addIfNotEmpty($xmlServices, 'SERVICE_TYPE', $se->getServiceType()->getName());
                        $endpoint = $se->getHostName() . $se->getServiceType()->getName();
                        $this->addIfNotEmpty($xmlServices, 'ENDPOINT', $endpoint);
                        $this->addIfNotEmpty($xmlServices, 'HOSTED_BY', $se->getParentSite()->getShortName());						
                    }
                }
                break;
            case 2:
                //Get result method for LONG REPEATING format downtime requests with page parameter				
                $xml = new \SimpleXMLElement("<results />");
                    foreach ( $results as $downtime ) {
                foreach ( $downtime->getEndpointLocations () as $els ) {
                    $se = $els->getService ();
                    
                    $xmlDowntime = $xml->addChild ( 'DOWNTIME' );
                    // ID is the internal object id/sequence
                    $xmlDowntime->addAttribute ( "ID", $downtime->getId () );
                    
                    // Note, we are preserving the v4 primary keys here.
                    // $xmlDowntime->addAttribute("PRIMARY_KEY", $downtime->getId() . "G0");
                    $xmlDowntime->addAttribute ( "PRIMARY_KEY", $downtime->getPrimaryKey () );
                    
                    $xmlDowntime->addAttribute ( "CLASSIFICATION", $downtime->getClassification () );
                    
                    // $this->addIfNotEmpty($xmlDowntime, 'PRIMARY_KEY', $downtime->getId() . "G0");
                    $this->addIfNotEmpty ( $xmlDowntime, 'PRIMARY_KEY', $downtime->getPrimaryKey () );
                    
                    $this->addIfNotEmpty ( $xmlDowntime, 'HOSTNAME', $se->getHostName () );
                    $this->addIfNotEmpty ( $xmlDowntime, 'SERVICE_TYPE', $se->getServiceType ()->getName () );
                    $endpoint = $se->getHostName () . $se->getServiceType ()->getName ();
                    $this->addIfNotEmpty ( $xmlDowntime, 'ENDPOINT', $endpoint );
                    $this->addIfNotEmpty ( $xmlDowntime, 'HOSTED_BY', $se->getParentSite ()->getShortName () );
                    $portalUrl = '#GOCDB_BASE_PORTAL_URL#/index.php?Page_Type=Downtime&id=' . $downtime->getId ();
                    $portalUrl = htmlspecialchars ( $portalUrl );
                    $this->addIfNotEmpty ( $xmlDowntime, 'GOCDB_PORTAL_URL', $portalUrl );
                    $this->addIfNotEmpty ( $xmlDowntime, 'SEVERITY', $downtime->getSeverity () );
                    $description = htmlspecialchars ( $downtime->getDescription () );
                    $this->addIfNotEmpty ( $xmlDowntime, 'DESCRIPTION', $description );
                    $this->addIfNotEmpty ( $xmlDowntime, 'INSERT_DATE', $downtime->getInsertDate ()->getTimestamp () );
                    $this->addIfNotEmpty ( $xmlDowntime, 'START_DATE', $downtime->getStartDate ()->getTimestamp () );
                    $this->addIfNotEmpty ( $xmlDowntime, 'END_DATE', $downtime->getEndDate ()->getTimestamp () );
                    $this->addIfNotEmpty ( $xmlDowntime, 'FORMATED_START_DATE', $downtime->getStartDate ()->format ( DATE_FORMAT ) );
                    $this->addIfNotEmpty ( $xmlDowntime, 'FORMATED_END_DATE', $downtime->getEndDate ()->format ( DATE_FORMAT ) );
                }
            }
                break;
            case 3:				
                //Get array method for SHORT format downtime requests without page parameter
                $xml = new \SimpleXMLElement("<results/>");
                
                foreach ($results as $downtimeArray) {
                    $xmlDowntime = $xml->addChild('DOWNTIME');
                    //header start
                    $xmlDowntime->addAttribute("ID", $downtimeArray['id']);
                    $xmlDowntime->addAttribute("PRIMARY_KEY", $downtimeArray['primaryKey']);
                    $xmlDowntime->addAttribute("CLASSIFICATION", $downtimeArray['classification']);
                    //header end
                    
                    $this->addIfNotEmpty($xmlDowntime, 'SEVERITY', $downtimeArray['severity']);
                    $this->addIfNotEmpty($xmlDowntime, 'DESCRIPTION', htmlspecialchars ( $downtimeArray['description']));
                        
                    $this->addIfNotEmpty($xmlDowntime, 'INSERT_DATE', strtotime($downtimeArray['insertDate']->format('Y-m-d H:i:s')));
                    $this->addIfNotEmpty($xmlDowntime, 'START_DATE', strtotime($downtimeArray['startDate']->format('Y-m-d H:i:s')));
                    $this->addIfNotEmpty($xmlDowntime, 'END_DATE', strtotime($downtimeArray['endDate']->format('Y-m-d H:i:s')));
                    $this->addIfNotEmpty($xmlDowntime, 'FORMATED_START_DATE', $downtimeArray['startDate']->format('Y-m-d H:i'));
                    $this->addIfNotEmpty($xmlDowntime, 'FORMATED_END_DATE', $downtimeArray['endDate']->format('Y-m-d H:i'));
                    $portalUrl = '#GOCDB_BASE_PORTAL_URL#/index.php?Page_Type=Downtime&id=' . $downtimeArray['id'];
                    $portalUrl = htmlspecialchars ( $portalUrl );
                    $this->addIfNotEmpty($xmlDowntime, 'GOCDB_PORTAL_URL', $portalUrl);
                    
                    $xmlImpactedSE = $xmlDowntime->addChild('SERVICES');						
                    foreach($downtimeArray['endpointLocations'] as $affectedEndpointArray){
                        $xmlServices = $xmlImpactedSE->addChild('SERVICE');						
                        $this->addIfNotEmpty($xmlServices, 'PRIMARY_KEY', $affectedEndpointArray['service']['id']);
                        $this->addIfNotEmpty($xmlServices, 'HOSTNAME', htmlspecialchars ( $affectedEndpointArray['service']['hostName'] ));
                        $this->addIfNotEmpty($xmlServices, 'SERVICE_TYPE', $affectedEndpointArray['service']['serviceType']['name']);
                        $this->addIfNotEmpty($xmlServices, 'ENDPOINT', $affectedEndpointArray['service']['hostName'] . $affectedEndpointArray['service']['serviceType']['name']);
                        $this->addIfNotEmpty($xmlServices, 'HOSTED_BY', $affectedEndpointArray['service']['parentSite']['shortName']);
                        
                
                    }
                }
                break;
            case 4:
                //Get array method for LONG REPEATING downtime requests without page parameter
                $xml = new \SimpleXMLElement("<results/>");
                                
                foreach ($results as $downtimeArray) {
                    foreach($downtimeArray['endpointLocations'] as $affectedEndpointArray){
                        $xmlDowntime = $xml->addChild('DOWNTIME');
                        //header start
                        $xmlDowntime->addAttribute("ID", $downtimeArray['id']);
                        $xmlDowntime->addAttribute("PRIMARY_KEY", $downtimeArray['primaryKey']);
                        $xmlDowntime->addAttribute("CLASSIFICATION", $downtimeArray['classification']);
                        //header end
                
                        $this->addIfNotEmpty($xmlDowntime, 'PRIMARY_KEY', $downtimeArray['primaryKey']);
                        $this->addIfNotEmpty($xmlDowntime, 'HOSTNAME', htmlspecialchars ( $affectedEndpointArray['service']['hostName'] ));
                        $this->addIfNotEmpty($xmlDowntime, 'SERVICE_TYPE', $affectedEndpointArray['service']['serviceType']['name']);
                        $this->addIfNotEmpty($xmlDowntime, 'ENDPOINT', $affectedEndpointArray['service']['hostName'] . $affectedEndpointArray['service']['serviceType']['name']);
                        $this->addIfNotEmpty($xmlDowntime, 'HOSTED_BY', $affectedEndpointArray['service']['parentSite']['shortName']);
                        $portalUrl = '#GOCDB_BASE_PORTAL_URL#/index.php?Page_Type=Downtime&id=' . $downtimeArray['id'];
                        $portalUrl = htmlspecialchars ( $portalUrl );
                        $this->addIfNotEmpty($xmlDowntime, 'GOCDB_PORTAL_URL', $portalUrl);
                        $this->addIfNotEmpty($xmlDowntime, 'SEVERITY', $downtimeArray['severity']);
                        $this->addIfNotEmpty($xmlDowntime, 'DESCRIPTION', htmlspecialchars ( $downtimeArray['description']));
                            
                        $this->addIfNotEmpty($xmlDowntime, 'INSERT_DATE', strtotime($downtimeArray['insertDate']->format('Y-m-d H:i:s')));
                        $this->addIfNotEmpty($xmlDowntime, 'START_DATE', strtotime($downtimeArray['startDate']->format('Y-m-d H:i:s')));
                        $this->addIfNotEmpty($xmlDowntime, 'END_DATE', strtotime($downtimeArray['endDate']->format('Y-m-d H:i:s')));
                        $this->addIfNotEmpty($xmlDowntime, 'FORMATED_START_DATE', $downtimeArray['startDate']->format('Y-m-d H:i'));
                        $this->addIfNotEmpty($xmlDowntime, 'FORMATED_END_DATE', $downtimeArray['endDate']->format('Y-m-d H:i'));
                
                    }
                }
                break;
        }
        
        
        $dom_sxe = dom_import_simplexml($xml);
        $dom = new \DOMDocument('1.0');
        $dom->encoding='UTF-8';
        $dom_sxe = $dom->importNode($dom_sxe, true);
        $dom_sxe = $dom->appendChild($dom_sxe);
        $dom->formatOutput = true;
        $xmlString = $dom->saveXML();
        return $xmlString;
        
    }
    
    /**
     * Return an XML document that encodes the downtimes selected from the DB.
     * Optionally provide an associative array of query parameters with values used to restrict the results.
     * Only known parameters are honoured while unknown params produce an error doc. 
     * Parmeter array keys include:
     * <pre>
     * 'interval', 'scope', 'scope_match' (where scope refers to Service scope) 
     * </pre>
     * @param array $parameters Associative array of parameters to narrow the query
     * @return string XML result string
     * @throws \Exception
     */
    public function getDowntimeToBroadcast($parameters){
        $supportedQueryParams = array('interval', 'scope', 'scope_match');
        $this->validateParams($supportedQueryParams, $parameters);

        //Ensure all dates are  UTC
        date_default_timezone_set("UTC");
        define('DATE_FORMAT', 'Y-m-d H:i');
        
        if(isset($parameters['interval'])) {
            if(is_numeric($parameters['interval'])) {
                $interval = $parameters['interval'];
            } else {
                 echo '<error>interval is not a number</error>';
                 die();
            }
        } else {
            // Default: downtimes declared in the last day
            $interval = '1';
        }

        if(isset($parameters['scope'])) {
            $scopeArray = $this->convertScopesToArray($parameters['scope']);
        } else {
            $scopeArray = $this->convertScopesToArray($this->defaultScope());
        } 
        
        $scopeClause = "";
        if($this->containsValidScope($scopeArray)){
            $scopeClause = $this->scopeClauseFromScopeArray($scopeArray, "sc", 
                    $this->allScopesMustMatch($parameters), 'Service', 'se') . " 
                AND";
        } 
        
        // Subtract 'interval' number days from current time. 
        // DM: Note, you can do this using the DATE_SUB DQL function used 
        // within the query, e.g. DATE_SUB(:now, :interval, 'DAY') however, 
        // I can't get it to work with Oracle so am calculating the new date 
        // in php not in DQL. 
        $nowMinusIntervalDays = new \DateTime();  
        $nowMinusIntervalDays->sub(new \DateInterval('P'.$interval.'D'));

        $dql = "SELECT d
                FROM Downtime d
                JOIN d.endpointLocations els
                JOIN els.service se
                JOIN se.parentSite s
                LEFT JOIN se.scopes sc
                JOIN s.ngi n
                JOIN s.country c
                WHERE " . $scopeClause //includes AND
                . " d.insertDate > :nowMinusIntervalDays"
                ;
        
        $q = $this->em->createQuery($dql)
                ->setParameter('nowMinusIntervalDays', $nowMinusIntervalDays);

        $q = $this->setScopeBindParameters($scopeArray, $q);

        $downtimes = $q->getResult();
        
        $xml = new \SimpleXMLElement("<results />");
        foreach($downtimes as $downtime) {
            //foreach($downtime->getServices() as $se) {
            foreach($downtime->getEndpointLocations() as $els){
                $se = $els->getService(); 
                $xmlDowntime = $xml->addChild('DOWNTIME');
                $xmlDowntime->addAttribute("ID", $downtime->getId());
                // Note, we are preserving the v4 primary keys here. 
                //$xmlDowntime->addAttribute("PRIMARY_KEY", $downtime->getId() . "G0");
                $xmlDowntime->addAttribute("PRIMARY_KEY", $downtime->getPrimaryKey());

                $xmlDowntime->addAttribute("CLASSIFICATION", $downtime->getClassification());
                //$xmlDowntime->addChild("PRIMARY_KEY", $downtime->getId() . "G0");
                $xmlDowntime->addChild("PRIMARY_KEY", $downtime->getPrimaryKey());

                // Intentionally left blank to duplicate GOCDBv4 PI behaviour
                $xmlDowntime->addChild("SITENAME", "");
                $xmlDowntime->addChild("HOSTNAME", $se->getHostName());
                $xmlDowntime->addChild("SERVICE_TYPE", $se->getServiceType()->getName());
                $xmlDowntime->addChild("HOSTED_BY", $se->getParentSite()->getShortName());
                $portalUrl = '#GOCDB_BASE_PORTAL_URL#/index.php?Page_Type=Downtime&id=' . $downtime->getId();
                $portalUrl = htmlspecialchars($portalUrl);
                $xmlDowntime->addChild('GOCDB_PORTAL_URL', $portalUrl);
                $xmlDowntime->addChild('SEVERITY', $downtime->getSeverity());
                $xmlDowntime->addChild('DESCRIPTION', htmlspecialchars($downtime->getDescription()));
                $xmlDowntime->addChild('INSERT_DATE', $downtime->getInsertDate()->getTimestamp());
                $xmlDowntime->addChild('START_DATE', $downtime->getStartDate()->getTimestamp());
                $xmlDowntime->addChild('END_DATE', $downtime->getEndDate()->getTimestamp());
                $xmlDowntime->addChild('REMINDER_START_DOWNTIME', $downtime->getAnnounceDate()->getTimestamp());
                // Intentionally left blank to duplicate GOCDBv4 PI behaviour
                $xmlDowntime->addChild('BROADCASTING_START_DOWNTIME', "");
            }
        }

        $dom_sxe = dom_import_simplexml($xml);
        $dom = new \DOMDocument('1.0');
        $dom->encoding='UTF-8';
        $dom_sxe = $dom->importNode($dom_sxe, true);
        $dom_sxe = $dom->appendChild($dom_sxe);
        $dom->formatOutput = true;

        $xmlString = $dom->saveXML();

        return $xmlString;
    }

    /**
     * Return an XML document that encodes the NGIs selected from the DB.
     * Optionally provide an associative array of query parameters with values to restrict the results.
     * Only known parameters are honoured while unknown params produce an error doc. 
     * Parmeter array keys include:
     * <pre>
     * 'roc', 'scope', 'scope_match' (where scope refers to NGI scope)  
     * </pre>
     * Implemented with Doctrine.
     * @param array $parameters Associative array of parameters to narrow the query
     * @return string XML result string
     * @throws \Exception
     */
    public function getNgi($parameters){
        $supportedQueryParams = array('roc', 'scope', 'scope_match');
        $this->validateParams($supportedQueryParams, $parameters);

        if(isset($parameters['roc'])) {
            $ngi = $parameters['roc'];
        } else {
            $ngi = '%%';
        }

        if(isset($parameters['scope'])) {
            $scopeArray = $this->convertScopesToArray($parameters['scope']);
        } else {
            $scopeArray = $this->convertScopesToArray($this->defaultScope());
        }
        
        $scopeClause = "";
        if($this->containsValidScope($scopeArray)){
            $scopeClause = $this->scopeClauseFromScopeArray($scopeArray, "sc", 
                    $this->allScopesMustMatch($parameters), 'NGI', 'n') . " 
                AND";
        } 
        
        $dql = "SELECT n FROM NGI n
                LEFT JOIN n.scopes sc
                WHERE" . $scopeClause // AND clause is already appended to $scopeClause where relevant.
                . " n.name LIKE :ngi";
        
        $q = $this->em->createQuery($dql)
                    ->setParameter('ngi', $ngi);
        
        $q = $this->setScopeBindParameters($scopeArray, $q);
        
        $ngis = $q->getResult();

        $xml = new \SimpleXMLElement("<results />");
        foreach($ngis as $ngi) {
            $xmlNgi = $xml->addChild('NGI');
            $xmlNgi->addAttribute("NAME", $ngi->getName());
            $xmlNgi->addChild("PRIMARY_KEY", $ngi->getId());
            $xmlNgi->addChild("NAME", $ngi->getName());
            $xmlNgi->addChild("OBJECT_ID", $ngi->getId());
            $xmlNgi->addChild("DESCRIPTION", $ngi->getDescription());
            $xmlNgi->addChild("EMAIL", $ngi->getEmail());
            $xmlNgi->addChild("GGUS_SU", $ngi->getGgus_Su());
            $xmlNgi->addChild("ROD_EMAIL", $ngi->getRodEmail());
            $xmlNgi->addChild("HELPDESK_EMAIL", $ngi->getHelpdeskEmail());
            $xmlNgi->addChild("SECURITY_EMAIL", $ngi->getSecurityEmail());
            $xmlNgi->addChild("SITE_COUNT", count($ngi->getSites()));
        }

        $dom_sxe = dom_import_simplexml($xml);
        $dom = new \DOMDocument('1.0');
        $dom->encoding='UTF-8';
        $dom_sxe = $dom->importNode($dom_sxe, true);
        $dom_sxe = $dom->appendChild($dom_sxe);
        $dom->formatOutput = true;

        $xmlString = $dom->saveXML();

        return $xmlString;
    }

    /**
     * Return an XML document that encodes the users.
     * Optionally provide an associative array of query parameters with values to restrict the results.
     * Only known parameters are honoured while unknown params produce an error doc.
     * Parmeter array keys include:
     * <pre>
     * 'dn', 'dnlike', 'forename', 'surname', 'roletype'
     * </pre>
     * Implemented with Doctrine.
     *
     * @param array $parameters	Associative array of parameters to narrow the query
     * @return string XML result string
     * @throws \Exception
     */
    public function getUser($parameters) {
        $supportedQueryParams = array (
                'dn',
                'dnlike',
                'forename',
                'surname',
                'roletype'
        );
        $this->validateParams ( $supportedQueryParams, $parameters );
        
        //Initialize base query
        $qb = $this->em->createQueryBuilder();
        
        $qb	->select('u')
        ->from('User', 'u')
        ->leftJoin('u.roles', 'r')
        ->orderBy('u.id', 'ASC');
        

        //Add each parameter to query if it is set		
        if (isset ( $parameters ['forename'] )) {	
            $qb	->andWhere($qb->expr()->like('u.forename', ':forename'))	
                ->setParameter('forename', $parameters['forename']);
        }
        
        if (isset ( $parameters ['surname'] )) {
            $qb	->andWhere($qb->expr()->like('u.surname', ':surname'))
                ->setParameter('surname', $parameters['surname']);
        }
        
        if (isset ( $parameters ['dn'] )) {
            $qb	->andWhere($qb->expr()->like('u.certificateDn', ':dn'))
                ->setParameter('dn', $parameters['dn']);
        }
        
        if (isset ( $parameters ['dnlike'] )) {
            $qb	->andWhere($qb->expr()->like('u.certificateDn', ':dnlike'))
                ->setParameter('dnlike', $parameters['dnlike']);
        }
        
        /*If the user has specified a role type generate a new subquery
         * and join this to the main query with "where r.roleType in"
         */ 
        if (isset ( $parameters ['roletype'])) {
            
            $qb1 = $this->em->createQueryBuilder();
            $qb1->select('rt.id')
                ->from('roleType', 'rt')
                ->where($qb1->expr()->in('rt.name', ':roleType'));			
            
            $qb ->andWhere($qb->expr()->in('r.roleType', $qb1->getDQL()));
            //If user provided comma seprated values explode it and bind the resulting array
            if(strpos($parameters['roletype'], ',')){
                $exValues = explode(',',$parameters['roletype']);
                $qb->setParameter('roleType', $exValues);		
            }else{
                $qb->setParameter('roleType', $parameters['roletype']); 
            }
        }		
        //Get Results
        $query = $qb->getQuery();		
        $users = $query->execute();
        
        $xml = new \SimpleXMLElement ( "<results />" );
        
        foreach ( $users as $user ) {
            $xmlUser = $xml->addChild ( 'EGEE_USER' );
            $xmlUser->addAttribute ( "ID", $user->getId () . "G0" );
            $xmlUser->addAttribute ( "PRIMARY_KEY", $user->getId () . "G0" );
            $xmlUser->addChild ( 'FORENAME', $user->getForename () );
            $xmlUser->addChild ( 'SURNAME', $user->getSurname () );
            $xmlUser->addChild ( 'TITLE', $user->getTitle () );
            /*
             * Description is always blank in the PROM get_user output so we'll keep it blank in the Doctrine output for compatibility
             */
            $xmlUser->addChild ( 'DESCRIPTION', "" );
            $portalUrl = '#GOCDB_BASE_PORTAL_URL#/index.php?Page_Type=User&id=' . $user->getId ();
            $portalUrl = htmlspecialchars ( $portalUrl );
            $xmlUser->addChild ( 'GOCDB_PORTAL_URL', $portalUrl );
            $xmlUser->addChild ( 'EMAIL', $user->getEmail () );
            $xmlUser->addChild ( 'TEL', $user->getTelephone () );
            $xmlUser->addChild ( 'WORKING_HOURS_START', $user->getWorkingHoursStart () );
            $xmlUser->addChild ( 'WORKING_HOURS_END', $user->getWorkingHoursEnd () );
            $xmlUser->addChild ( 'CERTDN', $user->getCertificateDn () );

                
            /*
             * APPROVED and ACTIVE are always blank in the GOCDBv4 get_user output so we'll keep it blank in the GOCDBv5 output for compatibility
             */
            $xmlUser->addChild ( 'APPROVED', null );
            $xmlUser->addChild ( 'ACTIVE', null );
            $homeSite = "";
            if ($user->getHomeSite () != null) {
                $homeSite = $user->getHomeSite ()->getShortName ();
            }
            $xmlUser->addChild ( 'HOMESITE', $homeSite );
            /*
             * Add a USER_ROLE element to the XML for each role this user holds.
             */
            foreach ( $user->getRoles () as $role ) {
                if ($role->getStatus () == "STATUS_GRANTED"){
                    $xmlRole = $xmlUser->addChild ( 'USER_ROLE' );
                    $xmlRole->addChild ( 'USER_ROLE', $role->getRoleType ()->getName () );
                    
                    /*
                     * Find out what the owned entity is to get its name and type
                     */
                    $ownedEntity = $role->getOwnedEntity ();
                    // We should use the below method from the ownedEntityService
                    // to get the type value, but we may need to display 'group' to be
                    // backward compatible as below. Also added servicegroup to below else if.
                    // $type = $ownedEntityService->getOwnedEntityDerivedClassName($ownedEntity);
                    $name = $ownedEntity->getName ();
                    $type = '';
                    if ($ownedEntity instanceof \Site) {
                        $type = "site";
                    } else if ($ownedEntity instanceof \NGI) {
                        $type = "group";
                    } else if ($ownedEntity instanceof \Project) {
                        $type = "group";
                    } else if ($ownedEntity instanceof \ServiceGroup) {
                        $type = 'servicegroup';
                    } // note, no subgrids but we are removing subgrids.
                    
                    $xmlRole->addChild ( 'ON_ENTITY', $name );
                    $xmlRole->addChild ( 'ENTITY_TYPE', $type );
                }
            }
        }
        
        $dom_sxe = dom_import_simplexml ( $xml );
        $dom = new \DOMDocument ( '1.0' );
        $dom->encoding = 'UTF-8';
        $dom_sxe = $dom->importNode ( $dom_sxe, true );
        $dom_sxe = $dom->appendChild ( $dom_sxe );
        $dom->formatOutput = true;
        $xmlString = $dom->saveXML ();
        
        return $xmlString;
    }


    /**
     * Adds a new tag $tagName to $xml if $value isn't "" or null
     * @param $xml SimpleXMLElement
     * @param $tagName String Name of the tag
     * @param $value String Tag value (nullable) 
     * @return string XML result string
     * @throws Exception 
     */
    private function addIfNotEmpty($xml, $tagName, $value) {
        if($value != null && $value != "") {  
        //if(! empty ($value)) { // empty if val was "0" (as string), FALSE, 0.0 (as float), 0 (as int) - but these could be valid values
            $xml->addChild($tagName, $value);
        }
    }

    /**
     * Ensure that testParams only contain array keys that are supported as listed in $supportedParams.
     * If an unsupported parameter is detected, then die with a message.
     *
     * @param type $supportedParams A single dimensional array of supported/expected parameter names
     * @param type $testParams An associatative array of key => value pairs (parameter key, value)
     * @throws InvalidArgument\Exception if either of the given args are not arrays.
     */
    private function validateParams($supportedParams, $testParams){
        if(!is_array($supportedParams) || !is_array($testParams)) {
            throw new \InvalidArgumentException; //InvalidArgument\Exception;
        }
        
        //Check the parmiter keys are supoported
        $testParamKeys = array_keys($testParams);
        foreach ($testParamKeys as $key ) {
            // if givenkey is not defined in supportedkeys it is unsupported
            if(!in_array($key, $supportedParams)){
                 echo '<error>Unsupported parameter: '.$key.'</error>';
                 die();
            }
        }
        
        //Check that the paramater does not contain invalid chracters
        $testParamValues = array_values($testParams);
        foreach($testParamValues as $value){
            if(!preg_match("/^[^\"';`]*$/", $value)){
                echo '<error>Unsuported chracter in value: '.$value.'</error>';
                die();
            }
        }
        
    }

    /**
     * Return an XML document that encodes the service groups selected from the DB.
     * Optionally provide an associative array of query parameters with values used to restrict the results.
     * Only known parameters are honoured while unknown params produce an error doc. 
     * Parmeter array keys include:
     * <pre>
     * 'service_group_name', 'scope', 'scope_match' 
     * </pre>
     * @param array $parameters Associative array of parameters to narrow the query
     * @return string XML result string
     * @throws \Exception
     */
    public function getServiceGroup($parameters){
        $supportedQueryParams = array('service_group_name', 'scope', 'scope_match');
        $this->validateParams($supportedQueryParams, $parameters);

        if(isset($parameters['service_group_name'])) {
            $name = $parameters['service_group_name'];
        } else {
            $name = '%%';
        }

         if(isset($parameters['scope'])) {
            $scopeArray = $this->convertScopesToArray($parameters['scope']);
        } else {
            $scopeArray = $this->convertScopesToArray($this->defaultScope());
        }
        
        $scopeClause = "";
        if($this->containsValidScope($scopeArray)){
            $scopeClause = $this->scopeClauseFromScopeArray($scopeArray, "sc", 
                    $this->allScopesMustMatch($parameters), 'ServiceGroup', 'sg') . " 
                AND";
        } 

        $q = $this->em->createQuery("
                SELECT sg, s, st 
                FROM ServiceGroup sg
                LEFT JOIN sg.services s
                LEFT JOIN s.serviceType st
                LEFT JOIN sg.scopes sc
                WHERE " . $scopeClause   // AND clause is already appended to $scopeClause where relevant.
                ." sg.name LIKE :name") 
             ->setParameter('name', $name); 
        
         $q = $this->setScopeBindParameters($scopeArray, $q);
         $sgs = $q->getResult();
        
        $xml = new \SimpleXMLElement("<results />");
        foreach($sgs as $sg) {
            $xmlSg = $xml->addChild('SERVICE_GROUP');
            $xmlSg->addAttribute("PRIMARY_KEY", $sg->getId() . "G0");
            $xmlSg->addChild('NAME', $sg->getName());
            $xmlSg->addChild('DESCRIPTION', htmlspecialchars($sg->getDescription()));
            $mon = ($sg->getMonitored()) ? 'Y' : 'N'; 
            $xmlSg->addChild('MONITORED', $mon);
            $xmlSg->addChild('CONTACT_EMAIL', $sg->getEmail());
            $url = '#GOCDB_BASE_PORTAL_URL#/index.php?Page_Type=Service_Group&id=' . $sg->getId();
            $url = htmlspecialchars($url);
            $xmlSg->addChild('GOCDB_PORTAL_URL', $url);

            foreach($sg->getServices() as $service){
               $xmlService = $xmlSg->addChild('SERVICE_ENDPOINT'); 
               $xmlService->addChild('HOSTNAME', $service->getHostName());  
               $url = '#GOCDB_BASE_PORTAL_URL#/index.php?Page_Type=Service&id=' . $service->getId(); 
               $xmlService->addChild('GOCDB_PORTAL_URL', htmlspecialchars($url));  
               $xmlService->addChild('SERVICE_TYPE', $service->getServiceType()->getName());  
               $xmlService->addChild('HOST_IP', $service->getIpAddress());  
               $xmlService->addChild('HOSTDN', $service->getDN()); 
               $prod = ($service->getProduction()) ? 'Y' : 'N';  
               $xmlService->addChild('IN_PRODUCTION', $prod);  
               $mon = ($service->getMonitored()) ? 'Y' : 'N'; 
               $xmlService->addChild('NODE_MONITORED', $mon);  
               // Will need to add the service DN when the central security ACL are 
               // setup using a service group. 
               //$xmlService->addChild('DN', htmlspecialchars($service->getDN())); 
            }
            // rendering the scope is not supported by this method yet. 
            //$xmlSg->addChild('SCOPE', $sg->getScopes()->first()->getName());
        }

        $dom_sxe = dom_import_simplexml($xml);
        $dom = new \DOMDocument('1.0');
        $dom->encoding='UTF-8';
        $dom_sxe = $dom->importNode($dom_sxe, true);
        $dom_sxe = $dom->appendChild($dom_sxe);
        $dom->formatOutput = true;
        $xmlString = $dom->saveXML();
        return $xmlString;
    }

    /**
     * Return an XML document that encodes the service groups selected from the DB.
     * Optionally provide an associative array of query parameters with values used to restrict the results.
     * Only known parameters are honoured while unknown params produce error doc. 
     * Parmeter array keys include:
     * <pre>
     * 'service_group_name'
     * </pre>
     * Implemented with Doctrine.
     * @param array $parameters Associative array of parameters to narrow the query
     * @return string XML result string
     * @throws \Exception
     */
    public function getServiceGroupRole($parameters){
        // Define supported parameters and validate given params (die if an unsupported param is given)
        $supportedQueryParams = array('service_group_name', 'scope', 'scope_match');
        $this->validateParams($supportedQueryParams, $parameters);

        if(isset($parameters['service_group_name'])) {
            $name = $parameters['service_group_name'];
        } else {
            $name = '%%';
        }

        if(isset($parameters['scope'])) {
            $scopeArray = $this->convertScopesToArray($parameters['scope']);
        } else {
            $scopeArray = $this->convertScopesToArray($this->defaultScope());
        }
        
        $scopeClause = "";
        if($this->containsValidScope($scopeArray)){
            $scopeClause = $this->scopeClauseFromScopeArray($scopeArray, "sc", 
                    $this->allScopesMustMatch($parameters), 'ServiceGroup', 'sg') . " 
                AND";
        } 

        /*$sgs = $this->em->createQuery("
                SELECT oe, r, u, rt 
                FROM OwnedEntity oe
                JOIN oe.roles r
                JOIN r.user u
                JOIN r.roleType rt
                WHERE oe INSTANCE OF ServiceGroup
                AND oe.id IN (
                    SELECT sg.id FROM ServiceGroup sg
                    WHERE sg.name LIKE :name
                )")->setParameter('name', $name)
                   ->getResult();*/

        $q = $this->em->createQuery("
                SELECT sg, r, u, rt 
                FROM ServiceGroup sg
                LEFT JOIN sg.roles r
                LEFT JOIN r.user u
                LEFT JOIN r.roleType rt
                LEFT JOIN sg.scopes sc
                WHERE ". $scopeClause   // AND clause is already appended to $scopeClause where relevant.
                . " sg.name LIKE :name")
                ->setParameter('name', $name); 
       
        $q = $this->setScopeBindParameters($scopeArray, $q);
        $sgs = $q->getResult();

        $xml = new \SimpleXMLElement("<results />");
        foreach($sgs as $sg) {
            $xmlSg = $xml->addChild('SERVICE_GROUP');
            $xmlSg->addAttribute("PRIMARY_KEY", $sg->getId() . "G0");
            $xmlSg->addChild('NAME', $sg->getName());
            $xmlSg->addChild('DESCRIPTION', htmlspecialchars($sg->getDescription()));
            $mon = ($sg->getMonitored()) ? 'Y' : 'N'; 
            $xmlSg->addChild('MONITORED', $mon);
            $xmlSg->addChild('CONTACT_EMAIL', $sg->getEmail());
            $url = '#GOCDB_BASE_PORTAL_URL#/index.php?Page_Type=Service_Group&id=' . $sg->getId();
            $url = htmlspecialchars($url);
            $xmlSg->addChild('GOCDB_PORTAL_URL', $url);
            foreach($sg->getRoles() as $role) {
                $user = $role->getUser();
                $xmlUser = $xmlSg->addChild('USER');
                $xmlUser->addChild('FORENAME', $user->getForename());
                $xmlUser->addChild('SURNAME', $user->getSurname());
                $xmlUser->addChild('CERTDN', $user->getCertificateDn());
                $url = '#GOCDB_BASE_PORTAL_URL#/index.php?Page_Type=User&id=' . $user->getId();
                $url = htmlspecialchars($url);
                $xmlUser->addChild('GOCDB_PORTAL_URL', $url);
                $xmlUser->addChild('ROLE', $role->getRoleType()->getName());
            }
        }

        $dom_sxe = dom_import_simplexml($xml);
        $dom = new \DOMDocument('1.0');
        $dom->encoding='UTF-8';
        $dom_sxe = $dom->importNode($dom_sxe, true);
        $dom_sxe = $dom->appendChild($dom_sxe);
        $dom->formatOutput = true;
        $xmlString = $dom->saveXML();
        return $xmlString;
    }
    
    /**
     * Gets the name of the default scope from the config service. 
     * 
     * @return string
     */
    private function defaultScope(){
        $configService = new \org\gocdb\services\Config();
        $scopes = $configService->getDefaultScopeName();
        return $scopes;
    }
    
    /**
     * Takes a string containing a comma seperated list of strings and creates 
     * an associative array. The key is used as a bind variab name  the value is
     * the name of the scope.
     * 
     * @param string $scopes
     * @return array associative array
     */
    private function convertScopesToArray($scopes){
        //explode the scopes string into an array
        $scopesNameArray = explode(",", $scopes);
        
        $scopeArray = array();
        $count=0;
        
        foreach($scopesNameArray as $scopeName){
            $scopeArray["scope".++$count] = $scopeName;
        }
        
        return $scopeArray;
    }
    

    /**
     * using the scope array and other paramater this function forms a dql string
     * to select entities based on the scope specified by the matching method 
     * specified
     * 
     * @param array $scopeArray key-value array. Keys are used as bind variable 
     *                          names values are the names of scopes
     * @param string $scopeAlias  alias for scope in DQL, usually 'sc'
     * @param boolean $shouldMatchAll if true all scopes specified must be matched
     *                                to the entity, if false one or moreof them
     *                                must match
     * @param string $entityName name of entity e.g. 'Service' or 'NGI'
     * @param string $entityAlias alias for the above, e.g. 's' or 'n' or 'se'
     * @return string DQL WHERE clause
     */
    private function scopeClauseFromScopeArray($scopeArray, $scopeAlias, $shouldMatchAll, 
                                                $entityName, $entityAlias){
        $clause = "";
        $count = 0;
        
        if ($shouldMatchAll){
            //Match every scope
            foreach(array_keys($scopeArray) as $bindName){
                $clause .= "
                            EXISTS (
                                SELECT " . $entityAlias . ++$count . ".id
                                FROM " . $entityName . " ".$entityAlias.$count . "
                                JOIN ".$entityAlias.$count . ".scopes " . $scopeAlias.$count . "
                                WHERE ". $scopeAlias.$count .".name = :" . $bindName . "
                                AND " . $entityAlias . ".id" . " = " . $entityAlias . $count . ".id
                            ) AND";
            } 
            //remove the last AND
            $clause = substr($clause, 0, -3);
        }
        else{
            // match any scope
            $clause=" (";
                
            foreach(array_keys($scopeArray) as $bindName){
                $clause .= $scopeAlias . ".name = :". $bindName . " OR ";
            }

            //remove last comma and space
            $clause = substr($clause, 0, -3);
            $clause .= ")"; 
        }
        
        return $clause;
    }

    
    
    /**
     * Sets the scope bind parameters in the query using an array containing 
     * scope names and bind parameter names
     * 
     * @param array $scopeArray array containing a seriese of arrays which contain
     *                          a scope name and a bind parameter name
     * @param  $doctrineQuery   Doctrine query to have bind parameters set in.
     * @return DQL
     */
    private function setScopeBindParameters($scopeArray, $doctrineQuery) {
        //If there are no scopes specified and there is no default scope set,
        //the array will still contain a value, which we want to ignore
        if($this->containsValidScope($scopeArray)){
            foreach(array_keys($scopeArray) as $bindName){
                $doctrineQuery = $doctrineQuery->setParameter($bindName,$scopeArray[$bindName]);
            }
        }
        
        return $doctrineQuery;
    }
    
    /**
     * if the Scope array contains anything over than a scopename with an empty 
     * string as a name return true. Will return false when the user has not 
     * specified a scope and the instance has no default scope specified
     * 
     * @param array $scopeArray
     * @return boolean
     */
    private function containsValidScope ($scopeArray){
        foreach ($scopeArray as $scopeName){
            if($scopeName != ''){
                return true;
            }
        }
   
        return false;
    }
    
    /**
     * If all the scopes specified should be matched it returns true, if any of 
     * the scopes specified  being matched to an object is suffecient for it to
     * be included in the results then it retuns false. This is first determined
     * by the scope_match parameter, if this has not been set, the default 
     * decision is used
     * 
     * @param array $parameters
     * @return boolean
     */
    private function allScopesMustMatch($parameters){
        if(!isset($parameters['scope_match'])) {
            $configService = new \org\gocdb\services\Config();
            $scopeMatch = $configService->getDefaultScopeMatch();
        }
        else{
            $scopeMatch = strtolower($parameters['scope_match']);
        }
        if($scopeMatch=='all'){
                return true;
        }
        elseif($scopeMatch=='any'){
            return false;
        }
        else{
            echo '<error>Unsuported value: '.$parameters['scope_match']." . scope_match accepts either 'any' or 'all'.</error>";
            die();
        }
    }

    
    private function is_whole_number($var){
       return (is_numeric($var)&&(intval($var)==floatval($var)));
    }
    



}

//////////////NOTE 1////////////
// A downtime is rendered if there is at least one service linked to
// that DT that has the required (any or all) scope(s):
//
// - If there is a single service that links to the downtime and this service does NOT
// have the required scope(s), then the downtime will be excluded (the outer query will
        // not join that DT when the WHERE EXISTS clauses return false for that service).
//
// - If there are two or more servcies that link to the downtime, and at least
// one (or more) of those services DO have the required scope(s), the outer query will
// join that DT as the WHERE EXISTS will return true for those services with the required scopes.
// The DT itself will not be dupicated as this is DQL, but there will be many
// joined services for that DT if there are multiple services linked to that DT
// with the required scopes (note we create a new <DOWNTIME> element per linked service).
        //
        // - If there are many services that link to the DT, and NONE of those services
// have the required scopes, then the DT will be excluded (the outer
// query will fail for all those DTs as the WHERE EXISTS will return
// false for all the joined services).

///////////NOTE 2/////////////
// It is impt we use a DQL FETCH joins to eager fetch the entity graph in
// one hit by specifying all the entites we require hydrating in the dql
// SELECT clause. This saves costly additional hydration when iterating
// the entity graph to build the XML doc. Therefore, it is important
// to correspond the SELECT clause with the entities that are needed in
// the XML. Also note that fetch joins have implications on
// pagination as explained below.
// Note, if your query contains a fetch-joined collection, the setFirstResult()
// and setMaxResult() may not work as expected (i.e. producing seemingly few
// results than specified).  This is because setMaxResults() restricts
// the number of database result rows, however in the case of fetch-joined
// collections one root entity might appear in many rows, effectively
// hydrating less than the specified number of results.
//
// To solve this, we could use the DQL Paginator provided the maxResults
// is 1000 or less (and not use $downtimes = $q->getResult());
// $paginator = new Paginator($q, $fetchJoinCollection = true);
// $totalDowntimeCount = count($paginator); // Total count is stored in paginator (ie full total unlimited by maxResults):
        // see: http://docs.doctrine-project.org/en/latest/tutorials/pagination.html
        //
        // Impt: If maxResults is set to more than 1000, the paginator can fail because 1000
        // is greater than the  maximum number of expressions allowed in a WHERE IN list
// (the Paginator behind the scences perform a WHERE IN query to get all results
// for the current page as explained in the link above).
// On Oracle, this produces the following error:
// 'ORA-01795: maximum number of expressions in a list is 1000'
//
// If you need to set maxResults to >1000, then you can replace the
// Paginator line below with '$results = $q->getResult();' but you
// will need to explain the caveat that the page of results may be
// fewer than expected due to the reasons explained above.
