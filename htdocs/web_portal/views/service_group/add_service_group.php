<div class="rightPageContainer">
    <!--<script language="JavaScript" src="<?php echo \GocContextPath::getPath()?>javascript/vsites/add_ses_to_vsite.js"></script>-->
    <script language="JavaScript" src="<?php echo \GocContextPath::getPath()?>javascript/ajax.js"></script>
    <form name="New_Site" action="index.php?Page_Type=Add_Service_Group" method="post" class="inputForm">
    	<h1>New Service Group</h1>
    	<br />

    	<span class="input_name">
            Name
            <span class="input_syntax" >(Preferably all upper case, underscores for spaces, short and easily identifiable)</span>
        </span>
        <input class="input_input_text" type="text" name="name" value="" />

        <span class="input_name">
            Description
        </span>
        <input class="input_input_text" type="text" name="description" value="" />

        <span class="input_name">
            Contact E-Mail *
            <span class="input_syntax" >(valid email format)</span>
        </span>
        <input class="input_input_text" type="text" name="email" value="" />

        <span class="input_name" style="">
           Should this Service Group be monitored?
        </span>
        <input class="add_edit_form" style="width: auto; display: inline;" type="checkbox" name="monitored" value="" checked="checked"/>


        <br>
        <br>
        <div class="h4">Scope Tags
            <span class="input_syntax">(At least <?php echo $params['numberOfScopesRequired'] ?> Optional tag must be selected)</span>
        </div>
        <br>

        <div id="allscopeCheckBoxDIV">
            <h4>Optional Scope Tags</h4>
            <div id="optionalScopeCheckBoxDIV"></div> 
            <br/>
            <h4>Reserved Scope Tags</h4>
            <div id="reservedScopeCheckBoxDIV"></div> 
        </div>
          

    	<input class="input_button" type="submit" value="Add Service Group" />
    </form>
</div>

<script type="text/javascript" src="<?php echo \GocContextPath::getPath() ?>javascript/buildScopeCheckBoxes.js"></script>
<script type="text/javascript">

    $(document).ready(function () {
        var scopeJSON = JSON.parse('<?php echo($params["scopejson"]) ?>');
        addScopeCheckBoxes(scopeJSON, '#reservedScopeCheckBoxDIV', '#optionalScopeCheckBoxDIV', true);
    });
</script>  