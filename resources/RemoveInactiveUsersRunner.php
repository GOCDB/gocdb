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

$dql = "SELECT u FROM User u";
$users = $entityManager->createQuery($dql)->getResult();

echo "Scanning user login dates in database at: ".date('D, d M Y H:i:s')."\n";

$today = new DateTime();

foreach ($users as $user) {
    echo 'User ID: ' . $user->getId() . "\n";

    $creationDate = $user->getCreationDate();
    $creationStr = $creationDate->format('Y-m-d H:i:s');

    $lastLoginDate = $user->getLastLoginDate();

    if (!$user->getAPIAuthenticationEntities()->isEmpty()) {
       // Prevent creating orphaned API credentials.
       echo "Cannot delete a user with attached API credentials.\n";
       // Move onto the next users.
       continue;
    }

    if ($lastLoginDate) { // null lastLoginDate check
        $interval = $today->diff($lastLoginDate);
    } else { // This might only be run once, since new users always have field filled.
        echo "User has no lastLoginDate (it may have been a very long time.)\n";
        echo "Deleting user.\n";
        deleteUser($user, $entityManager);
        echo "\n";
        // Move onto the next users.
        continue;
    }

    $elapsedMonths = (int) $interval->format('%a') / 30;
    echo 'Months elapsed since last login: ' . $elapsedMonths . "\n";

    if ($elapsedMonths > $deletionThreshold) { // Delete user
        echo "Deleting user\n";
        deleteUser($user, $entityManager);
    } elseif ($elapsedMonths > $warningThreshold) { // Warn user
        echo "Sending user warning email.\n";
        sendWarningEmail($user, $elapsedMonths, $deletionThreshold);
    } elseif ($elapsedMonths < $warningThreshold) { // Do Nothing
        echo "Doing nothing.\n";
    }
}

$entityManager->flush();
echo "Completed ok: ".date('D, d M Y H:i:s');

function usage() {
    echo "Usage: " .
         "RemoveInactiveUsersRunner.php " .
         "--warning_threshold X " .
         "--deletion_threshold Y" .
         "\n\n";
    echo "Options:\n\n";
    echo "--warning_threshold X " .
        "    After this many months, users will receive email warnings." .
        "\n";
    echo "--deletion_threshold Y" .
        "    After this many months, users will be deleted." .
        "\n";

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
    
    $user_ids = $user->getUserIdentifiers();
    // If a user has identifiers, show the user them in the warning email. If not, show the cert DN.
    if (!$user_ids->isEmpty()) {
        foreach ($user_ids as $identifier) {
            $body .= "  - " . $identifier->getKeyName() .": " . $identifier->getKeyValue(). "\n";
        };
    } else {
        $body .= "  - ". $user->getCertificateDn() . "\n";
    }; 

    $body .= "\n";
    $body .= "You can prevent the deletion of this account by visiting the " .
             "GOCDB portal while authenticated with one of the above " .
             "identifiers.\n";


    // Handle all mail related printing/debugging
    \Factory::getEmailService()->send($emailAddress, $subject, $body, $headers);
}
