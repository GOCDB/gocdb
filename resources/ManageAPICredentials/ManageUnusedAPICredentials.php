<?php

/**
 * Maintenance utility for API credential management.
 *
 * Finds API credentials that have not been used for a given
 * number of months and deletes them, then finds credentials that have not
 * been used for a different, smaller number of months and sends warning email
 * messages to the owners telling them the credentials will be deleted if not
 * used before the larger month deadline is reached.
 * A dry-run option is provided to report what would have been done but without
 * taking any actions.
 *
 * Sender and reply-to email addresses are taken from
 * the local_info.xml config file.
 *
 * Usage: php ManageUnusedAPICredentials.php [ --help | -h ] [ --dry-run ] \\
 *                                   [[ --warning_threshold | -w ] LITTLE_MONTHS ] \\
 *                                   [[ --deletion_threshold | -d ] BIG_MONTHS ]
 *
 * Warning and deletion thresholds are optional.
 * If missing the respective action is not taken.
 * If both are specified, the deletion threshold MUST be
 * greater than the warning threshold.
 */

namespace org\gocdb\scripts;

require_once dirname(__FILE__) . "/../../lib/Gocdb_Services/Factory.php";
require_once dirname(__FILE__) . "/ManageAPICredentialsActions.php";
require_once dirname(__FILE__) . "/ManageUnusedAPICredentialsOptions.php";

use DateTime;
use DateTimeZone;
use InvalidArgumentException;

require_once dirname(__FILE__) . "/../../lib/Doctrine/bootstrap.php";

// Fetch sender and replyTo email addresses from the local config file.
// There is no capability to override based on URL as the webserver does with -
$configService = \Factory::getConfigService();
$fromEmail = (string) $configService->getEmailFrom();
$replyToEmail = (string) $configService->getEmailTo();

try {
    $options = new ManageUnusedAPICredentialsOptions();

    if ($options->isShowHelp()) {
        return;
    }

    $baseTime = new DateTime("now", new DateTimeZone('UTC'));

    $actions = new ManageAPICredentialsActions(
        $options->isDryRun(),
        $entityManager,
        $baseTime
    );

    $creds = $actions->getCreds($options->getThreshold(), 'lastUseTime');

    if ($options->isDeleteEnabled()) {
        $creds = $actions->deleteCreds(
            $creds,
            $options->getDelete()
        );
    }

    if ($options->isWarnEnabled()) {
        $actions->warnUsers(
            $creds,
            $options->getWarn(),
            $options->getDelete(),
            $fromEmail,
            $replyToEmail
        );
    }
} catch (InvalidArgumentException $except) {
    ManageUnusedAPICredentialsOptions::usage($except->getMessage());
}
