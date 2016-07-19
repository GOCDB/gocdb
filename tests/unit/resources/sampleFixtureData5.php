<?php

/*
 * Copyright (C) 2015 STFC
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


        /*
         * Create test data with the following structure (note p3 has two ngis)
         *
         * p1  p2  p3
         * |   |  /|
         * n1  n2  n3
         * |   |   |
         * s1  s2  s3
         */
        $p1 = new \Project("p1");
        $n1 = TestUtil::createSampleNGI("n1");
        $s1 = TestUtil::createSampleSite("s1");
        $this->em->persist($p1);
        $this->em->persist($n1);
        $this->em->persist($s1);
        $n1->addSiteDoJoin($s1);
        $p1->addNgi($n1);

        $p2 = new \Project("p2");
        $n2 = TestUtil::createSampleNGI("n2");
        $s2 = TestUtil::createSampleSite("s2");
        $this->em->persist($p2);
        $this->em->persist($n2);
        $this->em->persist($s2);
        $n2->addSiteDoJoin($s2);
        $p2->addNgi($n2);

        $p3 = new \Project("p3");
        $n3 = TestUtil::createSampleNGI("n3");
        $s3 = TestUtil::createSampleSite("s3");
        $this->em->persist($p3);
        $this->em->persist($n3);
        $this->em->persist($s3);
        $n3->addSiteDoJoin($s3);
        $p3->addNgi($n3);
        $p3->addNgi($n2);     // note the p3 is parent to n2 + n3

