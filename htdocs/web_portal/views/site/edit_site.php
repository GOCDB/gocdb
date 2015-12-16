<?php
$site = $params['site'];
$siteName = $site->getName();
$ngiName = $site->getNgi()->getName();
$siteScopes = $site->getScopes();
?>
<div class="rightPageContainer">
    <form name="Edit_Site" action="index.php?Page_Type=Edit_Site" method="post" class="inputForm">
        <h1>Edit Site</h1>
        <br />

        <!-- Countries -->    	
        <span class="input_name">Country</span>
        <select class="add_edit_form" name="Country">
            <?php foreach ($params['countries'] as $country) { ?>
                <option value="<?php xecho($country->getName()) ?>"<?php if ($site->getCountry() == $country) echo " selected=\"selected\""; ?>><?php xecho($country->getName()) ?></option>
            <?php } ?>
        </select>

        <!-- Timezones -->
        <span class="input_name">Timezone</span>
        <select class="add_edit_form" name="TIMEZONE">
            <?php foreach ($params['timezones'] as $key => $tz) { ?>
                <?php /* <!--<option value="<?php xecho($tz->getName()) ?>"<?php if($site->getTimezone() == $tz) echo " selected=\"selected\""; ?>><?php xecho($tz->getName()) ?></option> */ ?>
                <option value="<?php xecho($tz) ?>" <?php if ($site->getTimezoneId() == $tz) {
                echo " selected=\"selected\"";
            } ?>>
                <?php xecho($tz); ?>
                </option>
<?php } ?>
        </select>

        <!-- Production Statuses -->
        <span class="input_name">Infrastructure</span>
        <select class="add_edit_form" name="ProductionStatus">
            <?php foreach ($params['prodStatuses'] as $status) { ?>
                <option value="<?php xecho($status->getName()) ?>"<?php if ($site->getInfrastructure() == $status) echo " selected=\"selected\""; ?>><?php xecho($status->getName()) ?></option>
<?php } ?>
        </select>

        <!-- Domain -->
        <span class="input_name">Domain *</span>
        <input class="input_input_text" type="text" name="DOMAIN" value="<?php xecho($site->getDomain()) ?>" />

        <!-- Short Name -->
        <span class="input_name">Short Name * 
            <span class="input_syntax" >
                (Alphanumeric, dot dash and underscore)
            </span>
        </span>
        <input class="input_input_text" type="text" name="SHORT_NAME" value="<?php xecho($site->getShortName()) ?>" />

        <!--  Official Name -->
        <span class="input_name">Official Name 
            <span class="input_syntax" >
                (Alphanumeric and basic punctuation)
            </span>
        </span>
        <input class="input_input_text" type="text" name="OFFICIAL_NAME" value="<?php xecho($site->getOfficialName()) ?>" />

        <!-- URL -->
        <span class="input_name">
            Home URL 
            <span class="input_syntax" >
                (http(s)://url_format)
            </span>
        </span>
        <input class="input_input_text" type="text" name="HOME_URL" value="<?php xecho($site->getHomeUrl()) ?>" />

        <!-- GIIS URL -->
        <span class="input_name">
            GIIS URL
            <span class="input_syntax" >
                (ldap://giis_url_format)
            </span>
        </span>
        <input class="input_input_text" type="text" name="GIIS_URL" value="<?php xecho($site->getGiisUrl()) ?>" />

        <!-- IP Range -->
        <span class="input_name">
            IP Range
            <span class="input_syntax" >
                (a.b.c.d/e.f.g.h)
            </span>
        </span>
        <input class="input_input_text" type="text" name="IP_RANGE" value="<?php xecho($site->getIpRange()) ?>" />

        <!-- IP v6 Range -->        
        <span class="input_name">
            IPv6 Range
            <span class="input_syntax" >(0000:0000:0000:0000:0000:0000:0000:0000[/int]) (optional [/int] range)</span>
        </span>        
        <input class="input_input_text" type="text" name="IP_V6_RANGE" value="<?php xecho($site->getIpV6Range()) ?>" />

        <!-- Location -->
        <span class="input_name">
            Location
            <span class="input_syntax" >
                (Alphanumeric and basic punctuation)
            </span>
        </span>
        <input class="input_input_text" type="text" name="LOCATION" value="<?php xecho($site->getLocation()) ?>" />

        <!-- Latitude -->
        <span class="input_name">
            Latitude    
            <span class="input_syntax" >(-90 <= number <= 90)</span>
        </span>
        <input class="input_input_text" type="text" name="LATITUDE" value="<?php xecho($site->getLatitude()) ?>" />

        <!-- Longitude -->
        <span class="input_name">
            Longitude
            <span class="input_syntax" >(-180 <= number <= 180)</span>
        </span>
        <input class="input_input_text" type="text" name="LONGITUDE" value="<?php xecho($site->getLongitude()) ?>" />

        <!-- Description -->
        <span class="input_name">
            Description
            <span class="input_syntax" >
                (Alphanumeric and basic punctuation)
            </span>
        </span>
        <input class="input_input_text" type="text" name="DESCRIPTION" value="<?php xecho($site->getDescription()) ?>" />

        <!-- E-Mail -->
        <span class="input_name">
            E-Mail * 
            <span class="input_syntax" >
                (valid email format)
            </span>
        </span>
        <input class="input_input_text" type="text" name="EMAIL" value="<?php xecho($site->getEmail()) ?>" />

        <!-- Contact Telephone Number -->
        <span class="input_name">
            Contact Telephone Number *
            <span class="input_syntax" >
                (optional + at the start, numbers, dots spaces or dashes, multiple comma separated numbers allowed)
            </span>
        </span>
        <input class="input_input_text" type="text" name="CONTACTTEL" value="<?php xecho($site->getTelephone()) ?>" />

        <!-- Emergency Telephone Number -->    
        <span class="input_name">
            Emergency Telephone Number
            <span class="input_syntax" >
                (optional + at the start, numbers, dots spaces or dashes, multiple comma separated numbers allowed)
            </span>
        </span>
        <input class="input_input_text" type="text" name="EMERGENCYTEL" value="<?php xecho($site->getEmergencyTel()) ?>" />

        <!-- Security e-mail -->
        <span class="input_name">
            Security Contact E-mail (CSIRT E-Mail)
            <span class="input_syntax" >
                (valid email format)
            </span>
        </span>
        <input class="input_input_text" type="text" name="CSIRTEMAIL" value="<?php xecho($site->getCsirtEmail()) ?>" />

        <!--  Security telephone number -->
        <span class="input_name">
            Security Contact Telephone Number (CSIRT Telephone Number)
            <span class="input_syntax" >
                (optional + at the start, numbers, dots spaces or dashes, multiple comma separated numbers allowed)
            </span>
        </span>
        <input class="input_input_text" type="text" name="CSIRTTEL" value="<?php xecho($site->getCsirtTel()) ?>" />

        <!--  Alarm e-mail -->
        <span class="input_name">
            Alarm E-Mail (for LCG Tiers 1)
            <span class="input_syntax" >
                (valid email format)
            </span>
        </span>
        <input class="input_input_text" type="text" name="EMERGENCYEMAIL" value="<?php xecho($site->getAlarmEmail()) ?>" />

        <!-- Helpdesk email -->        
        <span class="input_name">
            Helpdesk E-Mail
            <span class="input_syntax" >
                (valid email format, multiple comma or semicolon separated addresses allowed)
            </span>
        </span>
        <input class="input_input_text" type="text" name="HELPDESKEMAIL" value="<?php xecho($site->getHelpdeskEmail()) ?>" />


        <br>
        <br>
        <!-- Scope Tags-->
        <div class="h4">Scope Tags
            <span class="input_syntax">(At least <?php echo $params['numberOfScopesRequired'] ?> Optional tag must be selected)</span>
        </div>

        <br>
        <span class="input_name">
            Action to Take For All Child Service Scopes
        </span>
        <select class="add_edit_form" name="childServiceScopeAction">
            <option value="noModify" selected="true">Do not modify child Service scopes</option>
            <option value="inherit">Inherit all Site scopes (leaves additional Service scopes that are not used by the Site intact)</option>
            <option value="override">Override Service Scopes with Site scopes (removes Service scopes that are not used/checked by Site)</option>
        </select>

        <br>

        <div id="allscopeCheckBoxDIV">
            <h4>Optional Scope Tags</h4>
            <div id="optionalScopeCheckBoxDIV"></div> 
            <br/>
            <h4>Reserved Scope Tags</h4>
            <div id="reservedScopeCheckBoxDIV"></div> 
        </div>


        <input class="input_input_hidden" type="hidden" name="ID" value="<?php xecho($site->getId()) ?>" />

        <input class="input_button" type="submit" value="Edit Site" />

    </form>
</div>

<script type="text/javascript" src="<?php echo \GocContextPath::getPath() ?>javascript/buildScopeCheckBoxes.js"></script>
<script type="text/javascript">

    $(document).ready(function () {
        var scopeJSON = JSON.parse('<?php echo($params["scopejson"]) ?>');
        addScopeCheckBoxes(scopeJSON, '#reservedScopeCheckBoxDIV', '#optionalScopeCheckBoxDIV', true);
    });
</script>    