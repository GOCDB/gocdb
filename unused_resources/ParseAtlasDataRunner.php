<?php

/*
 * Copyright (C) 2015 STFC
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


// Simple script to extract 'name' value from 'rcsite' json array/structure 
// and print this to standard output. This output can be re-directed to a 
// text file and modified to produce a php file that contains an array of site 
// names for subsequent import into: 
// e.g. 'resources/ApplyScopeTagsToSitesAndServicesRunner.php'. 

// download json file for atlas from: http://atlas-agis-api.cern.ch/request/site/query/list/?json&vo_name=atlas
$jsonstring = file_get_contents("atlas-agis-api.cern.json");
//$json_a = json_decode($string, true);

$siteArray = array();

$jsonIterator = new RecursiveIteratorIterator(
    new RecursiveArrayIterator(json_decode($jsonstring, TRUE)),
    RecursiveIteratorIterator::SELF_FIRST);

foreach ($jsonIterator as $key => $val) {
    if(is_array($val)) {
        //echo "$key:\n";
	if($key == 'rcsite'){
	    //echo "$key\n"; 
	    if(isset($val['name'])){ 
	      $siteArray[] = $val['name']; 
	    }
	    
	    //echo $val."\n"; 
	}
    } else {
//        echo "$key => $val\n";
    }
}

// remove duplicates 
$siteArray = array_unique($siteArray); 
foreach($siteArray as $siteName){
    echo $siteName."\n"; 
}

