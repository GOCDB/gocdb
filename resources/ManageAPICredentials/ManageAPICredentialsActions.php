<?php

/**
 * Implement API credential management actions such as listing, sending warning emails and
 * deleting credentials.
 */

namespace org\gocdb\scripts;

require_once dirname(__FILE__) . "/../../lib/Gocdb_Services/APIAuthenticationService.php";
require_once dirname(__FILE__) . "/../../lib/Gocdb_Services/Factory.php";

use APIAuthentication;
use DateInterval;
use DateTime;
use Factory;
use org\gocdb\services\APIAuthenticationService;

class ManageAPICredentialsActions
{
    private $dryRun;
    private $entityManager;
    private $baseTime;

    public function __construct($dryRun, $entityManager, $baseTime)
    {
        /**
        * @param \Doctrine\Orm\EntityManager $entitymanager A valid Doctrine Entity Manager
        * @param bool          $dryRun         If true no action is taken and a report is generated instead
        * @param \DateTime     $baseTime       Time from which interval of no-use is measured
        **/
        $this->dryRun = $dryRun;
        $this->entityManager = $entityManager;
        $this->baseTime = $baseTime;
    }
    /**
     * Find API credentials unused for a number of months.
     *
     * Find API credentials which have not been used for a number of months prior to a given base time based
     * on the credential property lastUseTime.
     *
     * @param int           $threshold      The number of months of no-use prior to $baseTime to use for selection
     */
    public function getCreds($threshold, $propertyName)
    {
        $qbl = $this->entityManager->createQueryBuilder();

        $qbl->select('cred')
            ->from('APIAuthentication', 'cred')
            ->where($qbl->expr()->isNotNull("cred.user")) // cope with legacy entities
            ->andWhere('cred.' . $propertyName . '< :threshold');

        $timeThresh = clone $this->baseTime;

        if ($threshold > 0) {
            $timeThresh->sub(new DateInterval("P" . $threshold . "M"));
        };

        $qbl->setParameter('threshold', $timeThresh->format('Y-m-d 00:00:00'));

        $creds = $qbl->getQuery()->getResult();

        return $creds;
    }
    /**
     * Select API credentials for deletion.
     *
     * Find API credentials which have not been used for a given number of months
     * and delete them or, if dry-run option is true, generate a summary report
     * of the credentils found.
     *
     * @param array             $creds              Array of credentials to process.
     * @param \Doctrine\Orm\EntityManager $entitymanager A valid Doctrine Entity Manager
     * @param \DateTime         $baseTime           Time from which interval of no-use is measured
     * @param int               $deleteThreshold    The number of months of no-use which will trigger deletion
     * @param bool              $isRenewalRequest   Flag indicating the
     *                                              presence or absence of the
     *                                              `r` command-line argument.
     * @return array                                Credentials which were not deleted.
     */
    public function deleteCreds($creds, $deleteThreshold, $isRenewalRequest)
    {
        $deletedCreds = [];

        $serv = new APIAuthenticationService();
        $serv->setEntityManager($this->entityManager);

        /* @var $apiCred APIAuthentication */
        foreach ($creds as $apiCred) {
            if (
                $this->isOverThreshold(
                    $apiCred,
                    $this->baseTime,
                    $deleteThreshold,
                    $isRenewalRequest
                )
            ) {
                $deletedCreds[] = $apiCred;

                if (!$this->dryRun) {
                    $serv->deleteAPIAuthentication($apiCred);
                }
            }
        }
        if ($this->dryRun) {
            $this->reportDryRun($deletedCreds, "deleting");
        }

        return array_udiff($creds, $deletedCreds, array($this, 'compareCredIds'));
    }

