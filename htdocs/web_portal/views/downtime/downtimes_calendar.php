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

<div class="rightPageContainer" style="position: relative">

    <div style="float: left;">
        <img src="<?php echo \GocContextPath::getPath() ?>img/down_arrow.png" class="pageLogo"/>
    </div>
    <a href="index.php?Page_Type=Downtimes_Calendar" style="text-decoration: none"><h1>Downtimes Calendar</h1></a>

    <div class="siteContainer container-fluid">

        <div class="row">


            <div class="col-sm-3">
                <span><a href="index.php?Page_Type=Scope_Help">Scopes:</a> </span>
                <br/>

                <select id="scopeSelect" name="scope" class="" style="width: 150px" multiple="multiple" name="mscope[]">
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
                Service types:
                <br/>
                <select id="servTypeSelect" name="service_type" class="" style="width: 150px" multiple="multiple">
                    <?php foreach ($params['serviceTypes'] as $servType) { ?>
                        <option value="<?php xecho($servType->getName()); ?>"
                            <?php if (in_array($servType->getName(), $params['selectedServiceTypes']) || count($params['selectedServiceTypes']) == 0) {
                                echo ' selected';
                            } ?> >
                            <?php xecho($servType->getName()); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>

            <div class="col-sm-3">
                Sites:
                <br/>
                <select id="siteSelect" name="site" class="" style="width: 150px" multiple="multiple" name="site[]">
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

            <div class="col-sm-3">
                NGIs:
                <br/>
                <select id="ngiSelect" name="ngi" class="" style="width: 150px" multiple="multiple" name="ngi[]">
                    <?php foreach ($params['ngis'] as $ngi) { ?>
                        <option value="<?php xecho($ngi->getName()); ?>"
                            <?php if (in_array($ngi->getName(), $params['selectedNGIs']) || count($params['selectedNGIs']) == 0) {
                                echo ' selected';
                            } ?> >
                            <?php xecho($ngi->getName()); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
            </div>
        <div class="row">

                <div class="col-sm-2">
                    Severity:
                    <br/>
                    <select name="severity" class="selectpicker" id="severity_selector">
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

                <div class="col-sm-2">
                    Classification:
                    <br/>
                    <select class="selectpicker" name="classification" id="class_selector">
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

            <div class="col-sm-2">
                Certification:
                <br/>
                <select class="selectpicker" name="certStatus" id="certStatus_selector" >
                    <option value="ALL">All</option>
                    <?php foreach ($params['certStatuses'] as $certStatus) { ?>
                        <option value="<?php xecho($certStatus->getName()); ?>"
                            <?php if ($params['certStatus'] == $certStatus->getName()){ echo " selected";} ?> >
                            <?php xecho($certStatus->getName()); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>

            <div class="col-sm-2">
                Production:
                <br/>
                <select class="selectpicker" name="production" id="prod_selector">
                    <option value="ALL">All</option>
                    <option value="1" <?php if ($params['production'] == "1") {
                        echo ' selected';
                    } ?>>Yes
                    </option>
                    <option value="0" <?php if ($params['production'] == "0") {
                        echo ' selected';
                    } ?>>No
                    </option>
                </select>
            </div>

            <div class="col-sm-2">
                Monitored:
                <br/>
                <select class="selectpicker" name="monitored" id="monitored_selector">
                    <option value="ALL">All</option>
                    <option value="1" <?php if ($params['monitored'] == "1") {
                        echo ' selected';
                    } ?>>Yes
                    </option>
                    <option value="0" <?php if ($params['monitored'] == "0") {
                        echo ' selected';
                    } ?>>No
                    </option>
                </select>
            </div>

            <div class="col-sm-2">
                <br/>

                <button id="applyFilters" type="button" class="btn btn-primary">Apply Filters</button>
            </div>

            </div>
        <div class="row"><br/></div>


        <div class="row">

            <div class="col-sm-4">
                <h2 style="display:none; font-size: 26px;" id="dateMonthTitle"></h2>
                <h2 style="display:none; font-size: 26px;" id="dateWeekTitle"></h2>
            </div>


            <div class='col-sm-8'>
                <div class="btn-group">
                    <button id="weekView" type="button" class="viewButton btn btn-secondary-outline">Week</button>
                    <button id="monthView" type="button" class="viewButton btn btn-secondary-outline">Month</button>
                </div>
                <div class="btn-group">
                    <button id="prevMonth" type="button" class=" btn btn-secondary-outline">
                        <span class="glyphicon glyphicon-chevron-left"></span>
                    </button>
                    <button id="currentMonth" type="button" class="btn btn-secondary-outline">Current</button>
                    <button id="nextMonth" type="button" class="btn btn-secondary-outline"><span
                            class="glyphicon glyphicon-chevron-right"></span>
                    </button>
                </div>
                <div class="form-group col-sm-5">
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
        <div id='loading' class="grayed-out">
            <div class="loader" style="vertical-align: middle;">Loading...</div>

        </div>
        </div>


        <script type="text/javascript" src="<?php GocContextPath::getPath() ?>javascript/fullcalendar/fullcalendar.min.js"></script>
        <script type="text/javascript" src="<?php GocContextPath::getPath() ?>javascript/jquery.multiple.select.js"></script>
        <script type="text/javascript" src="<?php GocContextPath::getPath() ?>javascript/qtip/jquery.qtip.min.js"></script>
        <link rel="stylesheet" href="<?php GocContextPath::getPath() ?>javascript/qtip/jquery.qtip.min.css" />
        <link rel="stylesheet" href="<?php GocContextPath::getPath() ?>javascript/fullcalendar/fullcalendar.min.css" />
        <link rel="stylesheet" href="<?php GocContextPath::getPath() ?>css/downtime-calendar.css" />



        <script type="text/javascript">

            //this function is used to update the  url bar, allowing filter settings to be bookmarked by users
            //mostly grabbed from a forum post, had to add the ability to delete queries from the string
            //and make in do nothing if the value is null, or "ALL"
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
                        //this is needed to stop it adding a 'null' when it's a null array, or 'ALL' when that's selected
                        if (value == null || value == "ALL") {
                            params = params.replace(keyRegex, "");
                        } else {
                            params = params.replace(keyRegex, "$1" + newParam);
                        }
                    } else if (value != null && value != "ALL") { // Otherwise, add it to end of query string, unless it's null or "ALL", then do nothing
                        params = params + '&' + newParam;
                    }
                }

                window.history.replaceState({}, "", baseUrl + params);
            }


            //when updating the query string parameter from a multi select box, check if all of the options are selected
            //and if so, remove the QSP
            function multiSelectUpdateQuery ( name , id ){
                var multiSelect = $(id);
                if (multiSelect.val() == null || multiSelect.find('option').length == multiSelect.val().length)
                    updateQueryStringParam(name, null);
                else
                    updateQueryStringParam(name, multiSelect.val());

            }

            //update all the QSPs
