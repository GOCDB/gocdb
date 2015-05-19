<?php
$downtime = $params['dt'];
$format = $params['format'];

$startDate = $downtime->getStartDate();
$endDate = $downtime->getEndDate();
$severity = $downtime->getSeverity();

foreach($downtime->getServices() as $service){
    $affectedSites[] = $service->getParentSite();
    $affectedServices[] = $service;
}

if(count($affectedSites == 1)){
   $siteTimezoneId = $affectedSites[0]->getTimezoneId();  
} else {
   $siteTimezoneId = 'UTC';  
}

foreach($downtime->getEndpointLocations() as $endpoints){
    $affectedEndpoints[] = $endpoints;
}

?>

<!-- @author James McCarthy -->
<div class="rightPageContainer">

	<h1>Edit Downtime</h1>
    <ul>
        <li>Downtimes can only be <b>shortened &rAarr;&lAarr;</b>, add a new downtime to extend.
    </ul>
	<br>


	<form role="form" name="Add_Downtime" id="addDTForm" action="index.php?Page_Type=Edit_Downtime" method="post" onchange="validate()">
		<div class="form-group" id="severityGroup">
			<label for="severity">Severity:</label> <select class="form-control"
				name="SEVERITY" id="severity" size="2">
				<?php if($severity == 'Outage'): ?>
				<option value="OUTAGE" SELECTED>Outage</option>
				<option value="WARNING">Warning</option>
				<?php else: ?>
				<option value="OUTAGE">Outage</option>
				<option value="WARNING" SELECTED>Warning</option>
				<?php endif;?>
			</select>
			<span id="severityError" class="label label-danger hidden"></span>
		</div>

		<div class="form-group" id="descriptionGroup">
			<label class="control-label" for="description">Description:</label>
			<div class="controls">
				<input type="text" class="form-control" name="DESCRIPTION" id="description" onkeyup="validate()" value="<?php xecho($downtime->getDescription());?>">
			</div>
			<span id="descriptionError" class="label label-danger hidden"></span> 
		</div>

        <br>
        <br>


        <div class="form-group" id="timezoneSelectGroup">
            <label class="control-label">Specify Start/End Time In UTC or Site's local Timezone:</label>
            <br>
            <ul>
                <li>If Site timezone is selected, the time will be converted and saved as UTC</li> 
            </ul>
            <input type="radio" name="DEFINE_TZ_BY_UTC_OR_SITE" value="utc" checked>
            UTC (time in UTC since last refresh <?php xecho($params['nowUtc']);?>) &nbsp;&nbsp;&nbsp;&nbsp;
            <input type="radio" name="DEFINE_TZ_BY_UTC_OR_SITE" value="site">
            Site's Timezone 
            <input type="text" id="siteTimezoneText" placeholder="Updated on site selection" readonly>
        </div>    

        
		<label for="startDate">Starts on:</label>
		<div class="smallLabelText">(DD/MM/YYYY) (HH:MM AM/PM)</div>
		<div class="form-group" id="startDateGroup">
			<!-- Date Picker -->

			<div class="input-group date datePicker" id="startDate">
				<input type='text' name="startDate" class="form-control"
					data-format="DD/MM/YYYY" readonly="true" id="startDateContent" /> <span
					class="input-group-addon"><span
					class="glyphicon glyphicon-calendar"></span> </span>
			</div>

			<!-- Time Picker -->
			<div class="input-group date timePicker" id="startTime">
				<input type='text' class="form-control" readonly="true"
					id="startTimeContent" /> <span class="input-group-addon"><span
					class="glyphicon glyphicon-time"></span> </span>
			</div>
		</div>
		<div class="form-group"><span id="startError" class="label label-danger hidden"></span>&nbsp</div> <!-- Single space reserves a line for the label -->
		
		<label for="endDate">Ends on:</label>
		<div class="smallLabelText">(DD/MM/YYYY) (HH:MM AM/PM)</div>
		<div class="form-group" id="endDateGroup">
			<!-- Date Picker -->

			<div class="input-group date datePicker" id="endDate">
				<input type='text' class="form-control" data-format="DD/MM/YYYY"
					readonly="true" id="endDateContent" /> <span
					class="input-group-addon"><span
					class="glyphicon glyphicon-calendar"></span> </span>
			</div>

			<!-- Time Picker -->
			<div class="input-group date timePicker" id="endTime">
				<input type='text' class="form-control has-error" readonly="true"
					id="endTimeContent" /> <span class="input-group-addon"><span
					class="glyphicon glyphicon-time"></span> </span>
					
			</div>
		</div>		
				
		<div class="form-group"><span id="endError" class="label label-danger hidden"></span>&nbsp</div>  <!-- Single space reserves a line for the label -->

		<div id="chooseSite" style="width: 50%; float: left; display: inline-block;">
        <?php
            //$sites = array();
            // Get a unique list of sites
            //foreach($params['dt']->getServices() as $se) {
            //    $site = $se->getParentSite();
            //    $sites[] = $site;
            //}
            
            $sites = array_unique($affectedSites);
            usort($sites, function($a, $b) {
                return strcmp($a, $b);
            });
            ?>

            <label>Select Affected Site</label>

            <?php
                // calculate the size of the impacted SEs appropriately
                if(sizeof($sites) > 20) {
                    $size = 20;
                } else {
                    $size = sizeof($sites) + 2;
            }
            ?>
            <select style="width: 99%; margin-right: 1%"
				class="form-control" id="Select_Sites" name="select_sites" size="10"
				onclick="loadSitesServicesAndEndpoints()">

                <?php
                foreach($sites as $site){
                    $sName = xssafe($site); 
                    echo "<option value=\"{$site->getId()}\" SELECTED>$sName</option>";
                }
                ?>
            </select> <br /> <br />
		</div>

		<div id="chooseServices" style="width: 50%; float: left; display: inline-block;">
			<!-- Region will be loaded by AJAX - shows the services/endpoints of the chosen site -->
		</div>


		<!--  Create a hidden field to pass the confirmed value which at this point is false-->
        <?php $confirmed = false;?>
        <input class="input_input_text" type="hidden" name="CONFIRMED" value="<?php echo $confirmed;?>" />
        <input class="input_input_text" type="hidden" id="startTimestamp" name ="START_TIMESTAMP" value="" />  <!-- Hidden fields that will hold the timetamp value of the selected times -->
        <input class="input_input_text" type="hidden" id="endTimestamp" name ="END_TIMESTAMP" value="" />
        <input class="input_input_text" type="hidden" name ="DOWNTIME_ID" value="<?php echo $downtime->getId();?>" />
		<button type="submit" id="submitDowntime_btn" class="btn btn-default" style="width: 100%" disabled>Edit Downtime</button>
	</form>
