<?php
require_once dirname(__FILE__) . "/../lib/Doctrine/bootstrap.php";
require dirname(__FILE__) . '/../lib/Doctrine/bootstrap_doctrine.php';
require_once dirname(__FILE__) . '/../lib/Gocdb_Services/User.php';

$em = $entityManager;
$dql = "SELECT u FROM User u";
$users = $entityManager->createQuery($dql)->getResult();

echo "Scanning user login dates in database at: ".date('D, d M Y H:i:s')."\n";

$today = new DateTime();
echo $today->format('Y-m-d H:i:s');

foreach ($users as $user) {
    echo 'User ID: ' . $user->getId() . "\n";

    $CreationDate = $user->getCreationDate();
    $CreationStr = $CreationDate->format('Y-m-d H:i:s');

    $lastLoginDate = $user->getLastLoginDate();

    if ($lastLoginDate){ // null lastLoginDate check
        $interval = $today->diff($lastLoginDate);
    } else { // This might only be run once, since new users always have field filled.
        echo "User has no lastLoginDate (it may have been a very long time.)\n";
        echo "Deleting user.\n";
	    deleteUser($user, $em);
        echo "\n";
        return;
    }

    $elapsedMonths = (int) $interval->format('%a') / 30;
    echo 'Months elapsed since last login: ' . $elapsedMonths . "\n";

    if ($elapsedMonths > 18){ // Delete user
        echo "Deleting user\n";
	    deleteUser($user);
    } 
    elseif ($elapsedMonths > 17){ // Warn user
        echo "Requesting user warning email.\n";
	    sendWarningEmail($user);
    } 
    elseif ($elapsedMonths < 17){ // Do Nothing
        echo "Doing nothing.\n";
    }

    echo "\n\n";
}

$em->flush();
echo "Completed ok: ".date('D, d M Y H:i:s');
    
function deleteUser($user){
    echo "User deleted.";
}

function sendWarningEmail($user){
    echo "Email sent.";
}
