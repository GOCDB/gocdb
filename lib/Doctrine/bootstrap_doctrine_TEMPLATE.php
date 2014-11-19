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
$config = Setup::createAnnotationMetadataConfiguration(array(__DIR__."/entities"), $isDevMode);
//$config = Setup::createYAMLMetadataConfiguration(array(__DIR__."/config/yaml"), $isDevMode);
//
// If you intend to use APC cache (recommended in production), then you need to 
// specify the following two lines and install APC cache (see GOCDB wiki): 
//$config->setMetadataCacheImpl(new \Doctrine\Common\Cache\ApcCache());
//$config->setQueryCacheImpl(new \Doctrine\Common\Cache\ApcCache());
//
// By default, Doctrine will automatically compile Doctrine proxy objects 
// into the system's tmp dir. This is not recommended for production. 
// For production, you can specify where these proxy objects should be stored 
// using "$config->setProxyDir('pathToYourProxyDir');" 
// If you specify the ProxyDir, then you also need to manually compile your proxy objects 
// into the specified ProxyDir using the following command:  
// 'doctrine orm:generate-proxies compiledEntities'  
//$config->setProxyDir(__DIR__.'/compiledEntities');
// 


    ///////////////////////SQLITE CONNECTION DETAILS/////////////////////////////////////////////
	// $conn = array(
	// 	'driver' => 'pdo_sqlite',
	// 	'path' => __DIR__ . '/db.sqlite',
	// );
    /////////////////////////////////////////////////////////////////////////////////////////////

	 
	///////////////////////ORACLE CONNECTION DETAILS////////////////////////////////////////////
	//	$conn = array(
	//		'driver' => 'oci8',
	//		'user' => 'Doctrine',
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