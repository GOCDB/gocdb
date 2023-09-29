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


        <br/>
        <br/>
        <!-- Scope Tags-->
        <?php
        $parentObjectTypeLabel = 'Project';
        require_once __DIR__ . '/../fragments/editScopesFragment.php';
        ?>

        <br />
        <input type="submit" value="Add NGI" class="input_button">
    </form>
</div>



<script type="text/javascript" src="<?php echo \GocContextPath::getPath() ?>javascript/buildScopeCheckBoxes.js"></script>
<script type="text/javascript">

    $(document).ready(function () {
        var scopeJSON = JSON.parse('<?php echo($params["scopejson"]) ?>');
        ScopeUtil.addScopeCheckBoxes(scopeJSON,
            '#reservedScopeCheckBoxDIV',
            '#reservedOptionalScopeCheckBoxDIV',
            '#reservedOptionalInhertiableScopeCheckBoxDIV',
            '#optionalScopeCheckBoxDIV',
            true);
    });
</script>
