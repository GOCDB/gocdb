<?php

/**
 * Maintenance utility for API credential management.
 *
 * Finds API credentials that have not been used for a given number of months and
 * deletes them, then finds credentials that have not been used for a different,
 * smaller number of months and sends warning email messages to the owners telling
 * them the credentials will be deleted if not used before the larger month
 * deadline is reached. A dry-run option is provided to report what would have been
 * done but without taking any actions.
 *
 * Sender and reply-to email addresses are taken from the local_info.xml config file.
 *
 * Usage: php ManageAPICredentials.php [ --help | -h ] [ --dry-run ] \\
 *                                   [[ --warning_threshold | -w ] LITTLE_MONTHS ] \\
 *                                   [[ --deletion_threshold | -d ] BIG_MONTHS ]
 *
 * Warning and deletion thresholds are optional. If missing the respective action is not taken.
 * If both are specified, the deletion threshold MUST be greater than the warning threshold.
 */

namespace gocdb\scripts;

require_once dirname(__FILE__) . "/../lib/Gocdb_Services/APIAuthenticationService.php";
require_once dirname(__FILE__) . "/../lib/Gocdb_Services/Factory.php";
require_once dirname(__FILE__) . "/ManageAPICredentialsOptions.php";

use APIAuthentication;
use DateTime;
use DateInterval;
use DateTimeZone;
use Factory;
use InvalidArgumentException;
use org\gocdb\services\APIAuthenticationService;

require_once dirname(__FILE__) . "/../lib/Doctrine/bootstrap.php";

// Fetch sender and replyTo email addresses from the local config file.
// There is no capability to override based on URL as the webserver does with -
// \Factory::getConfigService()->setLocalInfoOverride($_SERVER['SERVER_NAME']);

$configService = \Factory::getConfigService();
$fromEmail = (string) $configService->getEmailFrom();
$replyToEmail = (string) $configService->getEmailTo();

try {
    $options = new ManageAPICredentialsOptions();

    if ($options->isShowHelp()) {
        return;
    }

    $baseTime = new DateTime("now", new DateTimeZone('UTC'));

    $creds = getCreds($entityManager, $baseTime, $options->getThreshold());

    if ($options->isDeleteEnabled()) {
        $creds = deleteCreds(
            $creds,
            $options->isDryRun(),
            $entityManager,
            $baseTime,
            $options->getDelete()
        );
    }

    if ($options->isWarnEnabled()) {
        warnUsers(
            $creds,
            $options->isDryRun(),
            $baseTime,
            $options->getWarn(),
            $options->getDelete(),
            $fromEmail,
            $replyToEmail
        );
    }
} catch (InvalidArgumentException $except) {
    ManageAPICredentialsOptions::usage($except->getMessage());
}
/**
 * Select API credentials for deletion.
 *
 * Find API credentials which have not been used for a given number of months
 * and delete them or, if dry-run option is true, generate a summary report
 * of the credentils found.
 *
 * @param array             $creds              Array of credentials to process.
 * @param bool              $dryRun             If true no action is taken and a report is generated instead
 * @param \Doctrine\Orm\EntityManager $entitymanager A valid Doctrine Entity Manager
 * @param \DateTime         $baseTime           Time from which interval of no-use is measured
 * @param int               $deleteThreshold    The number of months of no-use which will trigger deletion
 * @return array                                Credentials which were not deleted.
 */

function deleteCreds($creds, $dryRun, $entityManager, $baseTime, $deleteThreshold)
{
    $deletedCreds = [];

    $serv = new APIAuthenticationService();
    $serv->setEntityManager($entityManager);

    /* @var $apiCred APIAuthentication */
    foreach ($creds as $apiCred) {
        if (isOverThreshold($apiCred, $baseTime, $deleteThreshold)) {
            $deletedCreds[] = $apiCred;
            if (!$dryRun) {
                $serv->deleteAPIAuthentication($apiCred);
            }
        }
    }
    if ($dryRun) {
        reportDryRun($deletedCreds, "deleting");
    }

    return array_udiff($creds, $deletedCreds, '\gocdb\scripts\compareCredIds');
}
/**
 * @return boolean true if the credential has not been used within $threshold months, else false
 */
