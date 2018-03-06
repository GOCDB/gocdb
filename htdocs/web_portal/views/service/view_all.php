<div class="rightPageContainer">
    <div style="float: left;">
        <img src="<?php echo \GocContextPath::getPath()?>img/service.png" class="pageLogo" />
    </div>
    <div style="float: left;">
        <h1 style="float: left; margin-left: 0em;">
                Services
        </h1>
        <span style="clear: both; float: left; padding-bottom: 0.4em;">All Services in GOCDB</span>
    </div>

    <!-- Filter -->
    <div class="siteContainer">
        <form action="index.php?Page_Type=Services" method="GET" class="inline">
            <input type="hidden" name="Page_Type" value="Services" />

            <span class="header leftFloat">
                Filter <a href="index.php?Page_Type=Services">&nbsp;&nbsp;(clear)</a>
            </span>

            <div class="topMargin leftFloat clearLeft siteFilter">
                <span>Service Type: </span>
                <select id="serviceTypeSelect" name="serviceType"  multiple="multiple">
<!--		<input name="serviceType" list="seTypes">-->
<!--		<datalist id="seTypes">-->
            <option value="">(all)</option>
                    <?php foreach($params['serviceTypes'] as $serviceType) { ?>
                        <option value="<?php xecho($serviceType->getName()); ?>"<?php if($params['selectedServiceType'] == $serviceType->getName()) echo " selected"?>>
                <?php xecho($serviceType->getName()); ?>
            </option>
                    <?php  } ?>
