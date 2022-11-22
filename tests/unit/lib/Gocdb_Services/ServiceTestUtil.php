<?php

require_once __DIR__ . "/../../../doctrine/TestUtil.php";

use org\gocdb\services\APIAuthenticationService;

/**
 *  Helper functions for GOCDB_Services tests
 *
 * @author Ian Neilsony
 */
class ServiceTestUtil {
  /**
   * Persist and flush an entity
   * @param \Doctrine\ORM\EntityManager $em Entity manager
   * @param Object $instance
   */
  public static function persistAndFlush ($em, $instance) {
      $em->persist($instance);
      $em->flush();
  }
  /**
   * Create some test site data
   * @param EntityManager $em Entity Manager handle
   */
  public static function getSiteData ($em) {

    $infra = TestUtil::createSampleInfrastructure('Production');
    $em->persist($infra);

    $ngi = TestUtil::createSampleNGI('ngi1_');
    $em->persist($ngi);

    $scope = TestUtil::createSampleScope('scope 1', 'Scope1');
    $em->persist($scope);

    $certStatus = TestUtil::createSampleCertStatus('Certified');
    $em->persist($certStatus);

    $country = TestUtil::createSampleCountry('Utopia');
    $em->persist($country);

    $em->flush();

    $siteData = array (
      'NGI' => $ngi->getId(),
      'Site' => array(
        'SHORT_NAME' => 's1',
        'DESCRIPTION' => 'A test site',
        'OFFICIAL_NAME' => 'An-official-site',
        'EMAIL' => 'anon@localhost.net',
        'HOME_URL' => 'https://www.s1.localhost.net',
        'CONTACTTEL' => '001 234 567 8',
        'GIIS_URL' => null,
        'LATITUDE' => '0',
        'LONGITUDE' => '0',
        'CSIRTEMAIL' => null,
        'IP_RANGE' => null,
        'IP_V6_RANGE' => null,
        'DOMAIN' => 's1.localhost.net',
        'LOCATION' => null,
        'CSIRTTEL' => '001 234 567 8',
        'EMERGENCYTEL' => '001 234 567 8',
        'EMERGENCYEMAIL' => 'anon@localhost.net',
        'HELPDESKEMAIL' => 'anon@localhost.net',
        'TIMEZONE' => 'GMT'),
      'Scope_ids' => array($scope->getId()),
      'ReservedScope_ids' => array(),
      'ProductionStatus' => $infra->getId(),
      'Certification_Status' => $certStatus->getId(),
      'Country' => $country->getId()
    );

    return $siteData;
  }
  /**
   * Create and return a minimal Site Service instance
   * @param \Doctrine\ORM\EntityManager EntityManager $em
   * @param array $siteData Minimal initial site data array
   * @return \Site $site
   */
  public static function createAndAddSite ($em, $siteData) {

    $user = TestUtil::createSampleUser('Alpha','User');
    // We don't want to test all the roleAction logic here so simply make us an admin
    $user->setAdmin(true);

    $identifier = TestUtil::createSampleUserIdentifier('X.509', '/Alpha.User');
    ServiceTestUtil::persistAndFlush($em, $identifier);

    $user->addUserIdentifierDoJoin($identifier);

    ServiceTestUtil::persistAndFlush($em, $user);

    $siteService = ServiceTestUtil::getSiteService($em);

    $site = $siteService->addSite($siteData, $user);

    return $site;
  }
  /**
   * Generate minimal Site Service
   * @param \Doctrine\ORM\EntityManager EntityManager $em
   * @return org\gocdb\services\Site $siteService
   */
  public static function getSiteService ($em) {
    $siteService = new org\gocdb\services\Site();
    $siteService->setEntityManager($em);

    // Need stubs for both role and scope services
    $roleAAS = TestUtil::createSampleRoleAAS(__DIR__ .
              "/../../resources/roleActionMappingSamples/TestRoleActionMappings5.xml");

    $roleAAS->setEntityManager($em);
    $siteService->setRoleActionAuthorisationService($roleAAS);
    $siteService->setScopeService(ServiceTestUtil::getScopeService($em));

    return $siteService;
  }
    /**
   * Generate a useless minimal Scope Service
   * NB. Should be done in the siteService constructor (?)
   */
  private static function getScopeService($em) {
    $scopeService = new \org\gocdb\services\Scope();
    $scopeService->setEntityManager($em);

    return $scopeService;
  }
  public static function createGocdbEntities($entityManager)
  {
    /**
     * Set up the site, user and service objects shared by
     * some tests.
     *
     * @return array Created User, Site and SiteService instances
     */

    $siteData = ServiceTestUtil::getSiteData($entityManager);
    $siteService = ServiceTestUtil::getSiteService($entityManager);
    $site = ServiceTestUtil::createAndAddSite($entityManager, $siteData);

    $user = TestUtil::createSampleUser('Beta', 'User');
    $user->setAdmin(true);

    $identifier = TestUtil::createSampleUserIdentifier('X.509', '/Beta.User');
    ServiceTestUtil::persistAndFlush($entityManager, $identifier);

    $user->addUserIdentifierDoJoin($identifier);

    ServiceTestUtil::persistAndFlush($entityManager, $user);

    $authEntServ = new APIAuthenticationService();
    $authEntServ->setEntityManager($entityManager);

    return [$user, $site, $siteService, $authEntServ];
  }

}
