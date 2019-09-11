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
require_once __DIR__ . '/RoleActionAuthorisationService.php';

/**
 * GOCDB Stateless service facade (business routnes) for downtime objects.
 * The public API methods are transactional.
 *
 * @author John Casson
 * @author James McCarthy
 * @author David Meredith
 */
class Downtime extends AbstractEntityService{

    private $roleActionAuthorisationService;

    function __construct(/*$roleActionAuthorisationService*/) {
        parent::__construct();
        //$this->roleActionAuthorisationService = $roleActionAuthorisationService;
    }

    public function setRoleActionAuthorisationService(RoleActionAuthorisationService $roleActionAuthService){
        $this->roleActionAuthorisationService = $roleActionAuthService;
    }

    // Date format used by the Javascript calendar
    const FORMAT = 'd/m/Y H:i';

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
     * Get a Downtime object.
     *
     * @param int $downtimeId Downtimeobject id
     * @return \Downtime object or null if downtime is not found
     */
    public function getDowntime($id){
        return $this->em->find("Downtime", $id);
    }

    /**
     * Array
     * (
     *     [DOWNTIME] => Array
     *     (
     *         [SEVERITY] => OUTAGE
     *         [DESCRIPTION] => Test
     *         [START_TIMESTAMP] => 20/03/2013 00:00
     *         [END_TIMESTAMP] => 23/03/2013 00:00
     *     )
     *     [Impacted_Services] => Array
     *     (
     *         [0] => 824
     *         [1] => 825
     *         [2] => 2146
     *     )
     *     [Impacted_Endpoints] => Array
     *     (
     *         [0] => 54
     *         [1] => 15
     *         [2] => 26
     *      )
     * Adds a downtime
     * @param Array $values Downtime values, shown above
     * @param \User $user User making the request
     * @return \Downtime $dt The new downtime
     */
    public function addDowntime($values, \User $user = null) {
        //Check the portal is not in read only mode, throws exception if it is
        $this->checkPortalIsNotReadOnlyOrUserIsAdmin($user);
        require_once __DIR__.'/ServiceService.php';
        $serviceService = new \org\gocdb\services\ServiceService();
        $serviceService->setEntityManager($this->em);

        // Get the affected services
        $services = array();
        foreach($values['Impacted_Services'] as $service) {
            $services[] = $serviceService->getService($service);
        }

        // Get the affected endpoints
        $endpoints = array();
        foreach($values['Impacted_Endpoints'] as $endpoint) {
            $endpoints[] = $serviceService->getEndpoint($endpoint);
        }

        if(count($services)==0){
            throw new \Exception("A downtime must affect at least one service");
        }

        // Check that each endpoint belongs to one of the services.
        // It is an error if an endpoint does not belong to one of the services.
        foreach($endpoints as $checkEndpoint){
            $endpointBelongsToService = false;
            foreach($services as $checkService){
               if(in_array($checkEndpoint, $checkService->getEndpointLocations()->toArray()) ){
                  $endpointBelongsToService = true;
               }
            }
            if(!$endpointBelongsToService){
               throw new \Exception('Error, affected endpoint is not owned by an affected service');
            }
        }

        // get the affected sites
        $sites = array();
        foreach($services as $se){
            $site = $se->getParentSite();
            if(!in_array($site, $sites)){
               $sites[] = $site;
            }
        }

        if(count($sites) != 1){
            // if there are multiple affected services from multiple sites,
            // the gui must either enforce single site selection
            // or prevent selecting 'define DT in site timezone' if selecting
            // multiple sites (i.e. utc only when selecting services from multiple sites).
            throw new \Exception("Downtime creation for multiple sites not supported yet");
        }


        // Check the user has a role covering the passed SEs
        $this->authorisation($services, $user);
        $this->validate($values['DOWNTIME']);

        $startStr = $values['DOWNTIME']['START_TIMESTAMP'];
        $endStr = $values['DOWNTIME']['END_TIMESTAMP'];
        //echo($startStr. ' '.$endStr); // 14/05/2015 16:16      14/05/2015 16:17   d/m/Y H:i

        // did the user specify the downtime info in utc (default) or in site's local timezone?
        /*$requestUtcOrSiteTimezone = $values['DOWNTIME']['DEFINE_TZ_BY_UTC_OR_SITE'];
        $tzStr = 'UTC'; // the timezone label specified by the user (e.g. 'UTC' or 'Europe/London')
        if($requestUtcOrSiteTimezone == 'site'){
            // if there are multiple affected services from multiple sites,
            // we assume utc - the gui must therefore either enforce single site selection
            // or prevent selecting 'define DT in site timezone' if selecting
            // multiple sites (i.e. utc only when selecting services from multiple sites).

            // get the site timezone label
            if(count($sites) > 1){
                $tzStr = 'UTC'; // if many sites are affected (not implemented yet), assume UTC
            } else {
                $siteTzOrNull = $sites[0]->getTimezoneId();
                if( !empty($siteTzOrNull)){
                    $tzStr = $siteTzOrNull;
                } else {
                    $tzStr = 'UTC';
                }
            }
        }

        // convert start and end into UTC
        $UTC = new \DateTimeZone("UTC");
        if($tzStr != 'UTC'){
            // specify dateTime in source TZ
            $sourceTZ = new \DateTimeZone($tzStr);
            $start = \DateTime::createFromFormat($this::FORMAT, $startStr, $sourceTZ);
            $end = \DateTime::createFromFormat($this::FORMAT, $endStr, $sourceTZ);
            // reset the TZ to UTC
            $start->setTimezone($UTC);
            $end->setTimezone($UTC);
        } else {
            $start = \DateTime::createFromFormat($this::FORMAT, $startStr, $UTC);
            $end = \DateTime::createFromFormat($this::FORMAT, $endStr, $UTC);
        }*/

        $start = \DateTime::createFromFormat($this::FORMAT, $startStr, new \DateTimeZone("UTC"));
        $end = \DateTime::createFromFormat($this::FORMAT, $endStr, new \DateTimeZone("UTC"));

        $this->validateDates($start, $end);

        // calculate classification
        $nowPlus1Day = new \DateTime(null, new \DateTimeZone('UTC'));
        $oneDay = \DateInterval::createFromDateString('1 days');
        if($start > $nowPlus1Day->add($oneDay)) {
            $class = "SCHEDULED";
        } else {
            $class = "UNSCHEDULED";
        }

        $this->em->getConnection()->beginTransaction();

        try {
            $dt = new \Downtime();
            $dt->setClassification($class);
            $dt->setDescription($values['DOWNTIME']['DESCRIPTION']);
            $dt->setSeverity($values['DOWNTIME']['SEVERITY']);
            $dt->setStartDate($start);
            $dt->setEndDate($end);
            $dt->setInsertDate(new \DateTime(null, new \DateTimeZone('UTC')) );
            // Create a new pk and persist/flush to sync in-mem object state
            // with DB - is needed so that the call to v4DowntimePK->getId() actually
            // returns a value (we can still rollback no probs if an issue occurs
            // to remove the Downtime)
            $v4DowntimePk = new \PrimaryKey();
            $this->em->persist($v4DowntimePk);
            $this->em->flush();
            $dt->setPrimaryKey($v4DowntimePk->getId().'G0');

            //Create a link to the services
            foreach($services as $service) {
                $dt->addService($service);
            }

            //Create a link to the affected endpoints (if any endpoints were selected)
            foreach($endpoints as $endpoint) {
                $dt->addEndpointLocation($endpoint);
            }
            $this->em->persist($dt);
            $this->em->flush();
            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollback();
            $this->em->close();
            throw $e;
        }
        return $dt;
    }


