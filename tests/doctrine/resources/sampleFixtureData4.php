<?php
 
/*
 * Include this file in your tests in order to persist a set of known fixuture data 
 * for subsequent use in testing.
 *  
 * The Fixture data consists of NGI, Sites, Services, User and Roles etc. 
 * If you update this fixture data, make sure you update the tests that 
 * include this file as the tests assume a known fixture data structure. 
 * 
 * See the corresponding fixtureData4ERD.svg file for an ERD of this fixture data 
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
        $endpoint1 = TestUtil::createSampleEndpointLocation(); 
        $endpoint2 = TestUtil::createSampleEndpointLocation(); 
        $service1->addEndpointLocationDoJoin($endpoint1); 
        $service1->addEndpointLocationDoJoin($endpoint2); 


        $service2 = TestUtil::createSampleService('site1_service2'); 
        $endpoint2_1 = TestUtil::createSampleEndpointLocation(); 
        $endpoint2_2 = TestUtil::createSampleEndpointLocation(); 
        $service2->addEndpointLocationDoJoin($endpoint2_1); 
        $service2->addEndpointLocationDoJoin($endpoint2_2); 
        

        
        // create downtimes  
        $downtime1 = TestUtil::createSampleDowntime(); 
        $downtime2 = TestUtil::createSampleDowntime(); 
        $downtime3 = TestUtil::createSampleDowntime(); 
        $downtime4 = TestUtil::createSampleDowntime(); 
        $downtime5 = TestUtil::createSampleDowntime(); 
        $downtime6 = TestUtil::createSampleDowntime(); 
        $downtime7 = TestUtil::createSampleDowntime(); 
        $downtimeOrphan = TestUtil::createSampleDowntime(); 
        $downtimeOrphan->setDescription("orphan");  
      
        // link downtimes to services and service-endpoints 
        $downtime1->addEndpointLocation($endpoint1); 
        $downtime1->addService($service1); 
        $downtime2->addEndpointLocation($endpoint1); 
        $downtime2->addService($service1); 
        $downtime3->addEndpointLocation($endpoint2); 
        $downtime3->addService($service1); 
        $downtime4->addEndpointLocation($endpoint2); 
        //$downtime4->addService($service1); // downtime4 is not linked to service !!!
        //$downtime5->addEndpointLocation($endpoint2); // downtime5 is not linked to endpoint
        $downtime5->addService($service1); 
        $downtime6->addService($service1); 
      

        //$downtime4->addEndpointLocation($endpoint2_1); // downtiem4 is not linked to endpoint ! 
        $downtime4->addService($service2);// downtime4 is directly linked to service2
        $downtime6->addEndpointLocation($endpoint2_1); //downtime6 is linked to service2 via its endpoint 
        //$downtime6->addService($service2);  // downtime6 is not linked to endpoint !  
        $downtime7->addService($service2); 
        $downtime7->addEndpointLocation($endpoint2_2); 

        
        
        $site1->addServiceDoJoin($service1); 
        $site1->addServiceDoJoin($service2); 
        $ngi->addSiteDoJoin($site1); 
        $ngi->addSiteDoJoin($site2); 


        $certStatusLog1 = TestUtil::createSampleCertStatusLog(); 
        $certStatusLog2 = TestUtil::createSampleCertStatusLog(); 
        $site2->addCertificationStatusLog($certStatusLog1); 
        $site2->addCertificationStatusLog($certStatusLog2); 

        // ngi, site, service
        $this->em->persist($ngi);
        $this->em->persist($site1);
        $this->em->persist($site2);
        $this->em->persist($service1);
        $this->em->persist($service2);
        // endpoints
        $this->em->persist($endpoint1);  
        $this->em->persist($endpoint2);  
        $this->em->persist($endpoint2_1);  
        $this->em->persist($endpoint2_2);  
        // downtimes 
        $this->em->persist($downtime1);  
        $this->em->persist($downtime2);  
        $this->em->persist($downtime3);  
        $this->em->persist($downtime4);  
        $this->em->persist($downtime5);  
        $this->em->persist($downtime6);  
        $this->em->persist($downtime7);  
        $this->em->persist($downtimeOrphan);  
        // cert status logs 
        $this->em->persist($certStatusLog1); 
        $this->em->persist($certStatusLog2); 

        
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
        $this->assertTrue($result->getRowCount() == 8);

        $result = $testConn->createQueryTable('results_table', "SELECT * FROM EndpointLocations");
        $this->assertTrue($result->getRowCount() == 4);
        
?>
