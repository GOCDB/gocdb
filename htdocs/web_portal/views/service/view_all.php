<div class="rightPageContainer">
    <div style="float: left;">
        <img src="img/service.png" class="pageLogo" />
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
            
            <div class="topMargin leftFloat clearLeft">
                <span class="">Service Type: </span>
                <select name="serviceType" onchange="form.submit()">
                	<option value="">(all)</option>
                    <?php foreach($params['serviceTypes'] as $serviceType) { ?>
                      	<option value="<?php echo $serviceType->getName(); ?>"<?php if($params['selectedServiceType'] == $serviceType->getName()) echo " selected"?>><?php echo $serviceType->getName(); ?></option> 
                    <?php  } ?>   
                </select>
            </div>
            
            <div class="topMargin leftFloat siteFilter">
            	<span class="">NGI:</span>
                <select name="ngi" onchange="form.submit()">                                        
                    <option value="">(all)</option>
                    <?php foreach ($params['ngis'] as $ngi){ ?>
                    	<option value="<?php echo $ngi->getName()?>"<?php if($params['selectedNgi'] == $ngi->getName()){echo " selected";} ?>><?php echo $ngi->getName()?></option>
                    <?php }?>
                    
                </select>
        	</div>
            <?php 
            //Clean off % from searchTerm
            if(isset($params['searchTerm'])){
                $params['searchTerm'] = str_replace('%', '', $params['searchTerm']);
            } 
            ?>
            <div class="topMargin leftFloat siteFilter">
                <span class="middle" style="margin-right: 0.4em">Search: </span>
                <input class="middle" style="width: 5.5em;" type="text" name="searchTerm" <?php if(isset($params['searchTerm'])) echo "value=\"{$params['searchTerm']}\"";?>/>
                <input class="middle" type="image" src="img/enter.png" name="image" width="20" height="20">        
            </div>
            
            <div class="topMargin leftFloat clearLeft">
            	<span class="">Production: </span>
                <select name="production" onchange="form.submit()">
                    <option value="">(all)</option>
                    <option value="TRUE"<?php if($params['selectedProduction'] == "TRUE") echo " selected" ?>>Y</option>
                    <option value="FALSE"<?php if($params['selectedProduction'] == "FALSE") echo " selected" ?>>N</option>
                </select>
        	</div>
        	
        	<div class="topMargin leftFloat siteFilter">
            	<span class="">Monitored: </span>
                <select name="monitored" onchange="form.submit()">
                    <option value="">(all)</option>
                    <option value="TRUE"<?php if($params['selectedMonitored'] == "TRUE") echo " selected" ?>>Y</option>
                    <option value="FALSE"<?php if($params['selectedMonitored'] == "FALSE") echo " selected" ?>>N</option>
                </select>
        	</div>
        	
        	<div class="topMargin leftFloat siteFilter">
            	<span class=""><a href="index.php?Page_Type=Scope_Help">Scope</a></span>
                <select name="scope" onchange="form.submit()">
                    <option value="">(all)</option>
                    <?php foreach ($params['scopes'] as $scope){ ?>
                        <option value="<?php echo $scope->getName()?>"<?php if($params['selectedScope'] == $scope->getName()){echo " selected";} ?>><?php echo $scope->getName()?></option>
                    <?php }?>
                </select>
        	</div>
        	
        	
        	<div class="topMargin leftFloat siteFilter">
            	<span class="">Certification:</span>
                <select name="certificationStatus" onchange="form.submit()">
					<option value="">(all)</option>
                    <?php foreach($params['certStatuses'] as $certStatus) { ?>
                        <option value="<?php echo $certStatus->getName(); ?>"<?php if($params['selectedCertStatus'] == $certStatus->getName()) echo " selected"?>><?php echo $certStatus->getName(); ?></option> 
                    <?php  } ?>                  
                </select>
        	</div>

        	
        	<div class="topMargin leftFloat siteFilter">
            	<span class="">Include Closed Sites: </span>
            	<input type="checkbox" value=""<?php if($params['showClosed'] == true) echo " checked=checked" ?> name="showClosed" onchange="form.submit()"> 
        	</div>        	<br>  

        	<div class="topMargin leftFloat siteFilter">
            	<span class="">Extension Name:</span>
                <select name="servKeyNames" onchange="form.submit()">
					<option value="">(none)</option>
                    <?php foreach($params['servKeyNames'] as $servExtensions) { ?>
                        <option value="<?php echo $servExtensions; ?>"<?php if($params['selectedServKeyNames'] == $servExtensions) echo " selected"?>><?php echo $servExtensions; ?></option> 
                    <?php  } ?>                  
                </select>
        	</div> 
        	<?php        	
        	if($params['selectedServKeyNames'] != ""){ ?>
 
             <div class="topMargin leftFloat siteFilter">
                <span class="middle" style="margin-right: 0.4em">Extension Value: </span>
                <input class="middle" style="width: 5.5em;" type="text" name="selectedServKeyValue" <?php if(isset($params['selectedServKeyValue'])) echo "value=\"{$params['selectedServKeyValue']}\"";?>/>
                <input class="middle" type="image" src="img/enter.png" name="image" width="20" height="20">        
            </div>
        	
            <?php }?>               	
        </form>
    </div>
    
    <!--  Services -->
    <div class="listContainer">
        <span class="header listHeader">
            <?php echo $params['totalServices'] ?> Service<?php if($params['totalServices'] != 1) echo "s"?>
        </span>
        <span class="listHeader">
            (Showing <?php echo $params['startRecord'] ?> - <?php echo $params['endRecord'] ?>)
        </span>
        <img src="img/grid.png" class="decoration" />
        <table class="vSiteResults" id="selectedSETable">
            <tr class="site_table_row_1">
                <th class="site_table">Hostname</th>
                <th class="site_table">Service Type</th>
                <th class="site_table">Production</th>
                <th class="site_table">Monitored</th>
                <th class="site_table"><a href="index.php?Page_Type=Scope_Help">Scope(s)</a></th>
                <th class="site_table">Host Site</th>
            </tr>
            <?php
            $num = 2;
            if(count($params['services']) > 0) {
            foreach($params['services'] as $se) {
                        
            $scopeC = count($se->getScopes());
            $style=""; //Set no style as the default
                if($scopeC != 0){
                    if($se->getScopes()->first()->getName() == "Local") { 
                        $style = " style=\"background-color: #A3D7A3;\"";
                    } 
                } 
            ?>
            <tr class="site_table_row_<?php echo $num ?>" <?php echo $style ?>>
                <td class="site_table">
                    <div style="background-color: inherit;">
                        <span style="vertical-align: middle;">
                            <a href="index.php?Page_Type=Service&id=<?php echo $se->getId() ?>">
                                <span>&raquo; </span><?php echo $se->getHostName(); ?>
                            </a>
                        </span>
                    </div>
                </td>
                    
                <td class="site_table">
                    <?php echo $se->getServiceType()->getName(); ?>
                </td>
                
                <td class="site_table">
                    <?php if($se->getProduction() == true) { ?>
                    	<img src="img/tick.png" height=22px />
                   	<?php } else { ?>
                   		<img src="img/cross.png" height=22px />
                   	<?php } ?>
                </td>
                
                <td class="site_table">
                    <?php if($se->getMonitored() == true) { ?>
                    	<img src="img/tick.png" height=22px />
                   	<?php } else { ?>
                   		<img src="img/cross.png" height=22px />
                   	<?php } ?>
                </td>
                
                <td class="site_table">
                    <?php echo $se->getScopeNamesAsString() ?>
                </td>
                
                <td class="site_table">
                    <a href="index.php?Page_Type=Site&id=<?php echo $se->getParentSite()->getId(); ?>">
                        <?php echo $se->getParentSite()->getShortName(); ?>
                    </a>
                </td>
            </tr>
            <?php  
                if($num == 1) { $num = 2; } else { $num = 1; }
                } // End of the foreach loop iterating over sites
            }
            ?>
        </table>
        <!--  Navigation -->
        <div style="text-align: center">
            <a href="<?php echo $params['firstLink'] ?>">
                <img class="nav" src="img/first.png" />
            </a>
            <a href="<?php echo $params['previousLink'] ?>">
                <img class="nav" src="img/previous.png" />
            </a>
            <a href="<?php echo $params['nextLink'] ?>">
                <img class="nav" src="img/next.png" />
            </a>
            <a href="<?php echo $params['lastLink'] ?>">
                <img class="nav" src="img/last.png" />
            </a>  
        </div>    
        
    </div>
</div>