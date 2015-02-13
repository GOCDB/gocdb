<div class="rightPageContainer">
    <div style="float: left;">
        <img src="<?php echo \GocContextPath::getPath()?>img/site.png" class="pageLogo" />
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
                <select name="NGI" onchange="form.submit()">
                    <option value="">(all)</option>
                    <?php foreach($params['NGIs'] as $ngi) { ?>
                        <option value="<?php xecho($ngi->getName()); ?>"<?php if($params['selectedNgi'] == $ngi->getName()) echo " selected"?>><?php xecho($ngi->getName()); ?></option> 
                    <?php  } ?>
                </select>
        </div>
        
        <div class="topMargin leftFloat siteFilter">
            <span class="">Certification: </span>
                <select name="certStatus" onchange="form.submit()">
                    <option value="">(all)</option>
                    <?php foreach($params['certStatuses'] as $certStatus) { ?>
                        <option value="<?php xecho($certStatus->getName()); ?>"<?php if($params['selectedCertStatus'] == $certStatus->getName()) echo " selected"?>><?php xecho($certStatus->getName()); ?></option> 
                    <?php  } ?>   
                </select>
        </div>
        
        <div class="topMargin leftFloat siteFilter">
            <span class="">Infrastructure: </span>
                <select name="prodStatus" onchange="form.submit()">
                    <option value="">(all)</option>
                    <?php foreach($params['prodStatuses'] as $prodStatus) { ?>
                        <option value="<?php xecho($prodStatus->getName()); ?>"<?php if($params['selectedProdStatus'] == $prodStatus->getName()) echo " selected"?>><?php xecho($prodStatus->getName()); ?></option> 
                    <?php  } ?>   
                </select>
        </div>
        
        <div class="topMargin leftFloat siteFilter">
            <span class="">Scope: </span>
                <select name="scope" onchange="form.submit()">
                    <option value="">(all)</option>
                    <?php foreach ($params['scopes'] as $scope){ ?>
                        <option value="<?php xecho($scope->getName()); ?>"<?php if($params['selectedScope'] ==  $scope->getName()) echo " selected" ?>><?php xecho($scope->getName()); ?></option>
                    <?php } ?>    
                        
                        
                </select>
        </div>
        
        <div class="topMargin leftFloat siteFilter">
            <span class="">Include Closed Sites: </span>
            <input type="checkbox" value=""<?php if($params['showClosed'] == true) echo " checked=checked" ?> name="showClosed" onchange="form.submit()"> 
        </div>
        <br>  

        	<div class="topMargin leftFloat siteFilter">
            	<span class="">Extension Name:</span>
                <select name="siteKeyNames" onchange="form.submit()">
					<option value="">(none)</option>
                    <?php foreach($params['siteKeyNames'] as $siteExtensions) { ?>
                        <option value="<?php echo $siteExtensions; ?>"<?php if($params['selectedSiteKeyNames'] == $siteExtensions) echo " selected"?>><?php echo $siteExtensions; ?></option> 
                    <?php  } ?>                  
                </select>
        	</div> 
        	<?php        	
        	if($params['selectedSiteKeyNames'] != ""){ ?> 
             <div class="topMargin leftFloat siteFilter">
                <span class="middle" style="margin-right: 0.4em">Extension Value: </span>
                <input class="middle" style="width: 5.5em;" type="text" name="selectedSiteKeyValue" <?php if(isset($params['selectedSiteKeyValue'])) echo "value=\"{$params['selectedSiteKeyValue']}\"";?>/>
                <input class="middle" type="image" src="<?php echo \GocContextPath::getPath()?>img/enter.png" name="image" width="20" height="20">        
            </div>        	
            <?php }?>   
        </form>
    </div>
    
    <!--  Sites -->
    <div class="listContainer">
        <span class="header listHeader">
            <?php echo sizeof($params['sites']) ?> Site<?php if(sizeof($params['sites']) != 1) echo "s"?>
        </span>
        <img src="<?php echo \GocContextPath::getPath()?>img/grid.png" class="decoration" />
        <table class="vSiteResults" id="selectedSETable">
            <tr class="site_table_row_1">
                <th class="site_table">Name</th>
                <th class="site_table">NGI</th>
                <th class="site_table">Infrastructure</th>
                <th class="site_table">Certification Status</th>
                <th class="site_table"><a href="index.php?Page_Type=Scope_Help">Scope(s)</a></th>
            </tr>
            <?php           
            $num = 2;
            if(sizeof($params['sites'] > 0)) {
            foreach($params['sites'] as $site) {
            ?>
            <?php
            $scopeC = count($site->getScopes());
            $style=""; //Set no style as the default
                if($scopeC != 0){
                    if($site->getScopes()->first()->getName() == "Local") { 
                        $style = " style=\"background-color: #A3D7A3;\"";
                    } 
                } 
            ?>
            <tr class="site_table_row_<?php echo $num ?>" <?php echo $style ?>>
                <td class="site_table">
                    <div style="background-color: inherit;">
                        <span style="vertical-align: middle;">
                            <a href="index.php?Page_Type=Site&id=<?php echo $site->getId() ?>">
                                <span>&raquo; </span><?php echo $site->getShortName(); ?>
                            </a>
                        </span>
                    </div>
                </td>
                    
                <td class="site_table">
                    <?php xecho($site->getNGI()->getName()); ?>
                </td>
                
                <td class="site_table">
                    <?php xecho($site->getInfrastructure()->getName()); ?>
                </td>
                
                <td class="site_table">
                    <?php xecho($site->getCertificationStatus()->getName()); ?>
                </td>
                
                
                <td class="site_table">
                    <?php xecho($site->getScopeNamesAsString()) ?>
                    </td>
                
            </tr>
            <?php  
                if($num == 1) { $num = 2; } else { $num = 1; }
                } // End of the foreach loop iterating over sites
            }
            ?>
        </table>
    </div>
</div>