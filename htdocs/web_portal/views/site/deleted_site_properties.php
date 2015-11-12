<?php 
$propertyArray = $params['propArr'];
$site = $params['site'];
?>
<div class="rightPageContainer">
    <h1 class="Success">Deletion Successful</h1><br />
    <p>
        The following properties have been successfully removed from site <?php xecho($site->getName());?>:<br/>


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
        <a href="index.php?Page_Type=Site&id=<?php echo $site->getId();?>">
            View Site</a>
    </p>

</div>