<!--		</datalist>	-->
                </select>



        <!--<button id="uncheckAllSeTypeBtn" onclick="return false;">&laquo;Any</button>-->
            </div>

            <div class="topMargin leftFloat siteFilter">
                <span>NGI:</span>
                <select name="ngi">
                    <option value="">(all)</option>
                    <?php foreach ($params['ngis'] as $ngi){ ?>
                        <option value="<?php xecho($ngi->getName())?>"<?php if($params['selectedNgi'] == $ngi->getName()){echo " selected";} ?>><?php xecho($ngi->getName())?></option>
                    <?php }?>

                </select>
            </div>
            <?php
            //Clean off % from searchTerm
            if(isset($params['searchTerm'])){
                $params['searchTerm'] = str_replace('%', '', $params['searchTerm']);
            }
            ?>
            <div class="topMargin leftFloat clearLeft siteFilter">
                <span>Search for text in Hostname or Service Description: </span>
                <input type="text" name="searchTerm" style="width: 400px" <?php if(isset($params['searchTerm'])) echo "value=\"{$params['searchTerm']}\"";?>/>
            </div>

            <div class="topMargin leftFloat clearLeft siteFilter">
            <span>Production Service: </span>
                <select name="production">
                    <option value="">(all)</option>
                    <option value="TRUE"<?php if($params['selectedProduction'] == "TRUE") echo " selected" ?>>Y</option>
                    <option value="FALSE"<?php if($params['selectedProduction'] == "FALSE") echo " selected" ?>>N</option>
                </select>
            </div>

        <div class="topMargin leftFloat siteFilter">
                <span class="">Monitored Service: </span>
                <select name="monitored">
                    <option value="">(all)</option>
                    <option value="TRUE"<?php if($params['selectedMonitored'] == "TRUE") echo " selected" ?>>Y</option>
                    <option value="FALSE"<?php if($params['selectedMonitored'] == "FALSE") echo " selected" ?>>N</option>
                </select>
        </div>

            <div class="topMargin leftFloat siteFilter">
                <span class="">Site Certification:</span>
                <select name="certStatus">
                    <option value="">(all)</option>
                    <?php foreach($params['certStatuses'] as $certStatus) { ?>
                        <option value="<?php xecho($certStatus->getName()); ?>"
                            <?php if($params['selectedCertStatus'] == $certStatus->getName()) echo " selected"?>>
                            <?php xecho($certStatus->getName()); ?>
                        </option>
                    <?php  } ?>
                </select>
            </div>


        <div class="topMargin leftFloat siteFilter">
        <span class=""><a href="index.php?Page_Type=Scope_Help">Service Scopes:</a> </span>
        <select id="scopeSelect" multiple="multiple" name="mscope[]" style="width: 200px">
            <?php foreach ($params['scopes'] as $scope) { ?>
            <option value="<?php xecho($scope->getName()); ?>"
                <?php if(in_array($scope->getName(), $params['selectedScopes'])){ echo ' selected';}?> >
                <?php xecho($scope->getName()); ?>
            </option>
            <?php } ?>
        </select>
            <span class="">Scope match: </span>

            <select id="scopeMatchSelect" name="scopeMatch">
                <!--                <option value="" disabled selected>match</option>-->
                <option value="all"<?php if ($params['scopeMatch'] == "all") {
                    echo ' selected';
                } ?>>all (selected tags are AND'd)</option>
                <option value="any"<?php if ($params['scopeMatch'] == "any") {
                    echo ' selected';
                } ?>>any (selected tags are OR'd)</option>

            </select>
        </div>








        <div class="topMargin leftFloat clearLeft siteFilter">
        <span class="">Service Extension Name:</span>
                <select name="servKeyNames">
            <option value="">(none)</option>
            <?php foreach ($params['servKeyNames'] as $servExtensions) { ?>
                <option value="<?php xecho($servExtensions); ?>"<?php if ($params['selectedServKeyNames'] == $servExtensions) echo " selected" ?>>
                <?php xecho($servExtensions); ?>
                </option>
            <?php } ?>
                </select>
        </div>


        <div class="topMargin leftFloat siteFilter siteFilter">
        <span class="middle" style="margin-right: 0.4em">Extension Value: </span>
        <input class="middle" type="text" name="servKeyValue"
            <?php if (isset($params['selectedServKeyValue'])) echo "value=\"{$params['selectedServKeyValue']}\""; ?>/>
        </div>


        <div class="topMargin leftFloat clearLeft siteFilter">
        <span class="">Include Closed Sites: </span>
        <input type="checkbox" value=""<?php if ($params['showClosed'] == true) echo " checked=checked" ?> name="showClosed" />
        <input type="submit" value="Filter Services">
        </div>

        </form>
    </div>

    <!--  Services -->
    <div class="listContainer">
        <span class="header listHeader">
            <?php echo $params['totalServices'] ?> Services
        </span>
        <span class="listHeader">
            (Showing <?php echo $params['startRecord'] ?> - <?php echo $params['endRecord'] ?>)
        </span>
        <img src="<?php echo \GocContextPath::getPath()?>img/grid.png" class="decoration" />

    <table id="selectedSETable" class="table table-striped table-condensed tablesorter">
        <thead>
        <tr>
            <th>Hostname</th>
            <th>Service Type</th>
            <th>Production</th>
            <th>Monitored</th>
            <th>Host Site</th>
            <th>Scope(s)</th>
        </tr>
        </thead>
        <tbody>
        <?php
        if (count($params['services']) > 0) {
            foreach ($params['services'] as $se) {
            ?>
            <tr>
                <td>
                <a href="index.php?Page_Type=Service&amp;id=<?php echo $se->getId() ?>">
                    <?php xecho($se->getHostName()); ?>
                </a>
                </td>
                <td>
                <?php xecho($se->getServiceType()->getName()); ?>
                </td>
                <td>
                <?php if ($se->getProduction() == true) { ?>
                    <img src="<?php echo \GocContextPath::getPath() ?>img/tick.png" height=22px />
                <?php } else { ?>
                    <img src="<?php echo \GocContextPath::getPath() ?>img/cross.png" height=22px />
                <?php } ?>
                </td>

                <td>
                <?php if ($se->getMonitored() == true) { ?>
                    <img src="<?php echo \GocContextPath::getPath() ?>img/tick.png" height=22px />
                <?php } else { ?>
                    <img src="<?php echo \GocContextPath::getPath() ?>img/cross.png" height=22px />
                <?php } ?>
                </td>

                <td>
                <a href="index.php?Page_Type=Site&amp;id=<?php echo $se->getParentSite()->getId(); ?>">
                    <?php xecho($se->getParentSite()->getShortName()); ?>
                </a>
                </td>

                <td>
                <textarea readonly="true" style="height: 25px;"><?php xecho($se->getScopeNamesAsString()); ?></textarea>
                </td>

            </tr>
            <?php
            }
        }
        ?>

        </tbody>
    </table>

        <!--  Navigation -->
        <div style="text-align: center">
            <a href="<?php echo $params['firstLink'] ?>">
                <img class="nav" src="<?php echo \GocContextPath::getPath()?>img/first.png" />
            </a>
            <a href="<?php echo $params['previousLink'] ?>">
                <img class="nav" src="<?php echo \GocContextPath::getPath()?>img/previous.png" />
            </a>
            <a href="<?php echo $params['nextLink'] ?>">
                <img class="nav" src="<?php echo \GocContextPath::getPath()?>img/next.png" />
            </a>
            <a href="<?php echo $params['lastLink'] ?>">
                <img class="nav" src="<?php echo \GocContextPath::getPath()?>img/last.png" />
            </a>
        </div>

    </div>
    <br>&nbsp;
    <br>&nbsp;
    <br>&nbsp;
    <br>&nbsp;
    <br>&nbsp;
    <br>&nbsp;
    <br>&nbsp;
    <br>&nbsp;
</div>

<script type="text/javascript" src="<?php GocContextPath::getPath()?>javascript/jquery.multiple.select.js"></script>

<script>
    $(document).ready(function()
    {

    //$("#selectedSETable").tablesorter();

    // sort on first and second table cols only
    $("#selectedSETable").tablesorter({
        // pass the headers argument and assing a object
        headers: {
        // assign the third column (we start counting zero)
        2: {
            sorter: false
        },
        3: {
            sorter: false
        }
        }
    });


    $('#scopeSelect').multipleSelect({
        filter: true,
            placeholder: "Service Scopes"
        });
    // serviceType
    $('#serviceTypeSelect').multipleSelect({
        filter: true,
        single: true,
            placeholder: "Service Types"
        });
//	$("#uncheckAllSeTypeBtn").click(function() {
//            $("#serviceTypeSelect").multipleSelect("uncheckAll");
//        });
    });
</script>

