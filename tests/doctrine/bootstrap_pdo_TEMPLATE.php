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
     //
     // //If you are using the 'taq/pdooci' composer dependency lib (see INSTALL.md) 
     // // instead of compiling php on *nix with --with-pdo-oci (See https://github.com/taq/pdooci),
     // // then use the following line to create the $pdo object:
     // $pdo = new PDOOCI\PDO('localhost:1521/xe', '<USER>', '<PASSWORD>'); //note the qualified PDOOCI\PDO obj and there is no 'oci:dbname=//' in the connection string
     //
     // //OR 
     //
     // //If you are NOT using the 'taq/pdooci' lib
     // //i.e you are on Win or you have compiled php --with-pdo-oci (see http://php.net/manual/en/ref.pdo-oci.php),
     // then use the following line to create the $pdo object: 
     // $pdo = new PDO('oci:dbname=//localhost:1521/xe', '<USER>', '<PASSWORD>'); // note 'oci:dbname=//' in the connection string
     //
     // // now return the required object:
     // return new PHPUnit_Extensions_Database_DB_DefaultDatabaseConnection($pdo, 'USERS');  // $pdo object, schema
     /////////////////////////////////////////////////////////////////////////////////////////////

     ///////////////////////MYSQL CONNECTION DETAILS//////////////////////////////////////////////
     //  $pdo = new PDO('mysql:host=localhost;dbname=doctrine;charset=UTF8', 'doctrine', 'doc');
     //  return new PHPUnit_Extensions_Database_DB_DefaultDatabaseConnection($pdo);
     /////////////////////////////////////////////////////////////////////////////////////////////
}


?>
