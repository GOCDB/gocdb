<?php
$sg = $params['serviceGroup'];
?>
<div class="rightPageContainer">
    <h1>Service Group</h1>
    <br />
    <form class="inputForm" method="post" action="index.php?Page_Type=Edit_Service_Group" name="editSGroup">
        <span class="input_name">Name</span>
        <input type="text" value="<?php xecho($sg->getName()) ?>" name="name" class="input_input_text">
        <span class="input_name">Description</span>
        <input type="text" value="<?php xecho($sg->getDescription()) ?>" name="description" class="input_input_text">
        <span class="input_name">Contact E-Mail *<span class="input_syntax" >(valid email format)</span></span>
        <input type="text" value="<?php xecho($sg->getEmail()) ?>" name="email" class="input_input_text">
        <span class="input_name">Should this service group be Monitored?</span>
        <input class="add_edit_form" style="width: auto; display: inline;" type="checkbox" name="monitored" value="" <?php if($sg->getMonitored() == true) echo " checked=\"checked\""; ?> />
        
        <span class="input_name">Scope(s)
            <span class="input_syntax">(Select at least <?php echo $params['numberOfScopesRequired']?>)</span>
        </span>
        <div style="margin-left: 2em">    
        <?php foreach ($params['scopes'] as $scopeArray){ ?>
            <br />
            <input type="checkbox" name="Scope_ids[]" value="<?php echo $scopeArray['scope']->getId();?>"<?php if($scopeArray['applied']){echo ' checked="checked"';}?>>
            <?php echo $scopeArray['scope']->getName();?>

        <?php } ?>
        </div>

        <input class="input_input_hidden" type="hidden" name="objectId" value="<?php echo $sg->getId(); ?>" />
        <br />
        <input type="submit" value="Submit Changes" class="input_button">
    </form>
</div>