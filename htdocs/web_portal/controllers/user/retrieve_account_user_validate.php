<?php
/*______________________________________________________
 *======================================================
 * File: delete_user.php
 * Author: George Ryall
 * Description: Final step for a user changing their dn. a link to the page this
 * controller is assoicated with is sent to the user to confirm they requested the change
 *
 *
 * License information
 *
 * Copyright 2009 STFC
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
 * Controller for user to confirm their DN change
 * @return null
 */
function validate_dn_change() {
    require_once __DIR__ . '/../../../../lib/Gocdb_Services/Factory.php';
    require_once __DIR__ . '/../../../../htdocs/web_portal/components/Get_User_Principle.php';
    require_once __DIR__ . '/utils.php';
    
    //Check the portal is not in read only mode, returns exception if it is
    checkPortalIsNotReadOnly();
    
    if(!isset($_REQUEST['c'])){
        show_view('error.php', "a confirmation code must be specified");
    }
    $confirmationCode = $_REQUEST['c'];

    $currentDn = Get_User_Principle();

    try {
        Factory::getRetrieveAccountService()->confirmAccountRetrieval($confirmationCode, $currentDn);
    } catch(\Exception $e) {
        show_view('error.php', $e->getMessage());
        die();
    }
    show_view('user/retrieved_account.php');
}