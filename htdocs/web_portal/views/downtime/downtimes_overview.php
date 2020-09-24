<?php
define('DATE_FORMAT', 'd-m-Y H:i');
$dtActive = $params['downtimesActive'];
$dtImmenent = $params['downtimesImmenent'];
$timePeriod = $params['timePeriod'];
$filterScope = $params['filterScope'];

$td1 = '<td class="site_table">';
$td2 = '</td>';
$td1np = '<td class="site_table_nopad">';
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

    <div style="overflow: hidden;">
        <div style="float: left;">
            <img src="<?php echo \GocContextPath::getPath()?>img/down_arrow.png" class="pageLogo" />
        </div>
        <div style="float: left;">
            <h1 style="float: left; margin-left: 0em; padding-bottom: 0.0em;">
                   Overview of Current and Planned Downtimes
            </h1>
            <span style="float: left; clear: both;">
                All currently active and planned downtimes over the coming weeks<br />
            </span>
        </div>

        <!--  Active Downtimes -->
        <div class="listContainer" style="width: 99.5%; float: left; margin-top: 3em; margin-right: 10px;">
            <span class="header" style="vertical-align:middle; float: left; padding-top: 0.9em; padding-left: 1em;">Currently Active Downtimes</span>
            <img src="<?php echo \GocContextPath::getPath()?>img/service.png" class="decoration"/>
            <table style="clear: both; width: 100%;">
                <tr class="site_table_row_1">
                    <th class="site_table">Downtime Id</th>
                    <th class="site_table">Site</th>
                    <th class="site_table">Description</th>
                    <th class="site_table">Severity</th>
                    <th class="site_table">Classification</th>
                    <th class="site_table">Services Affected</th>
                    <th class="site_table">Start</th>
                    <th class="site_table">End</th>
                </tr>

                <?php
                $count = 0;
                foreach($dtActive as $dt){
                    echo '<tr class="site_table_row_2">';

                    $affectedServices = $dt->getServices();
                    $affectedEPs = count($affectedServices);
                    $parentSite = $affectedServices->first()->getParentSite();
                    $siteTotalEPs = count($parentSite->getServices());
                    echo $td1 . '<a href="index.php?Page_Type=Downtime&amp;id='.$dt->getId().'"/>'.$dt->getId().'</a>'.$td2;

                    //find affected sites for the site column
                    echo $td1;
                    $siteIDArray = array();
                    foreach($dt->getServices() as $i=>$se){
                        $siteIDArray[$i] = array($se->getParentSite()->getId(), $se->getParentSite()->getName());
                    }
                    //sort into a unique array
                    $siteIDArrayUnique = array_unique($siteIDArray, SORT_REGULAR);
                    //print the array
                    foreach($siteIDArrayUnique as $i=>$site){
                        echo '<a href="index.php?Page_Type=Site&amp;id='.$site[0].'"/>'.$site[1].'</a>';
                        if ($i+1 < count($siteIDArrayUnique))
                            echo ', ';
                    }
                    echo $td2;

                    echo $td1 . xssafe($dt->getDescription()) .  $td2;
                    echo $td1 . xssafe($dt->getSeverity()) .  $td2;
                    echo $td1 . xssafe($dt->getClassification()) .  $td2;
                    echo $td1 . $affectedEPs . ' of ' . $siteTotalEPs .  $td2;
                    echo $td1 . $dt->getStartDate()->format(DATE_FORMAT) .  $td2;
                    echo $td1 . $dt->getEndDate()->format(DATE_FORMAT) .  $td2;
                    //There is dynamic creation of table ids here which are used to show and hide the extra services info
                    //when clicked. This sub table by default is hidden
                    echo '</tr>';
                    echo '<tr class="site_table_row_1"><td colspan="8" style="padding-left:2em">';
                    echo '<a href="#a'.$count.'" onclick="showHide(\'tablea_'.$count.'\');toggleMessage(\'diva_'.$count.'\');"/><div id="diva_'.$count.'">+Show Affected Services</div></a>';
                    echo '<table name="a'.$count.'" id="tablea_'.$count.'" style="clear: both; width: 100%; display:none;">';
                    echo '<tr class="site_table_row_1">';
                    echo '<th class="site_table">Sitename</th>';
                    echo '<th class="site_table">Hostname</th>';
                    echo '<th class="site_table">Production</th>';
                    echo '<th class="site_table">Monitored</th>';

                    foreach($dt->getServices() as $se){
                        echo '<tr class="site_table_row_2">';
                        $sID = $se->getParentSite()->getId();
                        echo $td1np . '<a href="index.php?Page_Type=Site&amp;id='.$sID.'"/>'.xssafe($se->getParentSite()->getName()).'</a>'.$td2;
                        echo $td1np . '<a href="index.php?Page_Type=Service&amp;id='.$se->getId().'"/>'.xssafe($se->getHostName()).'</a>'.$td2;
                        echo $td1np . (($se->getProduction()) ? 'Yes' : 'No') . $td2;
                        echo $td1np . (($se->getMonitored()) ? 'Yes' : 'No') . $td2;
                        echo '</tr>';
                    }


                    echo '</table>';
                    echo '</td></tr>';
                    $count++;
                }

                ?>
            </table>
        </div>


        <!--  Imminent Downtimes -->
        <div class="listContainer" style="width: 99.5%; float: left; margin-top: 3em; margin-right: 10px;">
            <span class="header" style="vertical-align:middle; float: left; padding-top: 0.9em; padding-left: 1em;">Downtimes Scheduled for the next week(s)</span>
            <img src="<?php echo \GocContextPath::getPath()?>img/service.png" class="decoration"/>

           <div class="topMargin rightFloat clearRight">
           <form action="index.php?Page_Type=Downtimes_Overview" method="GET" class="inline">
            <input type="hidden" name="Page_Type" value="Downtimes_Overview" />

                <select name="timePeriod" onchange="form.submit()">
                    <?php for($i=1; $i<5; $i++){ ?>
                        <option value="<?php echo $i ?>"<?php if($timePeriod == $i){echo " selected";} ?>><?php echo $i." weeks"?></option>
                    <?php }?>

                </select>
            </form>
            </div>
            <table style="clear: both; width: 100%;">
                <tr class="site_table_row_1">
                    <th class="site_table">Downtime Id</th>
                    <th class="site_table">Site</th>
                    <th class="site_table">Description</th>
                    <th class="site_table">Severity</th>
                    <th class="site_table">Classification</th>
                    <th class="site_table">Services Affected</th>
                    <th class="site_table">Start</th>
                    <th class="site_table">End</th>
                </tr>

                <?php
                $count = 0;
                foreach($dtImmenent as $dt){
                    echo '<tr class="site_table_row_2">';

                    $affectedServices = $dt->getServices();
                    $affectedEPs = count($affectedServices);
                    $parentSite = $affectedServices->first()->getParentSite();
                    $siteTotalEPs = count($parentSite->getServices());
                    echo $td1 . '<a href="index.php?Page_Type=Downtime&amp;id='.$dt->getId().'"/>'.$dt->getId().'</a>'.$td2;

                    //find affected sites for the site column
                    echo $td1;
                    $siteIDArray = array();
                    foreach($dt->getServices() as $i=>$se){
                        $siteIDArray[$i] = array($se->getParentSite()->getId(), $se->getParentSite()->getName());
                    }
                    //sort into a unique array
                    $siteIDArrayUnique = array_unique($siteIDArray, SORT_REGULAR);
                    //print the array
                    foreach($siteIDArrayUnique as $i=>$site){
                        echo '<a href="index.php?Page_Type=Site&amp;id='.$site[0].'"/>'.$site[1].'</a>';
                        if ($i+1 < count($siteIDArrayUnique))
                            echo ', ';
                    }
                    echo $td2;

                    echo $td1 . xssafe($dt->getDescription()) .  $td2;
                    echo $td1 . xssafe($dt->getSeverity()) .  $td2;
                    echo $td1 . xssafe($dt->getClassification()) .  $td2;
                    echo $td1 . $affectedEPs . ' of ' . $siteTotalEPs .  $td2;
                    echo $td1 . $dt->getStartDate()->format(DATE_FORMAT) .  $td2;
                    echo $td1 . $dt->getEndDate()->format(DATE_FORMAT) .  $td2;
                    //There is dynamic creation of table ids here which are used to show and hide the extra services info
                    //when clicked. This sub table by default is hidden
                    echo '</tr>';
                    echo '<tr class="site_table_row_1"><td colspan="8" style="padding-left:2em">';
                    echo '<a href="#b'.$count.'" onclick="showHide(\'tablei_'.$count.'\');toggleMessage(\'divi_'.$count.'\');"/><div id="divi_'.$count.'">+Show Affected Services</div></a>';
                    echo '<table name="b'.$count.'" id="tablei_'.$count.'" style="clear: both; width: 100%; display:none;">';
                    echo '<tr class="site_table_row_1">';
                    echo '<th class="site_table">Sitename</th>';
                    echo '<th class="site_table">Hostname</th>';
                    echo '<th class="site_table">Production</th>';
                    echo '<th class="site_table">Monitored</th>';

                    foreach($dt->getServices() as $se){
                        echo '<tr class="site_table_row_2">';
                        $sID = $se->getParentSite()->getId();
                        echo $td1np . '<a href="index.php?Page_Type=Site&amp;id='.$sID.'"/>'.xssafe($se->getParentSite()->getName()).'</a>'.$td2;
                        echo $td1np . '<a href="index.php?Page_Type=Service&amp;id='.$se->getId().'"/>'.xssafe($se->getHostName()).'</a>'.$td2;
                        echo $td1np . (($se->getProduction()) ? 'Yes' : 'No') . $td2;
                        echo $td1np . (($se->getMonitored()) ? 'Yes' : 'No') . $td2;
                        echo '</tr>';
                    }


                    echo '</table>';
                    echo '</td></tr>';
                    $count++;
                }

                ?>
            </table>
        </div>

    </div>
</div>