    /**
     * Is a user authorized to add, edit or delete a downtime over the passed SEs?
     * @param Array $ses An array of Service objects
     * @param \User $user The user making the request
     */
    public function authorisation($ses, \User $user = null) {
        if(is_null($user)) {
            throw new \Exception("Unregistered users can't edit a downtime.");
        }
        //require_once __DIR__.'/ServiceService.php';
        //$serviceService = new \org\gocdb\services\ServiceService();
        //$serviceService->setEntityManager($this->em);
         if (!$user->isAdmin()) {
            foreach ($ses as $se) {
                //if(count($serviceService->authorize Action(\Action::EDIT_OBJECT, $se, $user))==0){
                if ($this->roleActionAuthorisationService->authoriseAction(\Action::EDIT_OBJECT, $se->getParentSite(), $user)->getGrantAction() == FALSE ) {
                    throw new \Exception("You do not have permission over $se.");
                }
            }
        }
    }

    /**
     * Validates the user inputted site data against the
     * checks in the gocdb_schema.xml.
     * @param array $dtData The new Downtime values
     * @throws \Exception Human readable message if the data can't be validated.
     * @return null
     */
    public function validate($dtData) {
        require_once __DIR__ . '/Validate.php';
        $serv = new \org\gocdb\services\Validate();
        foreach($dtData as $field => $value) {
            $valid = $serv->validate('downtime', $field, $value);
            if(!$valid) {
                $error = "$field contains an invalid value: $value";
                throw new \Exception($error);
            }
        }
    }