function isOverThreshold(APIAuthentication $cred, DateTime $baseTime, $threshold)
{
    $lastUsed = $cred->getLastUseTime();

    $lastUseMonths = $baseTime->diff($lastUsed)->m;

    return $lastUseMonths >= $threshold;
}
/**
 * Helper function to check if two API credentials have the same id.
 *
 * @return integer zero if equal, -1 if id1 < id2, 1 if id1 > id2
 *
*/
function compareCredIds(APIAuthentication $cred1, APIAuthentication $cred2)
{
    $id1 = $cred1->getId();
    $id2 = $cred2->getId();

    if ($id1 == $id2) {
        return 0;
    };

    return $id1 > $id2 ? 1 : -1;
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
 * @param bool          $dryRun             If true no action is taken and a report is generated instead
 * @param \DateTime     $baseTime           Time from which interval of no-use is measured
 * @param int           $warningThreshold   The number of months of no-use which triggers warning emails
 * @param int           $deleteThreshold    The number of months of no-use which will trigger deletion
 * @param string        $fromEmail          Email address to use as sender's (From:) address
 * @param string        $replyToEmail       Email address for replies (Reply-To:)
 * @return array                            Array of credentials identifed for sending warning emails
 */
function warnUsers(
    $creds,
    $dryRun,
    $baseTime,
    $warningThreshold,
    $deletionThreshold,
    $fromEmail,
    $replyToEmail
) {
    $warnedCreds = [];

    /* @var $api APIAuthentication */
    foreach ($creds as $apiCred) {
        // The credentials list is pre-selected based on the given threshold in the query
        // so this check is probably redundant.
        if (isOverThreshold($apiCred, $baseTime, $warningThreshold)) {
            $lastUsed = $apiCred->getLastUseTime();
            $lastUseMonths = $baseTime->diff($lastUsed)->format('%m');

            if (!$dryRun) {
                sendWarningEmail($fromEmail, $replyToEmail, $apiCred, intval($lastUseMonths), $deletionThreshold);
            }

            $warnedCreds[] = $apiCred;
        }
    }

    if ($dryRun) {
        reportDryRun($warnedCreds, "sending warning emails");
    }

    return array_udiff($creds, $warnedCreds, '\gocdb\scripts\compareCredIds');
}
/**
 * Find API credentials unused for a number of months.
 *
 * Find API credentials which have not been used for a number of months prior to a given base time based
 * on the credential property lastUseTime.
 *
 * @param \Doctrine\Orm\EntityManager $entitymanager A valid Doctrine Entity Manager
 * @param \DateTime     $baseTime       Time from which interval of no-use is measured
 * @param int           $threshold      The number of months of no-use prior to $baseTime to use for selection
 */
function getCreds($entityManager, $baseTime, $threshold)
{
    $qbl = $entityManager->createQueryBuilder();

    $qbl->select('cred')
        ->from('APIAuthentication', 'cred')
        ->where('cred.lastUseTime < :threshold')
        ->andWhere($qbl->expr()->isNotNull("cred.user")); // cope with legacy entities

    $timeThresh = clone $baseTime;

    if ($threshold > 0) {
        $timeThresh->sub(new DateInterval("P" . $threshold . "M"));
    }

    $qbl->setParameter('threshold', $timeThresh->format('Y-m-d 00:00:00'));

    $creds = $qbl->getQuery()->getResult();

    return $creds;
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
 * @return void
 */
function sendWarningEmail(
    $fromEmail,
    $replyToEmail,
    \APIAuthentication $api,
    $elapsedMonths,
    $deletionThreshold
) {
    $user = $api->getUser();
    $userEmail = $user->getEmail();
    $siteName = $api->getParentSite()->getShortName();
    $siteEmail = $siteName . ' <' . $api->getParentSite()->getEmail() . '>';

    $headersArray = array ("From: $fromEmail",
                           "Cc: $siteEmail");
    if (strlen($replyToEmail) > 0 && $fromEmail !== $replyToEmail) {
        $headersArray[] = "Reply-To: $replyToEmail";
    }
    $headers = join("\r\n", $headersArray);

    $subject = "GOCDB: Site API credential deletion notice";

    $body = "Dear " . $user->getForename() . ",\n\n" .
        "The API credential associated with the following identifier registered\n" .
        "at site $siteName has not been used during\n" .
        "the last $elapsedMonths months and will be deleted if this period of inactivity\n" .
        "reaches $deletionThreshold months.\n\n";

    $body .= "Identifier:  " . $api->getIdentifier() . "\n";
    $body .= "Owner email: " . $userEmail . "\n";

    $body .= "\n";
    $body .= "Use of the credential will prevent its deletion.\n";
    $body .= "\nRegards,\nGOCDB Administrators\n";

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
function reportDryRun(array $creds, $text)
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