    /**
     * Send of warning emails where credentials have not been used for a given number of months
     *
     * Find API credentials from the input array which have not been used for a given number of months
     * and send emails to the owners and site address, taken from the credential object,
     * warning of impending deletion if the period of no-use reaches a given threshold.
     * If dry-run option is true, generate a summary report of the credentials found
     * instead of sending emails.
     *
     * @param array         $creds              Array of credentials to process.
     * @param int           $warningThreshold   The number of months of no-use which triggers warning emails
     * @param int           $deleteThreshold    The number of months of no-use which will trigger deletion
     * @param string        $fromEmail          Email address to use as sender's (From:) address
     * @param string        $replyToEmail       Email address for replies (Reply-To:)
     * @param bool          $isRenewalRequest   Flag indicating the presence
     *                                          or absence of the `r`
     *                                          command-line argument.
     * @return array        []                  An Array of credentials identifed
     *                                          for sending warning emails.
     */
    public function warnUsers(
        $creds,
        $warningThreshold,
        $deletionThreshold,
        $fromEmail,
        $replyToEmail,
        $isRenewalRequest
    ) {
        $warnedCreds = [];

        /* @var $api APIAuthentication */
        foreach ($creds as $apiCred) {
            // The credentials list is pre-selected based on the given threshold in the query
            // so this check is probably redundant.
            if (
                $this->isOverThreshold(
                    $apiCred,
                    $this->baseTime,
                    $warningThreshold,
                    $isRenewalRequest
                )
            ) {
                $lastUseOrRenewTime = $isRenewalRequest
                    ? $apiCred->getLastRenewTime()
                    : $apiCred->getLastUseTime();
                $elapsedMonths = $this->baseTime->diff($lastUseOrRenewTime)
                    ->format('%m');

                if (!$this->dryRun) {
                    $this->sendWarningEmail(
                        $fromEmail,
                        $replyToEmail,
                        $apiCred,
                        intval($elapsedMonths),
                        $deletionThreshold,
                        $isRenewalRequest
                    );
                }

                $warnedCreds[] = $apiCred;
            }
        }

        if ($this->dryRun) {
            $this->reportDryRun($warnedCreds, "sending warning emails");
        }

        return array_udiff($creds, $warnedCreds, array($this, 'compareCredIds'));
    }

/**
 * @return boolean true if the credential has not been used within $threshold months, else false
 */
    private function isOverThreshold(
        APIAuthentication $cred,
        DateTime $baseTime,
        $threshold,
        $isRenewalRequest
    ) {
        $lastUseOrRenewTime = $isRenewalRequest
            ? $cred->getLastRenewTime()
            : $cred->getLastUseTime();

        $diffTime = $baseTime->diff($lastUseOrRenewTime);
        $lastUseOrRenewMonths = ($diffTime->y * 12) + $diffTime->m;

        return $lastUseOrRenewMonths >= $threshold;
    }

/**
 * Helper function to check if two API credentials have the same id.
 *
 * @return integer zero if equal, -1 if id1 < id2, 1 if id1 > id2
 *
*/
    private function compareCredIds(APIAuthentication $cred1, APIAuthentication $cred2)
    {
        $id1 = $cred1->getId();
        $id2 = $cred2->getId();

        if ($id1 == $id2) {
            return 0;
        };

        return $id1 > $id2 ? 1 : -1;
    }

/**
 * Format and send warning emails.
 *
 * Send emails to API credential owner and the registered site address warning of impending credential deletion
 * if the credential remains unused until a given threshold of months.
 *
 * @param string    $fromEmail          Email address to use as sender's (From:) address
 * @param string    $replyToEmail       Email address for replies (Reply-To:)
 * @param \APIAuthentication $api       Credential to warn about
 * @param int       $elapsedMonths      The number of months of non-use so far.
 * @param int       $deleteionThreshold The number of months of no-use which will trigger deletion if reached.
 * @param bool      $isRenewalRequest   Flag indicating the presence
 *                                      or absence of the `r
 *                                      command-line argument.
 * @return void
 */
    private function sendWarningEmail(
        $fromEmail,
        $replyToEmail,
        \APIAuthentication $api,
        $elapsedMonths,
        $deletionThreshold,
        $isRenewalRequest
    ) {
        $subject = "GOCDB: Site API credential deletion notice";

        list($headers, $siteName) = $this->getHeaderContent(
            $api,
            $fromEmail,
            $replyToEmail
        );

        if ($isRenewalRequest) {
            list($userEmail, $body) = $this->getRenewalsBodyContent(
                $api,
                $siteName,
                $elapsedMonths,
                $deletionThreshold
            );
        } else {
            list($userEmail, $body) = $this->getInactiveBodyContent(
                $api,
                $siteName,
                $elapsedMonths,
                $deletionThreshold
            );
        }

        // Send the email (or not, according to local configuration)
        Factory::getEmailService()->send($userEmail, $subject, $body, $headers);
    }

/**
 * Generate a summary report.
 *
 * Generate a report to stdout summarising information about each credential in an array when
 * a dry-run operation is in progress.
 *
 * @param array      $creds          Array of API credential objects to be summarised.
 * @param string     $text           Brief description of the operation which would have been
 *                                   performed without dry-run to be included in the report.
 * @return void
 */
    private function reportDryRun(array $creds, $text)
    {
        if (count($creds) == 0) {
            print("Dry run: No matching credentials found for $text.\n");
            return;
        }

        print("Dry run: Found " . count($creds) . " credentials for $text.\n");

        foreach ($creds as $api) {
            print("Dry run: Processing credential id " . $api->getId() . "\n" .
              "         Identifier: " . $api->getIdentifier() . "\n" .
              "         User email: " . $api->getUser()->getEmail() . "\n" .
              "         Site:       " . $api->getParentSite()->getShortName() . "\n" .
              "         Last used:  " . $api->getLastUseTime() // DateTimeInterface::ISO8601
                                            ->format("Y-m-d\\TH:i:sO") . "\n"
            );
        }
    }

