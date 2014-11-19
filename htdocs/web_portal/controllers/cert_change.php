<?php
/*______________________________________________________
 *======================================================
 * File: cert_change.php
 * Author: John Casson, David Meredith
 * Description: Processes a cert change request
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
function Get_Validate_DN_Change_HTML(){
    require_once __DIR__.'/../../xml_input/insert_xml.php';
    require_once __DIR__.'/../../../lib/Gocdb_Services/Factory.php';
    global $silent;

    $error = "";
    $HTML = "";
    if (isset ($_REQUEST["c"])) {
        $code = $_REQUEST["c"];
    } else {
        return "<div class=\"rightPageContainer\"><h1>Error</h1><br />Missing validation code</div>";
    }

    // get the request corresponding to the given code
    $change_request = \Factory::getCertChangeService()->getChangeRequest($code);

    // verify the change request is valid
    if($change_request == null) {
        return "<div class=\"rightPageContainer\"><h1>Error</h1><br />There is no active request with supplied confirmation code</div>";
    }

    // get account corresponding to old DN and update the certificate on that account
    $account_to_update = \Factory::getCertChangeService()->getAccount($change_request["OLD_DN"]);
    $account_to_update["CERTIFICATE_DN"]=$change_request["NEW_DN"];
    $xml_to_insert = \Factory::getCertChangeService()->createAccountXml($account_to_update);
    $silent = 1;
    test_then_insert_xml($xml_to_insert);
    // delete change request
    \Factory::getCertChangeService()->deleteRequest($change_request["COBJECTID"], $change_request["CGRIDID"]);

    $HTML.="<div class=\"rightPageContainer\"><h1>Success</h1><br />Your certificate change is now complete. ";
    $HTML.="You can access your GOCDB account with your new certificate.</div>";

    return $HTML;
}

?>