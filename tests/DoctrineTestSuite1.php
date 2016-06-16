<?php

require_once __DIR__ . '/doctrine/DoctrineCleanInsert1Test.php';
require_once __DIR__ . '/doctrine/NGIServiceTest.php';
require_once __DIR__ . '/doctrine/RoleCascadeDeletionsTest.php';
require_once __DIR__ . '/doctrine/RoleServiceTest.php';
require_once __DIR__ . '/doctrine/RolesTest.php';
//require_once __DIR__.'/doctrine/ServiceAuthorizeActionTest.php';
require_once __DIR__ . '/doctrine/ServiceDAOTest.php';
require_once __DIR__ . '/doctrine/ServiceMoveTest.php';
require_once __DIR__ . '/doctrine/Site_CertStatusLogCascadeDeletionsTest.php';
require_once __DIR__ . '/doctrine/SiteMoveTest.php';
require_once __DIR__ . '/doctrine/ExtensionsTest.php';
require_once __DIR__ . '/doctrine/Scoped_IPIQuery_Test1.php';
require_once __DIR__ . '/doctrine/DowntimeServiceEndpointTest1.php';
require_once __DIR__ . '/unit/lib/Gocdb_Services/RoleActionAuthorisationServiceTest.php';
require_once __DIR__ . '/unit/lib/Gocdb_Services/RoleActionMappingServiceTest.php';
require_once __DIR__ . '/unit/lib/Gocdb_Services/ScopeServiceTest.php';

/**
 * TestSuite designed to run the main doctrine tests
 * @author David Meredith <david.meredith@stfc.ac.uk>
 */
class DoctrineTestSuite1 {
    public static function suite() {


        echo "\n\n-------------------------------------------------\n";
        echo "Executing Test Suite 1\n";
        //chdir('doctrine');
        //echo "Drop and recreate the test DB tables before executing tests?\n "
        //. "(Required for the first-time execution of tests, but not required for subsequent test runs)...\n";
        //shell_exec('sh recreateTestDB.sh');

        $suite = new PHPUnit_Framework_TestSuite('Test Suite 1');

        $suite->addTestSuite('DoctrineCleanInsert1Test');
        $suite->addTestSuite('NGIServiceTest');
        $suite->addTestSuite('RoleCascadeDeletionsTest');
        $suite->addTestSuite('RoleServiceTest');
        $suite->addTestSuite('RolesTest');
        //$suite->addTestSuite('ServiceAuthorizeActionTest');
        $suite->addTestSuite('ServiceDAOTest');
        $suite->addTestSuite('ServiceMoveTest');
        $suite->addTestSuite('Site_CertStatusLogCascadeDeletionsTest');
        $suite->addTestSuite('SiteMoveTest');
        $suite->addTestSuite('ExtensionsTest');
        $suite->addTestSuite('Scoped_IPIQuery_Test1');
        $suite->addTestSuite('DowntimeServiceEndpointTest1');

        $suite->addTestSuite('RoleActionAuthorisationServiceTest');
        $suite->addTestSuite('RoleActionMappingServiceTest');
        $suite->addTestSuite('ScopeServiceTest');

        return $suite;
    }
}

?>