//            function updateURL (){
//
//                multiSelectUpdateQuery("scope", "#scopeSelect");
//                multiSelectUpdateQuery("site", "#siteSelect");
//                multiSelectUpdateQuery("service_type", "#servTypeSelect");
//                multiSelectUpdateQuery("ngi", "#ngiSelect");
//
//                updateQueryStringParam("severity", $('#severity_selector').val());
//                updateQueryStringParam("classification", $('#class_selector').val());
//                updateQueryStringParam("monitored", $('#monitored_selector').val());
//                updateQueryStringParam("certStatus", $('#certStatus_selector').val());
//                updateQueryStringParam("production", $('#prod_selector').val());
//
//            }

            //these two
            function multiSelectToURLParam ( id ) {
                var select = $(id);
                multiSelectUpdateQuery(select.attr('name'), id);
                if ( select.val() === null || select.find('option').length == select.val().length)
                    return null;
                else
                    return select.val().toString();
            }

            function singleSelectToURLParam ( id ) {
                var select = $(id);
                updateQueryStringParam(select.attr('name'), select.val());
                if (select.val() == "ALL")
                    return null;
                else
                    return select.val();
            }

            function updateJSONParams () {
                return{

                    scope: multiSelectToURLParam('#scopeSelect'),
                    service_type: multiSelectToURLParam('#servTypeSelect'),
                    site: multiSelectToURLParam('#siteSelect'),
                    ngi: multiSelectToURLParam('#ngiSelect'),
                    severity: singleSelectToURLParam('#severity_selector'),
                    classification: singleSelectToURLParam('#class_selector'),
                    monitored: singleSelectToURLParam('#monitored_selector'),
                    production: singleSelectToURLParam('#prod_selector'),
                    certStatus: singleSelectToURLParam('#certStatus_selector')
                };
            }



            //This toggles the calendar view between month and week
            //this is needed because fullcalendar doesn't have a date picker
            //and so if you want to use a datepicker, you need to handle a few bits manually
            function changeViewMode(){
                if($('#calendar').fullCalendar('getView').name == "month"){
                    $('#monthpicker').data("DateTimePicker").format("YYYY-MM");
                    $('#dateWeekTitle').hide();
                    $('#dateMonthTitle').show();
                    updateQueryStringParam("view", "month");
                } else {
                    $('#monthpicker').data("DateTimePicker").format("YYYY-MM-DD");
                    $('#dateMonthTitle').hide();
                    $('#dateWeekTitle').show();
                    updateQueryStringParam("view", "basicWeek");
                }
            }

            $(document).ready(function () {


                var scopeSelector = $('#scopeSelect');
                var siteSelector = $('#siteSelect');
                var ngiSelector = $('#ngiSelect');
                var servTypeSelect = $('#servTypeSelect');
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

                //initilaise the ngi selector
                ngiSelector.multipleSelect({
                    filter: true,
                    placeholder: "NGIs"
                });

                //initilaise the service type selector
                servTypeSelect.multipleSelect({
                    filter: true,
                    placeholder: "Service types"
                });

                //get the time from the page controller, and turn it into a moment
                var time = moment(<?php if($params['date'] != null){ echo( "\"". $params['date'] . "\", \"YYYY-MM-DD\"");}?>);
                var view = "<?php if($params['view'] != null){ echo($params['view']);}?>";

                //initalise the monthpicker, with it starting on the month we just grabbed
                $('#monthpicker').datetimepicker({
                    viewMode: 'months',
                    format: 'YYYY-MM',
                    defaultDate: time
                });

                $('#dateMonthTitle').text(moment(time).format("MMMM YYYY"));
                $('#dateWeekTitle').text(moment(time).format("Do MMMM YYYY"));

                if(view == "month"){
                    $('#monthpicker').data("DateTimePicker").format("YYYY-MM")
                } else {
                    $('#monthpicker').data("DateTimePicker").format("YYYY-MM-DD")
                }


                //register a change listener to change the calender date if the user selects a new date
                //in the monthpicker
                $('#monthpicker').on("dp.change", function (e) {
                    calendar.fullCalendar('gotoDate', e.date);
                    $('#dateMonthTitle').text(moment(e.date).format("MMMM YYYY"));
                    $('#dateWeekTitle').text(moment(e.date).format("Do MMMM YYYY"));
                    //we also have to update the url query string, but I don't want someone press the current date button and then
                    // bookmark the page, assuming it will update as the month changes
                    if (moment(e.date).format("YYYY-MM") == moment().format("YYYY-MM")  && $('#calendar').fullCalendar('getView').name == "month" ) {
                        //this will delete the query string
                        updateQueryStringParam("date", null);
                    } else {
                        updateQueryStringParam("date", moment(e.date).format("YYYY-MM-DD"));

                    }
                });

                //event handlers for our custom navigation buttons

                $('#prevMonth').click(function () {
                    //grab the current date from the picker
                    date = $('#monthpicker').data("DateTimePicker").date();
                    if ($('#calendar').fullCalendar('getView').name == "month") {
                        date.add(-1, "month");
                    } else {
                        date.add(-1, "week");
                    }
                    $('#monthpicker').data("DateTimePicker").date(date);
                });

                $('#currentMonth').click(function () {
                    $('#monthpicker').data("DateTimePicker").date(moment());
                    calendar.fullCalendar('today');
                });

                $('#nextMonth').click(function () {
                    date = $('#monthpicker').data("DateTimePicker").date();
                    if ($('#calendar').fullCalendar('getView').name == "month") {
                        date.add(1, "month");
                    } else {
                        date.add(1, "week");
                    }
                    $('#monthpicker').data("DateTimePicker").date(date);
                });

                $('#weekView').click(function () {
                    $('#calendar').fullCalendar('changeView', 'basicWeek');
                    changeViewMode();
                });

                $('#monthView').click(function () {
                    $('#calendar').fullCalendar('changeView', 'month');
                    changeViewMode();
                });


                //apply filter event handler
                $('#applyFilters').click(function () {
                    //update the url
                    //updateURL();
                    //call the calendar to refetch the events
                    $('#calendar').fullCalendar('refetchEvents');
                });

                //initalise the calendar
                calendar.fullCalendar({

                    defaultDate: time,
                    defaultView: view,
                    header: false,
                    events: {
                        url: '/portal/index.php?Page_Type=Downtimes_Calendar&getDowntimesAsJSON',
                        data: function(){
                            return updateJSONParams();
                        }
                    },
                    loading: function( isLoading, view ) {
                        if(isLoading) {
                            $('#loading').show()
                        } else {
                            $('#loading').hide()
                        }
                    },
                    eventRender: function eventRender(event, element, view) {

                        //colour events by severity
                        element.find('span.fc-title').html(element.find('span.fc-title').text());
                        if (event.severity == "OUTAGE"){
                            //event.eventColor = "#BB4444";
                            element.css('background-color', '#B44');
                            element.css('border-color', '#922');
                        } else {
                            element.css('background-color', '#3A87AD');
                            element.css('border-color', '#279');
                        }

                        //tooltip setup
                        element.attr('data-tooltipURL', '/portal/index.php?Page_Type=Downtimes_Calendar&getTooltip&downtimeID=' + event.id);
                        element.qtip({
                            content: {
                                text: function (event, api) {
                                    $.ajax({
                                        url: element.attr('data-tooltipURL') // Use data-url attribute for the URL
                                    })
                                        .then(function (content) {
                                            // Set the tooltip content upon successful retrieval
                                            api.set('content.text', content);
                                        }, function (xhr, status, error) {
                                            // Upon failure... set the tooltip content to the status and error value
                                            api.set('content.text', status + ': ' + error);
                                        });

                                    return 'Loading...'; // Set some initial text
                                }
                            }
                        });
                    }
                });

                //check if the view parameter was passed in from the url, and make sure the ui bits are all in sync
                //might not be needed, but I encountered some issues with everything going a little weird without it
                changeViewMode();

            })
        </script>