<?php
$sites = $params['sites'];
$serviceTypes = $params['serviceTypes'];
?>
<div class="rightPageContainer">
    <form name="Add_Service" action="index.php?Page_Type=Add_Service" method="post" class="inputForm">
        <h1>Add Service</h1>
        <br />
        <span class="input_name">Hosting Site</span>
        <select class="add_edit_form" name="hostingSite" id="siteSelectPullDown">
            <?php
            foreach ($sites as $site) {
                // If this site is the same as the one from the passing page
                if ((isset($params['site']) && ($params['site']->getId() == $site->getId()))) {
                    // Make the site selected
                    echo "<option value=\"{$site->getId()}\" selected=\"selected\">$site</option>";
                } else {
                    /* If the site isn't the one the user selected then
                     * add it to the list but don't select it */
                    echo "<option value=\"{$site->getId()}\">$site</option>";
                }
            }
            ?>
        </select>

        <span class="input_name">Service Type</span>
        <select class="add_edit_form" name="serviceType">
            <?php
            foreach ($serviceTypes as $st) {
                echo "<option value=\"" . $st->getId() . "\">$st</option>";
            }
            ?>
        </select>

        <span class="input_name">
            Service URL
            <span class="input_syntax" >(Alphanumeric and $-_.+!*'(),:)</span>
        </span>
        <input class="input_input_text" type="text" name="endpointUrl" value="" />

        <span class="input_name">Host name *
            <span class="input_syntax" >(valid FQDN format)</span>
        </span>
        <input class="input_input_text" type="text" name="HOSTNAME" />

        <span class="input_name">Host IP
            <span class="input_syntax" >a.b.c.d</span>
        </span>
        <input class="input_input_text" type="text" name="HOST_IP" />

        <span class="input_name">Host IPv6
            <span class="input_syntax" >(0000:0000:0000:0000:0000:0000:0000:0000[/int]) (optional [/int] range)</span>
        </span>
        <input class="input_input_text" type="text" name="HOST_IP_V6" />

        <span class="input_name">Host DN
            <span class="input_syntax" >(/C=.../OU=.../...)</span>
        </span>
        <input class="input_input_text" type="text" name="HOST_DN" />

        <span class="input_name">Description *
            <span class="input_syntax" >(Alphanumeric and basic punctuation)</span>
        </span>
        <input class="input_input_text" type="text" name="DESCRIPTION" />

        <span class="input_name">Host Operating System
            <span class="input_syntax" >(Alphanumeric and basic punctuation)</span>
        </span>
        <input class="input_input_text" type="text" name="HOST_OS" />

        <span class="input_name">Host Architecture
            <span class="input_syntax" >(Alphanumeric and basic punctuation)</span>
        </span>
        <input class="input_input_text" type="text" name="HOST_ARCH" />

        <span class="input_name">Is it a beta service (formerly PPS service)?</span>
        <select class="add_edit_form" name="HOST_BETA">
            <option value="N">N</option>
            <option value="Y">Y</option>
        </select>

        <span class="input_name">Is this service in production?</span>
        <select class="add_edit_form" name="PRODUCTION_LEVEL">
            <option value="N">N</option>
            <option value="Y">Y</option>
        </select>

        <span class="input_name">Is this service monitored?</span>
        <select class="add_edit_form" name="IS_MONITORED">
            <option value="N">N</option>
            <option value="Y">Y</option>
        </select>

        <span class="input_name">Contact E-Mail * (valid email format)
            <span class="input_syntax" >valid email format</span>
        </span>
        <input class="input_input_text" type="text" name="EMAIL" />


        <!-- Scope Tags-->
        <?php
        $parentObjectTypeLabel = 'Site';
        require_once __DIR__ . '/../fragments/editScopesFragment.php';
        ?>

        <br>

        <input class="input_button" type="submit" value="Add Service" />
    </form>
</div>

<script type="text/javascript" src="<?php echo \GocContextPath::getPath() ?>javascript/buildScopeCheckBoxes.js"></script>
<script type="text/javascript">

    $(document).ready(function () {

        //console.log('defalutVal: '+$('#ngiSelectPullDown').val());
        var entityId = $('#siteSelectPullDown').val();
        ScopeUtil.queryForJsonScopesAddScopeCheckBoxes('Add_Service', entityId,
          '#reservedScopeCheckBoxDIV',
          '#reservedOptionalScopeCheckBoxDIV',
          '#reservedOptionalInhertiableScopeCheckBoxDIV',
          '#optionalScopeCheckBoxDIV',
          true);

        $('#siteSelectPullDown').change(function () {
            //console.log($('#ngiSelectPullDown').val());
            var entityId = $('#siteSelectPullDown').val();
            ScopeUtil.queryForJsonScopesAddScopeCheckBoxes('Add_Service', entityId,
              '#reservedScopeCheckBoxDIV',
              '#reservedOptionalScopeCheckBoxDIV',
              '#reservedOptionalInhertiableScopeCheckBoxDIV',
              '#optionalScopeCheckBoxDIV',
              true);
        });

    });
</script>