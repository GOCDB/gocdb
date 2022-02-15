<?php

/*
 * Copyright (C) 2012 STFC
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

/*
 * Script to query and update each user's EGI SSO user name value in the
 * DB (User->username1). Note, the EGI SSO username is NOT used as a the
 * user's unique principle string in the DB and so does not need to be unique.
 * The transaction is committed only after updating
 * all the username values in the DB (either all usernames are updated or none).
 * <p>
 * This script is usually ran from an external cron.hourly.
 *
 * @author David Meredith
 */
require_once dirname(__FILE__) . "/../lib/Doctrine/bootstrap.php";
require dirname(__FILE__) . '/../lib/Doctrine/bootstrap_doctrine.php';
require_once dirname(__FILE__) . '/../lib/Gocdb_Services/Factory.php';

echo "Querying for EGI sso username\n";

$em = $entityManager;
$dql = "SELECT u FROM User u";
$users = $entityManager->createQuery($dql)->getResult();

$serv = \Factory::getUserService();

echo "Starting update of EGI SSO usernames at: ".date('D, d M Y H:i:s')."\n";
$count = 0;
foreach ($users as $user) {
    ++$count;
    $dn = $serv->getIdStringByAuthType($user, 'X.509');
    $cleanDN = cleanDN($dn);
    if (!empty($cleanDN)) {
        $url = "https://sso.egi.eu/admin/api/user?dn=" . $cleanDN;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); //return result instead of outputting it
        curl_setopt($ch, CURLOPT_TIMEOUT, 3); //3secs
        $ssousername = trim(curl_exec($ch));
        //if(curl_errno($ch)){ // error occured. //}
        //$info = curl_getinfo($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        // username only returned on 200 OK response, e.g. user not found gives 404
        //if($info['http_code'] != '200'){
        if ($httpcode != 200) {
            $ssousername = null;
        }
        //echo $count.",";
        if($ssousername != null){
          $user->setUsername1($ssousername);
          $em->persist($user);
          //$em->flush();
        }
    }
}
$em->flush();
echo "Completed ok: ".date('D, d M Y H:i:s');

function cleanDN($dn) {
    return trim(str_replace(' ', '%20', $dn));
}


