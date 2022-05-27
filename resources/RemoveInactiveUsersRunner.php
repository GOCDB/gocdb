<?php
require_once dirname(__FILE__) . "/../lib/Doctrine/bootstrap.php";
require dirname(__FILE__) . '/../lib/Doctrine/bootstrap_doctrine.php';
require_once dirname(__FILE__) . '/../lib/Gocdb_Services/User.php';
require_once dirname(__FILE__) . '/../lib/Gocdb_Services/Factory.php';

$dql = "SELECT u FROM User u";
$users = $entityManager->createQuery($dql)->getResult();

echo "Scanning user login dates in database at: ".date('D, d M Y H:i:s')."\n";

$today = new DateTime();

foreach ($users as $user) {
    echo 'User ID: ' . $user->getId() . "\n";

    $creationDate = $user->getCreationDate();
    $creationStr = $creationDate->format('Y-m-d H:i:s');

    $lastLoginDate = $user->getLastLoginDate();

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

    if ($elapsedMonths > 18) { // Delete user
        echo "Deleting user\n";
        deleteUser($user, $entityManager);
    } elseif ($elapsedMonths > 17) { // Warn user
        echo "Sending user warning email.\n";
        sendWarningEmail($user);
    } elseif ($elapsedMonths < 17) { // Do Nothing
        echo "Doing nothing.\n";
    }
}

$entityManager->flush();
echo "Completed ok: ".date('D, d M Y H:i:s');

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

function sendWarningEmail($user)
{
    $emailAddress = $user->getEmail();

    // Email content
    $headers = "From: GOCDB <gocdb-admins@mailman.egi.eu>";
    $subject = "GOCDB: User account deletion notice";

    $body = "Dear ". $user->getForename() .",\n\n" .
            "Your GOCDB account, associated with the following identifiers, " .
            "has not been signed into during the last 17 months and will be " .
            "when this period of inactivity reaches 18 months.\n\n";

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
