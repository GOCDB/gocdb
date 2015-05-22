<?php
$configService = \Factory::getConfigService();

$services = $params['Impacted_Services'];
$endpoints = $params['Impacted_Endpoints'];

//To reuse this page for 'add' and 'edit' we use this boolean to change a couple of bits in the page
if(isset($params['isEdit'])){
    $edit = true;
}else{
    $edit = false;    
}

?>
<div class="rightPageContainer">
    <h1 class="Success">Confirm 
    <?php if($edit){
        echo "Edit";
    }?>
     Downtime</h1><br />
    Please review your downtime before submitting.<br />
    <ul>
    <li><b>Severity: </b><?php xecho($params['DOWNTIME']['SEVERITY'])?></li>
    <li><b>Description: </b><?php xecho($params['DOWNTIME']['DESCRIPTION'])?></li>
    <?php /*<li><b>Times defined in: </b><?php xecho($params['DOWNTIME']['DEFINE_TZ_BY_UTC_OR_SITE'])?> timezone</li> */ ?>
    <li><b>Starting (UTC): </b>
    <?php 
        //$startStamp = $params['DOWNTIME']['START_TIMESTAMP'];
	    //$timestamp = new DateTime("@$startStamp"); //Little PHP magic to create date object directly from timestamp
	    //echo date_format($timestamp, 'l jS \of F Y \a\t\: h:i A');
        xecho($params['DOWNTIME']['START_TIMESTAMP']);
    ?>
    </li>
    <li><b>Ending (UTC): </b>    
    <?php
        //$endStamp = $params['DOWNTIME']['END_TIMESTAMP'];
	    //$timestamp = new DateTime("@$endStamp"); //Little PHP magic to create date object directly from timestamp
	    //echo date_format($timestamp, 'l jS \of F Y \a\t\: h:i A'); 	    
        xecho($params['DOWNTIME']['END_TIMESTAMP']);
    ?></li>    
    <?php 
    if(count($services > 1)){
    	echo "<li><b>Affecting Services:</b>";	
    }else{
		echo "<li><b>Affecting Service:</b>";
	}   	
    ?>     
    	<ul>
    	<?php 
    	foreach($services as $id){
			$service = \Factory::getServiceService()->getService($id);
            $safeHostName = xssafe($service->getHostname());  
    		echo "<li>" . $safeHostName . "</li>";
    		}
		?>
    	</ul>
    </li>
    <?php 
    if(count($endpoints > 1)){
    	echo "<li><b>Affecting Endpoints:</b>";	
    }else{
		echo "<li><b>Affecting Endpoint:</b>";
	}   	
    ?>     
    	<ul>
    	<?php 
    	foreach($endpoints as $id){
			$endpoint = \Factory::getServiceService()->getEndpoint($id);
			if($endpoint->getName() != ''){
                $name = xssafe($endpoint->getName());
            }else{
                $name = xssafe("myEndpoint");
            }
    		echo "<li>" . $name . "</li>";
    		}
		?>
    	</ul>
    </li>
 	</ul>
 	<!-- Echo out a page type of edit or add downtime depending on type.  -->
 	<?php if(!$edit):?>
    <form name="Add_Downtime" action="index.php?Page_Type=Add_Downtime" 
          method="post" class="inputForm" id="Downtime_Form" name=Downtime_Form 
          onsubmit="document.getElementById('confirmSubmitBtn').disabled=true">
    <?php else:?>
    <form name="Add_Downtime" action="index.php?Page_Type=Edit_Downtime" 
          method="post" class="inputForm" id="Downtime_Form" name=Downtime_Form 
          onsubmit="document.getElementById('confirmSubmitBtn').disabled=true">
    <?php endif;?>
    	<?php $confirmed = true;?> 	
        <input class="input_input_text" type="hidden" name="CONFIRMED" value="<?php echo $confirmed;?>" />        
         <!-- json_encode caters for UTF-8 chars -->
        <input class="input_input_text" type="hidden" name="newValues" value="<?php xecho(json_encode($params));?>" />
        
     	<?php if(!$edit):?>
        <input id="confirmSubmitBtn" type="submit" value="Add downtime to GocDB" class="input_button"  >
        <?php else:?>
        <input id="confirmSubmitBtn" type="submit" value="Confirm Edit" class="input_button"  >
        <?php endif;?>
    </form>
</div>

