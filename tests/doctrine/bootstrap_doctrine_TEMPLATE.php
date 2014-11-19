<?php
// bootstrap_doctrine.php

// See :doc:`Configuration <../reference/configuration>` for up to date autoloading details.
use Doctrine\ORM\Tools\Setup;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Event\Listeners\OracleSessionInit;

require_once "Doctrine/ORM/Tools/Setup.php";
Setup::registerAutoloadPEAR();

// Create a simple "default" Doctrine ORM configuration for XML Mapping
$isDevMode = true;
//$config = Setup::createXMLMetadataConfiguration(array(__DIR__."/config/xml"), $isDevMode);
// or if you prefer yaml or annotations
$config = Setup::createAnnotationMetadataConfiguration(array(__DIR__."/../../lib/Doctrine/entities"), $isDevMode);
//$config = Setup::createYAMLMetadataConfiguration(array(__DIR__."/config/yaml"), $isDevMode);
//$config->setMetadataCacheImpl(new \Doctrine\Common\Cache\ApcCache());
//$config->setQueryCacheImpl(new \Doctrine\Common\Cache\ApcCache());
//$config->setProxyDir('/usr/share/GOCDB5/doctrineproxies');


    ///////////////////////SQLITE CONNECTION DETAILS/////////////////////////////////////////////
	// $conn = array(
	// 	'driver' => 'pdo_sqlite',
	// 	'path' => __DIR__ . '/db.sqlite',
	// );
    /////////////////////////////////////////////////////////////////////////////////////////////

	 
	///////////////////////ORACLE CONNECTION DETAILS////////////////////////////////////////////
	//	$conn = array(
	//		'driver' => 'oci8',
	//		'user' => 'DoctrineUnitTests',
	//		'password' => 'doc',
	//		'host' => 'localhost',
	//		'port' => 1521,
	//		'dbname' => 'XE'
	//	);
	//  // Need to explicitly set the Oracle session date format [1]
	//  $evm = new EventManager();
	//  $evm->addEventSubscriber(new OracleSessionInit(array('NLS_TIME_FORMAT' => 'HH24:MI:SS')));	
    /////////////////////////////////////////////////////////////////////////////////////////////

	
	///////////////////////MYSQL CONNECTION DETAILS////////////////////////////////////////////
	//$conn = array(
	//	'driver' => 'pdo_mysql',
	//	'user' => 'doctrine',
	//	'password' => 'doc',
	//	'host' => 'localhost',
	//	'dbname' => 'doctrine'
	//);
    /////////////////////////////////////////////////////////////////////////////////////////////
	



// obtaining the entity manager
$entityManager = \Doctrine\ORM\EntityManager::create($conn, $config, $evm);