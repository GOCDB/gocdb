<?php
$ngi = $params['NGI'];
$ngiName = $ngi->getName();
$ngiId = $ngi->getId();
$services = $params['Services'];
$sites = $params['Sites'];
?>

<div class="rightPageContainer">
    <h1 class="Success">Delete <?php xecho( $ngiName); ?>?</h1><br />
    <p>
        Are you sure you want to delete the NGI '
        <a href="index.php?Page_Type=NGI&amp;id=<?php echo $ngiId;?>">
            <?php xecho($ngiName);?>
        </a>'? Deleting NGIs is a functionality reserved for GOCDB administrators
        and should. be undertaken with caution.
    </p>
    <p>
        If you delete <?php xecho($ngiName); ?>, the following sites will be deleted as well:
        <?php
            foreach($sites as $site){
                echo "<br />"
                    . "<a href=\"index.php?Page_Type=Site&amp;id=" . $site->getId() ."\">"
                    .  xssafe($site->getName())
                    . "</a> ";
            }
        ?>
    </p>
    <p>
        As a consequence, the following services will be deleted as well:
        <?php
            foreach($services as $service){
                echo "<br />"
                    . "<a href=\"index.php?Page_Type=Service&amp;id=" . $service->getId() ."\">"
                    .  xssafe($service->getHostName()) . " (" . xssafe($service->getServiceType()->getName()) . ")"
                    . "</a> ";
            }
        ?>
    </p>
    <p>
        Any down times associated with these services, and only these services,
        will also be removed from GOCDB.
    </p>
    <p>
        Are you sure you wish to continue?
    </p>

    <form class="inputForm" method="post" action="index.php?Page_Type=Admin_Delete_NGI&id=<?php echo $ngiId;?>" name="RemoveScope">
        <input class="input_input_hidden" type="hidden" name="UserConfirmed" value="true" />
        <input type="submit" value="Remove this NGI and all its associated sites and services from GOCDB" class="gocdb_btn_danger gocdb_btn_props">
    </form>

</div>
