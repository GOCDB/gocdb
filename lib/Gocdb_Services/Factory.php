<?php

/* Copyright © 2011 STFC
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

require_once __DIR__ . '/RoleConstants.php';
/**
 * A factory for returning GOCDB business services.
 * Most services are managed as static singleton instances, but you can choose
 * to instantiate a service class instance directly if necessary.
 *
 * Without a dependency injection framework, this class implements an object
 * creational pattern
 * {@link https://en.wikipedia.org/wiki/Creational_pattern Object creation}
 *
 * @author David Meredith
 */
class Factory {

    private static $siteService = null;
    private static $scopeService = null;
    private static $roleService = null;
    private static $roleActionAuthorisationService = null;
    private static $roleActionMappingService = null;
    private static $userService = null;
    private static $searchService = null;
    private static $downtimeService = null;
    private static $ngiService = null;
    private static $em = null;
    private static $seService = null;
    private static $authContextService = null;
    private static $serviceGroupService = null;
    private static $configService = null;
    private static $validateService = null;
    private static $certStatusService = null;
    private static $serviceTypeService = null;
    private static $projectService = null;
    private static $OwnedEntityService = null;
    private static $exService = null;
    private static $notificationService = null;
    private static $emailService = null;
    private static $linkIdentityService = null;

    public static $properties = array();
    //private static $properties = null;

    /**
     * Force non-instantiablity with private constructor
     */
    private function __construct() {
    }

    /**
     * Get a reference to the static/shared properties.
     * Note, if you want to update these properties, you will need to assign a
     * variable to that reference using 'assign by reference' e.g. in callling code:
     * @return array
     */
//    public static function &getPropertes(){
//        if(self::$properties == null){
//            self::$properties = array();
//            // configure with default properties first
//            self::$properties['PORTALURL'] = self::getConfigService()->GetPortalURL();
//            self::$properties['LOGOUTURL'] = self::getConfigService()->GetPortalURL();
//        }
//        return self::$properties;
//    }

    /**
     * Get a new EntityManager instance.
     * <p/>
     * You would typically create a new em for running a nested
     * (child)transaction that is isolated from an outer parent transaction
     * (i.e. to create a standalone atomic unit).
     * <p/>
     * @return \Doctrine\ORM\EntityManager
     */
    public static function getNewEntityManager(){
        //require_once __DIR__ . "/../Doctrine/bootstrap.php";
        require __DIR__ . "/../Doctrine/bootstrap.php";
        return $entityManager;
    }

    /**
     * Get a EntityManager singleton instance that can be shared across files.
     * Get a reference to the same {@link Doctrine\ORM\EntityManager} instance.
     * The returned em is a managed singleton that provides an
     * active connection to the database. Subsequent calls from different methods
     * will always return the same em. This facilitates transaction
     * propagation across different method calls/pages in the same request.
     * <p/>
     * @return \Doctrine\ORM\EntityManager
     */
    public static function getEntityManager(){
        if(self::$em == null){
            // we should only require the one file; bootstrap.php (note, the
            // gap file below introduced intentionally to eliminate from grep/search)
            //require __DIR__ . "/../Doctrine/bootstrap    _doctrine.php";
            require __DIR__ . "/../Doctrine/bootstrap.php";
            self::$em = $entityManager;
        }
        return self::$em;
    }

    /**
     * Sinlgeton Site service
     * @return org\gocdb\services\Site
     */
    public static function getSiteService() {
        if (self::$siteService == null) {
            require_once __DIR__ . '/Site.php';
            self::$siteService = new org\gocdb\services\Site();
            self::$siteService->setEntityManager(self::getEntityManager());
            self::$siteService->setRoleActionAuthorisationService(self::getRoleActionAuthorisationService());
            self::$siteService->setScopeService(self::getScopeService());
        }
        return self::$siteService;
    }

    /**
     * Singleton ServiceGroup service
     * @return org\gocdb\services\ServiceGroup
     */
    public static function getServiceGroupService() {
        if (self::$serviceGroupService == null) {
            require_once __DIR__ . '/ServiceGroup.php';
            //self::$serviceGroupService = new org\gocdb\services\ServiceGroup(self::getRoleActionAuthorisationService());
            self::$serviceGroupService = new org\gocdb\services\ServiceGroup();
            self::$serviceGroupService->setEntityManager(self::getEntityManager());
            self::$serviceGroupService->setRoleActionAuthorisationService(self::getRoleActionAuthorisationService());
            self::$serviceGroupService->setScopeService(self::getScopeService());
        }
        return self::$serviceGroupService;
    }

