<?php $ngis = $params['NGIs']?>
<?php $sites = $params['Sites']?>
<?php $sGroups = $params['ServiceGroups']?>
<?php $sEndpoints = $params['Services']?>
<?php $scopeId = $params['ID']?>

<div class="rightPageContainer">
	<h1 class="Success">Scope In Use</h1><br />
	The scope ' 
	<a href="index.php?Page_Type=Admin_Scope&id=<?php echo $scopeId;?>">
        <?php xecho($params['Name']);?>
    </a>' 
    is currently in use. If you are absolutely sure you still want to delete it, 
    a deletion button can be found at the bottom of the page.
    <br />
    <br />
    The following NGIs are currently tagged with this scope:
	<?php 
        foreach($ngis as $ngi){
            echo "<br />"
            	. "<a href=\"index.php?Page_Type=NGI&id=" . $ngi->getId() ."\">"
            	.  xssafe($ngi->getName())
            	. "</a> ";
        }	
	?>
    <br />
    <br />
    The following sites are currently tagged with this scope:
	<?php 
        foreach($sites as $site){
            echo "<br />"
            	. "<a href=\"index.php?Page_Type=Site&id=" . $site->getId() ."\">"
            	.  xssafe($site->getShortName())
            	. "</a> ";
        }	
	?>
    <br />
    <br />
    The following service groups are currently tagged with this scope:
	<?php 
        foreach($sGroups as $sGroup){
            echo "<br />"
            	. "<a href=\"index.php?Page_Type=Service_Group&id=" . $sGroup->getId() ."\">"
            	.  xssafe($sGroup->getName())
            	. "</a> ";
        }	
	?>
    <br />
    <br />
    The following services are currently tagged with this scope:
	<?php 
        foreach($sEndpoints as $sEndpoint){
            echo "<br />"
            	. "<a href=\"index.php?Page_Type=Service&id=" . $sEndpoint->getId() ."\">"
            	.  xssafe($sEndpoint->getHostName())
            	. "</a> ";
        }	
	?>
    <br />
    <br />
    <form class="inputForm" method="post" action="index.php?Page_Type=Admin_Remove_Scope" name="RemoveScope">
        <input class="input_input_hidden" type="hidden" name="id" value="<?php echo $scopeId ?>" />
        <input class="input_input_hidden" type="hidden" name="ScopeInUseOveride" value="true" />
        <input type="submit" value="Remove Scope which is currently in use" class="input_button">
    </form>

</div>