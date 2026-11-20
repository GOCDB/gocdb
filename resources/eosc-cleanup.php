<?php

/*
 * Script to delete the EOSCCore structure in GOCDB.
 *
 * Desired Behaviour:
 *   Delete the following NGIs.
 *     Collaboration_Tools, ID = 3100
 *     EOSC_AAI, ID = 3092
 *     EOSC_Accounting, ID = 3098
 *     EOSC_Data_Transfer, ID = 3531
 *     EOSC_Front-Office, ID = 3093
 *     EOSC_Helpdesk, ID = 3096
 *     EOSC_Messaging, ID = 3101
 *     EOSC_Monitoring, ID = 3097
 *     EOSC_Observatory, ID = 3551
 *     EOSC_Order_Management, ID = 3095
 *     EOSC_Resource_Catalogue, ID = 3094
 *     EOSC_Topology, ID = 3099
 *
 *   Delete the following Projects
 *     EOSCCore, ID = 3091
 *
 * Usage: php resources/eosc-cleanup.php [delete]
 */

require_once dirname(__FILE__) . "/../lib/Doctrine/bootstrap.php";
require dirname(__FILE__) . '/../lib/Doctrine/bootstrap_doctrine.php';
require_once dirname(__FILE__) . '/../lib/Gocdb_Services/Factory.php';

# An "I really mean it flag" to allow running of the script without deletion
# to ensure it only affects the expected entities.
$delete = isset($argv[1]) && strtolower($argv[1]) === 'delete';

$em = $entityManager;

# Get a user to do the deletions.
$serv = \Factory::getUserService();
# Using a DN feels safer here than using a ID. It's certainly more portable
# between dev and preprod/prod.
$user_dn = "/C=UK/O=eScience/OU=CLRC/L=RAL/CN=greg corbett";
$user = $serv->getUserByPrinciple($user_dn);

# To delete.
$ngi_ids = [
    3100, 3092, 3098, 3531, 3093, 3096, 3101, 3097, 3551, 3095, 3094, 3099
];

# To delete.
$project_ids = [
    3091
];

# Loop through NGIs, possibly deleting.
$serv = \Factory::getNgiService();
foreach ($ngi_ids as $id) {
    $ngi = $serv->getNgi($id);
    echo "Deleting " . $ngi->getName() . "... ";
    if ($delete) {
        $serv->deleteNGI($ngi, $user);
        echo "Done.\n";
    } else {
        echo "Dryrun.\n";
    }
};

# Loop through projects, possibly deleting.
$serv = \Factory::getProjectService();
foreach ($project_ids as $id) {
    $project = $serv->getProject($id);
    echo "Deleting " . $project->getName() . "... ";
    if ($delete) {
        $serv->deleteProject($project, $user);
        echo "Done.\n";
    } else {
        echo "Dryrun.\n";
    }
};
