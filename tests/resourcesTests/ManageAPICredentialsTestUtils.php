<?php

/**
 * Helper functions for API credential management scripts
 */

namespace org\gocdb\tests;

require_once __DIR__ . '/../unit/lib/Gocdb_Services/ServiceTestUtil.php';

use DateInterval;
use DateTime;
use DateTimeZone;
use org\gocdb\tests\ServiceTestUtil;

class ManageAPICredentialsTestUtils
{
    private $serviceTestUtil;
    private $entityManager;

    public function __construct($entityManager)
    {
        $this->entityManager = $entityManager;
        $this->serviceTestUtil = new ServiceTestUtil();
    }
    /**
     * Create a number of unique API authentication credentials, evenly spaced each
     * with last used time and last renewed time a given number of months before the
     * previous, starting the given number of months before the current time.
     *
     * @param integer $number The number of credentials to create
     * @param integer $intervalMonths The interval in months between credentials used to
     *                                set lastUseTime and lastRenewTime
     * @return \DateTime Time used as base: the first credential will have time values
     *                                      $intervalMonths older than this
     */
    public function createTestAuthEnts($number, $intervalMonths)
    {
        list($user, $site, $siteService) =
            $this->serviceTestUtil->createGocdbEntities($this->entityManager);

        $baseTime = new DateTime('now', new DateTimeZone('UTC'));

        $type = 'X.509';

        $time = clone $baseTime;

        for ($count = 1; $count <= $number; $count++) {
            // $useTime will be decremented by 6M for each loop
            $time->sub(new DateInterval('P' . $intervalMonths . 'M'));
            $ident = '/CN=A Dummy Subject ' . $count;
            $authEnt = $siteService->addAPIAuthEntity(
                $site,
                $user,
                array(
                    'IDENTIFIER' =>  $ident,
                    'TYPE' => $type,
                    'ALLOW_WRITE' => false
                )
            );
            $authEnt->setLastUseTime($time);
            $authEnt->setLastRenewTime($time);
        }
        return $baseTime;
    }
}
