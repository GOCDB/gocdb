<?php
$endpoint = $params['endpoint'];
$se = $endpoint->getService();
$properties = $endpoint->getEndpointProperties();
$epId = $endpoint->getId();
$seId = $se->getId();

	//throw new Exception(var_dump($se));

?>

<div class="rightPageContainer rounded">
    <div style="float: left; text-align: center;">
        <img src="<?php echo \GocContextPath::getPath() ?>img/serviceEndpoint.png" class="pageLogo" />
    </div>
    <div style="float: left; width: 50em;">
        <h1 style="float: left; margin-left: 0em;"><?php xecho('Service Endpoint: ' . $endpoint->getName()) ?> </h1>
        <span style="clear: both; float: left; padding-bottom: 0.4em;">
	    Description: <?php xecho($endpoint->getDescription()) ?> 
        </span>
    </div>


    <!--  Edit link -->
    <!--  only show this link if we're in read / write mode -->
    <?php if (!$params['portalIsReadOnly'] && $params['ShowEdit']): ?>
        <div style="float: right;">
    	<div style="float: right; margin-left: 2em;">
    	    <a href="index.php?Page_Type=Edit_Service_Endpoint&endpointid=<?php echo $endpoint->getId(); ?>&serviceid=<?php echo $seId; ?>">
    		<img src="<?php echo \GocContextPath::getPath() ?>img/pencil.png" height="25px" style="float: right;" />
    		<br />
    		<br />
    		<span>Edit</span>
    	    </a>
    	</div>
    	<div style="float: right;">
    	    <script type="text/javascript" src="<?php echo \GocContextPath::getPath() ?>javascript/confirm.js"></script>
    	    <a onclick="return confirmSubmit()" 
    	       href="index.php?Page_Type=Delete_Service_Endpoint&endpointid=<?php echo $endpoint->getId(); ?>&serviceid=<?php echo $seId; ?>">
    		<img src="<?php echo \GocContextPath::getPath() ?>img/cross.png" height="25px" style="float: right; margin-right: 0.4em;" />
    		<br />
    		<br />
    		<span>Delete</span>
    	    </a>
    	</div>
        </div>
    <?php endif; ?>

    <!-- Parent Service Information -->
    <div style="float: left; width: 100%; margin-top: 2em;">
        <!--  System -->
        <div class="tableContainer rounded" style="width: 100%; float: left;">
            <span class="header" style="vertical-align:middle; float: left; padding-top: 0.9em; padding-left: 1em;">Parent Service</span>
            <img src="<?php echo \GocContextPath::getPath() ?>img/service.png" class="titleIcon"/>
            <table style="clear: both; width: 100%;">
                <tr class="site_table_row_1">
                    <td class="site_table">Name</td><td class="site_table">
			<a href="index.php?Page_Type=Service&id=<?php echo $se->getId() ?>">
			    <?php xecho($se->getHostname() . " (" . $se->getServiceType()->getName() . ")"); ?>
			</a>
                    </td>
                </tr>
            </table>
        </div>
    </div>


    <!-- Endpoint Information -->
    <div style="float: left; width: 100%; margin-top: 2em;">
        <!--  System -->
        <div class="tableContainer rounded" style="width: 100%; float: left;">
            <span class="header" style="vertical-align:middle; float: left; padding-top: 0.9em; padding-left: 1em;">Endpoint</span>
            <img src="<?php echo \GocContextPath::getPath() ?>img/serviceEndpoint.png" class="titleIcon"/>
            <table style="clear: both; width: 100%;">
                <tr class="site_table_row_1">
                    <td class="site_table">Name</td><td class="site_table"><?php xecho($endpoint->getName()) ?></td>
                </tr>
                <tr class="site_table_row_2">
                    <td class="site_table">Url</td><td class="site_table"><?php xecho($endpoint->getUrl()) ?></td>
                </tr>
                <tr class="site_table_row_1">
                    <td class="site_table">Interface Name</td><td class="site_table"><?php xecho($endpoint->getInterfaceName()) ?></td>
                </tr>
                <tr class="site_table_row_2">
                    <td class="site_table">Id</td><td class="site_table"><?php echo $endpoint->getId() ?></td>
                </tr>

            </table>
        </div>
    </div>

    <div style="float: left; width: 100%; margin-top: 2em;"> 
	More (GLUE2) attributes can be added on request - please contact gocdb developers.  
    </div>                 


    <!-- Extension Properties -->
    <div class="tableContainer" style="width: 99.5%; float: left; margin-top: 3em; margin-right: 10px;">
        <span class="header" style="vertical-align:middle; float: left; padding-top: 0.9em; padding-left: 1em;">Endpoint Extension Properties</span>        
        <img src="<?php echo \GocContextPath::getPath() ?>img/keypair.png" height="25px" style="float: right; padding-right: 1em; padding-top: 0.5em; padding-bottom: 0.5em;" />
        <table id="endpointExtensionPropsTable" class="table table-striped table-condensed tablesorter">
	    <thead>
		<tr>
		    <th>Name</th>
		    <th>Value</th>  
		    <?php if (!$params['portalIsReadOnly']): ?>
    		    <th>Edit</th>
				<th><input type="checkbox" id="selectAllProps"/> Select All</th>
		    <?php endif; ?>              
		</tr>
	    </thead>
	    <tbody>
		<?php
		//$num = 2;
		foreach ($properties as $prop) {
		    ?>

    		<tr>
    		    <td style="width: 35%;"class="site_table"><?php xecho($prop->getKeyName()); ?></td>
    		    <td style="width: 35%;"class="site_table"><?php xecho($prop->getKeyValue()); ?></td>
			<?php if (!$params['portalIsReadOnly']): ?>	                
			    <!--<td style="width: 10%;"align = "center"class="site_table"><a href="index.php?Page_Type=Edit_Endpoint_Property&propertyid=<?php echo $prop->getId(); ?>&endpointid=<?php echo $epId; ?>"><img height="25px" src="<?php echo \GocContextPath::getPath() ?>img/pencil.png"/></a></td>-->
			    <td style="width: 10%;"><a href="index.php?Page_Type=Edit_Endpoint_Property&propertyid=<?php echo $prop->getId(); ?>"><img height="25px" src="<?php echo \GocContextPath::getPath() ?>img/pencil.png"/></a></td>
			    <!--<td style="width: 10%;"align = "center"class="site_table"><a href="index.php?Page_Type=Delete_Endpoint_Property&propertyid=<?php echo $prop->getId(); ?>&endpointid=<?php echo $epId; ?>"><img height="25px" src="<?php echo \GocContextPath::getPath() ?>img/cross.png"/></a></td>-->
<!--			    <td style="width: 10%;"><a href="index.php?Page_Type=Delete_Endpoint_Property&propertyid=--><?php //echo $prop->getId(); ?><!--"><img height="25px" src="--><?php //echo \GocContextPath::getPath() ?><!--img/cross.png"/></a></td>-->
				<!--autocomplete off stops the checkboxes remembering checked state when reloading and revisiting pages-->
				<td style="width: 10%;"><input type='checkbox' class="propCheckBox" form="Modify_Endpoint_Properties_Form" name='selectedPropIDs[]' value="<?php echo $prop->getId();?>" autocomplete="off"/></td>
			<?php endif; ?>
    		</tr>
		    <?php
		    //if($num == 1) { $num = 2; } else { $num = 1; }
		}
		?>
	    </tbody>
        </table>
        <!--  only show this link if we're in read / write mode -->
	<?php if (!$params['portalIsReadOnly'] && $params['ShowEdit']): ?>
    	<!-- Add new Service Property -->
    	<a href="index.php?Page_Type=Add_Endpoint_Property&endpointid=<?php echo $endpoint->getId(); ?>">
    	    <img src="<?php echo \GocContextPath::getPath() ?>img/add.png" height="50px" style="float: left; padding-top: 0.9em; padding-left: 1.2em; padding-bottom: 0.9em;"/>
    	    <span class="header" style="vertical-align:middle; float: left; padding-top: 1.1em; padding-left: 1em; padding-bottom: 0.9em;">
    		Add Property
    	    </span>
    	</a>
		<form action="index.php?Page_Type=Endpoint_Properties_Controller" method="post" id="Modify_Endpoint_Properties_Form" style="vertical-align:middle; float: right; padding-top: 1.1em; padding-right: 1em; padding-bottom: 0.9em;">
			<input class="input_input_text" type="hidden" name ="endpointID" value="<?php echo $endpoint->getId();?>" />
			<select name="action" autocomplete="off">
				<option value="" disabled selected>Select action...</option>
				<option value="delete">Delete selected</option>
			</select>


			<input class="input_button" type="submit" value="Modify Selected Service Properties"/>
		</form>
	<?php endif; ?>
    </div>

    <script type="text/javascript">
	$(document).ready(function () {

	    // sort on first and second table cols only 
	    $("#endpointExtensionPropsTable").tablesorter({
		// pass the headers argument and assing a object 
		headers: {
		    // assign the third column (we start counting zero) 
		    2: {
			// disable it by setting the property sorter to false 
			sorter: false
		    },
		    3: {
			// disable it by setting the property sorter to false 
			sorter: false
		    }
		}
	    });

		//register handler for the select/deselect all properties checkbox
		$("#selectAllProps").change(function(){
			$(".propCheckBox").prop('checked', $(this).prop("checked"));
		});

	}
	);
    </script>  
