

<div class="rightPageContainer">

	<h1>Add Downtime</h1>
	<div>Please enter all times in UTC. Time now in UTC is approx. ~<?php echo date("H:i", $params['nowUtc']);?>.</div>
	<br>


	<form role="form" name="Add_Downtime" id="addDTForm" action="index.php?Page_Type=Add_Downtime" method="post" onchange="validate()">
		<div class="form-group" id="severityGroup">
			<label for="severity">Severity:</label> <select class="form-control"
				name="SEVERITY" id="severity" size="2">
				<option value="OUTAGE">Outage</option>
				<option value="WARNING">Warning</option>
			</select>
			<span id="severityError" class="label label-danger hidden"></span>
		</div>

		<div class="form-group" id="descriptionGroup">
			<label class="control-label" for="description">Description:</label>
			<div class="controls">
				<input type="text" class="form-control" name="DESCRIPTION" id="description" onkeyup="validate()">
			</div>
			<span id="descriptionError" class="label label-danger hidden"></span> 
		</div>
        
        
		<label for="startDate">Starts on (UTC):</label>
		<div class="smallLabelText">Can't start before 48hrs from current time (DD/MM/YYYY) (HH:MM) <!--(Enter time after: <?php echo $params['twoDaysAgoUtc'];?>)--></div>
		<div class="form-group" id="startDateGroup">
			<!-- Date Picker -->

			<div class="input-group date datePicker" id="startDate">
				<input type='text' name="startDate" class="form-control"
					data-format="DD/MM/YYYY" id="startDateContent" /> <span
					class="input-group-addon"><span
					class="glyphicon glyphicon-calendar"></span> </span>
			</div>

			<!-- Time Picker -->
			<div class="input-group date timePicker" id="startTime">
				<input type='text' class="form-control" 
					id="startTimeContent" /> <span class="input-group-addon"><span
					class="glyphicon glyphicon-time"></span> </span>
			</div>
		</div>
		<div class="form-group"><span id="startError" class="label label-danger hidden"></span>&nbsp</div> <!-- Single space reserves a line for the label -->
		
		<label for="endDate">Ends on (UTC):</label>
		<div class="smallLabelText">(DD/MM/YYYY) (HH:MM)</div>
		<div class="form-group" id="endDateGroup">
			<!-- Date Picker -->

			<div class="input-group date datePicker" id="endDate">
				<input type='text' class="form-control" data-format="DD/MM/YYYY"
					id="endDateContent" /> <span
					class="input-group-addon"><span
					class="glyphicon glyphicon-calendar"></span> </span>
			</div>

			<!-- Time Picker -->
			<div class="input-group date timePicker" id="endTime">
				<input type='text' class="form-control has-error" 
					id="endTimeContent" /> <span class="input-group-addon"><span
					class="glyphicon glyphicon-time"></span> </span>
					
			</div>
		</div>		
				
		<div class="form-group"><span id="endError" class="label label-danger hidden"></span>&nbsp</div>  <!-- Single space reserves a line for the label -->

		<div id="chooseSite" style="width: 50%; float: left; display: inline-block;">
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
				onclick="getSitesServices()">

                <?php
                foreach($sites as $site){
                    echo "<option value=\"{$site->getId()}\">$site</option>";
                }
                ?>
            </select> <br /> <br />
		</div>

		<div id="chooseServices"
			style="width: 50%; float: left; display: inline-block;">
			<!-- Region that will show the services of a chosen site via AJAX-->
		</div>


		<!--  Create a hidden field to pass the confirmed value which at this point is false-->
        <?php $confirmed = false;?>
        <input class="input_input_text" type="hidden" name="CONFIRMED" value="<?php echo $confirmed;?>" />
        <input class="input_input_text" type="hidden" id="startTimestamp" name ="START_TIMESTAMP" value="" />  <!-- Hidden fields that will hold the timetamp value of the selected times -->
        <input class="input_input_text" type="hidden" id="endTimestamp" name ="END_TIMESTAMP" value="" />
		<button type="submit" id="submitDowntime_btn" class="btn btn-default" style="width: 100%" disabled>Add Downtime</button>
	</form>
</div>





<script type="text/javascript">
   
   function validate(){
	    var epValid=false;
	    var severityValid=false;
	    var descriptionValid=false;
	    var startDateValid=false;
	    var endDateValid=false;

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
	    var regEx = /^[A-Za-z0-9\s._(),:;/'\\]{0,4000}$/;    //This line may not appear valid in IDEs but it is
	    var description = $('#description').val();

    	if(description != '' && regEx.test(description) != false){
    		descriptionValid=true;
    		$("#descriptionError").addClass("hidden");    		
    		$('#descriptionGroup').addClass("has-success");
    		
    	}else if(description != ''){
    		descriptionValid=false;
    		$('#descriptionGroup').removeClass("has-success");
	    	$('#descriptionGroup').addClass("has-error");  
	    	if(regEx.test(description) == false){
	    		$("#descriptionError").removeClass("hidden");
	    		$("#descriptionError").text("You have used an invalid character in this description");			    	
	    	}	
    	}else if(description == ''){   //If field is empty then show no errors or colours
    		$("#descriptionError").addClass("hidden");
    		$("#descriptionGroup").removeClass("has-success");
    		$("#descriptionGroup").removeClass("has-error");
    	}

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
            console.log(diff1);
        	var now = moment();    
        	var diff2 = moment.duration(now - mStart);
            console.log(diff2);
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
        $('#startDate, #endDate').datetimepicker({
    		pickTime:false,
    		startDate:getDate()     //Only show dates 48 in the past
    	});
    });

    $(function () {
        $('#startTime, #endTime').datetimepicker({
        	format: 'HH:mm',
            pickDate: false,
            pickSeconds: false,
            pick12HourFormat: false
        });
    });

    function getSitesServices(){
    	var siteId=$('#Select_Sites').val();
    	    	
    	if(siteId != null){ //If the user clicks on the box but not a specific row there will be no input, so catch that here
        	$('#chooseEndpoints').empty(); //Remove any previous content from the endpoints select list         	    	
        	$('#chooseServices').load('index.php?Page_Type=Downtime_view_endpoint_tree&site_id='+siteId,function( response, status, xhr ) {
        		  if ( status == "success" ) {
        			    validate();
        			  }
            });
    	}
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


















