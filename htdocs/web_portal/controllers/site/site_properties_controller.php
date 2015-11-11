<?php
/*______________________________________________________
 *======================================================
 * File: site_properties_controller.php
 * Author: Tom Byrne, George Ryall, John Casson, David Meredith, James McCarthy
 * Description: Passes the site property action request onto the correct controller
 *
 * License information
 *
 * Copyright � 2015 STFC
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

function control() {

    if (!isset($_REQUEST['action'])) {
        throw new Exception("Choose an action from the dropdown to perform on the selected properties");
    }

    switch($_REQUEST['action']) {
        case "delete" :
            require_once __DIR__ . '/delete_site_properties.php';
            delete();
            break;
        case "something" :
            throw new Exception("ooooooh.");

    }

}