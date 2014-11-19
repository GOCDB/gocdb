<?php
$newValues = $params;
?>
<div class="rightPageContainer">
    <h1 class="Success">Confirm Downtime</h1><br />
    Please review your downtime before submitting.<br />
    <ul>
    <li><b>Severity: </b><?php echo $newValues['DOWNTIME']['SEVERITY']?></li>
    <li><b>Description: </b><?php echo $newValues['DOWNTIME']['DESCRIPTION']?></li>
    <li><b>Starting: </b>
    <?php 
	    $timestamp = $newValues['DOWNTIME']['START_TIMESTAMP'];
	    $format = 'd/m/Y H:i';
	    $date = DateTime::createFromFormat($format, $timestamp);
	    echo $date->format('l jS \of F Y \a\t\: h:i A');
    ?>
    </li>
    <li><b>Ending: </b>    
    <?php 
	    $timestamp = $newValues['DOWNTIME']['END_TIMESTAMP'];
	    $format = 'd/m/Y H:i';
	    $date = DateTime::createFromFormat($format, $timestamp);
	    echo $date->format('l jS \of F Y \a\t\: h:i A');
    ?></li>    
    <?php 
    if(count($newValues['Impacted_SEs']) > 1){
    	echo "<li><b>Affecting Services:</b>";	
    }else{
		echo "<li><b>Affecting Service:</b>";
	}   	
    ?>     
    	<ul>
    	<?php 
    	foreach($newValues['Impacted_SEs'] as $ise){
			$service = \Factory::getServiceService()->getService($ise);
    		echo "<li>" . $service->getHostname() . "</li>";
    		}
		?>
    	</ul>
    </li>
 	</ul>
    <form name="Add_Downtime" action="index.php?Page_Type=Add_Downtime" method="post" class="inputForm" id="Downtime_Form" name=Downtime_Form onsubmit="document.getElementById('confirmSubmitBtn').disabled=true">
    	<?php $confirmed = true;?> 	
        <input class="input_input_text" type="hidden" name ="CONFIRMED" value="<?php echo $confirmed;?>" />        
        <input class="input_input_text" type="hidden" name ="newValues" value="<?php echo htmlentities(serialize($newValues));?>" />
        
        
        <input id="confirmSubmitBtn" type="submit" value="Add downtime to GocDB" class="input_button"  >
    </form>
</div>

