<div class="rightPageContainer">
    <div style="float: left;">
        <img src="<?php echo \GocContextPath::getPath() ?>img/site.png" class="pageLogo" />
    </div>
    <div style="float: left;">
        <h1 style="float: left; margin-left: 0em;">
        Sites
        </h1>
        <span style="clear: both; float: left; padding-bottom: 0.4em;">All Sites in GOCDB</span>
    </div>

    <!-- Filter -->
    <div class="siteContainer">
        <form action="index.php?Page_Type=Sites" method="GET" class="inline">
        <input type="hidden" name="Page_Type" value="Sites" />

        <span class="header leftFloat">
        Filter <a href="index.php?Page_Type=Sites">&nbsp;&nbsp;(clear)</a>
        </span>

        <div class="topMargin leftFloat clearLeft">
        <span class="">NGI: </span>
                <select name="NGI">
                    <option value="">(all)</option>
            <?php foreach ($params['NGIs'] as $ngi) { ?>
                <option value="<?php xecho($ngi->getName()); ?>"
            <?php if ($params['selectedNgi'] == $ngi->getName()){ echo " selected";} ?>>
                <?php xecho($ngi->getName()); ?>
            </option> 
            <?php } ?>
                </select>
        </div>

        <div class="topMargin leftFloat siteFilter">
        <span class="">Certification: </span>
                <select name="certStatus" >
                    <option value="">(all)</option>
            <?php foreach ($params['certStatuses'] as $certStatus) { ?>
                <option value="<?php xecho($certStatus->getName()); ?>"
            <?php if ($params['selectedCertStatus'] == $certStatus->getName()){ echo " selected";} ?> >
                <?php xecho($certStatus->getName()); ?>
            </option> 
            <?php } ?>   
                </select>
        </div>

        <div class="topMargin leftFloat siteFilter">
        <span class="">Infrastructure: </span>
                <select name="prodStatus" >
                    <option value="">(all)</option>
            <?php foreach ($params['prodStatuses'] as $prodStatus) { ?>
                <option value="<?php xecho($prodStatus->getName()); ?>"
            <?php if ($params['selectedProdStatus'] == $prodStatus->getName()){ echo " selected";} ?>>
                <?php xecho($prodStatus->getName()); ?>
            </option> 
            <?php } ?>   
                </select>
        </div>

        <div class="topMargin leftFloat siteFilter">
        <span class=""><a href="index.php?Page_Type=Scope_Help">Site Scopes:</a> </span>
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



        <div class="topMargin leftFloat siteFilter">
        <span class="">Site Extension Name:</span>
                <select name="siteKeyNames">
            <option value="">(none)</option>
            <?php foreach ($params['siteKeyNames'] as $siteExtensions) { ?>
                <option value="<?php echo $siteExtensions; ?>"
            <?php if ($params['selectedSiteKeyNames'] == $siteExtensions) echo " selected" ?>>
                <?php echo $siteExtensions; ?>
            </option> 
            <?php } ?>                  
                </select>
        </div> 

        <div class="topMargin leftFloat siteFilter">
            <span class="middle" style="margin-right: 0.4em">Extension Value: </span>
            <input class="middle" type="text" name="selectedSiteKeyValue" 
            <?php if (isset($params['selectedSiteKeyValue'])) echo "value=\"{$params['selectedSiteKeyValue']}\""; ?>/>
            </div>  
        
        
        <div class="topMargin leftFloat siteFilter clearLeft">
        <span class="">Include Closed Sites: </span>
        <input type="checkbox" value=""<?php if ($params['showClosed'] == true){ echo " checked=checked";} ?> name="showClosed"> 
        <input type="submit" value="Filter Sites">
        </div>
        </form>
    </div>

    <!-- View Sites Table-->
    <div class="listContainer">
        <span class="header listHeader">
        <?php echo sizeof($params['sites']) ?> Site<?php if (sizeof($params['sites']) != 1) echo "s" ?>
        </span>
        <img src="<?php echo \GocContextPath::getPath() ?>img/grid.png" class="decoration" />
    
        <table id="selectedSiteTable" class="table table-striped table-condensed tablesorter">
        <thead>
            <tr>
                <th>Name</th>
                <th>NGI</th>
                <th>Infrastructure</th>
                <th>Certification Status</th>
                <th>Scope(s)</th>
            </tr>
        </thead>
        <tbody>
        <?php
        //$num = 2;
        if (sizeof($params['sites'] > 0)) {
        foreach ($params['sites'] as $site) {
            
//		    $scopeC = count($site->getScopes());
//		    $style = ""; //Set no style as the default
//		    if ($scopeC != 0) {
//			if ($site->getScopes()->first()->getName() == "Local") {
//			    $style = " style=\"background-color: #A3D7A3;\"";
//			}
//		    }
            ?>
            <tr>
            <td>
                <a href="index.php?Page_Type=Site&id=<?php echo $site->getId() ?>">
                <?php echo $site->getShortName(); ?>
                </a>
            </td>

            <td>
                <?php xecho($site->getNGI()->getName()); ?>
            </td>

            <td>
                <?php xecho($site->getInfrastructure()->getName()); ?>
            </td>

            <td>
                <?php xecho($site->getCertificationStatus()->getName()); ?>
            </td>

            <td>
                <textarea readonly="true" style="height: 25px;"><?php xecho($site->getScopeNamesAsString()); ?></textarea>
            </td>

            </tr>
            <?php
//		    if ($num == 1) {
//			$num = 2;
//		    } else {
//			$num = 1;
//		    }
        } // End of the foreach loop iterating over sites
        }
        ?>
        </tbody>	    
        </table>
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
        $("#selectedSiteTable").tablesorter(); 

    // sort on first and second table cols only 
//	$("#selectedSiteTable").tablesorter({ 
//	    // pass the headers argument and assing a object 
//	    headers: { 
//		// assign the third column (we start counting zero) 
//		4: { 
//		    sorter: false 
//		}
//	    } 
//	}); 
    
    $('#scopeSelect').multipleSelect({
        filter: true,
            placeholder: "Site Scopes"
        });
    });
</script>
