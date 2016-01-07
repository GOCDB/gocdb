<?php
define('DATE_FORMAT', 'd-m-Y H:i');
$td1 = '<td>';
$td2 = '</td>';
//throw new \Exception(var_dump($params))
?>

<!---
This page will show two tables, one of active downtimes and one of downtimes coming between 1-4 weeks. The user
can select the time period for planned downtimes to show. Extra information is shown by expanding a sub table 
from the main downtimes table. This table is shown and hidden by creating dynamically named tables and using 
javascript to show and hide these tables. 
--->
<div class="rightPageContainer">

    <div style="float: left;">
        <img src="<?php echo \GocContextPath::getPath() ?>img/down_arrow.png" class="pageLogo"/>
    </div>
    <h1>Downtime Calendar</h1>

    <div class="siteContainer container-fluid">

        <div class="row">




            <div class="col-sm-3">
                <span><a href="index.php?Page_Type=Scope_Help">Scopes:</a> </span>
                <br/>

                <select id="scopeSelect" class="" style="width: 150px" multiple="multiple" name="mscope[]">
                    <?php foreach ($params['scopes'] as $scope) { ?>
                        <option value="<?php xecho($scope->getName()); ?>"
                            <?php if (in_array($scope->getName(), $params['selectedScopes']) || count($params['selectedScopes']) == 0) {
                                echo ' selected';
                            } ?> >
                            <?php xecho($scope->getName()); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>

            <div class="col-sm-3">
                Sites:
                <br/>
                <select id="siteSelect" class="" style="width: 150px" multiple="multiple" name="site[]">
                    <?php foreach ($params['sites'] as $site) { ?>
                        <option value="<?php xecho($site->getName()); ?>"
                            <?php if (in_array($site->getName(), $params['selectedSites']) || count($params['selectedSites']) == 0) {
                                echo ' selected';
                            } ?> >
                            <?php xecho($site->getName()); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>

            <div class="col-sm-6">
                <div class="col-sm-4">
                    NGI:
                    <br/>
                    <select id="ngi_selector"  class="selectpicker" >
                        <option value="ALL">All</option>
                        <?php foreach ($params['ngis'] as $ngi) { ?>
                            <option value="<?php xecho($ngi->getName()); ?>"
                                <?php if ($ngi->getName() == $params['selectedNGI']) {
                                    echo ' selected';
                                } ?> >
                                <?php xecho($ngi->getName()); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>

                <div class="col-sm-4">
                    Severity:

                    <select class="selectpicker" id="severity_selector">
                        <option value="ALL">All</option>
                        <option value="OUTAGE" <?php if ($params['severity'] == "OUTAGE") {
                            echo ' selected';
                        } ?>>Outage
                        </option>
                        <option value="WARNING" <?php if ($params['severity'] == "WARNING") {
                            echo ' selected';
                        } ?>>Warning
                        </option>
                    </select>
                </div>

                <div class="col-sm-4">
                    Classification:
                    <select class="selectpicker" id="class_selector">
                        <option value="ALL">All</option>
                        <option value="SCHEDULED" <?php if ($params['classification'] == "SCHEDULED") {
                            echo ' selected';
                        } ?>>Scheduled
                        </option>
                        <option value="UNSCHEDULED" <?php if ($params['classification'] == "UNSCHEDULED") {
                            echo ' selected';
                        } ?>>Unscheduled
                        </option>
                    </select>
                </div>

            </div>

        </div>

        <div class="row">



    </div>
        <br/>
        <div class="row" >

            <div class="col-sm-7">
                <h2 id="dateTitle">Test</h2>
            </div>



            <div class='col-sm-5'>
                <div class="btn-group">
                    <button id="prevMonth" type="button" class=" btn btn-secondary-outline">
                        <span class="glyphicon glyphicon-chevron-left"></span>
                    </button>
                    <button id="currentMonth" type="button" class="btn btn-secondary-outline">Current</button>
                    <button id="nextMonth" type="button" class="btn btn-secondary-outline"><span
                            class="glyphicon glyphicon-chevron-right"></span>
                    </button>
                </div>
                <div class="form-group col-sm-6">
                    <div class='input-group date' id='monthpicker'>
                        <input type='text' class="form-control" value=""/>
                        <span class="input-group-addon">
                            <span class="glyphicon glyphicon-calendar"></span>
                        </span>
                    </div>
                </div>
            </div>

        </div>

    <div id='calendar'></div>


    <script type="text/javascript" src="<?php GocContextPath::getPath()?>javascript/jquery.multiple.select.js"></script>
<!--<script type="text/javascript" src="https://cdn.jsdelivr.net/qtip2/2.2.1/jquery.qtip.min.js"></script>-->
<!--<link rel="stylesheet" href="https://cdn.jsdelivr.net/qtip2/2.2.1/jquery.qtip.min.css" />-->


<script type="text/javascript">

    //Main downtime filter is run once for each of the events, and if of the subfilters
    //returns false it will to, causing the downtime event to not be rendered
    function filterEvent ( event ){

        if (
            !filterEventByScope(event) ||
            !filterEventBySeverity(event) ||
            !filterEventByClass(event) ||
            !filterEventByNGI(event) ||
            !filterEventBySite(event)
        ){
            return false;
        }
    }

    //checks if the severity of the downtime matches the selected severity in the filter panel
    function filterEventBySeverity (event){
        return ['ALL', event.severity].indexOf($('#severity_selector').val()) >= 0
    }

    //checks if the affected NGI of the downtime matches the selected NGI in the filter panel
    function filterEventByNGI (event){
        return ['ALL', event.ngi].indexOf($('#ngi_selector').val()) >= 0
    }

    //checks if the clasification of the downtime matches the selected class in the filter panel
    function filterEventByClass (event){
        return ['ALL', event.class].indexOf($('#class_selector').val()) >= 0
    }

    //checks if any affected sites of the downtime matches any of the selected sites in the filter panel
    function filterEventBySite (event){
        siteSelect = $('#siteSelect');
        if (siteSelect.val() == null){
            return false;
        }
        return siteSelect.val().indexOf(event.site) >=0;
    }

    //checks if any affected scopes of the downtime matches any of the selected scopes in the filter panel
    function filterEventByScope (event){
        var index;
        var scopeFilterArray = $('#scopeSelect').val();
        if (scopeFilterArray == null){
            return false;
        }
        var scopeArray = event.scopes;
        for (index = 0; index < scopeArray.length; ++index) {
            if (scopeFilterArray.indexOf(scopeArray[index]) >=0){
                return true;
            }
        }

        return false;
    }



    //this function is used to update the  url bar, allowing filter settings to be bookmarked by users
    //mostly grabbed from a forum post, had to add the ability to delete queries from the string
    //and make in do nothing if the value is null
    function updateQueryStringParam(key, value) {
        baseUrl = [location.protocol, '//', location.host, location.pathname].join('');
        urlQueryString = document.location.search;

        var newParam = key + '=' + value,
            params = '?' + newParam;

        // If the "search" string exists, then build params from it
        if (urlQueryString) {
            keyRegex = new RegExp('([\?&])' + key + '[^&]*');


            params = urlQueryString;
            // If param exists already, update it
            if (urlQueryString.match(keyRegex) !== null) {
                //this is needed to stop it adding a 'null' when it's a null array
                if (value == null){
                    params = params.replace(keyRegex, "");
                } else {
                    params = params.replace(keyRegex, "$1" + newParam);
                }
            } else if(value != null){ // Otherwise, add it to end of query string, unless it's null, then do nothing
                params = params + '&' + newParam;
            }
        }
        window.history.replaceState({}, "", baseUrl + params);
    }


    $(document).ready(function () {


        var scopeSelector = $('#scopeSelect');
        var siteSelector = $('#siteSelect');
        var calendar = $('#calendar');

        //initilaise the scope selector
        scopeSelector.multipleSelect({
            filter: true,
            placeholder: "Service Scopes"
        });

        //initilaise the site selector
        siteSelector.multipleSelect({
            filter: true,
            placeholder: "Sites"
        });

        //get the time from the page controller, and turn it into a moment
        var time = moment(<?php if($params['date'] != null){ echo( "\"". $params['date'] . "\", \"YYYY-MM\"");}?>);
        //set the date header
        $('#dateTitle').text(moment(time).format("MMMM YYYY"));


        //initalise the monthpicker, with it starting on the month we just grabbed
        $('#monthpicker').datetimepicker({
            viewMode: 'months',
            format: 'YYYY-MM',
            defaultDate: time
        });



        //register a change listener to change the calender date if the user selects a new date
        //in the monthpicker
        $('#monthpicker').on("dp.change", function(e) {
            calendar.fullCalendar( 'gotoDate', e.date);
            $('#dateTitle').text(moment(e.date).format("MMMM YYYY"));
            //we also have to update the url query string, but I don't want someone press the current date button and then
            // bookmark the page, assuming it will update as the month changes
            if (moment(e.date).format("YYYY-MM") == moment().format("YYYY-MM")){
                //this will delete the query
                updateQueryStringParam("date", null);
            } else {
                updateQueryStringParam("date", moment(e.date).format("YYYY-MM"));

            }
        });

        //initalise the calendar
        calendar.fullCalendar({

            defaultDate: time,
            header:false,
            events: '/portal/index.php?Page_Type=Downtimes_Calendar&getDowntimesAsJSON',
            eventRender: function eventRender( event, element, view ) {
                //var dtID = event.id;
//                element.qtip({
//                    content: {
//                        text: function(event, api) {
//                            console.log(event);
//                            $.ajax({
//                                //url: element.data('/portal/index.php?Page_Type=Downtimes_Calendar'), // Use data-url attribute for the URL &getTooltip&downtimeID=' + event.id
//                                data: {downtimeID: this.downtimeID, getTooltip: true}
//                            })
//                                .then(function(content) {
//                                    // Set the tooltip content upon successful retrieval
//                                    console.log(dtID);
//
//                                    api.set('content.text', content);
//                                }, function(xhr, status, error) {
//                                    // Upon failure... set the tooltip content to the status and error value
//                                    api.set('content.text', status + ': ' + error);
//                                });
//
//                            return 'Loading...'; // Set some initial text
//                        }
//                    }
//                });
                return filterEvent(event);
            }
        });

        //event handlers for our custom navigation buttons
        //they only need to affect the month picker, and then the month pickers change handler will do the rest

        $('#prevMonth').click(function() {
            date = $('#monthpicker').data("DateTimePicker").date();
            date.add(-1, 'months');
            $('#monthpicker').data("DateTimePicker").date(date);
        });

        $('#currentMonth').click(function() {
            $('#monthpicker').data("DateTimePicker").date(moment());
            calendar.fullCalendar('today');
        });

        $('#nextMonth').click(function() {
            date = $('#monthpicker').data("DateTimePicker").date();
            date.add(1, 'months');
            $('#monthpicker').data("DateTimePicker").date(date);
        });

        //change listeners for the various filters:

        scopeSelector.on('change',function(){
            $('#calendar').fullCalendar('rerenderEvents');

            scopeSelect = $('#scopeSelect');
            if (scopeSelect.val() == null || scopeSelect.find('option').length == scopeSelect.val().length)
                updateQueryStringParam("scope", null);
            else
                updateQueryStringParam("scope", scopeSelect.val());
        });

        siteSelector.on('change',function(){
            $('#calendar').fullCalendar('rerenderEvents');
            siteSelect = $('#siteSelect');
            if (siteSelect.val() == null || siteSelect.find('option').length == siteSelect.val().length)
                updateQueryStringParam("site", null);
            else
                updateQueryStringParam("site", siteSelect.val());
        });

        $('#severity_selector').on('change',function(){
            $('#calendar').fullCalendar('rerenderEvents');
            updateQueryStringParam("severity", $('#severity_selector').val());
        });

        $('#class_selector').on('change',function(){
            $('#calendar').fullCalendar('rerenderEvents');
            updateQueryStringParam("class", $('#class_selector').val());
        });

        $('#ngi_selector').on('change',function(){
            $('#calendar').fullCalendar('rerenderEvents');
            updateQueryStringParam("ngi", $('#ngi_selector').val());
        })

    })
</script>