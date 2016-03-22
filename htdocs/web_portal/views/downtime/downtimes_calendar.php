<?php
// IMPORTANT
// for the filtering ui, the html name attribute is used to specify the name of the url query string
// e.g.
//  <select id="scopeMatchSelect" name="scopeMatch">
//      <option value="any" selected>any</option>
//      <option value="all">all</option>
//  </select>
//
// will result in:
//  index.php?Page_Type=Downtimes_Calendar&scopeMatch=any
?>

<div class="rightPageContainer">

    <div style="float: left;">
        <img src="<?php echo \GocContextPath::getPath() ?>img/down_arrow.png" class="pageLogo"/>
    </div>
    <h1>Downtimes Calendar</h1>
    <a id="helpLink" style="float: right; cursor: pointer;"><span >help</span> <span  style="font-size:1.5em;" class="glyphicon glyphicon-question-sign"></span> </a>



    <div id="helpContainer" class="siteContainer container-fluid">
        <h3>Help</h3>

        <h4 class="filter-title">What is it?</h4>
        <p>
            This calendar displays the downtimes for the sites and services in GOCDB. Downtimes can be filtered, and the filtered view can be
            bookmarked for repeated viewing.
        </p>
        <p>

            The downtimes are colour coded by their severity:
        </p>
            <ul>
                <li><div class="legend-box" style="background-color:#3A87AD;"></div>Warning: affected services are at risk</li>
                <li><div class="legend-box" style="background-color:#B44;"></div>Outage: affected services are down</li>
            </ul>
        <p>
        Hovering over a downtime will reveal more information about that downtime, clicking on it will take you to the downtime page.
        </p>
        <h4 class="filter-title">Filtering downtimes</h4>
        <p>
            Downtimes can be filtered by any of the following parameters using the dropdowns below:
        </p>

        <ul>
            <li>Downtime
                <ul>
                    <li>Severity</li>
                    <li>Classification</li>
                </ul>
            </li>
            <li>Services
                <ul>
                    <li>Service scopes</li>
                    <li>Service types</li>
                    <li>Production status</li>
                    <li>Monitored status</li>

                </ul>
            </li>
            <li>Sites
                <ul>
                    <li>Site name</li>
                    <li>Certification status</li>
                </ul>
            </li>
            <li>NGI name</li>
        </ul>

        <p>
        Once the relevant filters have been selected, the "Fetch Downtimes" button will fetch the downtimes specified by your query.
        </p>

        <h4 class="filter-title">Saving views</h4>
        <p>
            Updating the filters and fetching the downtimes will also cause the URL of the page to be updated with the filter parameters.

            Once you have a set of filters you are interested in revisiting, simply bookmark the page, and the bookmark will load the calendar with the filters applied.
        </p>
        <p>
            Note: selecting a specific week/month will generate a url that will always loads the calendar on that time period, but selecting the current date
            (using the "current" button) will remove that parameter, causing the view to always be on the week/month containing today's date.
        </p>
    </div>

    <div class="siteContainer container-fluid" style="position: relative">

        <div class="row">


            <div class="col-sm-2">
                <span><a href="index.php?Page_Type=Scope_Help">Service scopes:</a> </span>
