<div class="rightPageContainer">
    <form name="Add_Downtime" action="index.php?Page_Type=Add_Downtime" method="post" class="inputForm" id="Downtime_Form" name=Downtime_Form>

    	<h1>Add Downtime</h1>
    		<div>Please enter all times in UTC. The current UTC time is <?php echo date("H:i", $params['nowUtc']);?>.</div>
        <br />
        <script language="Javascript">
        /* A mapping of sites to its services */
        var sitesToSEs=
        <?php
            require_once __DIR__.'/custom_json_encode.php';
            // Get a list of services sorted by site
            foreach($params['ses'] as $se) {
                $siteId = $se->getParentSite()->getId();
                $seId = $se->getId();
                $ses[$siteId][]=$seId;
            }

            echo __json_encode($ses);
        ?>
        ;
        </script>

        
    	<span class="input_name">Severity</span>
    	<select class="add_edit_form" name="SEVERITY">
    	   <option value="OUTAGE">OUTAGE</option>
    	   <option value="WARNING">WARNING</option>
    	</select>

        <span class="input_name">
            Description *
            <span class="input_syntax">(Alphanumeric and basic punctuation)</span>
        </span>
        <input class="input_input_text" type="text" name="DESCRIPTION" />

        <span class="input_name">Starts on (Please enter a UTC time after: <?php echo $params['twoDaysAgoUtc'];?>) *
            <span class="input_syntax" >(DD/MM/YYYY HH24:MI)</span>
        </span>

        <input class="input_input_date" type="text" name="START_TIMESTAMP" />
        <script language="JavaScript" src="<?php echo \GocContextPath::getPath()?>javascript/tigra_calendar/calendar_db.js">
        </script>
        <script language="JavaScript">
            new tcal ({
            // form name
            'formname': 'Add_Downtime',
            // input name
            'controlname': 'START_TIMESTAMP'
            });
        </script>

        <span class="input_name">Ends on (Please enter UTC time) *
            <span class="input_syntax" >(DD/MM/YYYY HH24:MI)</span>
        </span>
        <input class="input_input_date" type="text" name="END_TIMESTAMP" />
        <script language="JavaScript">
            new tcal ({
            // form name
            'formname': 'Add_Downtime',
            // input name
            'controlname': 'END_TIMESTAMP'
            });
        </script>

        <br />

        <div style="display: inline-block; vertical-align: top; margin-right: 4em;">
        <?php
                $sites = array();
                // Get a unique list of sites
                foreach($params['ses'] as $se) {
                    $site = $se->getParentSite();
                    $sites[] = $site;
                }
                $sites = array_unique($sites);
                usort($sites, function($a, $b) {
                    return strcmp($a, $b);
                });
                //}
            ?>

            <span class="input_name">
                Select All Services From a Site
            </span>

            <?php
                // calculate the size of the impacted SEs appropriately
                if(sizeof($sites) > 20) {
                    $size = 20;
                } else {
                    $size = sizeof($sites) + 2;
                }
            ?>
            <select class="Downtime_Select" id="Select_Sites" size="
            <?php echo $size; ?>
            " onclick="updateSEs()">

                <?php
                    foreach($sites as $site) {
                        echo "<option value=\"{$site->getId()}\">$site</option>";
                    }
                ?>
            </select>

        <br /><br />
        </div>

        <div style="display: inline-block; margin-left: auto; margin: auto;">
        <span class="input_name">
            Select Individual Services *
        </span>
        <?php
            // calculate the size of the impacted SEs appropriately
            if(sizeof($params['ses']) > 20) {
                $size = 20;
            } else {
                $size = sizeof($params['ses']);
            }
        ?>

        <select class="Downtime_Select" name="Impacted_SEs[]" size="
        <?php echo $size; ?>
        " multiple id="Selected_SEs" style="margin-left: 0em; width: 38em;">
            <?php
                foreach($params['ses'] as $se) {
                    $site = $se->getParentSite();
                    $hostname = $se->getHostname();
                    $serviceType = $se->getServiceType();
                    $id = $se->getId();

                    if(isset($params['selectAll'])) {
                        $selected = ' selected="selected"';
                    } else {
                        $selected = '';
                    }
                    echo "<option value=\"$id\"$selected>$serviceType - $hostname</option>";
                }
            ?>
        </select>
        </div>

        <script type="text/javascript">
        /**
         * This function is called when a user clicks on a site name in the select
         * site box. It selects only SEs from the site that the user clicked.
         */
        function updateSEs() {
            // Get the selected site's name
        	var sitesSelect=document.getElementById('Select_Sites');
        	var selectedSite=sitesSelect.value;

            // Get a reference to the SE selection box
        	var sesSelect=document.getElementById('Selected_SEs');

        	// Clear all the existing SE selections
        	for (i=0; i<sesSelect.options.length; i++) {
    		   sesSelect.options[i].selected=false;
            }

        	// array of object IDs of SEs hosted by the selected site
            var sesToSelect = sitesToSEs[selectedSite];

            /* For each SE in the selection box, see if it's ID is one of the
             * ones from the selected site. If it is, select it */
        	for (i=0; i<sesSelect.options.length; i++) {
            	for(se in sesToSelect) {
                	if(sesToSelect[se] == sesSelect.options[i].value) {
                		sesSelect.options[i].selected=true;
                	}
            	}
        	}
        }
        </script>

        <br /><br />
        <!--  Create a hidden field to pass the confirmed value which at this point is false-->
        <?php $confirmed = false;?>
        <input class="input_input_text" type="hidden" name ="CONFIRMED" value="<?php echo $confirmed;?>" />
        
    	<input class="input_button" type="submit" value="Add Downtime" />
    </form>
</div>