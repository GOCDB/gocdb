<?php

require_once __DIR__ . "/../bootstrap.php";
require_once __DIR__ . "/AddUtils.php";

/**
 * AddNGIs.php: Loads a list of NGIs from an XML file and inserts them into
 * the doctrine prototype.
 * XML format is the output from get_roc_list PI query.
 */

$ngisFileName = __DIR__ . "/" . $GLOBALS['dataDir'] . "/NGIs.xml";
$ngis = simplexml_load_file($ngisFileName);

// Find the EGI project object
$egiProject = $entityManager->getRepository('Project')->findOneBy(array("name" => "EGI"));

//Find the EGI scope tag
$egiScope = $entityManager->getRepository('Scope')->findOneBy(array("name" => "EGI"));

//Add Local Scope so specified NGI is not part of EGI project
$localScope = $entityManager->getRepository('Scope')->findOneBy(array("name" => "Local"));

foreach($ngis as $xmlNgi) {
    $doctrineNgi = new NGI();
    $name = "";
    $email = "";
    $rodEmail = "";
    $helpdeskEmail = "";
    $securityEmail = "";
    $objectID = "";
    $creationDate = new \DateTime("now");

    foreach ($xmlNgi as $key => $value) {
        if ((string) $key == "NAME") {
            $name = (string) $value;
        }

        if ((string) $key == "EMAIL") {
            $email = (string) $value;
        }

        if ((string) $key == "DESCRIPTION") {
            $description = (string) $value;
        }

        if ((string) $key == "ROD_EMAIL") {
            $rodEmail = (string) $value;
        }

        if ((string) $key == "HELPDESK_EMAIL") {
            $helpdeskEmail = (string) $value;
        }

        if ((string) $key == "SECURITY_EMAIL") {
            $securityEmail = (string) $value;
        }

        if ((string) $key == "OBJECT_ID") {
            $objectID = (string) $value;
        }

        if ((string) $key == "CDATEON") {
            // $cdateonString has the following format: '12-JAN-10 14.12.56.000000'
            $cdateonString = (string) $value;

            //convert to date time
            $creationDate = DateTime::createFromFormat('d-M-y G.i.s.u', $cdateonString, new DateTimeZone('UTC'));

            if ($creationDate == false) {
            throw new LogicException("Datetime in unexpected format. datetime: '" . $cdateonString . "'");
            }
        }

    }
    $doctrineNgi->setCreationDate($creationDate);
    $doctrineNgi->setDescription($description);
    $doctrineNgi->setName($name);
    $doctrineNgi->setEmail($email);
    $doctrineNgi->setRodEmail($rodEmail);
    $doctrineNgi->setHelpdeskEmail($helpdeskEmail);
    $doctrineNgi->setSecurityEmail($securityEmail);

    // TODO
    //if($cdateon == null) throw new Exception("CDATEON is null");
    //$doctrineNgi->setCreationDate($cdateon);

    // if the NGI has id 67518 (NGI_HU) do not add it to EGI Project
    if ($objectID == "67518") {
        $doctrineNgi->addScope($localScope);
        $entityManager->persist($doctrineNgi);
    } else {
        // add NGI to EGI project and give it EGI scope
        $egiProject->addNgi($doctrineNgi);
        $doctrineNgi->addScope($egiScope);
        $entityManager->persist($doctrineNgi);
    }

}

// don't need to merge egiProject
//$entityManager->merge($egiProject);
$entityManager->flush();
