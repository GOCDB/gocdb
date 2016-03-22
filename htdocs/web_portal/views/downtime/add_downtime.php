<!-- 
@author James McCarthy 

DM: The downtime interface/logic needs to be reworked and tidied-up: 
- It needs to allow services from multiple sites to be put into downtime 
(currently it only allows a single site to be selected which limits the
selectable services to those only from that site). 

- There is almost certainly a more elegant way to pass down the UTC offset 
(secs) and timezoneId label for each site (rather than using an AJAX call to query 
for these values on site selection). This will be needed in order to cater for 
multi-site selection. Perhaps pass down a set of DataTransferObjects or JSON string
rather than the Site entities themselves, and specify tz, offset in the DTO/JSON. 

-->

<div class="rightPageContainer">

	<h1>Add Downtime</h1>
    <div>
    <ul>    
        <li>To be <strong>SCHEDULED</strong>, start must be <strong>24hrs</strong> in the future</li>
        <?php /*<li>Time in UTC since last page refresh <strong><?php xecho($params['nowUtc']);?></strong></li>*/ ?>
        <li>Time in UTC: <mark><label id="timeinUtcNowLabel"></label></mark></li>
    </ul>
    </div>
	<br>


	<form role="form" name="Add_Downtime" id="addDTForm" action="index.php?Page_Type=Add_Downtime" method="post">

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
				<input type="text" class="form-control" name="DESCRIPTION" id="description"> 
			</div>
			<span id="descriptionError" class="label label-danger hidden"></span> 
		</div>

        <br>
        <br>
                
        <div class="alert alert-warning" role="alert">
          You may need to update your site's timezone from UTC to the required value for your site - this version of GOCDB uses
          a new timezone logic with new timezone labels taken from the 'Olsen' database. Where possible, the
          old values have been copied over, but some legacy values including those starting 'Etc/' are not supported and so UTC 
          is specified by default.
        </div>


        
        <div class="form-group" id="timezoneSelectGroup">
            <!--If Site timezone is selected, the time will be converted and saved as UTC-->
            <label class="control-label">Enter Times In:</label>&nbsp;&nbsp;&nbsp;&nbsp;
            <input type="radio" name="DEFINE_TZ_BY_UTC_OR_SITE" id="utcRadioButton" value="utc" checked>UTC&nbsp;&nbsp;&nbsp;&nbsp; 
            <input type="radio" name="DEFINE_TZ_BY_UTC_OR_SITE" id="siteRadioButton" value="site">Site Timezone 
            <input type="text" id="siteTimezoneText" value="UTC" placeholder="Updated on site selection" readonly> 
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<mark><label id="schedulingStatusLabel"></label></mark>
        </div>    


        
		<label for="startDate">Starts on:</label>
        <mark><label id="startUtcLabel"></label></mark> 
        <div class="form-group" id="startDateGroup">
			<!-- Date Picker -->
			<div class="input-group date datePicker" id="startDate">
				<input type='text' name="startDate" class="form-control"
					data-format="DD/MM/YYYY" id="startDateContent"/> 
                <span class="input-group-addon">
                    <span class="glyphicon glyphicon-calendar" ></span> 
                </span>
			</div>

			<!-- Time Picker -->
			<div class="input-group date timePicker" id="startTime">
				<input type='text' class="form-control" 
					id="startTimeContent"/> 
                <span class="input-group-addon">
                    <span class="glyphicon glyphicon-time" ></span> 
                </span>
			</div>
		</div>
		<div class="form-group"><span id="startError" class="label label-danger hidden"></span>&nbsp</div> <!-- Single space reserves a line for the label -->
		
		<label for="endDate">Ends on:</label>
        <mark><label id="endUtcLabel"></label></mark>
		<div class="form-group" id="endDateGroup"">
			<!-- Date Picker -->
			<div class="input-group date datePicker" id="endDate">
				<input type='text' class="form-control" data-format="DD/MM/YYYY"
					id="endDateContent" /> 
                <span class="input-group-addon">
                    <span class="glyphicon glyphicon-calendar"></span> 
                </span>
			</div>

			<!-- Time Picker -->
			<div class="input-group date timePicker" id="endTime">
				<input type='text' class="form-control has-error" 
					id="endTimeContent" /> 
                <span class="input-group-addon">
                    <span class="glyphicon glyphicon-time"></span> 
                </span>
			</div>
		</div>		
		<div class="form-group"><span id="endError" class="label label-danger hidden"></span>&nbsp</div>  <!-- Single space reserves a line for the label -->


        <div>
            
             
        </div>
        

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
				onclick="getSitesServices();onSiteSelected();">

                <?php
                foreach($sites as $site){
                    $siteName = $site->getName(); 
                    $ngiName = $site->getNgi()->getName(); 
                    $label = xssafe($site."   (".$ngiName.")"); 
                    echo "<option value=\"{$site->getId()}\">$label</option>";
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
        <input class="input_input_text" type="hidden" id="startTimestamp" name ="START_TIMESTAMP" value="" />  <!-- Hidden fields that will hold the timestamp value of the selected times -->
        <input class="input_input_text" type="hidden" id="endTimestamp" name ="END_TIMESTAMP" value="" />
		<button type="submit" id="submitDowntime_btn" class="btn btn-default" style="width: 100%" disabled>Add Downtime</button>
	</form>
</div>





<script type="text/javascript">
   /* global moment */
   /* global TARGETTIMEZONEID: true */
   /* global TARGETTIMEZONEOFFSETFROMUTCSECS: true */
   /* global M_START_UTC: true */
   /* global M_END_UTC: true */
    
   var TARGETTIMEZONEID = "UTC";  // e.g. Europe/London 
   var TARGETTIMEZONEOFFSETFROMUTCSECS = 0; // e.g. 3600 if TARGETTIMEZONEID is ahead of UTC by 1hr
   var M_START_UTC = null; 
   var M_END_UTC = null; 

   $(document).ready(function() {
        // configure date pickers 
        $('#startDate, #endDate').datetimepicker({
            format: 'DD/MM/YYYY',

            //pickTime:false,
    		//startDate:getDate()     //Only show dates 48 in the past
    	});
        // configure time pickers 
        $('#startTime, #endTime').datetimepicker({
        	format: 'HH:mm',
            //pickDate: false,
            //pickSeconds: false,
            //pick12HourFormat: false
        });
   
        // invoke ajax call to get selected sites timezoneId and offset (if 
        // a site is specified in the URL bar) 
        updateSiteTimezoneVars(getURLParameter('site'));

       // Add the jQuery form change event handlers
       $("#addDTForm").find(":input").change(function(){
           validate();
       });

       $("#timezoneSelectGroup").find(":input").change(function(){
           updateStartEndTimesInUtc();
       });

       // The bootstrap datetimepickers don't fire the change event
       // but they trigger a change.dp event instead so a separate 
       // jQuery handler is needed.
       $('.date').on("dp.change", function(e) {
           updateStartEndTimesInUtc();
           validate();
       });



    });

    /**
     * Get the start/end time strings and calculate the UTC equivalents using 
     * the {@link TARGETTIMEZONEID} and {@link TARGETTIMEZONEOFFSETFROMUTCSECS} 
     * values, then update the global vars {@link M_START_UTC} and {@link M_END_UTC}. 
     * and the startTimestamp and endTimestamp form submission parameters.  
     * Finally update the GUI labels.  
     * 
     * @returns {null}
     */
    function updateStartEndTimesInUtc(){
        // get date/time text strings from GUI
        var sDate = $('#startDateContent').val();
    	var eDate = $('#endDateContent').val();
    	var sTime = $('#startTimeContent').val();
    	var eTime = $('#endTimeContent').val(); 

        // calculate the start date time in UTC 
        if(sDate && sTime){
            // First Parse the input string as UTC
            // (use moment.utc(), otherwise moment parses in current timezone)
        	var start = sDate +" "+sTime; 
        	var mStart = moment.utc(start, "DD-MM-YYYY, HH:mm"); // parse in utc
            //console.log(mStart); 
            // Then update utc time to time in target timezone; 
            // if SiteTimezone RB is selected, subtract offset from time to 
            // get time in specified tz 
            if($('#siteRadioButton').is(':checked')){ 
               mStart.subtract(TARGETTIMEZONEOFFSETFROMUTCSECS, 's'); 
            }
            M_START_UTC = mStart; 
            //console.log(M_START_UTC.format("DD-MM-YYYY, HH:mm")); 
            //console.log(M_START_UTC.format()); 
            $('#startUtcLabel').text(M_START_UTC.format("DD/MM/YYYY HH:mm")+' UTC'); 
            $('#startTimestamp').val(M_START_UTC.format("DD/MM/YYYY HH:mm")); 

            // refresh the SCHEDULED/UNSCHEDULED label  
            refreshScheduledStatus();  
        }
        // calculate the end date time in UTC 
        if(eDate && eTime){
            // First Parse the input string as UTC
            // (use moment.utc(), otherwise moment parses in current timezone)
        	var end = eDate +" "+eTime;    
        	var mEnd = moment.utc(end, "DD-MM-YYYY, HH:mm"); // parse in utc
            //console.log(mEnd);
            // Then update utc time to time in target timezone; 
            // if SiteTimezone RB is selected, subtract offset from time to 
            // get time in specified tz 
            if($('#siteRadioButton').is(':checked')){  
                mEnd.subtract(TARGETTIMEZONEOFFSETFROMUTCSECS, 's'); 
            }
            M_END_UTC = mEnd; 
            //console.log(M_END_UTC.format("DD-MM-YYYY, HH:mm")); 
            //console.log(M_END_UTC.format()); 
            $('#endUtcLabel').text(M_END_UTC.format("DD/MM/YYYY HH:mm")+' UTC'); 
            $('#endTimestamp').val(M_END_UTC.format("DD/MM/YYYY HH:mm")); 
        }
   }

   /*
    * Dynamically update the UTC time label and SCHEDULED/UNSCHEDULED labels. 
    */
   setInterval(refreshScheduledStatus,5000);
   setInterval(refreshCurrentUtcTimeLabel,1000); 
  
   /**
    * Update the UTC time label, executed every second. 
    * @returns {null}
    */
   function refreshCurrentUtcTimeLabel(){
       var nowUtc = moment.utc(); 
       $('#timeinUtcNowLabel').text(nowUtc.format("DD/MM/YYYY HH:mm:ss"));  
   } 

   /**
    * Update the SCHEDULED/UNSCHEDULED status label depending on 
    * currently specified start/end time values, executed every 5 secs.   
    * @returns {null}
    */
   function refreshScheduledStatus(){
        $('#schedulingStatusLabel').text(''); 
        var nowUtc = moment.utc();    
        var duration24hrs = moment.duration(24, 'hours'); 
        if(M_START_UTC){
            if( M_START_UTC > (nowUtc + duration24hrs)){
                $('#schedulingStatusLabel').text('SCHEDULED'); 
            } else {
                $('#schedulingStatusLabel').text('UNSCHEDULED'); 
            }
        }
            
        /*var sDate = $('#startDateContent').val();
    	var sTime = $('#startTimeContent').val(); 
        console.log("sDate: ["+sDate+"] sTime: ["+sTime+"]"); 

        // calculate the start date time in UTC 
        if(sDate && sTime){
            // First Parse the input string as UTC
            // (use moment.utc(), otherwise moment parses in current timezone)
        	var start = sDate +" "+sTime; 
        	var mStart = moment.utc(start, "DD-MM-YYYY, HH:mm"); // parse in utc
            
            // Is M_START_UTC >24hrs in future (SCHEDULED) or <24hrs (UNSCHEDULED) 
            // this logic should go into a self-refresh loop. 
            //console.log("mStart utc: "+mStart.format("DD/MM/YYYY HH:mm:ss")); 

            // Then update utc time to time in target timezone; 
            // if SiteTimezone RB is selected, subtract offset from time to 
            // get time in specified tz 
            if($('#siteRadioButton').is(':checked')){ 
               mStart.subtract(TARGETTIMEZONEOFFSETFROMUTCSECS, 's'); 
            }
            
            $('#schedulingStatusLabel').text(''); 
            var nowUtc = moment.utc();    
            var duration24hrs = moment.duration(24, 'hours'); 
            if( mStart > (nowUtc + duration24hrs)){
               $('#schedulingStatusLabel').text('SCHEDULED'); 
            } else {
               $('#schedulingStatusLabel').text('UNSCHEDULED'); 
            }
        }*/
    }

    function validate(){
	    var epValid=false;
	    var severityValid=false;
	    var descriptionValid=false;
        var datesValid = false; 

	    //----------Validate the Severity-------------//
    	var severityStatus = $('#severity').val();
    	if(severityStatus){
    		severityValid=true;
    		$('#severityGroup').removeClass("has-error");
    		$('#severityGroup').addClass("has-success");
    		$('#severityError').addClass("hidden");
    		
    	}else{
    		severityValid=false;
    		$('#severityGroup').addClass("has-error");  
    		$('#severityError').removeClass("hidden");
    		//$("#severityError").text("Please choose a severity for this downtime.");
    	}
	    
	    //----------Validate the Description-------------//
	    //var regEx = /^[A-Za-z0-9\s._(),:;/'\\]{0,4000}$/;    //This line may not appear valid in IDEs but it is
	    var regEx = /^[^`'\";<>]{0,4000}$/;    //This line may not appear valid in IDEs but it is
	    var description = $('#description').val();
    	if(description && regEx.test(description) !== false){
    		descriptionValid=true;
    		$("#descriptionError").addClass("hidden");    		
    		$('#descriptionGroup').addClass("has-success");
    		
    	} else { 
    		descriptionValid=false;
    		$('#descriptionGroup').removeClass("has-success");
	    	$('#descriptionGroup').addClass("has-error");  
	    	if(regEx.test(description) === false){
	    		$("#descriptionError").removeClass("hidden");
	    		$("#descriptionError").text("You have used an invalid character in this description");			    	
	    	}	
    	}

        //----------Validate the dates-------------//
        datesValid = validateUtcDates(); //validateDates(); 
        
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
        if(selectedEPs){
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

    function validateUtcDates(){
       var datesValid = false; 
       if(M_END_UTC && M_START_UTC){
        //Check end is after start:
            var diff1 = moment.duration(M_END_UTC - M_START_UTC);
            //console.log(diff1);
        	var now = moment.utc();    
        	var diff2 = moment.duration(now - M_START_UTC);
            //console.log(diff2);
            //Downtime either ends before it begins or its start is over 48 hours ago 
            if(diff1 <= 0 || diff2 > 172800000){  // if (diff2 > 2daysInMilliSecs) 
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
       } 
       return datesValid; 
    }
    
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

    /**
     * If a site is selected, get the site's timezone and update the 
     * siteTimezoneText text input. 
     * @returns {Null} 
     */
    function onSiteSelected(){
        var siteId=$('#Select_Sites').val();
        updateSiteTimezoneVars(siteId); 
    }

    /**
     * Update the TARGETTIMEZONEID and TARGETTIMEZONEOFFSETFROMUTCSECS global 
     * vars and update text area with the timezoneId label 
     * @param {int} siteId
     * @returns {null}
     */
    function updateSiteTimezoneVars(siteId){
        if(siteId){ 
           // use ajax to get the selected site's timezone label and offset 
           // and update display+vars
           //console.log('fetching selected site timezone label');
           $.get('index.php', {Page_Type: 'Add_Downtime', siteid_timezone: siteId}, 
           function(data){
              var jsonRsp = JSON.parse(data); 
              // update global variables - used when calculating DT rules 
              TARGETTIMEZONEID = jsonRsp[0]; 
              TARGETTIMEZONEOFFSETFROMUTCSECS = jsonRsp[1]; //Returns the targetTimezone offset in seconds from UTC 
              //console.log("updateSiteTimezoneVars, siteId: ["+siteId+"] TARGETTIMEZONEID: ["+TARGETTIMEZONEID+"] TARGETTIMEZONEOFFSETFROMUTCSECS: ["+TARGETTIMEZONEOFFSETFROMUTCSECS+"]"); 
              $('#siteTimezoneText').val(TARGETTIMEZONEID); 
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

    function getURLParameter(name) {
        // see: http://stackoverflow.com/questions/11582512/how-to-get-url-parameters-with-javascript
        return decodeURIComponent((new RegExp('[?|&]' + name + '=' + '([^&;]+?)(&|#|;|$)').exec(location.search)||[,""])[1].replace(/\+/g, '%20'))||null
    }
   


    /*function validateDates(){
        var datesValid = false; 
    	var sDate = $('#startDateContent').val();
    	var eDate = $('#endDateContent').val();
    	var sTime = $('#startTimeContent').val();
    	var eTime = $('#endTimeContent').val();

        //Once all time and dates have been set validate to ensure date is not 48 hours in the past
    	if(sDate && eDate && sTime  && eTime){
        	var startString = sDate +" "+sTime; 
        	var endString = eDate +" "+eTime;    
            // moment parses the input string in current LOCAL timezone 
            // This is what we want because we will be comparing time durations 
            // against time now using 'moment()' which returns now in current timezone. 
        	var mStart = moment(startString, "DD-MM-YYYY, HH:mm");  
        	var mEnd = moment(endString, "DD-MM-YYYY, HH:mm");
            
        	//$('#startTimestamp').val(startString);
        	//$('#endTimestamp').val(endString);
        	
            //Check end is after start:
            var diff1 = moment.duration(mEnd - mStart);
            //console.log(diff1);
        	var now = moment();    
        	var diff2 = moment.duration(now - mStart);
            //console.log(diff2);
            //Downtime either ends before it begins or its start is over 48 hours ago 
            if(diff1 <= 0 || diff2 > 172800000){  // if (diff2 > 2daysInMilliSecs) 
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
        }
        return datesValid; 
    }*/
    
</script>


















