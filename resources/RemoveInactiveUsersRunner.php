<?php
require_once dirname(__FILE__) . "/../lib/Doctrine/bootstrap.php";
require dirname(__FILE__) . '/../lib/Doctrine/bootstrap_doctrine.php';
require_once dirname(__FILE__) . '/../lib/Gocdb_Services/User.php';
require_once dirname(__FILE__) . '/../lib/Gocdb_Services/Factory.php';

// Configure script options
$longOptions = array(
    // Required Option. After this many months, users will receive email warnings.
    "warning_threshold:",
    // Required Option. After this many months, users will be deleted.
    "deletion_threshold:",
    // Not required. If this option is added, it will be a dry run. Else it won't be.
    "dry_run"
);

$options = getopt("", $longOptions);

// Handle the cases where options were not passed.

if (isset($options["warning_threshold"])) {
    $warningThreshold = $options["warning_threshold"];
} else {
    echo "Error: warning_threshold option must be set.\n";
    usage();
    return;
};

if (isset($options["deletion_threshold"])) {
    $deletionThreshold = $options["deletion_threshold"];
} else {
    echo "Error: deletion_threshold option must be set.\n";
    usage();
    return;
};

if (isset($options["dry_run"])) {
    // If the dry_run option is added, variable dryRun set to true.
    $dryRun = true;
    echo "Dry run is on.\n\n";
} else {
    // If the dry_run option isn't added, variable dryRun set to false.
    $dryRun = false;
    echo "Dry run is off.\n\n";
};

$dql = "SELECT u FROM User u";
$users = $entityManager->createQuery($dql)->getResult();

echo "Scanning user login dates in database at: " . date('D, d M Y H:i:s') . "\n\n";

if ($dryRun == true) {
    // If dry run option has been selected, text is overwritten to InactiveUsersToBeDeleted.csv
    file_put_contents("InactiveUsersToBeDeleted.csv", "userID, lastLoginDate, elapsedMonths\n");
};

$today = new DateTime();

foreach ($users as $user) {
    echo "User ID: " . $user->getId() . "\n";

    $creationDate = $user->getCreationDate();
    $creationStr = $creationDate->format('Y-m-d H:i:s');

    $lastLoginDate = $user->getLastLoginDate();
    if (!$user->getAPIAuthenticationEntities()->isEmpty()) {
       // Prevent creating orphaned API credentials.
       echo "Cannot delete a user with attached API credentials.\n\n";
       // Move onto the next users.
       continue;
    }

    if ($lastLoginDate) {
        // null lastLoginDate check
        $interval = $today->diff($lastLoginDate);
    } else {
        // This might only be run once, since new users always have field filled.
        echo "Deleting this user as it has no last login date " .
        "(it may have been a very long time).\n";
        if ($dryRun == true) {
            // As dry run option is true, writing to file to say who would have been deleted
            file_put_contents("InactiveUsersToBeDeleted.csv", $user->getId() . ", `null`, " .
            "`?`\n", FILE_APPEND);
            echo "\n";
        } else {
            // As dry run option is false, user can be deleted.
            deleteUser($user, $entityManager);
            // Move onto the next users.
            continue;
        }
        continue;
    }

    // 30.4375 is used becuase it is 365.25/12
    $elapsedMonths = (int) $interval->format('%a') / 30.4375;
    // Rounded months since last login to 2 decimal places on line below for easier reading.
    echo 'Months elapsed since last login: ' . round($elapsedMonths, 2) . "\n";

    if ($elapsedMonths > $deletionThreshold) {
        // Delete user, but first check if it is a dry run
        if ($dryRun == true) {
            // Dry run option is set, so append user to be deleted to the file
            echo "This user will be deleted when it isn't a dry run.\n\n";
            file_put_contents("InactiveUsersToBeDeleted.csv", $user->getId() . "," .
            " " . $lastLoginDate->format("D d M Y H:i:s") . "," .
            " " . floor($elapsedMonths) . "\n", FILE_APPEND);
        } else {
            // Delete user as dry run option not set.
            echo "Deleting user.\n";
            deleteUser($user, $entityManager);
        };
    } elseif ($elapsedMonths > $warningThreshold) {
        // Warn user, but first check if it is a dry run
        if ($dryRun == true) {
            // Dry run option is set, so warning email isn't sent
            echo "This user would have been sent a warning email if dry run was not set.\n\n";
        } else {
            // Dry run option not set, so warning email is sent
            echo "Sending the user a warning email.\n\n";
            sendWarningEmail($user, $elapsedMonths, $deletionThreshold);
            echo "\n";
        }
    } elseif ($elapsedMonths < $warningThreshold) {
        // Do Nothing
        echo "Doing nothing.\n\n";
    }
}

$entityManager->flush();
// Check if it is a dry run
if ($dryRun == true) {
    // Telling the user to check the file we appended to see who would have been deleted
    echo "View the contents of InactiveUsersToBeDeleted.csv to see the list of users " .
    "who would have been deleted had this not have been a dry run.\n";
};
// No else statement needed

echo "\n";
echo "Completed ok: " . date('D, d M Y H:i:s') . "\n\n";


function usage() {
    echo "Usage: " .
         "RemoveInactiveUsersRunner.php " .
         "--warning_threshold X " .
         "--deletion_threshold Y " .
         "--dry_run" .
         "\n\n";
    echo "Options:\n\n";
    echo "--warning_threshold X " .
        "    After this many months, users will receive email warnings." .
        "\n";
    echo "--deletion_threshold Y" .
        "    After this many months, users will be deleted." .
        "\n";
    echo "--dry_run" .
        "                 Use dry_run option if you want a dry run.\n";
    echo "\n";
};

function deleteUser($user, $entityManager)
{
    $entityManager->getConnection()->beginTransaction();
    try {
        $entityManager->remove($user);
        $entityManager->flush();
        $entityManager->getConnection()->commit();
        echo "User deleted.\n";
    } catch (\Exception $e) {
        $entityManager->getConnection()->rollback();
        $entityManager->close();
        echo "User not deleted.\n";
        throw $e;
    }
}

function sendWarningEmail($user, $elapsedMonths, $deletionThreshold)
{
    $emailAddress = $user->getEmail();

    // Email content
    $headers = "From: GOCDB <gocdb-admins@mailman.egi.eu>";
    $subject = "GOCDB: User account deletion notice";

    $body = "Dear ". $user->getForename() .",\n\n" .
            "Your GOCDB account, associated with the following " .
            "identifiers, has not been signed into during the last " .
            floor($elapsedMonths) . " months and will be deleted when " .
            "this period of inactivity reaches " .
            $deletionThreshold . " months.\n\n";

    $body .= "Identifiers:\n";
    foreach ($user->getUserIdentifiers() as $identifier) {
        $body .= "  - " . $identifier->getKeyName() .": " . $identifier->getKeyValue(). "\n";
    }

    $body .= "\n";
    $body .= "You can prevent the deletion of this account by visiting the " .
             "GOCDB portal while authenticated with one of the above " .
             "identifiers.\n";


    // Handle all mail related printing/debugging
    \Factory::getEmailService()->send($emailAddress, $subject, $body, $headers);
}
