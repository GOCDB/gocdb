<?php
/*
 * Include this file in your tests in order to persist a set of known fixuture data
 * for subsequent use in testing.
 *
 * @author David Meredith
 */


// Sites ****************************
$site0 = TestUtil::createSampleSite("Site0");
$site1 = TestUtil::createSampleSite("Site1");
$site2 = TestUtil::createSampleSite("Site2");
$site3 = TestUtil::createSampleSite("Site3");
$site4 = TestUtil::createSampleSite("Site4");
$this->em->persist($site0);
$this->em->persist($site1);
$this->em->persist($site2);
$this->em->persist($site3);
$this->em->persist($site4);
// Sites ****************************


// site1 has 1 prop
$s1p1 = TestUtil::createSampleSiteProperty("s1p1", "v1");
$site1->addSitePropertyDoJoin($s1p1);
$this->em->persist($s1p1);

// site2 has 2 props
$s2p1 = TestUtil::createSampleSiteProperty("s2p1", "v1");
$s2p2 = TestUtil::createSampleSiteProperty("s2p2", "v2");
$this->em->persist($s2p1);
$this->em->persist($s2p2);
$site2->addSitePropertyDoJoin($s2p1);
$site2->addSitePropertyDoJoin($s2p2);

// site3
$s3p1 = TestUtil::createSampleSiteProperty("s3p1", "v1");
$s3p2 = TestUtil::createSampleSiteProperty("s3p2", "v2");
$s3p3 = TestUtil::createSampleSiteProperty("VO", "foo");
$s3p4 = TestUtil::createSampleSiteProperty("VO2", "bar");
$this->em->persist($s3p1);
$this->em->persist($s3p2);
$this->em->persist($s3p3);
$this->em->persist($s3p4);
$site3->addSitePropertyDoJoin($s3p1);
$site3->addSitePropertyDoJoin($s3p2);
$site3->addSitePropertyDoJoin($s3p3);
$site3->addSitePropertyDoJoin($s3p4);

// site4
$s4p1 = TestUtil::createSampleSiteProperty("s4p1", "v1");
$s4p2 = TestUtil::createSampleSiteProperty("s4p2", "v2");
$s4p3 = TestUtil::createSampleSiteProperty("VO", "foo");
$s4p4 = TestUtil::createSampleSiteProperty("VO", "bar");
$s4p5 = TestUtil::createSampleSiteProperty("VO2", "baz");
$s4p6 = TestUtil::createSampleSiteProperty("VO2", "bing");
$this->em->persist($s4p1);
$this->em->persist($s4p2);
$this->em->persist($s4p3);
$this->em->persist($s4p4);
$this->em->persist($s4p5);
$this->em->persist($s4p6);
$site4->addSitePropertyDoJoin($s4p1);
$site4->addSitePropertyDoJoin($s4p2);
$site4->addSitePropertyDoJoin($s4p3);
$site4->addSitePropertyDoJoin($s4p4);
$site4->addSitePropertyDoJoin($s4p5);
$site4->addSitePropertyDoJoin($s4p6);


// commit
$this->em->flush();

// Assert fixture data is setup correctly in the DB.
$testConn = $this->getConnection();


$result = $testConn->createQueryTable('results_table', "SELECT * FROM Sites");
$this->assertTrue($result->getRowCount() == 5);

$result = $testConn->createQueryTable('results_table', "SELECT * FROM Site_Properties");
$this->assertTrue($result->getRowCount() == 13);

?>
