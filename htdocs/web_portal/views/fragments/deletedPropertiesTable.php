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
