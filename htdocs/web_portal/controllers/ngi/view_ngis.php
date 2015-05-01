<?php
/*______________________________________________________
 *======================================================
 * File: view_ngis.php
 * Author: John Casson, David Meredith (modifications)
 * Description: Controller for showing all NGIs in GOCDB
 *
 * License information
 *
 * Copyright � 2011 STFC
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

function view_ngis() {
    require_once __DIR__.'/../../../../lib/Gocdb_Services/Factory.php';
    
    $scope = '%%';
    if(!empty($_GET['scope'])) { 
       $scope = $_GET['scope'];
    }
    
    $scopes = \Factory::getScopeService()->getScopes();
    
    $ngis = \Factory::getNgiService()->getNGIs($scope);
    
    
    $params['ngis'] = $ngis;
    $params['scopes']=$scopes;
    $params['selectedScope']=$scope;
    show_view('ngi/view_ngis.php', $params, "NGIs");
}

?>