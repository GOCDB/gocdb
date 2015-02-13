<div class="rightPageContainer">
    <form name="Update_Group" action="index.php?Page_Type=Edit_NGI" method="post" class="inputForm">
    	
    	<h1><?php xecho($params['ngi']->getName()) ?></h1>
    	<br />
    	
        <span class="input_name">
            Management contact/mailing list 
            <span class="input_syntax">
                (valid email format)
            </span>
        </span>
        <input class="input_input_text" type="text" name="EMAIL" value="<?php xecho($params['ngi']->getEmail()); ?>">
        
        <span class="input_name">
            Helpdesk / Mailing list for GGUS tickets
            <span class="input_syntax">
                (valid email format)
            </span>
        </span>
        <input class="input_input_text" type="text" name="HELPDESK_EMAIL" value="<?php xecho($params['ngi']->getHelpdeskEmail()); ?>">
        
        <span class="input_name">
            ROD Mailing list
            <span class="input_syntax">
                (valid email format)
            </span>
        </span>
        <input class="input_input_text" type="text" name="ROD_EMAIL" value="<?php xecho($params['ngi']->getRodEmail()); ?>">
        
        <span class="input_name">
            Security contact / mailing list 
            <span class="input_syntax">
                (valid email format)
            </span>
        </span>
        <input class="input_input_text" type="text" name="SECURITY_EMAIL" value="<?php xecho($params['ngi']->getSecurityEmail()); ?>">

        <span class="input_name">
            GGUS Support Unit 
            <span class="input_syntax">
            </span>
        </span>
        <input class="input_input_text" type="text" name="GGUS_SU" value="<?php xecho($params['ngi']->getGgus_Su()); ?>">
        
        <span class="input_name">Scope(s)
            <span class="input_syntax">(Select at least <?php xecho($params['numberOfScopesRequired'])?>)</span>
        </span>
        <div style="margin-left: 2em">    
        <?php foreach ($params['scopes'] as $scopeArray){ ?>
            <br />
            <input type="checkbox" name="SCOPE_IDS[]" value="<?php echo $scopeArray['scope']->getId();?>"<?php if($scopeArray['applied']){echo ' checked="checked"';}?>>
            <?php xecho($scopeArray['scope']->getName());?>

        <?php } ?>
        </div>
            
        <input class="input_input_hidden" type="hidden" name="ID" value="<?php echo $params['ngi']->getId(); ?>">
        <br />
        <input class="input_button" type="submit" value="Update NGI">
    </form>
</div>