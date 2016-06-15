<?php
$service = $params['se'];
$serviceName = $service->getHostName();
$serviceTypes = $params['serviceTypes'];
$siteName = $service->getParentSite()->getName();
?>
<div class="rightPageContainer">
    <form name="Edit_Service" action="index.php?Page_Type=Edit_Service" method="post" class="inputForm">
        <h1>Edit Service</h1>
        <br />

        <!-- Service Type -->
        <span class="input_name">Service Type</span>
        <select class="add_edit_form" name="serviceType">
            <?php foreach ($serviceTypes as $type) { ?>
                <option value="<?php echo $type->getId() ?>"<?php if ($service->getServiceType() == $type) echo " selected=\"selected\""; ?>><?php echo $type->getName() ?></option>
            <?php } ?>
        </select>

        <!-- URL -->
        <span class="input_name">Service URL
            <span class="input_syntax" >
                (RFC 3986 chars)
            </span>
        </span>
        <input class="input_input_text" type="text" name="endpointUrl" value="<?php xecho($service->getUrl()); ?>" />

        <!--  Host Name -->
        <span class="input_name">Host name *
            <span class="input_syntax" >
                (valid FQDN format)
            </span>
        </span>
        <input class="input_input_text" type="text" name="HOSTNAME" value="<?php xecho($service->getHostName()) ?>" />

        <!-- Contact E-Mail -->
        <span class="input_name">
            Contact E-Mail *
            <span class="input_syntax" >
                (Valid email format)
            </span>
        </span>
        <input class="input_input_text" type="text" name="EMAIL" value="<?php xecho($service->getEmail()) ?>" />

        <!-- Host IP -->
        <span class="input_name">
            Host IP
            <span class="input_syntax" >
                a.b.c.d
            </span>
        </span>
        <input class="input_input_text" type="text" name="HOST_IP" value="<?php xecho($service->getIpAddress()) ?>" />

        <span class="input_name">
            Host IPv6
            <span class="input_syntax" >
                (0000:0000:0000:0000:0000:0000:0000:0000[/int]) (optional [/int] range)
            </span>
        </span>
        <input class="input_input_text" type="text" name="HOST_IP_V6" value="<?php xecho($service->getIpV6Address()) ?>" />


        <!-- Host DN -->
        <span class="input_name">
            Host DN
            <span class="input_syntax" >
                (/C=.../OU=.../...)
            </span>
        </span>
        <input class="input_input_text" type="text" name="HOST_DN" value="<?php xecho($service->getDn()) ?>" />

        <!-- Description  -->
        <span class="input_name">
            Description *
            <span class="input_syntax" >
                (Alphanumeric and basic punctuation)
            </span>
        </span>
        <input class="input_input_text" type="text" name="DESCRIPTION" value="<?php xecho($service->getDescription()) ?>" />

        <!-- Host Operating System -->
        <span class="input_name">
            Host Operating System
            <span class="input_syntax" >
                (Alphanumeric and basic punctuation)
            </span>
        </span>
        <input class="input_input_text" type="text" name="HOST_OS" value="<?php xecho($service->getOperatingSystem()) ?>" />

        <!-- Host Architecture  -->
        <span class="input_name">
            Host Architecture
            <span class="input_syntax" >
                (Alphanumeric and basic punctuation)
            </span>
        </span>
        <input class="input_input_text" type="text" name="HOST_ARCH" value="<?php xecho($service->getArchitecture()) ?>" />

        <!-- Beta -->
        <span class="input_name">
            Is this a beta service (formerly PPS service)?
        </span>
        <select class="add_edit_form" name="HOST_BETA">
            <option value="N"<?php if ($service->getBeta() == false) echo " selected=\"selected\"" ?>>N</option>
            <option value="Y"<?php if ($service->getBeta() == true) echo " selected=\"selected\"" ?>>Y</option>
        </select>

        <!-- Production -->
        <span class="input_name">
            Is this service in production?
        </span>
        <select class="add_edit_form" name="PRODUCTION_LEVEL">
            <option value="N"<?php if ($service->getProduction() == false) echo " selected=\"selected\"" ?>>N</option>
            <option value="Y"<?php if ($service->getProduction() == true) echo " selected=\"selected\"" ?>>Y</option>
        </select>

        <!-- Production -->
        <span class="input_name">
            Is this service monitored?
        </span>
        <select class="add_edit_form" name="IS_MONITORED">
            <option value="N"<?php if ($service->getMonitored() == false) echo " selected=\"selected\"" ?>>N</option>
            <option value="Y"<?php if ($service->getMonitored() == true) echo " selected=\"selected\"" ?>>Y</option>
        </select>

        <br>
        <br>

        <!-- Scope Tags-->
        <?php
        $parentObjectTypeLabel = 'Site';
        require_once __DIR__ . '/../fragments/editScopesFragment.php';
        ?>

        <br>

        <div class="alert alert-warning" role="alert">
            Note, rather than setting scope tags individually for each service, you can update
            the scopes of every service when editing the parent Site
            (options such as 'Inherit Site scopes' and 'Override Service scopes with Site scopes'
            are provided for your convenience)
        </div>

        <input class="input_input_hidden" type="hidden" value="<?php echo $service->getId() ?>" name="ID">

        <input class="input_button" type="submit" value="Edit Service">
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