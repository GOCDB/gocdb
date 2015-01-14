<?php

//$ses = $params['ses'];
$impactedServices = $params['impactedSes'];
$dt = $params['dt'];
$format = $params['format'];
?>
<div class="rightPageContainer">
    <form name="Edit_Downtime" action="index.php?Page_Type=Edit_Downtime" method="post" class="inputForm">
    	<h1>Edit Downtime</h1>
    	<br />

        <span class="input_name">
            Impacted Services *
        </span>
        <?php
            // calculate the size of the impacted SEs appropriately
            if(sizeof($impactedServices) > 20) {
                $size = 20;
            } else {
                $size = sizeof($impactedServices);
            }
        ?>

        <select class="add_edit_form" name="Impacted_SEs[]" size="
        <?php echo $size; ?>
        " multiple id="Selected_SEs">
            <?php
                //foreach($ses as $se) {
                foreach($impactedServices as $se){
                    $site = $se->getParentSite();
                    $id = $se->getId();

                    $selected = '';
                    //foreach($ses as $)
                    //foreach($impactedServices as $impacted) {
                    //    if($impacted == $se) {
                            $selected = 'selected="selected"';
                    //    }
                    //}

                    echo "<option value=\"$id\" $selected>$se</option>";
                }
            ?>
        </select>

        <span class="input_name">Severity</span>
        <select class="add_edit_form"name="SEVERITY">
           <?php

            if($dt->getSeverity() == 'WARNING') {
                echo '
                    <option value="OUTAGE">OUTAGE</option>
                    <option value="WARNING" selected="selected">WARNING</option>
                ';
            } else if($dt->getSeverity() == 'OUTAGE') {
                echo '
                    <option value="OUTAGE" selected="selected">OUTAGE</option>
                    <option value="WARNING">WARNING</option>
                ';
            }
           ?>

        </select>

        <span class="input_name">
            Description *
            <span class="input_syntax">(Alphanumeric and basic punctuation)</span>
        </span>
        <input class="input_input_text" type="text"name="DESCRIPTION" value="<?php echo $dt->getDescription();?>"/>

        <span class="input_name">Starts on (Please enter UTC time) *
            <span class="input_syntax" >(DD/MM/YYYY HH24:MI)</span>
        </span>

        <input class="input_input_date" type="text" name="START_TIMESTAMP" value="<?php echo $dt->getStartDate()->format($format);?>"/>
        <script language="JavaScript" src="<?php echo \GocContextPath::getPath()?>javascript/tigra_calendar/calendar_db.js">
        </script>
        <script language="JavaScript">
            new tcal ({
            // form name
            'formname': 'Edit_Downtime',
            // input name
            'controlname': 'START_TIMESTAMP'
            });
        </script>

        <span class="input_name">Ends on (Please enter UTC time) *
            <span class="input_syntax" >(DD/MM/YYYY HH24:MI)</span>
        </span>
        <input class="input_input_date" type="text" name="END_TIMESTAMP" value="<?php echo $dt->getEndDate()->format($format);?>"/>
        <script language="JavaScript">
            new tcal ({
            // form name
            'formname': 'Edit_Downtime',
            // input name
            'controlname': 'END_TIMESTAMP'
            });
        </script>
        <input class="input_input_hidden" type="hidden" value="<?php echo $dt->getId(); ?>" name="ID">
        <br />
        <input class="input_button" type="submit" value="Save Changes" />
    </form>
</div>