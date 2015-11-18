<?php
$propertyArray = $params['propArr'];
$service = $params['service'];
?>

<div class="rightPageContainer">
	<h1 class="Success">Delete Endpoint Properties</h1><br/>
    <p>
        You are about to delete the following properties:<br/><br/>
    </p>

    <table id="propertiesToBeDeletedTable" class="table table-striped table-condensed tablesorter">
        <thead>
        <tr>
            <th>Name</th>
            <th>Value</th>
            <th>Delete?</th>
        </tr>
        </thead>
        <tbody>

        <?php
        //$num = 2;
        foreach($propertyArray as $prop) {
            ?>

            <tr>
                <td style="width: 35%;"><?php xecho($prop->getKeyName()); ?></td>
                <td style="width: 35%;"><?php xecho($prop->getKeyValue()); ?></td>
                <td style="width: 10%;"><input type='checkbox' form="RemoveEndpointPropertiesForm" name='selectedPropIDs[]' value="<?php echo $prop->getId();?>" checked/></td>
            </tr>
            <?php
        }
        ?>
        </tbody>
    </table>
    <p>
        Are you sure you wish to continue?
    </p>

    <form class="inputForm" method="post" action="index.php?Page_Type=Delete_Endpoint_Properties" name="RemoveEndpointProperties" id="RemoveEndpointPropertiesForm">
        <input class="input_input_hidden" type="hidden" name="UserConfirmed" value="true" />
        <input class="input_input_text" type="hidden" name ="serviceID" value="<?php echo $service->getId();?>" />
        <input type="submit" value="Remove selected service properties from GOCDB" class="input_button">
    </form>
</div>