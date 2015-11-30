<?php
/*______________________________________________________
 *======================================================
 * File: add_service_properties.php
 * Author: John Casson, George Ryall, David Meredith, James McCarthy
 * Description: Processes a new property request. If the user
 *              hasn't POSTed any data we draw the add property
 *              form. If they post data we assume they've posted it from
 *              the form and add it.
 *
 * License information
 *
 * Copyright 2013 STFC
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 /*======================================================*/
require_once __DIR__.'/../../../../lib/Gocdb_Services/Factory.php';
require_once __DIR__.'/../utils.php';
require_once __DIR__.'/../../../web_portal/components/Get_User_Principle.php';
    
/**
 * Controller for a new_property request
 * @global array $_POST only set if the browser has POSTed data
 * @return null
 */
function add_service_properties() {
    $dn = Get_User_Principle();
    $user = \Factory::getUserService()->getUserByPrinciple($dn);

    //Check the portal is not in read only mode, returns exception if it is and user is not an admin
    checkPortalIsNotReadOnlyOrUserIsAdmin($user);



    
    if($_POST) {     	// If we receive a POST request it's for new properties

        //Get the parent service we want to add properties to.
        //I'm trying to use "parent" rather than "service" wherever possible to make this code more generic.
        $service = \Factory::getServiceService()->getService($_REQUEST['PARENT']);

        //this is a little awkward, as we have to handle 3 cases here. Submitting a single property,
        //submitting a .property text file/block, or submitting the parsed and confirmed properties.

        //Figure out where the request has come from and format the inputs accordingly.

        //if the request is for a multi property input, parse the file and generate the array of properties
        //this will go to confirm()
        if(isset($_REQUEST['PROPERTIES'])) {
            $propertyArray = parse_properties($_REQUEST['PROPERTIES']);
        }
        //if the request is from the multi property confirmation page
        //reassign the selected properties to the property array.
        //this will go to submit()
        elseif (isset($_REQUEST['selectedProps'])){
            $propertyArray = $_REQUEST['selectedProps'];
        }
        //if the request is for a single property, skip the confirmation view and submit the request directly
        //this will go to submit()
        elseif (isset($_REQUEST['KEYPAIRNAME']) && isset($_REQUEST['KEYPAIRVALUE'])) {
            $propertyArray = array(
                $_REQUEST['KEYPAIRNAME'] => $_REQUEST['KEYPAIRVALUE'],
            );

            //will go straight to submit()
            $_REQUEST['UserConfirmed'] = "true";

        } else {
            //you really shouldn't end up here unless you are mangling your post requests
            throw new Exception("Properties could not be parsed");
        }

        //quick sanity check, are we actually adding any properties?
        if(empty($propertyArray)){
            show_view('error.php', "At least one property name and value must be provided.");
            die();
        }


        //Now we have our $propertyArray, either send it to the confirmation page or actually submit the props
        if(isset($_REQUEST['UserConfirmed'])) {
            submit($service, $user, $propertyArray);
        }
        else {
            confirm($propertyArray, $service, $user);
        }
    } else { 			// If there is no post data, draw the new properties form
        draw($user);
    }
}

/**
 * Submits the property array to the services layer's property functions.
 * @param array $propArr
 * @param Service $service
 * @param User|null $user
 * @throws Exception
 */
function submit( \Service $service, \User $user = null, array $propArr) {
//    $newValues = getSerPropDataFromWeb();
//    $serviceID = $newValues['SERVICEPROPERTIES']['SERVICE'];
//    if($newValues['SERVICEPROPERTIES']['NAME'] == null || $newValues['SERVICEPROPERTIES']['VALUE'] == null){
//        show_view('error.php', "A property name and value must be provided.");
//        die();
//    }
    $serv = \Factory::getServiceService();
    $sp = $serv->addProperties($service, $user, $propArr);
    //show_view("service/add_service_property.php", $serviceID);
}

/**
 * Draws the confirmation page.
 * @param array $propArr
 * @param Service $service
 * @param User|null $user
 */
function confirm(array $propArr, \Service $service, \User $user = null){

    $params['proparr'] = $propArr;
    $params['service'] = $service;
    show_view("service/add_service_properties_confirmation.php", $params);
}


/**
 *  Draws a form to add a new service property
 * @param \User $user current user 
 * @return null
 */
function draw(\User $user = null) {

	if(is_null($user)) {
        throw new Exception("Unregistered users can't add a service property.");
    }
    if (!isset($_REQUEST['parentid']) || !is_numeric($_REQUEST['parentid']) ){
        throw new Exception("An id must be specified");
    }
    $serv = \Factory::getServiceService();
    $service = $serv->getService($_REQUEST['parentid']); //get service by id
    //Check user has permissions to add service property
    $serv->validateAddEditDeleteActions($user, $service);
        
    $params['parentid'] = $_REQUEST['parentid'];
	show_view("service/add_service_properties.php", $params);

}

?>