<?php
$propertyArray = $params['propArr'];
$service = $params['service'];
?>
<div class="rightPageContainer">
    <h1 class="Success">Deletion Successful</h1><br />
    <p>
        The following properties have been successfully removed from service <?php xecho($service->getHostName());?>:<br/>


    <table class="table table-striped table-condensed tablesorter">
        <thead>
        <tr>
            <th>Name</th>
            <th>Value</th>
        </tr>
        </thead>
        <tbody>

        <?php
        foreach($propertyArray as $prop) {
            ?>

            <tr>
                <td style="width: 35%;"><?php xecho($prop->getKeyName()); ?></td>
                <td style="width: 35%;"><?php xecho($prop->getKeyValue()); ?></td>
            </tr>
            <?php
        }
        ?>
        </tbody>
    </table>
    </p>
    <p>
        <a href="index.php?Page_Type=Service&id=<?php echo $service->getId();?>">
            View service</a>
    </p>

</div>