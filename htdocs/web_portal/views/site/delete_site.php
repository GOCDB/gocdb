<?php
$site = $params['Site'];
$siteName = $site->getName();
$siteId = $site->getId();
$services = $params['Services']
?>

<div class="rightPageContainer">
    <h1 class="Success">Delete <?php xecho($siteName); ?>?</h1><br />
    <p>
        Are you sure you want to delete the site '
        <a href="index.php?Page_Type=Site&id=<?php echo $siteId;?>">
            <?php xecho($siteName);?>
        </a>'? Deleting sites is a functionality reserved for GOCDB administrators.
    </p>
    <p>
        If you delete this site, the following services will be deleted as well:
        <?php
            foreach($services as $service){
                echo "<br />"
                    . "<a href=\"index.php?Page_Type=Service&id=" . $service->getId() ."\">"
                    .  $service->getHostName() . " (" . $service->getServiceType()->getName() . ")"
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

    <form class="inputForm" method="post" action="index.php?Page_Type=Delete_Site&id=<?php echo $siteId;?>" name="RemoveScope">
        <input class="input_input_hidden" type="hidden" name="UserConfirmed" value="true" />
        <input type="submit" value="Remove this site and all its associated services from GOCDB" class="input_button">
    </form>

</div>