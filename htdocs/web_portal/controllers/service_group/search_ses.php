<?php
use Doctrine\Common\Cache\ArrayCache;
/*______________________________________________________
 *======================================================
 * File: search_ses.php
 * Author: John Casson, James McCarthy
 * Description: Returns a JSON view of service
 *              affected by a downtime, also used to add
 *              SEs to a service group.
 *
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
 *
 /*====================================================== */

function search_ses() {
    if(!isset($_REQUEST['term'])) {
        return "";
    }else{
        $searchTerm = strip_tags(trim($_REQUEST['term']));
    }
    if(1 === preg_match("/[';\"]/", $searchTerm)){ throw new Exception("Invalid char in search term"); }

    if(substr($searchTerm, 0,1) != '%'){
        $searchTerm ='%'.$searchTerm;
    }

    if(substr($searchTerm,-1) != '%'){
        $searchTerm = $searchTerm.'%';
    }
    require_once __DIR__.'/../../../../lib/Gocdb_Services/Factory.php';
    try {
       $ses = \Factory::getServiceService()->getSes($searchTerm,null,null,null,null,null,null,null,null,null,null,null,true);



    } catch(Exception $ex){
        show_view(  'error.php', $ex->getMessage() . "<br /><br />Please contact the "
        . "<a href=\"index.php?Page_Type=Help_And_Contact">"
        . "GOCDB support team</a> if you need help with this issue.");
    }
    $params = array('ses' => $ses);

    show_view('service_group/se_search.php', $params, null, true);
}
