<?php

/**
 * Provides a connection to the test database. Copy this file and rename it
 * to 'bootstrap_pdo.php' in the same directory.
 * The DBUnit tests then require these methods to get a connection to the
 * test db.
 *
 * @author David Meredith
 */

/**
 * Returns the database connection to your test databse.
 * Modify as required to return a connection to your test db.
 * @return PHPUnit_Extensions_Database_DB_IDatabaseConnection
 */
function getConnectionToTestDB() {

     /*** Uncomment and fill in the connection details for your chosen database ***/


     ///////////////////////SQLITE CONNECTION DETAILS/////////////////////////////////////////////
     // $sqliteFile = __DIR__ . '/../db.sqlite';
     // $pdo = new PDO("sqlite:" . $sqliteFile);
     // return new PHPUnit_Extensions_Database_DB_DefaultDatabaseConnection($pdo, 'sqlite');
     /////////////////////////////////////////////////////////////////////////////////////////////

     ///////////////////////ORACLE CONNECTION DETAILS/////////////////////////////////////////////
     // $pdo = new PDO('oci:dbname=//localhost:1521/xe', '<USER>', '<PASSWORD>');
     // return new PHPUnit_Extensions_Database_DB_DefaultDatabaseConnection($pdo, 'USERS');
     /////////////////////////////////////////////////////////////////////////////////////////////

     ///////////////////////MYSQL CONNECTION DETAILS//////////////////////////////////////////////
     //  $pdo = new PDO('mysql:host=localhost;dbname=doctrine;charset=UTF8', 'doctrine', 'doc');
     //  return new PHPUnit_Extensions_Database_DB_DefaultDatabaseConnection($pdo);
     /////////////////////////////////////////////////////////////////////////////////////////////
}


?>