</div>



</div>


<script type="text/javascript">
   
   function validate(){
	    var epValid=false;
	    var severityValid=false;
	    var descriptionValid=false;
        var datesValid=false; 

	    //----------Validate the Severity-------------//
    	var severityStatus = $('#severity').val();
    	if(severityStatus != null){
    		severityValid=true;
    		$('#severityGroup').removeClass("has-error");
    		$('#severityGroup').addClass("has-success");
    		$('#severityError').addClass("hidden");
    		
    	}else{
    		severityValid=false;
    		$('#severityGroup').addClass("has-error");  
    		$('#severityError').removeClass("hidden");
    		$("#severityError").text("Please choose a severity for this downtime.");
    	}
	    
	    //----------Validate the Description-------------//
	    //var regEx = /^[-A-Za-z0-9\s._(),:;/'\\]{0,4000}$/;    //This line may not appear valid in IDEs but it is
        var regEx = /^[^`'\";<>]{0,4000}$/;  
	    var description = $('#description').val();

    	if(description != '' && regEx.test(description) != false){
    		descriptionValid=true;
    		$("#descriptionError").addClass("hidden");    		
    		$('#descriptionGroup').addClass("has-success");
    		
    	}else { //if(description != ''){
    		descriptionValid=false;
    		$('#descriptionGroup').removeClass("has-success");
	    	$('#descriptionGroup').addClass("has-error");  
	    	if(regEx.test(description) == false){
	    		$("#descriptionError").removeClass("hidden");
	    		$("#descriptionError").text("You have used an invalid character in this description");			    	
	    	}	
    	}
//        else if(description == ''){   //If field is empty then show no errors or colours
//    		$("#descriptionError").addClass("hidden");
//    		$("#descriptionGroup").removeClass("has-success");
//    		$("#descriptionGroup").removeClass("has-error");
//    	}

    	//----------Validate the Dates-------------//
    	var sDate = $('#startDateContent').val();
    	var eDate = $('#endDateContent').val();
    	var sTime = $('#startTimeContent').val();
    	var eTime = $('#endTimeContent').val();

        //Once all time and dates have been set validate to ensure date is not 48 hours in the past
    	if(sDate != '' && eDate != '' && sTime != '' && eTime != ''){
        	var start = sDate +" "+sTime; 
        	var end = eDate +" "+eTime;    
        	var mStart = moment(start, "DD-MM-YYYY, HH:mm");
        	var mEnd = moment(end, "DD-MM-YYYY, HH:mm");
        	$('#startTimestamp').val(start);
        	$('#endTimestamp').val(end);
        	
            //Check end is after start:
            var diff1 = moment.duration(mEnd - mStart);
            //console.log(diff1);
        	var now = moment();    
        	var diff2 = moment.duration(now - mStart);
            //console.log(diff2);
            //Downtime either ends before it begins or its start is over 48 hours ago 
            if(diff1 <= 0 || diff2 > 172800000){
            	$('#startDateGroup').removeClass("has-success");
            	$('#endDateGroup').removeClass("has-success");
            	if(diff1 <= 0){
            		$('#endError').removeClass("hidden");
            		$("#endError").text("A downtime cannot end before it begins.");
            		$('#endDateGroup').addClass("has-error");                    
                }else{
                	$('#startError').addClass("hidden");
                	$('#endDateGroup').removeClass("has-error");
                }
                if(diff2 > 172800000){
                    $('#startError').removeClass("hidden");
            		$("#startError").text("The start time of the downtime must be within the last 48 hrs");
            		$('#startDateGroup').addClass("has-error");            		
                }else{
                    $('#startError').addClass("hidden");       
                    $('#startDateGroup').removeClass("has-error");             
                }             
                datesValid=false;
            }else{
                datesValid=true;
        		$('#endError').addClass("hidden");
        		$('#startError').addClass("hidden");    
                $('#startDateGroup').addClass("has-success");
                $('#endDateGroup').addClass("has-success");             
        		                            
            }
        }else{
        	datesValid=false;
        }
        
	    //----------Validate the Endpoints-------------//
        //Get the selected options from the select services and endpoints list
        
    	//var selectedEPs = $('#Select_Services').val();
        //If this string contains an e then and endpoint has been selected
        //if(selectedEPs != null){            
        //	$(selectedEPs).each(function(index){    //Iterate over each selected option and check for e.        	
        //   	if(this.indexOf('e') >= 0){
        //        	epValid = true;
        //    		$('#chooseSite').addClass("has-success");
        //    		$('#chooseServices').addClass("has-success");                	
        //    	}else{
        //        	$('#chooseSite').removeClass("has-success");
        //    		$('#chooseServices').removeClass("has-success");                        	
        //    	}
        //	});
        //}else{
        //	epValid=false;
        //	$('#chooseSite').removeClass("has-success");
    	//	$('#chooseServices').removeClass("has-success");
        //}

        var selectedEPs = $('#Select_Services').val();
        //If this string contains an e then and endpoint has been selected
        if(selectedEPs != null){
            epValid = true;
            $('#chooseSite').addClass("has-success");
            $('#chooseServices').addClass("has-success");                	
            
        }else{
        	epValid=false;
        	$('#chooseSite').removeClass("has-success");
    		$('#chooseServices').removeClass("has-success");
        }
        
	    //----------Set the Button based on validate status-------------//

	    if(epValid && severityValid && descriptionValid && datesValid){
	    	$('#submitDowntime_btn').addClass('btn btn-success');
	    	$('#submitDowntime_btn').prop('disabled', false);
	    }else{
	    	$('#submitDowntime_btn').removeClass('btn btn-success');
	    	$('#submitDowntime_btn').addClass('btn btn-default');
	    	$('#submitDowntime_btn').prop('disabled', true);
	    	
	    }
	    
		   
   }


    $(function () {
        $('#siteTimezoneText').val("<?php echo($siteTimezoneId); ?>");  
    }); 
    
    $(function () {
        //Setup datetimepicker
        $('#startDate, #endDate').datetimepicker({
    		pickTime:false,
    		startDate:getDate()     //Only show dates 48hrs in the past
    	});

        $('#startTime, #endTime').datetimepicker({
        	format: 'HH:mm',
            pickDate: false,
            pickSeconds: false,
            pick12HourFormat: false
        });

        //echo out the start and end date into the Jquery setters for the datetime picker
        $('#startDate').data("DateTimePicker").setDate("<?php echo date_format($startDate,"m/d/Y"); ?>");
        $('#endDate').data("DateTimePicker").setDate("<?php echo date_format($endDate,"m/d/Y"); ?>");

        //Set the start and finish times - we echo in the full date but DateTimePicker will only render the time values
        $('#startTime').data("DateTimePicker").setDate("<?php echo date_format($startDate,"m/d/Y H:i"); ?>");
        $('#endTime').data("DateTimePicker").setDate("<?php echo date_format($endDate,"m/d/Y H:i"); ?>");

        // By default select the original affected services and endpoints  
        loadSitesServicesAndEndpoints();
        // Run through the validate and set edit downtime submit button to enabled or not.  
        validate();
        
    });
    
    /**
     * Load the affected services and endpoints of the original downtime (before edit). 
     * This loads an html <select> list that displays all of the site's 
     * services and all of their endpoints, and HIGHLIGHTS ONLY THE SERVICES AND 
     * ENDPOINTS THAT ARE AFFECTED BY THE CURRENT DOWNTIME. 
     * The services and endpoints that are affected by the current downtime can
     * be subsequently re-selected for update.
     * Note, only the services and endpoints belonging to the original site can 
     * be udpated, and at least one service must be selected.   
     * 
     * @returns {undefined}
     */
    function loadSitesServicesAndEndpoints(){
    	var dtId = <?php echo $downtime->getId();?>;
    	var siteId=$('#Select_Sites').val();    	    	
    	
    	$('#chooseServices').empty(); //Remove any previous content from the endpoints select list         	    	
        // The Page_Type handler for 'Edit_Downtime_view_endpoint_tree' in the front controller loads the 
        // following view: 'views/downtime/downtime_edit_view_nested_endpoints_list.php'
        // loading the downtime and the site. 
    	$('#chooseServices').load('index.php?Page_Type=Edit_Downtime_view_endpoint_tree&dt_id='+dtId+'&site_id='+siteId,
          function( response, status, xhr ) {
    		  if ( status == "success" ) {
    			    validate();
    			  }
        });
    	
    }      

    //This function will select all of a services endpoints when the user clicks just the service option in the list
    function selectServicesEndpoint(){

    	//Loop through all the selected options of the list
    	var id = $('#Select_Services').children(":selected").attr("id");
    	console.log(id);
		$('#'+id).prop('selected', true);	    //Set the service parent to be selected
    	
    }   

    //This function uses pure javascript to return the date - 2 days
    function getDate(){
    	   var today = new Date();
    	   var dd = today.getDate()-2;
    	   var mm = today.getMonth()+1; //January is 0!

    	   var yyyy = today.getFullYear();
    	   if(dd<10){
    		   dd='0'+dd
    	   } 
    	   if(mm<10){
    		   mm='0'+mm
    	   } 

    	   date = mm+'/'+dd+'/'+yyyy;
    	   return date;
    }
    
</script>


















