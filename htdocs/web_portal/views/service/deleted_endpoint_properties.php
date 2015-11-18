<?php
$propertyArray = $params['propArr'];
$service = $params['service'];
$endpoint = $params['endpoint']; 
?>
<div class="rightPageContainer">
    <h1 class="Success">Deletion Successful</h1><br />
    <p>
        The following properties have been successfully removed from endpoint <?php xecho($endpoint->getName());?>:<br/>


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
        <a href="index.php?Page_Type=View_Service_Endpoint&id=<?php echo $endpoint->getId();?>">
            View endpoint</a>
    </p>

</div>