    /**
     * Singleton Downtime service
     * @return org\gocdb\services\Downtime
     */
    public static function getDowntimeService() {
        if (self::$downtimeService == null) {
            require_once __DIR__ . '/Downtime.php';
            self::$downtimeService = new org\gocdb\services\Downtime();
            self::$downtimeService->setEntityManager(self::getEntityManager());
            self::$downtimeService->setRoleActionAuthorisationService(self::getRoleActionAuthorisationService());
        }
        return self::$downtimeService;
    }

    /**
     * Singleton ServiceService
     * @return org\gocdb\services\ServiceService
     */
    public static function getServiceService() {
        if (self::$seService == null) {
            require_once __DIR__ . '/ServiceService.php';
            self::$seService = new org\gocdb\services\ServiceService();
            self::$seService->setEntityManager(self::getEntityManager());
            self::$seService->setRoleActionAuthorisationService(self::getRoleActionAuthorisationService());
            self::$seService->setScopeService(self::getScopeService());
        }
        return self::$seService;
    }


    /**
     * Singleton Role service
     * @return org\gocdb\services\Role
     */
    public static function getRoleService() {
        if (self::$roleService == null) {
            require_once __DIR__ . '/Role.php';
            self::$roleService = new org\gocdb\services\Role();
            self::$roleService->setEntityManager(self::getEntityManager());
            self::$roleService->setDowntimeService(self::getDowntimeService());
            self::$roleService->setRoleActionAuthorisationService(self::getRoleActionAuthorisationService());
        }
        return self::$roleService;
    }

    /**
     * Get singleton RoleActionAuthorisationService instance with injected dependencies.
     * @return org\gocdb\services\RoleActionAuthorisationService
     */
    public static function getRoleActionAuthorisationService(){
        if(self::$roleActionAuthorisationService == null){
            require_once __DIR__ . '/RoleActionAuthorisationService.php';
            $roleActionMappingService = self::getRoleActionMappingService();
            //$roleService = self::getRoleService();
            self::$roleActionAuthorisationService = new org\gocdb\services\RoleActionAuthorisationService($roleActionMappingService/*, $roleService*/);
            self::$roleActionAuthorisationService->setEntityManager(self::getEntityManager());
        }
        return self::$roleActionAuthorisationService;
    }

    /**
     * Get singleton RoleActionMappingService instance with injected dependencies.
     * @return org\gocdb\services\RoleActionMappingService
     */
    public static function getRoleActionMappingService(){
        if(self::$roleActionMappingService == null){
            require_once __DIR__ . '/RoleActionMappingService.php';
            self::$roleActionMappingService = new \org\gocdb\services\RoleActionMappingService();
        }
        return self::$roleActionMappingService;
    }


    /**
     * Singleton User service
     * @return org\gocdb\services\User
     */
    public static function getUserService() {
        if (self::$userService == null) {
            require_once __DIR__ . '/User.php';
            self::$userService = new org\gocdb\services\User();
            self::$userService->setEntityManager(self::getEntityManager());
        }
        return self::$userService;
    }

    /**
     * Singleton NGI service
     * @return org\gocdb\services\NGI
     */
    public static function getNgiService() {
        if (self::$ngiService == null) {
            require_once __DIR__ . '/NGI.php';
            self::$ngiService = new org\gocdb\services\NGI();
            self::$ngiService->setEntityManager(self::getEntityManager());
            self::$ngiService->setRoleActionAuthorisationService(self::getRoleActionAuthorisationService());
            self::$ngiService->setScopeService(self::getScopeService());
        }
        return self::$ngiService;
    }

    /**
     * Singleton Search service
     * @return org\gocdb\services\Search
     */
    public static function getSearchService() {
        if (self::$searchService == null) {
            require_once __DIR__ . '/Search.php';
            self::$searchService = new org\gocdb\services\Search();
            self::$searchService->setEntityManager(self::getEntityManager());
        }
        return self::$searchService;
    }


