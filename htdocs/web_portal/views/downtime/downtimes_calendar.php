<?php
define('DATE_FORMAT', 'd-m-Y H:i');
$dtActive = $params['downtimesActive'];
$dtImmenent = $params['downtimesImmenent'];
$timePeriod = $params['timePeriod'];

$td1 = '<td>';
$td2 = '</td>';
?>

<!---
This page will show two tables, one of active downtimes and one of downtimes coming between 1-4 weeks. The user
can select the time period for planned downtimes to show. Extra information is shown by expanding a sub table 
from the main downtimes table. This table is shown and hidden by creating dynamically named tables and using 
javascript to show and hide these tables. 
--->
<div class="rightPageContainer">
    <script type="text/javascript" src="<?php echo \GocContextPath::getPath()?>javascript/confirm.js"></script>
    <script type="text/javascript" src="<?php echo \GocContextPath::getPath()?>javascript/showHide.js"></script>
        
<!--    <div style="overflow: hidden;">-->
<!--        <div style="float: left;">-->
<!--            <img src="--><?php //echo \GocContextPath::getPath()?><!--img/down_arrow.png" class="pageLogo" />-->
<!--        </div>-->
<!--        <div style="float: left; width: 45em;">-->
<!--            <h1 style="float: left; margin-left: 0em; padding-bottom: 0.0em;">-->
<!--                   Overview of Current and Planned Downtimes-->
<!--            </h1>-->
<!--            <span style="float: left; clear: both;">-->
<!--            	All currently active and planned downtimes over the coming weeks<br />-->
<!--             </span>-->
<!--        </div>-->
<!---->
<!--        <!--  Active Downtimes -->
<!--        <div class="listContainer" style="width: 99.5%; float: left; margin-top: 3em; margin-right: 10px;">-->
<!--            <span class="header" style="vertical-align:middle; float: left; padding-top: 0.9em; padding-left: 1em;">Currently Active Downtimes</span>-->
<!--            <img src="--><?php //echo \GocContextPath::getPath()?><!--img/service.png" class="decoration"/>-->
<!--            <table style="clear: both; width: 100%;">-->
<!--                <tr class="site_table_row_1">-->
<!--                    <th class="site_table">Downtime Id</th>-->
<!--                    <th class="site_table">Site(s)</th>-->
<!--                    <th class="site_table">Description</th>-->
<!--                    <th class="site_table">Severity</th>-->
<!--                    <th class="site_table">Classification</th>-->
<!--                    <th class="site_table">Services Affected</th>-->
<!--                    <th class="site_table">Start</th>-->
<!--                    <th class="site_table">End</th>                    -->
<!--                </tr>-->
<!--                -->
<!--                --><?php //
//                $count = 0;
//                foreach($dtActive as $dt){
//                	echo '<tr class="site_table_row_2">';
//
//					$affectedServices = $dt->getServices();
//					$affectedEPs = count($affectedServices);
//					$parentSite = $affectedServices->first()->getParentSite();
//					$siteTotalEPs = count($parentSite->getServices());
//					echo $td1 . '<a href="index.php?Page_Type=Downtime&id='.$dt->getId().'"/>'.$dt->getId().'</a>'.$td2;
//
//                    //find affected sites for the site column
//                    echo $td1;
//                    $siteIDArray = array();
//                    foreach($dt->getServices() as $i=>$se){
//                        $siteIDArray[$i] = array($se->getParentSite()->getId(), $se->getParentSite()->getName());
//                    }
//                    //sort into a unique array
//                    $siteIDArrayUnique = array_unique($siteIDArray, SORT_REGULAR);
//                    //print the array
//                    foreach($siteIDArrayUnique as $i=>$site){
//                        echo '<a href="index.php?Page_Type=Site&id='.$site[0].'"/>'.$site[1].'</a>';
//                        if ($i+1 < count($siteIDArrayUnique))
//                            echo ', ';
//                    }
//                    echo $td2;
//					echo $td1 . xssafe($dt->getDescription()) .  $td2;
//					echo $td1 . xssafe($dt->getSeverity()) .  $td2;
//					echo $td1 . xssafe($dt->getClassification()) .  $td2;
//					echo $td1 . $affectedEPs . ' of ' . $siteTotalEPs .  $td2;
//					echo $td1 . $dt->getStartDate()->format(DATE_FORMAT) .  $td2;
//					echo $td1 . $dt->getEndDate()->format(DATE_FORMAT) .  $td2;
//                    //There is dynamic creation of table ids here which are used to show and hide the extra services info
//                    //when clicked. This sub table by default is hidden
//	                echo '</tr>';
//	                echo '<tr class="site_table_row_1"><td colspan="8" style="padding-left:2em">';
//        			echo '<a href="#a'.$count.'" onclick="showHide(\'tablea_'.$count.'\');toggleMessage(\'diva_'.$count.'\');"/><div id="diva_'.$count.'">+Show Affected Services</div></a>';
//        			echo '<table name="a'.$count.'" id="tablea_'.$count.'" style="clear: both; width: 100%; display:none;">';
//	                echo '<tr class="site_table_row_1">';
//            		echo '<th class="site_table">Sitename</th>';
//            		echo '<th class="site_table">Hostname</th>';
//            		echo '<th class="site_table">Production</th>';
//            		echo '<th class="site_table">Monitored</th>';
//
//            		foreach($dt->getServices() as $se){
//						echo '<tr class="site_table_row_2">';
//            			$sID = $se->getParentSite()->getId();
//            			echo $td1 . '<a href="index.php?Page_Type=Site&id='.$sID.'"/>'.xssafe($se->getParentSite()->getName()).'</a>'.$td2;
//            			echo $td1 . '<a href="index.php?Page_Type=Service&id='.$se->getId().'"/>'.xssafe($se->getHostName()).'</a>'.$td2;
//            			echo $td1 . (($se->getProduction()) ? 'Yes' : 'No') . $td2;
//            			echo $td1 . (($se->getMonitored()) ? 'Yes' : 'No') . $td2;
//            			echo '</tr>';
//            		}
//
//
//        			echo '</table>';
//        			echo '</td></tr>';
//					$count++;
//                }
//
//                ?><!--           -->
<!--            </table>-->
<!--        </div>-->
<!--        -->
<!--        -->
<!--        <!--  Imminent Downtimes -->
<!--        <div class="listContainer" style="width: 99.5%; float: left; margin-top: 3em; margin-right: 10px;">-->
<!--            <span class="header" style="vertical-align:middle; float: left; padding-top: 0.9em; padding-left: 1em;">Downtimes Schedules for the next week(s)</span>-->
<!--            <img src="--><?php //echo \GocContextPath::getPath()?><!--img/service.png" class="decoration"/>-->
<!--           -->
<!--           <div class="topMargin rightFloat clearRight">            -->
<!--           <form action="index.php?Page_Type=Downtimes_Overview" method="GET" class="inline">-->
<!--            <input type="hidden" name="Page_Type" value="Downtimes_Overview" />-->
<!---->
<!--                <select name="timePeriod" onchange="form.submit()">                                        -->
<!--                    --><?php //for($i=1; $i<5; $i++){ ?>
<!--                    	<option value="--><?php //echo $i ?><!--"--><?php //if($timePeriod == $i){echo " selected";} ?><!--><?php //echo $i." weeks"?><!--</option>-->
<!--                    --><?php //}?>
<!--                    -->
<!--                </select>-->
<!--            </form>-->
<!--            </div>-->
<!--            <table style="clear: both; width: 100%;">-->
<!--                <tr class="site_table_row_1">-->
<!--                    <th class="site_table">Downtime Id</th>-->
<!--                    <th class="site_table">Site(s)</th>-->
<!--                    <th class="site_table">Description</th>-->
<!--                    <th class="site_table">Severity</th>-->
<!--                    <th class="site_table">Classification</th>-->
<!--                    <th class="site_table">Services Affected</th>-->
<!--                    <th class="site_table">Start</th>-->
<!--                    <th class="site_table">End</th>                    -->
<!--                </tr>-->
<!--                -->
<!--                --><?php //
//                $count = 0;
//                foreach($dtImmenent as $dt){
//                	echo '<tr class="site_table_row_2">';
//
//					$affectedServices = $dt->getServices();
//					$affectedEPs = count($affectedServices);
//					$parentSite = $affectedServices->first()->getParentSite();
//					$siteTotalEPs = count($parentSite->getServices());
//					echo $td1 . '<a href="index.php?Page_Type=Downtime&id='.$dt->getId().'"/>'.$dt->getId().'</a>'.$td2;
//
//                    //find affected sites for the site column
//                    echo $td1;
//                    $siteIDArray = array();
//                    foreach($dt->getServices() as $i=>$se){
//                        $siteIDArray[$i] = array($se->getParentSite()->getId(), $se->getParentSite()->getName());
//                    }
//                    //sort into a unique array
//                    $siteIDArrayUnique = array_unique($siteIDArray, SORT_REGULAR);
//                    //print the array
//                    foreach($siteIDArrayUnique as $i=>$site){
//                        echo '<a href="index.php?Page_Type=Site&id='.$site[0].'"/>'.$site[1].'</a>';
//                        if ($i+1 < count($siteIDArrayUnique))
//                            echo ', ';
//                    }
//                    echo $td2;
//                    echo $td1 . xssafe($dt->getDescription()) .  $td2;
//					echo $td1 . xssafe($dt->getSeverity()) .  $td2;
//					echo $td1 . xssafe($dt->getClassification()) .  $td2;
//					echo $td1 . $affectedEPs . ' of ' . $siteTotalEPs .  $td2;
//					echo $td1 . $dt->getStartDate()->format(DATE_FORMAT) .  $td2;
//					echo $td1 . $dt->getEndDate()->format(DATE_FORMAT) .  $td2;
//					//There is dynamic creation of table ids here which are used to show and hide the extra services info
//					//when clicked. This sub table by default is hidden
//	                echo '</tr>';
//	                echo '<tr class="site_table_row_1"><td colspan="8" style="padding-left:2em">';
//        			echo '<a href="#b'.$count.'" onclick="showHide(\'tablei_'.$count.'\');toggleMessage(\'divi_'.$count.'\');"/><div id="divi_'.$count.'">+Show Affected Services</div></a>';
//        			echo '<table name="b'.$count.'" id="tablei_'.$count.'" style="clear: both; width: 100%; display:none;">';
//	                echo '<tr class="site_table_row_1">';
//            		echo '<th class="site_table">Sitename</th>';
//            		echo '<th class="site_table">Hostname</th>';
//            		echo '<th class="site_table">Production</th>';
//            		echo '<th class="site_table">Monitored</th>';
//
//            		foreach($dt->getServices() as $se){
//						echo '<tr class="site_table_row_2">';
//            			$sID = $se->getParentSite()->getId();
//            			echo $td1 . '<a href="index.php?Page_Type=Site&id='.$sID.'"/>'.xssafe($se->getParentSite()->getName()).'</a>'.$td2;
//            			echo $td1 . '<a href="index.php?Page_Type=Service&id='.$se->getId().'"/>'.xssafe($se->getHostName()).'</a>'.$td2;
//            			echo $td1 . (($se->getProduction()) ? 'Yes' : 'No') . $td2;
//            			echo $td1 . (($se->getMonitored()) ? 'Yes' : 'No') . $td2;
//            			echo '</tr>';
//            		}
//
//
//        			echo '</table>';
//        			echo '</td></tr>';
//					$count++;
//                }
//
//                ?><!--           -->
<!--            </table>-->
<!--        </div>-->
<!--        -->
<!--    </div>-->


            <div style="float: left;">
                <img src="<?php echo \GocContextPath::getPath()?>img/down_arrow.png" class="pageLogo" />
            </div>
    <h1>Downtime Calendar</h1>
    <div class="siteContainer">
