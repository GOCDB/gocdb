<?php
// cli-config.php
// see: http://doctrine-orm.readthedocs.io/en/latest/reference/configuration.html#setting-up-the-commandline-tool

// Doctrine 2.4 and above:
//use Doctrine\ORM\Tools\Console\ConsoleRunner;
//require_once dirname(__FILE__)."/bootstrap.php";
//return ConsoleRunner::createHelperSet($entityManager);


// Doctrine 2.3 and below: 
require_once dirname(__FILE__)."/bootstrap.php";

$helperSet = new \Symfony\Component\Console\Helper\HelperSet(array(
    'db' => new \Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper($entityManager->getConnection()),
    'em' => new \Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper($entityManager)
));
return $helperSet; 


