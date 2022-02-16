<?php
// bootstrap_doctrine.php

// See :doc:`Configuration <../reference/configuration>` for up to date autoloading details.
use Doctrine\ORM\Tools\Setup;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Event\Listeners\OracleSessionInit;

require_once  __DIR__."/../../vendor/autoload.php";

// Create a simple "default" Doctrine ORM configuration for XML Mapping
$isDevMode = true;
$config = Setup::createAnnotationMetadataConfiguration(array(__DIR__."/../../lib/Doctrine/entities"), $isDevMode);

$conn = array(
    'driver' => 'pdo_sqlite',
    'path' => '/tmp/gocdb.sqlite',
);

$evm = new EventManager();

$entityManager = \Doctrine\ORM\EntityManager::create($conn, $config, $evm);