<div style="display: inline">
    Downtime type:
    <select id="severity_selector">
        <option value="ALL">All</option>
        <option value="OUTAGE" <?php if($params['severity'] == "OUTAGE"){ echo ' selected';}?>>Outage</option>
        <option value="WARNING" <?php if($params['severity'] == "WARNING"){ echo ' selected';}?>>Warning</option>
    </select>
</div>
    <div style="float: right;">
        <span class=""><a href="index.php?Page_Type=Scope_Help">With Scopes:</a> </span>
        <select id="scopeSelect" multiple="multiple" name="mscope[]" style="width: 200px">
            <?php foreach ($params['scopes'] as $scope) { ?>
                <option value="<?php xecho($scope->getName()); ?>"
                    <?php if(in_array($scope->getName(), $params['selectedScopes']) || $params['selectedScopes'] === NULL){ echo ' selected';}?> >
                    <?php xecho($scope->getName()); ?>
                </option>
            <?php } ?>
        </select>
    </div>
    </div>

    <br/>
    <br/>

    <div id='calendar' class="siteContainer"></div>

</div>

<script type="text/javascript" src="<?php GocContextPath::getPath()?>javascript/jquery.multiple.select.js"></script>


<script type="text/javascript">

    function filterEvent ( event ){

        if (!filterEventByScope(event) || !filterEventBySeverity(event)){
            return false;
        }
    }

    function filterEventBySeverity (event){
        return ['ALL', event.severity].indexOf($('#severity_selector').val()) >= 0
    }

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

    // Explicitly save/update a url parameter using HTML5's replaceState().
    function updateQueryStringParam(key, value) {
        baseUrl = [location.protocol, '//', location.host, location.pathname].join('');
        console.log(baseUrl);
        urlQueryString = document.location.search;
        console.log(urlQueryString);

        var newParam = key + '=' + value,
            params = '?' + newParam;

        // If the "search" string exists, then build params from it
        if (urlQueryString) {
            keyRegex = new RegExp('([\?&])' + key + '[^&]*');
            // If param exists already, update it
            if (urlQueryString.match(keyRegex) !== null) {
                params = urlQueryString.replace(keyRegex, "$1" + newParam);
            } else { // Otherwise, add it to end of query string
                params = urlQueryString + '&' + newParam;
            }
        }
        window.history.replaceState({}, "", baseUrl + params);
    }


    $(document).ready(function () {

        $('#scopeSelect').multipleSelect({
            filter: true,
            placeholder: "Service Scopes"
        });

        $('#calendar').fullCalendar({
            events: '/portal/index.php?Page_Type=Downtimes_Calendar&getDowntimesAsJSON',
            eventRender: function eventRender( event, element, view ) {
                return filterEvent(event);
            }
        });

        $('#calendar').fullCalendar( 'gotoDate', "2016-01-01");

        $('#scopeSelect').on('change',function(){
            $('#calendar').fullCalendar('rerenderEvents');
            updateQueryStringParam("scope", $('#scopeSelect').val())
        });

        $('#severity_selector').on('change',function(){
            $('#calendar').fullCalendar('rerenderEvents');
            updateQueryStringParam("severity", $('#severity_selector').val());
        })

    })
</script>