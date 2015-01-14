<div class="rightPageContainer">
    <div style="float: left;">
        <img src="<?php echo \GocContextPath::getPath()?>img/virtualSite.png" class="pageLogo" />
    </div>
    <div style="float: left;">
        <h1 style="float: left; margin-left: 0em;">
                Service Groups
        </h1>
        <span style="clear: both; float: left; padding-bottom: 0.4em;">
            All Service Groups in GOCDB.
        </span>
        <span style="clear: both; float: left; padding-bottom: 0.4em;">
            <a style="float: left; padding-top: 0.3em;" href="https://wiki.egi.eu/wiki/GOCDB/Input_System_User_Documentation#Service_Groups">
                What is a service group?
            </a>
        </span>
		<!-- <span style="clear: both; float: left;">Intentionally Blank</span>  -->
    </div>
    
    <!-- Filter -->
    <div class="siteContainer">
        <form action="index.php?Page_Type=Service_Groups" method="GET" class="inline">
        <input type="hidden" name="Page_Type" value="Service_Groups" />
        
        <span class="header leftFloat">
            Filter <a href="index.php?Page_Type=Service_Groups">&nbsp;&nbsp;(clear)</a>
        </span>
        <br />      
        <div class="topMargin leftFloat clearLeft">
            <span class="">Scope: </span>
                <select name="scope" onchange="form.submit()">
                    <option value="">(all)</option>
                    <?php foreach ($params['scopes'] as $scope){ ?>
                        <option value="<?php echo $scope->getName(); ?>"<?php if($params['selectedScope'] ==  $scope->getName()) echo " selected" ?>><?php echo $scope->getName(); ?></option>
                    <?php } ?>          
                </select>
        </div>                  

        	<div class="topMargin leftFloat siteFilter">
            	<span class="">Extension Name:</span>
                <select name="sgKeyNames" onchange="form.submit()">
					<option value="">(none)</option>
                    <?php foreach($params['sgKeyName'] as $sgExtensions) { ?>
                        <option value="<?php echo $sgExtensions; ?>"<?php if($params['selectedSGKeyName'] == $sgExtensions) echo " selected"?>><?php echo $sgExtensions; ?></option> 
                    <?php  } ?>                  
                </select>
        	</div> 
        	
        	<?php        	
        	if($params['selectedSGKeyName'] != ""){ ?> 
             <div class="topMargin leftFloat siteFilter">
                <span class="middle" style="margin-right: 0.4em">Extension Value: </span>
                <input class="middle" style="width: 5.5em;" type="text" name="selectedSGKeyValue" <?php if(isset($params['selectedSGKeyValue'])) echo "value=\"{$params['selectedSGKeyValue']}\"";?>/>
                <input class="middle" type="image" src="<?php echo \GocContextPath::getPath()?>img/enter.png" name="image" width="20" height="20">        
            </div>        	
            <?php }?>  
        </form>
    </div>

    <!--  Service Groups -->
    <div class="listContainer">
        <span class="header listHeader">
            <?php echo sizeof($params['sGroups']) ?> Service Group<?php if(sizeof($params['sGroups']) != 1) echo "s"?>
        </span>
        <img src="<?php echo \GocContextPath::getPath()?>img/grid.png" class="decoration" />
        <table class="vSiteResults" id="selectedSETable">
            <tr class="site_table_row_1">
                <th class="site_table">Name</th>
                <th class="site_table">Description</th>
                <th class="site_table"><a href="index.php?Page_Type=Scope_Help">Scope(s)</a></th>
            </tr>
            <?php           
            $num = 2;
            foreach($params['sGroups'] as $sGroup) {
            ?>
            <?php 
            if($sGroup->getScopes()->first() != null && 
                    $sGroup->getScopes()->first()->getName() == "Local") { $style = " style=\"background-color: #A3D7A3;\""; } else { $style = ""; } ?>
            <tr class="site_table_row_<?php echo $num ?>" <?php echo $style ?>>
                <td class="site_table">
                    <div style="background-color: inherit;">
                        <span style="vertical-align: middle;">
                            <a href="index.php?Page_Type=Service_Group&id=<?php echo $sGroup->getId()?>">
                                <span>&raquo; </span><?php echo $sGroup->getName(); ?>
                            </a>
                        </span>
                    </div>
                </td>
                    
                <td class="site_table">
                    <?php echo $sGroup->getDescription(); ?>
                </td>
                
                
                <td class="site_table">
                    <?php echo $sGroup->getScopeNamesAsString(); ?>
                </td>
            </tr>
            <?php  
                if($num == 1) { $num = 2; } else { $num = 1; }
                } // End of the foreach loop iterating over SEs
            ?>
        </table>
    </div>
</div>