    /**
     * Singleton Scope service
     * @return org\gocdb\services\Scope
     */
    public static function getScopeService(){
        if(self::$scopeService == null) {
            require_once __DIR__ . '/Scope.php';
            self::$scopeService = new org\gocdb\services\Scope();
            self::$scopeService->setEntityManager(self::getEntityManager());
        }
        return self::$scopeService;
    }

    /**
     * Singleton Config service
     * @return org\gocdb\services\Config
     */
    public static function getConfigService() {
        if (self::$configService == null) {
            require_once __DIR__ . '/Config.php';
            self::$configService = new org\gocdb\services\Config();
        }
        return self::$configService;
    }

    /**
     * Singleton Validate service
     * @return org\gocdb\services\Validate
     */
    public static function getValidateService() {
        if (self::$validateService == null) {
            require_once __DIR__ . '/Validate.php';
            self::$validateService = new org\gocdb\services\Validate();
        }
        return self::$validateService;
    }

    /**
     * Singleton Cert Status service
     * @return org\gocdb\services\CertificationStatus
     */
    public static function getCertStatusService() {
        if (self::$certStatusService == null) {
            require_once __DIR__ . '/CertificationStatus.php';
            self::$certStatusService = new org\gocdb\services\CertificationStatus();
            self::$certStatusService->setEntityManager(self::getEntityManager());
            self::$certStatusService->setRoleActionAuthorisationService(self::getRoleActionAuthorisationService());
        }
        return self::$certStatusService;
    }

     /**
     * Singleton Service Type service
     * @return org\gocdb\services\ServiceType
     */
    public static function getServiceTypeService() {
        if (self::$serviceTypeService == null) {
            require_once __DIR__ . '/ServiceType.php';
            self::$serviceTypeService = new org\gocdb\services\ServiceType;
            self::$serviceTypeService->setEntityManager(self::getEntityManager());
        }
        return self::$serviceTypeService;
    }

     /**
     * Singleton project service
     * @return org\gocdb\services\Project
     */
    public static function getProjectService() {
        if (self::$projectService == null) {
            require_once __DIR__ . '/Project.php';
            self::$projectService = new org\gocdb\services\Project;
            self::$projectService->setEntityManager(self::getEntityManager());
            self::$projectService->setRoleActionAuthorisationService(self::getRoleActionAuthorisationService());
        }
        return self::$projectService;
    }

    /**
     * Singleton Link Identity service
     * @return org\gocdb\services\LinkIdentity
     */
    public static function getLinkIdentityService() {
        if (self::$linkIdentityService == null) {
            require_once __DIR__ . '/LinkIdentity.php';
            self::$linkIdentityService = new org\gocdb\services\LinkIdentity();
            self::$linkIdentityService->setEntityManager(self::getEntityManager());
        }
        return self::$linkIdentityService;
    }

     /**
     * Singleton Retrieve Account service
     * @return org\gocdb\services\OwnedEntity
     */
    public static function getOwnedEntityService() {
        if (self::$OwnedEntityService == null) {
            require_once __DIR__ . '/OwnedEntity.php';
            self::$OwnedEntityService = new org\gocdb\services\OwnedEntity();
            self::$OwnedEntityService->setEntityManager(self::getEntityManager());
        }
        return self::$OwnedEntityService;
    }

    /**
     * Singleton ExtensionsService
     * @return org\gocdb\services\ExtensionsService
     */
    public static function getExtensionsService() {
        if (self::$exService == null) {
            require_once __DIR__ . '/ExtensionsService.php';
            self::$exService = new org\gocdb\services\ExtensionsService();
            self::$exService->setEntityManager(self::getEntityManager());
        }
        return self::$exService;
    }

    /**
     * Singleton NotificationService
     * @return org\gocdb\services\NotificationService
     */
    public static function getNotificationService() {
        if (self::$notificationService == null) {
            require_once __DIR__ . '/NotificationService.php';
            self::$notificationService = new org\gocdb\services\NotificationService();
            self::$notificationService->setEntityManager(self::getEntityManager());
        }
        return self::$notificationService;
    }

    /**
     * Singleton EmailService
     * @return org\gocdb\services\EmailService
     */
    public static function getEmailService() {
        if (self::$emailService == null) {
            require_once __DIR__ . '/EmailService.php';
            self::$emailService = new org\gocdb\services\EmailService();
            self::$emailService->setEntityManager(self::getEntityManager());
        }
        return self::$emailService;
    }
}

?>
