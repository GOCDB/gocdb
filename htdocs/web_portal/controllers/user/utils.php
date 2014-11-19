<?php
/*______________________________________________________
 *======================================================
 * File: utils.php
 * Author: John Casson, George Ryall
 * Description: Utilities used in the web portal to manipulate
 *              user data.
 *
 * License information
 *
 * Copyright � 2009 STFC
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

/**
 *  Gets the relevant fields from a user's web request
 *  ($_REQUEST) and returns an associative array compatible with
 *  add_user().
 *  @global array $_REQUEST user data submitted by the end user
 *  @return array an array representation of the user */
function getUserDataFromWeb() {
    $fields = array('TITLE', 'FORENAME', 'SURNAME', 'EMAIL', 'TELEPHONE');
    foreach($fields as $field) {
        $user_data[$field] = $_REQUEST[$field];
    }

    if(!empty($_REQUEST['OBJECTID'])) {
        $user_data['ID'] = $_REQUEST['OBJECTID'];
    }
    return $user_data;
}


/**
 * Checks witht the config service if the portal is in read only mode and if 
 * it is throws an exception
 * @throws \Exception
 */
function checkPortalIsNotReadOnly(){
    require_once __DIR__ . '/../../../../lib/Gocdb_Services/Factory.php';
    if (\Factory::getConfigService()->IsPortalReadOnly()){
        throw new \Exception("The portal is currently in read only mode, changes can not be made.");
    }
}