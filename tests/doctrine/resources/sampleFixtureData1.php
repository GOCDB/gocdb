<?php

/*
 * Include this file in your tests in order to persist a set of known fixuture data
 * for subsequent use in testing.
 *
 * The Fixture data consists of NGI, Sites, Services, User and Roles etc.
 * If you update this fixture data, make sure you update the tests that
 * include this file as the tests assume a known fixture data structure.
 *
 * See the corresponding fixtureDataERD.svg file for an ERD of this fixture data
 *
 * @author David Meredith
 */

        $roleType1 = TestUtil::createSampleRoleType("NAME");
        $roleType2 = TestUtil::createSampleRoleType("NAME2");
        $this->em->persist($roleType1);
        $this->em->persist($roleType2);

        // Create a user
        $userWithRoles = TestUtil::createSampleUser("Test", "Testing", "/c=test");
        $this->em->persist($userWithRoles);
        $userId = $userWithRoles->getId();

        // Create an NGI, site and services
        $ngi = TestUtil::createSampleNGI("MYNGI");
        $site1 = TestUtil::createSampleSite('site1');
        $site2 = TestUtil::createSampleSite('site2');
        $service1 = TestUtil::createSampleService('site1_service1');
        $service2 = TestUtil::createSampleService('site1_service2');

        $endpoint1 = TestUtil::createSampleEndpointLocation();
        $downtime1 = TestUtil::createSampleDowntime();
        $downtime2 = TestUtil::createSampleDowntime();
        $service1->addEndpointLocationDoJoin($endpoint1);
        $downtime1->addEndpointLocation($endpoint1);
        $downtime2->addEndpointLocation($endpoint1);

        $site1->addServiceDoJoin($service1);
        $site1->addServiceDoJoin($service2);

        $ngi->addSiteDoJoin($site1);
        $ngi->addSiteDoJoin($site2);


        $certStatusLog1 = TestUtil::createSampleCertStatusLog();
        $certStatusLog2 = TestUtil::createSampleCertStatusLog();
        $site2->addCertificationStatusLog($certStatusLog1);
        $site2->addCertificationStatusLog($certStatusLog2);


        $this->em->persist($ngi);
        $this->em->persist($site1);
        $this->em->persist($site2);
        $this->em->persist($service1);
        $this->em->persist($service2);
        $this->em->persist($certStatusLog1);
        $this->em->persist($certStatusLog2);
        $this->em->persist($endpoint1);
        $this->em->persist($downtime1);
        $this->em->persist($downtime2);

        // Create some roles and link to the user, role type and ngi
        // roles on ngi
        $ngiRole1 = TestUtil::createSampleRole($userWithRoles, $roleType1, $ngi, RoleStatus::GRANTED);
        $ngiRole2 = TestUtil::createSampleRole($userWithRoles, $roleType2, $ngi, RoleStatus::GRANTED);
        // roles on site1
        $site1Role1 = TestUtil::createSampleRole($userWithRoles, $roleType1, $site1, RoleStatus::GRANTED);
        $site1Role2 = TestUtil::createSampleRole($userWithRoles, $roleType2, $site1, RoleStatus::GRANTED);
        // roles on site2
        $site2Role1= TestUtil::createSampleRole($userWithRoles, $roleType1, $site2, RoleStatus::GRANTED);
        $site2Role2= TestUtil::createSampleRole($userWithRoles, $roleType2, $site2, RoleStatus::GRANTED);

        $this->em->persist($ngiRole1);
        $this->em->persist($ngiRole2);
        $this->em->persist($site1Role1);
        $this->em->persist($site1Role2);
        $this->em->persist($site2Role1);
        $this->em->persist($site2Role2);
        $this->em->flush();



        // Assert fixture data is setup correctly in the DB.
        $testConn = $this->getConnection();

        $result = $testConn->createQueryTable('results_table', "SELECT * FROM Users");
        $this->assertTrue($result->getRowCount() == 1);

        $result = $testConn->createQueryTable('results_table', "SELECT * FROM Roles");
        $this->assertTrue($result->getRowCount() == 6);

        $result = $testConn->createQueryTable('results_table', "SELECT * FROM NGIs");
        $this->assertTrue($result->getRowCount() == 1);

        $result = $testConn->createQueryTable('results_table', "SELECT * FROM Sites");
        $this->assertTrue($result->getRowCount() == 2);

        $result = $testConn->createQueryTable('results_table', "SELECT * FROM Services");
        $this->assertTrue($result->getRowCount() == 2);

        $result = $testConn->createQueryTable('results_table', "SELECT * FROM Downtimes");
        $this->assertTrue($result->getRowCount() == 2);

        $result = $testConn->createQueryTable('results_table', "SELECT * FROM EndpointLocations");
        $this->assertTrue($result->getRowCount() == 1);

        $result = $testConn->createQueryTable('results_table', "SELECT * FROM CertificationStatusLogs");
        $this->assertTrue($result->getRowCount() == 2);
?>