    /**
     * Helper to generate header content.
     *
     * @param \APIAuthentication $api          Credential to warn about.
     * @param string             $fromEmail    Email address to use
     *                                         as sender's (From:) address.
     * @param string             $replyToEmail Email address for replies
     *                                         (Reply-To:)
     *
     * @return array An array containing $headers and $siteName.
     */
    private function getHeaderContent(
        \APIAuthentication $api,
        $fromEmail,
        $replyToEmail
    ) {
        $siteName = $api->getParentSite()->getShortName();
        $siteEmail = $siteName . ' <'
            . $api->getParentSite()->getEmail() . '>';
        $headersArray = array ("From: $fromEmail", "Cc: $siteEmail");

        if (strlen($replyToEmail) > 0 && $fromEmail !== $replyToEmail) {
            $headersArray[] = "Reply-To: $replyToEmail";
        }

        $headers = join("\r\n", $headersArray);

        return [$headers, $siteName];
    }

    /**
     * Helper to generate body content for `renewals` option request.
     *
     * @param \APIAuthentication $api Credential to warn about.
     * @param string $siteName        Site Name.
     * @param int $elapsedMonths      The number of months of non-use so far.
     * @param int $deleteionThreshold The number of months of no-use which
     *                                will trigger deletion if reached.
     *
     * @return array An array containing $userEmail and $body content.
     */
    private function getRenewalsBodyContent(
        \APIAuthentication $api,
        $siteName,
        $elapsedMonths,
        $deletionThreshold
    ) {
        $user = $api->getUser();
        $userEmail = $user->getEmail();

        $body = "Dear " . $user->getForename() . ",\n\n" .
        "The API credential associated with the following identifier\n" .
        "registered at site $siteName has not been renewed for\n" .
        "the last $elapsedMonths months and will be deleted if it " .
        "reaches $deletionThreshold months.\n\n";

        $body .= "Identifier: " . $api->getIdentifier() . "\n";
        $body .= "Owner email: " . $userEmail . "\n";

        $body .= "\n";
        $body .= "Renewal of the credential will prevent its deletion.\n";
        $body .= "\nRegards,\nGOCDB Administrators\n";

        return [$userEmail, $body];
    }

    /**
     * Helper to generate body content for `inactive` option request.
     *
     * @param \APIAuthentication $api Credential to warn about.
     * @param string $siteName        Site Name.
     * @param int $elapsedMonths      The number of months of non-use so far.
     * @param int $deleteionThreshold The number of months of no-use which
     *                                will trigger deletion if reached.
     *
     * @return array An array containing $userEmail and $body content.
     */
    private function getInactiveBodyContent(
        \APIAuthentication $api,
        $siteName,
        $elapsedMonths,
        $deletionThreshold
    ) {
        $user = $api->getUser();
        $userEmail = $user->getEmail();

        $body = "Dear " . $user->getForename() . ",\n\n" .
        "The API credential associated with the following identifier " .
        "registered\nat site $siteName has not been used during the last " .
        "$elapsedMonths months\nand will be deleted if this period of " .
        "inactivity reaches $deletionThreshold months.\n\n";

        $body .= "Identifier: " . $api->getIdentifier() . "\n";
        $body .= "Owner email: " . $userEmail . "\n";

        $body .= "\n";
        $body .= "Use of the credential will prevent its deletion.\n";
        $body .= "\nRegards,\nGOCDB Administrators\n";

        return [$userEmail, $body];
    }
}
