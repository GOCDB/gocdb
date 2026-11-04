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
namespace org\gocdb\tests;

require_once dirname(__FILE__) . '/../../../../lib/Gocdb_Services/Validate.php';

use org\gocdb\services\Validate;

/**
 * Checks that the HOST_DN schema regex accepts a colon in a DN, as issued
 * for robot certificates (GFD.225 allows ':' in PrintableString).
 */
class HostDNColonTest extends \PHPUnit\Framework\TestCase
{
    public const ROBOT_DN = '/DC=ch/DC=cern/OU=Units/OU=Users/CN=css/CN=111111/CN=Robot: OSG Accounting';
    public const PLAIN_VALID_DN = '/C=UK/O=eScience/OU=CLRC/L=RAL/CN=gocdb.somedomain.com';

    public function testHostAcceptsColon()
    {
        $validate = new Validate();
        $this->assertTrue($validate->validate('service', 'HOST_DN', self::ROBOT_DN));
    }

    public function testHostStillAcceptsNormalDN()
    {
        $validate = new Validate();
        $this->assertTrue($validate->validate('service', 'HOST_DN', self::PLAIN_VALID_DN));
    }

    public function testHostStillRejectsInvalidCharacters()
    {
        $validate = new Validate();
        $this->assertFalse($validate->validate('service', 'HOST_DN', '/CN=Robot: <script>'));
    }
    public function testCertReqDnsAcceptColon()
    {
        $validate = new Validate();
        $this->assertTrue($validate->validate('change_cert_req', 'OLD_DN', self::ROBOT_DN));
        $this->assertTrue($validate->validate('change_cert_req', 'NEW_DN', self::ROBOT_DN));
    }
}
