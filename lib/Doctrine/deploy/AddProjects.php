<?php
require_once __DIR__ . "/../bootstrap.php";
/* AddProjects.php: Manually inserts projects into
 * the doctrine prototype.
 */
$proj = new Project("EGI");
$entityManager->persist($proj);
$entityManager->flush();
