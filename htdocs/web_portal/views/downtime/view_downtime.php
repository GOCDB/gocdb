<?php
$dt = $params['downtime'];
?>
<div class="rightPageContainer">
    <script type="text/javascript" src="<?php echo \GocContextPath::getPath()?>javascript/confirm.js"></script>
    <div style="overflow: hidden;">
        <div style="float: left;">
            <img src="<?php echo \GocContextPath::getPath()?>img/down_arrow.png" class="pageLogo" />
        </div>
        <div style="float: left; width: 45em;">
            <h1 style="float: left; margin-left: 0em; padding-bottom: 0.0em;">
                   Downtime <?php echo $dt->getId() ?>
            </h1>
            <span style="float: left; clear: both;">
                <?php xecho( $dt->getDescription()) ?><br />
             </span>
        </div>

        <!--  Edit Downtime link -->
        <!--  only show this link if we're in read / write mode -->
        <?php if(!$params['portalIsReadOnly']):?>
            <div style="float: right;">

                <div style="float: right; margin-left: 2em;">
                    <a href="index.php?Page_Type=Edit_Downtime&amp;id=<?php echo $dt->getId()?>">
                        <img src="<?php echo \GocContextPath::getPath()?>img/pencil.png" height="25px" style="float: right;" />
                        <br />
                        <br />
                        <span>Edit</span>
                    </a>
                </div>
                <div style="float: right; margin-left: 2em;">
                    <script type="text/javascript" src="<?php echo \GocContextPath::getPath()?>javascript/confirm.js"></script>
                    <a onclick="return confirmSubmit()"
                        href="index.php?Page_Type=Delete_Downtime&id=<?php echo $dt->getId()?>">
                        <img src="<?php echo \GocContextPath::getPath()?>img/trash.png" height="25px" style="float: right; margin-right: 0.4em;" />
                        <br />
                        <br />
                        <span>Delete</span>
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <!-- If the downtime is ongoing and the portal is not in read only mode, show a notification and show the end downtime button -->
        <?php if($dt->isOnGoing() and !$params['portalIsReadOnly']) { ?>
            <div style="float: left; width: 100%; margin-top: 2em;">
                <div class="tableContainer" style="width: 99.5%; float: left;">
                    <span class="header" style="vertical-align:middle; float: left; padding-top: 0.9em; padding-left: 1em;">
                        Downtime is ongoing
                    </span>
                    <img src="<?php echo \GocContextPath::getPath()?>img/star.png" class="decoration">
                    <table style="clear: both; width: 100%; table-layout: fixed;">
                        <tr class="site_table_row_1">
                            <td class="site_table" style="width: 30%">
                                <script type="text/javascript">
                                    function submitform()
                                    {
                                        document.forms["endNow"].submit();
                                    }
                                </script>
                                <form id="endNow" action="index.php" method="post">
                                    <input type='Hidden' name='Page_Type' value="End_Downtime" />
                                    <input type='Hidden' name='id' value="<?php echo $dt->getId()?>" />
                                    <a href="javascript: submitform()">End Now</a>
                                </form>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        <?php } ?>

        <!-- Downtime information and timing container div -->
        <div style="float: left; width: 100%; margin-top: 2em;">
            <!--  Information -->
            <div class="tableContainer" style="width: 55%; float: left;">
                <span class="header" style="vertical-align:middle; float: left; padding-top: 0.9em; padding-left: 1em;">Information</span>
                <img src="<?php echo \GocContextPath::getPath()?>img/contact_card.png" class="decoration" />
                <table style="clear: both; width: 100%;">
                    <tr class="site_table_row_2">
                        <td class="site_table">Severity</td><td class="site_table">
                        <?php xecho($dt->getSeverity()) ?></td>
                    </tr>

                    <tr class="site_table_row_1">
                        <td class="site_table">Classification</td><td class="site_table">
                            <?php xecho($dt->getClassification()) ?>
                        </td>
                    </tr>
                </table>
            </div>

            <!--  Timing -->
            <div class="tableContainer" style="width: 42%; float: right;">
                <span class="header" style="vertical-align:middle; float: left; padding-top: 0.9em; padding-left: 1em;">Timing (UTC)</span>
                <img src="<?php echo \GocContextPath::getPath()?>img/clock.png" class="decoration" />
                <table style="clear: both; width: 100%;">
                    <tr class="site_table_row_1">
                        <td class="site_table">Start Date</td><td class="site_table">
                            <?php echo $dt->getStartDate()->format($dt::DATE_FORMAT) ?>
                        </td>
                    </tr>

                    <tr class="site_table_row_2">
                        <td class="site_table">End Date</td><td class="site_table">
                            <?php echo $dt->getEndDate()->format($dt::DATE_FORMAT) ?>
                        </td>
                    </tr>

                    <tr class="site_table_row_1">
                        <td class="site_table">Declaration Date</td><td class="site_table">
                            <?php echo $dt->getInsertDate()->format($dt::DATE_FORMAT) ?>
                        </td>
                    </tr>

                    <tr class="site_table_row_2">
                        <td class="site_table">Announcement Date</td><td class="site_table">
                            <?php
                                echo $dt->getAnnounceDate()->format($dt::DATE_FORMAT);
                            ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!--  Services -->
        <div class="listContainer" style="width: 99.5%; float: left; margin-top: 3em; margin-right: 10px;">
            <span class="header" style="vertical-align:middle; float: left; padding-top: 0.9em; padding-left: 1em;">
                Affected Services&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                <input type="checkbox" id="affectedEndpointsCheckBox" /> &nbsp;Show Affected Child Endpoints
            </span>
            <img src="<?php echo \GocContextPath::getPath()?>img/service.png" class="decoration" />
            <table style="clear: both; width: 100%;">
                <tr class="site_table_row_1">
                    <th class="site_table">Service Hostname (service type)</th>
                    <th class="site_table">Description</th>
                    <th class="site_table">Production</th>
                    <th class="site_table"><a href="index.php?Page_Type=Scope_Help">Scope(s)</a></th>
                </tr>

                <?php
                $num = 2;
                foreach($dt->getServices() as $se) {
                    $alreadyRenderedSE = array();

                    if(in_array($se->getId(), $alreadyRenderedSE)){
                        continue;
                    } else {
                        $alreadyRenderedSE[] = $se->getId();
                    }
                ?>

                <tr class="site_table_row_<?php echo $num ?>">
                    <td class="site_table" style="width: 35%">
                        <div style="background-color: inherit;">
                            <span style="vertical-align: middle;">
                            <a href="index.php?Page_Type=Service&amp;id=<?php echo $se->getId() ?>">
                                <?php echo xssafe($se->getHostname()) . " (" . xssafe($se->getServiceType()->getName()) . ")";?>
                            </a>
                            </span>
                        </div>
                    </td>
                    <td class="site_table"><?php xecho($se->getDescription()) ?></td>
                    <td class="site_table">
                    <?php
                    switch($se->getProduction()) {
                        case true:
                            ?>
                            <img src="<?php echo \GocContextPath::getPath()?>img/tick.png" height="22px" style="vertical-align: middle;" />
                            <?php
                            break;
                        case false:
                            ?>
                            <img src="<?php echo \GocContextPath::getPath()?>img/cross.png" height="22px" style="vertical-align: middle;" />
                            <?php
                            break;
                    }
                    ?>
                    </td>
                    <td class="site_table">
                    <?php xecho($se->getScopeNamesAsString()); ?>
                    </td>
                </tr>
                <!--&rdsh; -->
                <tr class="affectedendpointsrow site_table_row_<?php echo $num ?>">
                    <td class="site_table" colspan="4" style="padding-left:5em">
                        <table class="site_table" style="width: 100%; border: solid #D5D5D5 thin">
                            <tr>
                                <th class="site_table">Affected Endpoints</th>
                                <th class="site_table">Url</th>
                                <th class="site_table">Interface Name</th>
                            </tr>
                            <?php
                            foreach($se->getEndpointLocations() as $el){
                                echo '<tr>';
                                if(in_array($el, $dt->getEndpointLocations()->toArray())){
                                    echo '<td>&check; <a href="index.php?Page_Type=View_Service_Endpoint&amp;id=' . $el->getId() . '">'.xssafe($el->getName()).'</a></td>';
                                    echo '<td>'.xssafe($el->getUrl()).'</td>';
                                    echo '<td>'.xssafe($el->getInterfaceName()).'</td>';
                                } else {
                                    echo '<td><span style=\'color: grey\'>&cross; <a href="index.php?Page_Type=View_Service_Endpoint&amp;id='.$el->getId().'">'.xssafe($el->getName()).'</span></td>';
                                    echo "<td><span style='color: grey'>".xssafe($el->getUrl()).'</span></td>';
                                    echo "<td><span style='color: grey'>".xssafe($el->getInterfaceName()).'</span></td>';
                                }
                                echo '</tr>';
                            }
                            ?>
                        </table>
                    </td>
                </tr>

                <?php
                    if($num == 1) { $num = 2; } else { $num = 1; }
                } // End of the foreach loop iterating over SEs
                ?>
            </table>
        </div>

    </div>
</div>

<script>
$(document).ready(function(){
    // hide all nested affected endpoints by default
    $(".affectedendpointsrow").hide();

    // toggle hide/show affected endpoints on click
    $("#affectedEndpointsCheckBox").click(function(){
        $(".affectedendpointsrow").toggle();
    });
});
</script>
