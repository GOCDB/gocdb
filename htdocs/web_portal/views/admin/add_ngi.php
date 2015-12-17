<div class="rightPageContainer">
    <h1>Add NGI</h1>
    <br />
        When adding an NGI, please <b>also add a relevant image file</b> (usually a national flag)
        to web_portal/img/ngi/fullsize with the same name as the NGI and '.jpg' file
        extension. A smaller copy with the same name should be placed in 
        web_portal/img/ngi, this smaller image should not exceed width:28px, height:25px.
    <br />
    <br />
    <form class="inputForm" method="post" action="index.php?Page_Type=Admin_Add_NGI" name="addNGI">
        
        <span class="input_name">Name
            <span class="input_syntax">(Unique name)</span>
        </span>
        <input type="text" value="" name="NAME" class="input_input_text">
        
        <span class="input_name">Management contact/mailing list 
            <span class="input_syntax">(valid email format)</span>
        </span>
        <input class="input_input_text" type="text" name="EMAIL" value="">
        
        <span class="input_name">Helpdesk / Mailing list for GGUS tickets
            <span class="input_syntax">(valid email format)</span>
        </span>
        <input class="input_input_text" type="text" name="HELPDESK_EMAIL" value="">
        
        <span class="input_name">ROD Mailing list
            <span class="input_syntax">(valid email format)</span>
        </span>
        <input class="input_input_text" type="text" name="ROD_EMAIL" value="">
        
        <span class="input_name">Security contact / mailing list 
            <span class="input_syntax">(valid email format)</span>
        </span>
        <input class="input_input_text" type="text" name="SECURITY_EMAIL" value="">

        <span class="input_name">GGUS Support Unit
            <span class="input_syntax"></span>
        </span>
        <input class="input_input_text" type="text" name="GGUS_SU" value="">
       
        <?php /*
        <span class="input_name">Scope(s)
            <span class="input_syntax">(Select at least <?php xecho($params['NumberOfScopesRequired'])?>)</span>
        </span>
        <div style="margin-left: 2em">    
        <?php foreach ($params['Scopes'] as $scopeArray){ ?>
            <br />
            <input type="checkbox" name="SCOPE_IDS[]" value="<?php echo $scopeArray['scope']->getId();?>"<?php if($scopeArray['applied']){echo ' checked="checked"';}?>>
            <?php xecho($scopeArray['scope']->getName());?>

        <?php } ?>
        </div>
         */?>
        
        <br/>
        <br/>
        <!-- Scope Tags-->
        <div class="h4">Scope Tags
            <span class="input_syntax">(At least <?php echo $params['numberOfScopesRequired'] ?> Optional tag must be selected)</span>
        </div>
        <br/>
        <div id="allscopeCheckBoxDIV">
            <h4>Optional Scope Tags</h4>
            <div id="optionalScopeCheckBoxDIV"></div> 
            <br/>
            <h4>Reserved Scope Tags</h4>
            <div id="reservedScopeCheckBoxDIV"></div> 
        </div> 
        
        <br />
        <input type="submit" value="Add NGI" class="input_button">
    </form>
</div>



<script type="text/javascript" src="<?php echo \GocContextPath::getPath() ?>javascript/buildScopeCheckBoxes.js"></script>
<script type="text/javascript">

    $(document).ready(function () {
        var scopeJSON = JSON.parse('<?php echo($params["scopejson"]) ?>');
        addScopeCheckBoxes(scopeJSON, '#reservedScopeCheckBoxDIV', '#optionalScopeCheckBoxDIV', true);
    });
</script> 