    /**
     * Validate the passed downtime data's dates
     * @param \DateTime $start Start time
     * @return \DateTime $end End date
     * @throws \Exception With a human readable message if the dates are invalid
     */
    private function validateDates(\DateTime $start, \DateTime $end) {
        $now = new \DateTime(null, new \DateTimeZone('UTC'));
        $start->setTimezone(new \DateTimeZone('UTC'));
        $end->setTimezone(new \DateTimeZone('UTC'));
        if ($start >= $end) {
            throw new \Exception ("A downtime cannot start after it's ended.");
        }

        $di = \DateInterval::createFromDateString('2 days');
        $twoDaysAgo = $now->sub($di);

        // check that start date is later than the threshold limit
        // Downtimes are only allowed to be declared up to 48 hours in the past
        if ($start < $twoDaysAgo) {
            throw new \Exception ("Error - The requested start time of the downtime must be within the last 48 hrs"); //"Downtimes can't be declared more than 48 hours in the past.");
        }
    }

    /**
     * This is the format of newValues that is passed to this edit downtime. JM - 20/06/2014
     * Array (
     *       [DOWNTIME] => Array (
     *          [SEVERITY] => WARNING
     *          [DESCRIPTION] => Edit Test
     *          [START_TIMESTAMP] => 20/06/2014 17:46
     *          [END_TIMESTAMP] => 22/06/2014 17:46 )
     *          [Impacted_Endpoints] => Array ( [0] => 5746 )
     *          [Impacted_Services] => Array ( [0] => 4588 [1] => 4455 [2] => 4495 )
     *          )
     * @param \Downtime $dt Existing downtime to edit
     * @param array New Values defined above
     * @param \User $user
     */
    public function editDowntime(\Downtime $dt, $newValues, \User $user = null) {

        //-----------------------------------------------------------------------------//
        //throw new \Exception('@DAVE - This is the point at which I left you. The function is receiving the correct values for the edit
        // contained in the newValues variable and the $dt variable is the existing downtime we want to edit. <br>
        //<br>The id of the downtime we are editting:'.$dt->getId().'<br>'.var_dump($newValues));
        //-----------------------------------------------------------------------------//


        //Check the portal is not in read only mode, throws exception if it is
        $this->checkPortalIsNotReadOnlyOrUserIsAdmin($user);

        require_once __DIR__.'/ServiceService.php';
        $serviceService = new \org\gocdb\services\ServiceService();
        $serviceService->setEntityManager($this->em);

        // Get the services that are **ALREADY LINKED** to this downtime
        //
        // Check that the user has adequate permissions over these services. This
        // is necessary if the user wants to either edit or *remove* the existing
        // service(s) associated with this downtime.
        $downtimesExistingSEs = array();
        foreach($dt->getEndpointLocations() as $els){
            $downtimesExistingSEs[] = $els->getService();
        }
        $this->authorisation($downtimesExistingSEs, $user);



        // Get the **NEWLY SELECTED** services and endpoints to be linked to this dt
        //
        // Check that at least one Service was actually selected to be associated
        // with the downtime - these can be the same as the exising services and/or
        // new services entirley.
        if(!isset($newValues['Impacted_Services'])){
           throw new \Exception('Error, this downtime affects no service -
               at least one service must be selected');
        }
        $newServices = array();
        foreach($newValues['Impacted_Services'] as $id) {
            $newServices[] = $serviceService->getService($id);
        }
        $newEndpoints = array();
        foreach($newValues['Impacted_Endpoints'] as $id) {
            $newEndpoints[] = $serviceService->getEndpoint($id);
        }
        if(count($newServices) == 0){
           throw new \Exception('Error, this downtime affects no service -
               at least one service must be selected');
        }
        // Check that the user has permissions over the list of (potentially different)
        // services affected by this downtime.
        $this->authorisation($newServices, $user);

        // Check that each newEndpoint belongs to one of the newServices.
        // It is an error if a newEndpoint does not belong to one
        // of the newServices.
        foreach($newEndpoints as $checkNewEndpoint){
            $newEndpointBelongsToNewService = false;
            foreach($newServices as $newService){
               if(in_array($checkNewEndpoint, $newService->getEndpointLocations()->toArray()) ){
                  $newEndpointBelongsToNewService = true;
               }
            }
            if(!$newEndpointBelongsToNewService){
               throw new \Exception('Error, affected endpoint is not owned by an affected service');
            }
        }

        // Validate the submitted downtime properties against regex in gocdb_schema
        $this->validate($newValues['DOWNTIME']);


        // Check only one site is affected (don't support multiple site selection yet)
        // get the affected sites
        $newSites = array();
        foreach($newServices as $se){
            $site = $se->getParentSite();
            if(!in_array($site, $newSites)){
               $newSites[] = $site;
            }
        }
        if(count($newSites) != 1){
            // if there are multiple affected services from multiple sites,
            // the gui must either enforce single site selection
            // or prevent selecting 'define DT in site timezone' if selecting
            // multiple sites (i.e. utc only when selecting services from multiple sites).
            throw new \Exception("Downtime editing for multiple sites not supported yet");
        }

        $newStartStr = $newValues['DOWNTIME']['START_TIMESTAMP'];
        $newEndStr = $newValues['DOWNTIME']['END_TIMESTAMP'];
        //echo($newStartStr. ' '.$newEndStr); // 14/05/2015 16:16      14/05/2015 16:17   d/m/Y H:i

        // did the user specify the downtime info in utc (default) or in site's local timezone?
        /*$requestUtcOrSiteTimezone = $newValues['DOWNTIME']['DEFINE_TZ_BY_UTC_OR_SITE'];
        $tzStr = 'UTC'; // the timezone label specified by the user (e.g. 'UTC' or 'Europe/London')
        if($requestUtcOrSiteTimezone == 'site'){
            // if there are multiple affected services from multiple sites,
            // we assume utc - the gui must therefore either enforce single site selection
            // or prevent selecting 'define DT in site timezone' if selecting
            // multiple sites (i.e. utc only when selecting services from multiple sites).

            // get the site timezone label
            if(count($newSites) > 1){
                $tzStr = 'UTC'; // if many sites are affected (not implemented yet), assume UTC
            } else {
                $siteTzOrNull = $newSites[0]->getTimezoneId();
                if( !empty($siteTzOrNull)){
                    $tzStr = $siteTzOrNull;
                } else {
                    $tzStr = 'UTC';
                }
            }
        }


        // convert start and end into UTC
        $UTC = new \DateTimeZone("UTC");
        if($tzStr != 'UTC'){
            // specify dateTime in source TZ
            $sourceTZ = new \DateTimeZone($tzStr);
            $newStart = \DateTime::createFromFormat($this::FORMAT, $newStartStr, $sourceTZ);
            $newEnd = \DateTime::createFromFormat($this::FORMAT, $newEndStr, $sourceTZ);
            // reset the TZ to UTC
            $newStart->setTimezone($UTC);
            $newEnd->setTimezone($UTC);
        } else {
            $newStart = \DateTime::createFromFormat($this::FORMAT, $newStartStr, $UTC);
            $newEnd = \DateTime::createFromFormat($this::FORMAT, $newEndStr, $UTC);
        }*/

        // Make sure all dates are treated as UTC!
        //date_default_timezone_set("UTC");

        $newStart = \DateTime::createFromFormat($this::FORMAT, $newStartStr, new \DateTimeZone("UTC"));
        $newEnd = \DateTime::createFromFormat($this::FORMAT, $newEndStr, new \DateTimeZone("UTC"));


        // check the new start/end times of the downtime are valid according
        // to GOCDB business rules
        $this->editValidation($dt, $newStart, $newEnd);
        $this->validateDates($newStart, $newEnd);

        // recalculate classification
        $nowPlus1Day = new \DateTime(null, new \DateTimeZone('UTC'));
        $oneDay = \DateInterval::createFromDateString('1 days');
        if($newStart > $nowPlus1Day->add($oneDay)) {
            $class = "SCHEDULED";
        } else {
            $class = "UNSCHEDULED";
        }


        $this->em->getConnection()->beginTransaction();
        try {
            $dt->setClassification($class);
            $dt->setDescription($newValues['DOWNTIME']['DESCRIPTION']);
            $dt->setSeverity($newValues['DOWNTIME']['SEVERITY']);
            $dt->setStartDate($newStart);
            $dt->setEndDate($newEnd);

            // First unlink all the previous els from the downtime
            foreach ($dt->getEndpointLocations() as $linkedEl) {
                $dt->getEndpointLocations()->removeElement($linkedEl);
                $linkedEl->getDowntimes()->removeElement($dt);
            }

            // Second unlink all the previous services from the downtime
            foreach ($dt->getServices() as $linkedServ) {
                //echo $linkedServ->getHostName();
                $dt->getServices()->removeElement($linkedServ);
                $linkedServ->getDowntimes()->removeElement($dt);
            }

            //Now relink all services and endpoints selected in the edit
            //Create a link to the service
            foreach($newServices as $service) {
                $dt->addService($service);
            }

            //Create a link to the effected endpoints
            foreach($newEndpoints as $endpoint) {
                $dt->addEndpointLocation($endpoint);
            }

            $this->em->merge($dt);
            $this->em->flush();
            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollback();
            $this->em->close();
            throw $e;
        }

        return $dt;
    }

    /**
     * Check with the business rules that the existing downtime's dates
     * allow editing of the downtime.
     * @link https://wiki.egi.eu/wiki/GOCDB/Input_System_User_Documentation#Downtime_shortening_and_extension downtime shortening and extension
     * @param \Downtime $dt
     * @throws \Exception if the downtime is not eligible for editing.
     */
    public function editValidationDatePreConditions(\Downtime $dt){
        // Make sure all dates are treated as UTC!
        //date_default_timezone_set("UTC");
        $nowUtc = new \DateTime(null, new \DateTimeZone('UTC'));
        /* @var $oldStart \DateTime */
        $oldStart = $dt->getStartDate()->setTimezone(new \DateTimeZone('UTC'));
        /* @var $oldEnd \DateTime */
        $oldEnd = $dt->getEndDate()->setTimezone(new \DateTimeZone('UTC'));

        // Can't change a downtime if it's already ended
        if($oldEnd < $nowUtc) {
            throw new \Exception("Can't edit a downtime that has already finished.");
        }

        $oneDay = \DateInterval::createFromDateString('1 days');
        $tomorrowUtc = $nowUtc->add($oneDay);

        if($dt->getClassification() == "SCHEDULED") {
            // Can't edit dt if it start within 24 hours
            if($oldStart < $tomorrowUtc) {
                throw new \Exception("Can't edit a SCHEDULED downtime starting within 24 hours.");
            }
        }
    }

    /**
     * Checks the proposed edit against the business rules at
     * @link https://wiki.egi.eu/wiki/GOCDB/Input_System_User_Documentation#Downtime_shortening_and_extension downtime shortening and extension
     * @param \Downtime $dt Downtime before modification
     * @param \DateTime $newStart New start date
     * @param \DateTime $newEnd New end date
     * @throws \Exception if the downtime is not eligible for editing.
     */
    private function editValidation(\Downtime $dt, \DateTime $newStart, \DateTime $newEnd) {
        $this->editValidationDatePreConditions($dt);

        $oldStart = $dt->getStartDate()->setTimezone(new \DateTimeZone('UTC'));
        $oldEnd = $dt->getEndDate()->setTimezone(new \DateTimeZone('UTC'));
        $now = new \DateTime(null, new \DateTimeZone('UTC'));

        // Duration can't increase
        // Duration is measured in seconds
        $oldDuration = abs($oldStart->getTimestamp() - $oldEnd->getTimestamp());
        $newDuration = abs($newStart->getTimestamp() - $newEnd->getTimestamp());
        if($newDuration > $oldDuration) {
            throw new \Exception("Downtimes can't be extended - please add a new downtime.");
        }

        // If a new start date has been requested, it Can't start in the past
        if($newStart < $now && $newStart != $dt->getStartDate()) {
            throw new \Exception("Downtime start date can only be changed to a date in the future"); //Downtime can't start in the past.");
        }

        $oneDay = \DateInterval::createFromDateString('1 days');
        $tomorrow = $now->add($oneDay);

        // Rules specific to scheduled downtimes
        if($dt->getClassification() == "SCHEDULED") {
            // A scheduled downtime can't start less than 24 hours from now.
            if($newStart < $tomorrow) {
                throw new \Exception("A SCHEDULED downtime can't start less than 24 hours from now.");
            }
        }
    }


    /**
     * Ends a downtime
     * (Within the next 60 secs to avoid "Downtime can't be ended in the past" errors).
     * @param \Downtime $dt
     * @param \User $user User making the request
     * @throws \Exception
     */
    public function endDowntime(\Downtime $dt, \User $user = null) {
        //Check the portal is not in read only mode, throws exception if it is
        $this->checkPortalIsNotReadOnlyOrUserIsAdmin($user);

        $ses = $dt->getServices();

        $this->authorisation($ses, $user);

        // Make sure all dates are treated as UTC!
        //date_default_timezone_set("UTC");

        if(!$dt->isOnGoing()) {
            throw new \Exception("Downtime isn't on-going.");
        }

        $sixtySecs = \DateInterval::createFromDateString('1 minutes');
        $now = new \DateTime(null, new \DateTimeZone('UTC'));
        $sixtySecsFromNow = $now->add($sixtySecs);
        //$this->validateDates($dt->getStartDate(), $end);

        // dt start date is in the future
        if ($dt->getStartDate()->setTimezone(new \DateTimeZone('UTC')) >= $sixtySecsFromNow) {
            throw new \Exception ("Logic error - Downtime start time is after the requested end time");
        }
        // dt has already ended
        if($dt->getEndDate()->setTimezone(new \DateTimeZone('UTC')) < $now){
            throw new \Exception ("Logic error - Downtime has already ended or will within the next 60 secs");
        }
        // ok. dt endDate is in the future and dt is ongoing/has started.

        $this->em->getConnection()->beginTransaction();
        try {
            $dt->setEndDate($sixtySecsFromNow);
            $this->em->merge($dt);
            $this->em->getConnection()->commit();
            $this->em->flush();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollback();
            $this->em->close();
            throw $e;
        }
    }

    /**
     * Deletes a downtime
     * @param \Downtime $dt
     * @param \User $user
     */
    public function deleteDowntime(\Downtime $dt, \User $user = null) {
        //Check the portal is not in read only mode, throws exception if it is
        $this->checkPortalIsNotReadOnlyOrUserIsAdmin($user);


        $ses = $dt->getServices();
        $this->authorisation($ses, $user);

        // Make sure all dates are treated as UTC!
        //date_default_timezone_set("UTC");

        if($dt->hasStarted()) {
            throw new \Exception("This downtime has already started.");
        }

        $this->em->getConnection()->beginTransaction();
        try {

            //For MEPS we need to delete from both service and enpoints
            foreach($dt->getServices() as $se){
                // Downtime is the owning side so remove elements from downtime.
                $dt->removeService($se);  // unlinks on both sides
                //$dt->getServices()->removeElement($se); //unlinking
                //$se->getDowntimes()->removeElement($dt);

            }

            foreach($dt->getEndpointLocations() as $ep){
              // Downtime is the owning side so remove elements from downtime.
              $dt->getEndpointLocations()->removeElement($ep); //unlinking
              // Since Doctrine always only looks at the owning side of a
              // bidirectional association for updates, it is not necessary for
              // write operations that an inverse collection of a bidirectional
              // one-to-many or many-to-many association is updated. This
              // knowledge can often be used to improve performance by avoiding
              // the loading of the inverse collection.
              //$ep->getDowntimes()->removeElement($dt);
            }

            $this->em->remove($dt);
            $this->em->flush();
            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollback();
            $this->em->close();
            throw $e;
        }
    }


    /**
     * Return all {@see \Site}s that satisfy the specfied filter parameters.
     * <p>
     * $filterParams defines an associative array of optional parameters for
     * filtering the sites. The supported Key => Value pairs include:
     * <ul>
     *   <li>'sitename' => String site name</li>
     *   <li>'roc' => String name of parent NGI/ROC</li>
     *   <li>'country' => String country name</li>
     *   <li>'certification_status' => String certification status value e.g. 'Certified'</li>
     *   <li>'exclude_certification_status' => String exclude sites with this certification status</li>
     *   <li>'production_status' => String site production status value</li>
     *   <li>'scope' => 'String,comma,sep,list,of,scopes,e.g.,egi,wlcg'</li>
     *   <li>'scope_match' => String 'any' or 'all' </li>
     *   <li>'extensions' => String extensions expression to filter custom key=value pairs</li>
     * <ul>
     *
     * @param array $filterParams
     * @return array Site array
     */
    public function getDowntimesFilterByParams($filterParams){
        require_once __DIR__.'/PI/GetDowntime.php';
        $getDowntime = new GetDowntime($this->em);
        //$params = array('service_type_list' => 'APEL', 'scope' => 'tier1');
        //$params = array('scope' => 'Local,EGI', 'scope_match' => 'any', 'site' => 'test,GRIDOPS-GOCDB', 'startdate' => '2015-01-21');
//        $params[] = array('scope_match' => 'all');
//        $params[] = array('topentity' => 'GRIDOPS-GOCDB');
//        $params = array('scope' => 'EGI,DAVE', 'sitename' => 'GRIDOPS-GOCDB');
        //$params = array('scope' => 'EGI,Local', 'scope_match' => 'any', 'exclude_certification_status' => 'Closed');
        //$params = array('scope' => 'EGI,Local', 'scope_match' => 'all');
        //$params = array('scope' => 'EGI,DAVE', 'scope_match' => 'all');
        //$params = array('extensions' => '(aaa=123)(dave=\(someVal with parethesis\))(test=test)');
        $getDowntime->validateParameters($filterParams);
        //$getDowntime->validateParameters($params);
        $getDowntime->createQuery();
        $downtimes = $getDowntime->executeQuery();
        return $downtimes;
    }
    /**
     */
    public function getActiveDowntimes() {
        $dql = "SELECT DISTINCT d, se, s, st
                FROM Downtime d
                JOIN d.services se
                JOIN se.parentSite s
                JOIN se.serviceType st
                JOIN se.scopes sc
                WHERE (
                        :onGoingOnly = 'no'
                        OR
                        (:onGoingOnly = 'yes'
                        AND d.startDate < :now
                        AND d.endDate > :now)
                    )";

        $filterByScope = \Factory::getConfigService()->getFilterDowntimesByScope();

        if ($filterByScope) {
            $defaultScope = \Factory::getConfigService()->getDefaultScopeName();
            $dql .= " AND (sc.name = :defaultScope)";
        }
        
        $dql .= " ORDER BY d.startDate DESC";

        $q = $this->em->createQuery( $dql )
                ->setParameter( 'onGoingOnly', 'yes' )
                ->setParameter( 'now', new \DateTime () );

        if ($filterByScope) {
            $q->setParameter('defaultScope', $defaultScope);
        }

        return $downtimes = $q->getResult ();
    }

    /**
     * Get downtimes where the downtime endDate is after windowStart, and
     * downtime start date is before windowEnd.
     *
     * @param \Date $windowStart
     * @param \Date $windowEnd
     */
    public function getImminentDowntimes($windowStart, $windowEnd) {
        $dql = "SELECT DISTINCT d, se, s, st
                FROM Downtime d
                JOIN d.services se
                JOIN se.parentSite s
                JOIN se.serviceType st
                JOIN se.scopes sc
                WHERE (
                    :windowStart IS null
                    OR d.endDate > :windowStart
                )
                AND (
                    :windowEnd IS null
                    OR d.startDate < :windowEnd
                )";

        $filterByScope = \Factory::getConfigService()->getFilterDowntimesByScope();

        if ($filterByScope) {
            $defaultScope = \Factory::getConfigService()->getDefaultScopeName();
            $dql .= " AND (sc.name = :defaultScope)";
        }
        
        $dql .= " ORDER BY d.startDate DESC";

        $q = $this->em->createQuery( $dql )
                ->setParameter( 'windowStart', $windowStart )
                ->setParameter( 'windowEnd', $windowEnd );

        if ($filterByScope) {
            $q->setParameter('defaultScope', $defaultScope);
        }

        return $downtimes = $q->getResult ();
    }

}
