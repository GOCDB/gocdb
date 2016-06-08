<?php
// see: http://doctrine-orm.readthedocs.io/en/latest/reference/configuration.html#setting-up-the-commandline-tool

// Doctrine 2.4 and above:
use Doctrine\ORM\Tools\Console\ConsoleRunner;
require_once "bootstrap.php";
return ConsoleRunner::createHelperSet($entityManager);


// Doctrine 2.3 and below:
/*
// cli-config.php
require_once "bootstrap.php";

// DM: Came across the following issue:
// See: http://stackoverflow.com/questions/25131662/doctrine-orm-cli-tool-not-working
// 
// This occurred When we had the following $helperSet var assignment as commented out below 
// (note we only set the 'em' var, not the 'db' var and we didn't return the $helperSet var
// which is now required). 
// The issue occurred due to a change somewhere between Doctrine 2.3.* and 2.4.*
// 
//$helperSet = new \Symfony\Component\Console\Helper\HelperSet(array(
//    'em' => new \Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper($entityManager)
//));

$helperSet = new \Symfony\Component\Console\Helper\HelperSet(array(
    'db' => new \Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper($entityManager->getConnection()),
    'em' => new \Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper($entityManager)
));
return $helperSet; 
*/