<!--                <input type="checkbox" id="scopeMatch" value="and"-->
<!--                <span style="float:right">Match:</span>-->

                <br/>
                <select id="scopeSelect" name="scope" class="" style="width: 100%" multiple="multiple">
                    <?php foreach ($params['scopes'] as $scope) { ?>
                        <option value="<?php xecho($scope->getName()); ?>"
                            <?php if (in_array($scope->getName(), $params['selectedScopes'])) {
                                echo ' selected';
                            } ?> >
                            <?php xecho($scope->getName()); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>


            <div class="col-sm-1" style="padding: 0">

                <span><small>Scope Match:</small></span>
                <br/>
                <select style="width: 45px;" id="scopeMatchSelect" name="scopeMatch">
                    <option value="any"<?php if ($params['scopeMatch'] == "any") {
                        echo ' selected';
                    } ?>>any (selected tags are OR'd)</option>
                    <option value="all"<?php if ($params['scopeMatch'] == "all") {
                        echo ' selected';
                    } ?>>all&nbsp;&nbsp;&nbsp;(selected tags are AND'd)</option>
                </select>

            </div>


            <div class="col-sm-3">
                Service types:
                <br/>
                <select id="servTypeSelect" name="service_type" class="" style="width: 100%" multiple="multiple">
                    <?php foreach ($params['serviceTypes'] as $servType) { ?>
                        <option value="<?php xecho($servType->getName()); ?>"
                            <?php if (in_array($servType->getName(), $params['selectedServiceTypes'])) {
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
                <select id="siteSelect" name="site" class="" style="width: 100%" multiple="multiple" name="site[]">
                    <?php foreach ($params['sites'] as $site) { ?>
                        <option value="<?php xecho($site->getName()); ?>"
                            <?php if (in_array($site->getName(), $params['selectedSites'])) {
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
                <select id="ngiSelect" name="ngi" class="" style="width: 100%" multiple="multiple" name="ngi[]">
                    <?php foreach ($params['ngis'] as $ngi) { ?>
                        <option value="<?php xecho($ngi->getName()); ?>"
                            <?php if (in_array($ngi->getName(), $params['selectedNGIs'])) {
                                echo ' selected';
                            } ?> >
                            <?php xecho($ngi->getName()); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
        </div>
        <!--        <div class="row">-->

        <div class="row" style="padding: 3px;">
            <hr class="filter-hr"/>
        </div>


        <div class="row">


            <div class="col-sm-3">
                <div class="input-group" style="width: 100%;">
                    <span class="input-group-addon calendar-imput-group-addon" data-container="body" style="width: 100%">Severity</span>
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
            </div>

            <div class="col-sm-3">
                <div class="input-group" style="width: 100%">
                    <span class="input-group-addon calendar-imput-group-addon" style="width: 100%">Production</span>
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
            </div>

            <div class="col-sm-3">
                <div class="input-group" style="width: 100%">
                    <span class="input-group-addon calendar-imput-group-addon" style="width: 100%">Site Certification</span>

                    <select class="selectpicker" data-container="body" name="certStatus" id="certStatus_selector">
                        <option value="ALL">All</option>
                        <?php foreach ($params['certStatuses'] as $certStatus) { ?>
                            <option value="<?php xecho($certStatus->getName()); ?>"
                                <?php if ($params['certStatus'] == $certStatus->getName()) {
                                    echo " selected";
                                } ?> >
                                <?php xecho($certStatus->getName()); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
            </div>


        </div>

        <div class="row" style="padding: 3px;"></div>


        <div class="row">

            <div class="col-sm-3">
                <div class="input-group" style="width: 100%">
                    <span class="input-group-addon calendar-imput-group-addon" data-container="body" style="width: 100%">Classification</span>
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
            </div>



            <div class="col-sm-3">
                <div class="input-group" style="width: 100%">
                    <span class="input-group-addon calendar-imput-group-addon" style="width: 100%">Monitored</span>
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
            </div>



            <div class="col-sm-3 pull-right">

                <button id="applyFilters" style="width: 100%;" type="button" class="btn btn-primary">Fetch Downtimes
                </button>
            </div>


        </div>
        <div class="row">
            <div class="col-sm-3 pull-right">

                <a href="index.php?Page_Type=Downtimes_Calendar" id="clearFilters" style="float: right; text-align: right">Clear filters
                </a>
            </div>
        </div>        <div class="row" style="padding: 3px;">
            <hr class="filter-hr"/>
        </div>


        <div class="row">

            <div class="col-sm-3">
                <h2 style="display:none; font-size: 26px;" id="dateMonthTitle"></h2>

                <h2 style="display:none; font-size: 26px;" id="dateWeekTitle"></h2>
            </div>


            <div class="col-sm-3">
                <div class="btn-group" style="width: 100%">
                    <button id="weekView" type="button" class="viewButton btn btn-secondary-outline" style="width: 50%">Week</button>
                    <button id="monthView" type="button" class="viewButton btn btn-secondary-outline" style="width: 50%">Month</button>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="btn-group" style="width: 100%">
                    <button id="prevMonth" type="button" class=" btn btn-secondary-outline" style="width: 20%">
                        <span class="glyphicon glyphicon-chevron-left"></span>
                    </button>
                    <button id="currentMonth" type="button" class="btn btn-secondary-outline" style="width: 60%">Current</button>
                    <button id="nextMonth" type="button" class="btn btn-secondary-outline" style="width: 20%"><span
                            class="glyphicon glyphicon-chevron-right"></span>
                    </button>
                </div>
            </div>
            <div class="col-sm-3 pull-right">
                <div class="form-group">
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
        <div
    </div>

</div>


<script type="text/javascript" src="<?php GocContextPath::getPath() ?>javascript/fullcalendar/fullcalendar.min.js"></script>
<script type="text/javascript" src="<?php GocContextPath::getPath() ?>javascript/jquery.multiple.select.js"></script>
<script type="text/javascript" src="<?php GocContextPath::getPath() ?>javascript/qtip/jquery.qtip.min.js"></script>
<link rel="stylesheet" href="<?php GocContextPath::getPath() ?>javascript/qtip/jquery.qtip.min.css"/>
<link rel="stylesheet" href="<?php GocContextPath::getPath() ?>javascript/fullcalendar/fullcalendar.min.css"/>
<link rel="stylesheet" href="<?php GocContextPath::getPath() ?>css/downtime-calendar.css"/>


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
    function multiSelectUpdateQuery(name, id) {
        var multiSelect = $(id);
        if (multiSelect.val() == null || multiSelect.find('option').length == multiSelect.val().length)
            updateQueryStringParam(name, null);
        else
            updateQueryStringParam(name, multiSelect.val());

    }

    //given an multi select element id, return the selected items as a comma separated string,
    // unless they are all selected, when null is returned
    function multiSelectToURLParam(id) {
        var select = $(id);
        multiSelectUpdateQuery(select.attr('name'), id);
        if (select.val() === null || select.find('option').length == select.val().length)
            return null;
        else
            return select.val().toString();
    }

    //given an single select element id, return the value, unless the value is "ALL", when null is returned
    function singleSelectToURLParam(id) {
        var select = $(id);
        updateQueryStringParam(select.attr('name'), select.val());
        if (select.val() == "ALL")
            return null;
        else
            return select.val();
    }

    //this returns the array of parameters for the getDonwtimesAsJSON function, extracted from the various multiselect
    //boxes and select boxes in the filter panel
    function updateJSONParams() {
        return {

            scope: multiSelectToURLParam('#scopeSelect'),
            //scopeMatch is a little different, as you don't want it returning a value if there's no scopes selected
            scopeMatch: function () {
                //get the value of the multiselect and update the url
                var param =  singleSelectToURLParam('#scopeMatchSelect');
                var scopeSelect = $('#scopeSelect');
                //if no/all scopes are selected
                if (scopeSelect.val() === null || scopeSelect.find('option').length == scopeSelect.val().length) {
                    //remove the url param
                    updateQueryStringParam("scopeMatch", null);
                } else {
                    //return all/any
                    return param;
                }
            },
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
    function changeViewMode() {
        if ($('#calendar').fullCalendar('getView').name == "month") {
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

    function updateDate(time) {
        $('#dateMonthTitle').text(moment(time).format("MMM YYYY"));
        $('#dateWeekTitle').text("Week " + moment(time).format("W\,  GGGG"));
    }

    $(document).ready(function () {

        //hide the help div
        $('#helpContainer').hide();


        //apply filter event handler
        $('#helpLink').click(function () {
            $('#helpContainer').toggle();
        });

        var scopeSelector = $('#scopeSelect');
        var siteSelector = $('#siteSelect');
        var ngiSelector = $('#ngiSelect');
        var servTypeSelect = $('#servTypeSelect');
        var calendar = $('#calendar');

        //initilaise the scope selector
        scopeSelector.multipleSelect({
            filter: true,
            placeholder: "Scopes"
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
        
        moment.locale('en', {
            week: { dow: 1 } // Monday is the first day of the week in the datetimepicker
        });

        //initalise the monthpicker, with it starting on the month we just grabbed
        $('#monthpicker').datetimepicker({
            viewMode: 'months',
            format: 'YYYY-MM-DD',
            defaultDate: time,
            widgetPositioning: {
                vertical: 'auto',
                horizontal: 'right'
            }
        });

        updateDate(time);

        if (view == "month") {
            $('#monthpicker').data("DateTimePicker").format("YYYY-MM")
        } else {
            $('#monthpicker').data("DateTimePicker").format("YYYY-MM-DD")
        }

        //register a change listener to change the calender date if the user selects a new date
        //in the monthpicker
        $('#monthpicker').on("dp.change", function (e) {
            calendar.fullCalendar('gotoDate', e.date);
            updateDate(e.date);
            //we also have to update the url query string, but I don't want someone press the current date button and then
            // bookmark the page, assuming it will update as the month changes
            if (moment(e.date).format("YYYY-MM") == moment().format("YYYY-MM") && $('#calendar').fullCalendar('getView').name == "month") {
                //this will delete the query string
                updateQueryStringParam("date", null);
            } else {
                updateQueryStringParam("date", moment(e.date).format("YYYY-MM-DD"));

            }
        });

        //event handlers for our custom navigation buttons

        $('#prevMonth').click(function () {
            //grab the current date from the picker
            var date = $('#monthpicker').data("DateTimePicker").date();
            console.log("before: " + moment(date).format("YYYY-MM-DD") + $('#calendar').fullCalendar('getView').name);
            if ($('#calendar').fullCalendar('getView').name == "month") {
                date.add(-1, "month");
            } else {
                date.add(-1, "week");
            }
            console.log("after: " + moment(date).format("YYYY-MM-DD"));

            $('#monthpicker').data("DateTimePicker").date(date);
        });

        $('#currentMonth').click(function () {
            $('#monthpicker').data("DateTimePicker").date(moment());
            calendar.fullCalendar('today');
        });

        $('#nextMonth').click(function () {
            var date = $('#monthpicker').data("DateTimePicker").date();
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
            //call the calendar to refetch the events
            $('#calendar').fullCalendar('refetchEvents');
        });

        //initalise the calendar
        calendar.fullCalendar({

            defaultDate: time,
            defaultView: view,
            firstDay: 1,
            timeFormat: 'H(:mm)',
            header: false,
            columnFormat: {
                week: 'ddd D/M' //also tried 'D' but a number displayed instead
            },
            events: {
                url: '/portal/index.php?Page_Type=Downtimes_Calendar&getDowntimesAsJSON',
                data: function () {
                    return updateJSONParams();
                }
            },
            loading: function (isLoading, view) {
                if (isLoading) {
                    $('#loading').show()
                } else {
                    $('#loading').hide()
                }
            },
            eventRender: function eventRender(event, element, view) {

                //colour events by severity
                element.find('span.fc-title').html(element.find('span.fc-title').text());
                if (event.severity == "OUTAGE") {
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
                    },
                    position: {
                        viewport: $(window)
                    }
                });
            }
        });

        //check if the view parameter was passed in from the url, and make sure the ui bits are all in sync
        //might not be needed, but I encountered some issues with everything going a little weird without it
        changeViewMode();

    })
</script>