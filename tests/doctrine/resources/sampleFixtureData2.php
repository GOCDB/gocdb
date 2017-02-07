<?php
/*
 * Include this file in your tests in order to persist a set of known fixuture data
 * for subsequent use in testing.
 *
 * @author David Meredith
 */

/*
 * Entity graph is as follows (<IScopedEntity> can be subsituted for NGI, Site, Service, ServiceGroup):
 *
 * <IScopedEntity0>
 *  -
 *
 * <IScopedEntity1>
 *  - Scope0
 *
 * <IScopedEntity2>
 *  - Scope0
 *  - Scope1
 *
 * <IScopedEntity3>
 *  - Scope0
 *  - Scope1
 *  - Scope2
 *
 * <IScopedEntity4>
 *  - Scope0
 *  - Scope1
 *  - Scope2
 *  - Scope3
 *  - Scope4
 *  - Scope5
 */
        $scopeCount = 6;
        $scopes = array();
        // create scopes and persist
        for($i=0; $i<$scopeCount; $i++){
            $scopes[] = TestUtil::createSampleScope("Proj scope ".$i, "Scope".$i);
            $this->em->persist($scopes[$i]);
        }

        // NGIs ****************************
        // create ngis and persist
        $ngi0 = TestUtil::createSampleNGI("MYNGI0");
        $ngi1 = TestUtil::createSampleNGI("MYNGI1");
        $ngi2 = TestUtil::createSampleNGI("MYNGI2");
        $ngi3 = TestUtil::createSampleNGI("MYNGI3");
        $ngi4 = TestUtil::createSampleNGI("MYNGI4");
        $this->em->persist($ngi0);
        $this->em->persist($ngi1);
        $this->em->persist($ngi2);
        $this->em->persist($ngi3);
        $this->em->persist($ngi4);
        // Add different scopes to selected ngis:
        // ngi0 has no scope
        // ngi1 has one scope
        $ngi1->addScope($scopes[0]);
        // ngi2 has 2 scopes
        $ngi2->addScope($scopes[0]);
        $ngi2->addScope($scopes[1]);
        // ngi3 has 3 scopes
        $ngi3->addScope($scopes[0]);
        $ngi3->addScope($scopes[1]);
        $ngi3->addScope($scopes[2]);
        //  ngi4 has all scopes
        for($i=0; $i<$scopeCount; $i++){
            $ngi4->addScope($scopes[$i]);
        }
        // NGIs ****************************


        // At least one certStatus is needed for the tests
        $certStatus = new CertificationStatus();
        $certStatus->setName('Certified');
        $this->em->persist($certStatus);

        // Sites: all have same 'Certified' cert status
        // ****************************
        $site0 = TestUtil::createSampleSite("Site0");
        $site0->setCertificationStatus($certStatus);
        $site1 = TestUtil::createSampleSite("Site1");
        $site1->setCertificationStatus($certStatus);
        $site2 = TestUtil::createSampleSite("Site2");
        $site2->setCertificationStatus($certStatus);
        $site3 = TestUtil::createSampleSite("Site3");
        $site3->setCertificationStatus($certStatus);
        $site4 = TestUtil::createSampleSite("Site4");
        $site4->setCertificationStatus($certStatus);


        $this->em->persist($site0);
        $this->em->persist($site1);
        $this->em->persist($site2);
        $this->em->persist($site3);
        $this->em->persist($site4);
        // Add different scopes to selected sites:
        // site0 has no scope
        // site1 has one scope
        $site1->addScope($scopes[0]);
        // site2 has two scopes
        $site2->addScope($scopes[0]);
        $site2->addScope($scopes[1]);
        // site3 has three scopes
        $site3->addScope($scopes[0]);
        $site3->addScope($scopes[1]);
        $site3->addScope($scopes[2]);
        //  site4 has all scopes
        for($i=0; $i<$scopeCount; $i++){
            $site4->addScope($scopes[$i]);
        }
        // Sites ****************************


        // Services: all services are added to $site0
        // ****************************
        $service0 = TestUtil::createSampleService("Service0");
        $site0->addServiceDoJoin($service0);
        $service1 = TestUtil::createSampleService("Service1");
        $site0->addServiceDoJoin($service1);
        $service2 = TestUtil::createSampleService("Service2");
        $site0->addServiceDoJoin($service2);
        $service3 = TestUtil::createSampleService("Service3");
        $site0->addServiceDoJoin($service3);
        $service4 = TestUtil::createSampleService("Service4");
        $site0->addServiceDoJoin($service4);
        $this->em->persist($service0);
        $this->em->persist($service1);
        $this->em->persist($service2);
        $this->em->persist($service3);
        $this->em->persist($service4);
        // Add different scopes to selected services:
        // service0 has no scope
        // service1 has one scope
        $service0->addScope($scopes[0]);
        // service2 has two scopes
        $service2->addScope($scopes[0]);
        $service2->addScope($scopes[1]);
        // service3 has three scopes
        $service3->addScope($scopes[0]);
        $service3->addScope($scopes[1]);
        $service3->addScope($scopes[2]);
        //  service4 has all scopes
        for($i=0; $i<$scopeCount; $i++){
            $service4->addScope($scopes[$i]);
        }
        // Services ****************************


        // ServiceGroups ****************************
        $sg0 = TestUtil::createSampleServiceGroup("SG0");
        $sg1 = TestUtil::createSampleServiceGroup("SG1");
        $sg2 = TestUtil::createSampleServiceGroup("SG2");
        $sg3 = TestUtil::createSampleServiceGroup("SG3");
        $sg4 = TestUtil::createSampleServiceGroup("SG4");
        $this->em->persist($sg0);
        $this->em->persist($sg1);
        $this->em->persist($sg2);
        $this->em->persist($sg3);
        $this->em->persist($sg4);
        // Add different scopes to selected sgs:
        // sg0 has no scope
        // sg1 has one scope
        $sg1->addScope($scopes[0]);
        // sg2 has two scopes
        $sg2->addScope($scopes[0]);
        $sg2->addScope($scopes[1]);
        // sg3 has three scopes
        $sg3->addScope($scopes[0]);
        $sg3->addScope($scopes[1]);
        $sg3->addScope($scopes[2]);
        //  sg4 has all scopes
        for($i=0; $i<$scopeCount; $i++){
            $sg4->addScope($scopes[$i]);
        }
        // ServiceGroups ****************************

        // commit
        $this->em->flush();

        // Assert fixture data is setup correctly in the DB.
        $testConn = $this->getConnection();

        $result = $testConn->createQueryTable('results_table', "SELECT * FROM Scopes");
        $this->assertTrue($result->getRowCount() == $scopeCount);

        $result = $testConn->createQueryTable('results_table', "SELECT * FROM NGIs");
        $this->assertTrue($result->getRowCount() == 5);

        $result = $testConn->createQueryTable('results_table', "SELECT * FROM Sites");
        $this->assertTrue($result->getRowCount() == 5);

        $result = $testConn->createQueryTable('results_table', "SELECT * FROM Services");
        $this->assertTrue($result->getRowCount() == 5);

        $result = $testConn->createQueryTable('results_table', "SELECT * FROM ServiceGroups");
        $this->assertTrue($result->getRowCount() == 5);
?>
