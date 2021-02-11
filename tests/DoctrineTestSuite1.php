<?php
// Most of our environments (including Travis) default to (or are configured to) a memory limit of 128M.
// The test suite goes over this limit.
// So we increase the limit when running the test suite to 512M.
ini_set('memory_limit', '512M');
require_once __DIR__ . '/doctrine/DoctrineCleanInsert1Test.php';
require_once __DIR__ . '/doctrine/NGIServiceTest.php';
require_once __DIR__ . '/doctrine/RoleCascadeDeletionsTest.php';
require_once __DIR__ . '/doctrine/RoleServiceTest.php';
require_once __DIR__ . '/doctrine/RolesTest.php';
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
require_once __DIR__ . '/unit/lib/Gocdb_Services/UserServiceTest.php';
require_once __DIR__ . '/unit/lib/Gocdb_Services/SiteServiceTest.php';
require_once __DIR__ . '/unit/lib/Gocdb_Services/RoleServiceTest2.php';
require_once __DIR__ . '/unit/lib/Gocdb_Services/APIAuthenticationServiceTest.php';
require_once __DIR__ . '/writeAPI/siteMethods.php';
require_once __DIR__ . '/writeAPI/serviceMethods.php';
require_once __DIR__ . '/writeAPI/endpointMethods.php';

require_once __DIR__ . '/unit/lib/Gocdb_Services/ServiceServiceTest.php';

/**
 * TestSuite designed to run the main doctrine tests
 * @author David Meredith <david.meredith@stfc.ac.uk>
 */
class DoctrineTestSuite1 {
  public static function suite() {
    echo "\n\n-------------------------------------------------\n";
    echo "Executing Test Suite 1\n";

    $suite = new PHPUnit_Framework_TestSuite('Test Suite 1');

    $suite->addTestSuite('DoctrineCleanInsert1Test');
    $suite->addTestSuite('NGIServiceTest');
    $suite->addTestSuite('RoleCascadeDeletionsTest');
    $suite->addTestSuite('RoleServiceTest');
    $suite->addTestSuite('RolesTest');
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
    $suite->addTestSuite('UserServiceTest');
    $suite->addTestSuite('SiteServiceTest');
    $suite->addTestSuite('RoleServiceTest2');
    $suite->addTestSuite('APIAuthenticationServiceTest');
    $suite->addTestSuite('WriteAPIsiteMethodsTests');
    $suite->addTestSuite('WriteAPIserviceMethodsTests');
    $suite->addTestSuite('WriteAPIendpointMethodsTests');

    $suite->addTestSuite('ServiceServiceTest');

    return $suite;
  }
}

?>
