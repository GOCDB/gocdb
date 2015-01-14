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
            <?php foreach($params['countries'] as $country) { ?>
                <option value="<?php echo $country->getName() ?>"<?php if($site->getCountry() == $country) echo " selected=\"selected\""; ?>><?php echo $country->getName() ?></option>
            <?php } ?>
        </select>
        
        <!-- Timezones -->
        <span class="input_name">Timezone</span>
        <select class="add_edit_form" name="Timezone">
            <?php foreach($params['timezones'] as $tz) { ?>
                <option value="<?php echo $tz->getName() ?>"<?php if($site->getTimezone() == $tz) echo " selected=\"selected\""; ?>><?php echo $tz->getName() ?></option>
            <?php } ?>
        </select>
        
        <!-- Production Statuses -->
        <span class="input_name">Infrastructure</span>
        <select class="add_edit_form" name="ProductionStatus">
            <?php foreach($params['prodStatuses'] as $status) { ?>
                <option value="<?php echo $status->getName() ?>"<?php if($site->getInfrastructure() == $status) echo " selected=\"selected\""; ?>><?php echo $status->getName() ?></option>
            <?php } ?>
        </select>
        
        <!-- Domain -->
        <span class="input_name">Domain *</span>
        <input class="input_input_text" type="text" name="DOMAIN" value="<?php echo $site->getDomain() ?>" />
        
        <!-- Short Name -->
        <span class="input_name">Short Name * 
            <span class="input_syntax" >
                (Alphanumeric, dot dash and underscore)
            </span>
        </span>
        <input class="input_input_text" type="text" name="SHORT_NAME" value="<?php echo $site->getShortName() ?>" />
        
        <!--  Official Name -->
        <span class="input_name">Official Name 
            <span class="input_syntax" >
                (Alphanumeric and basic punctuation)
            </span>
        </span>
        <input class="input_input_text" type="text" name="OFFICIAL_NAME" value="<?php echo $site->getOfficialName() ?>" />
        
        <!-- URL -->
        <span class="input_name">
            Home URL 
            <span class="input_syntax" >
                (http(s)://url_format)
            </span>
        </span>
        <input class="input_input_text" type="text" name="HOME_URL" value="<?php echo $site->getHomeUrl() ?>" />
        
        <!-- GIIS URL -->
        <span class="input_name">
            GIIS URL
            <span class="input_syntax" >
                (ldap://giis_url_format)
            </span>
        </span>
        <input class="input_input_text" type="text" name="GIIS_URL" value="<?php echo $site->getGiisUrl() ?>" />
        
        <!-- IP Range -->
        <span class="input_name">
            IP Range
            <span class="input_syntax" >
                (a.b.c.d/e.f.g.h)
            </span>
        </span>
        <input class="input_input_text" type="text" name="IP_RANGE" value="<?php echo $site->getIpRange() ?>" />
        
        <!-- IP v6 Range -->        
        <span class="input_name">
            IPv6 Range
            <span class="input_syntax" >(0000:0000:0000:0000:0000:0000:0000:0000[/int]) (optional [/int] range)</span>
        </span>        
        <input class="input_input_text" type="text" name="IP_V6_RANGE" value="<?php echo $site->getIpV6Range() ?>" />
        
        <!-- Location -->
        <span class="input_name">
            Location
            <span class="input_syntax" >
                (Alphanumeric and basic punctuation)
            </span>
        </span>
        <input class="input_input_text" type="text" name="LOCATION" value="<?php echo $site->getLocation() ?>" />
        
        <!-- Latitude -->
        <span class="input_name">
            Latitude    
            <span class="input_syntax" >(-90 <= number <= 90)</span>
        </span>
        <input class="input_input_text" type="text" name="LATITUDE" value="<?php echo $site->getLatitude() ?>" />
        
        <!-- Longitude -->
        <span class="input_name">
            Longitude
            <span class="input_syntax" >(-180 <= number <= 180)</span>
        </span>
        <input class="input_input_text" type="text" name="LONGITUDE" value="<?php echo $site->getLongitude() ?>" />
        
        <!-- Description -->
        <span class="input_name">
            Description
            <span class="input_syntax" >
                (Alphanumeric and basic punctuation)
            </span>
        </span>
        <input class="input_input_text" type="text" name="DESCRIPTION" value="<?php echo $site->getDescription() ?>" />
        
        <!-- E-Mail -->
        <span class="input_name">
            E-Mail * 
            <span class="input_syntax" >
                (valid email format)
            </span>
        </span>
        <input class="input_input_text" type="text" name="EMAIL" value="<?php echo $site->getEmail() ?>" />
        
        <!-- Contact Telephone Number -->
        <span class="input_name">
            Contact Telephone Number *
            <span class="input_syntax" >
                (optional + at the start, numbers, dots spaces or dashes, multiple comma separated numbers allowed)
            </span>
        </span>
        <input class="input_input_text" type="text" name="CONTACTTEL" value="<?php echo $site->getTelephone() ?>" />
            
        <!-- Emergency Telephone Number -->    
        <span class="input_name">
            Emergency Telephone Number
            <span class="input_syntax" >
                (optional + at the start, numbers, dots spaces or dashes, multiple comma separated numbers allowed)
            </span>
        </span>
        <input class="input_input_text" type="text" name="EMERGENCYTEL" value="<?php echo $site->getEmergencyTel() ?>" />
        
        <!-- Security e-mail -->
        <span class="input_name">
            Security Contact E-mail (CSIRT E-Mail)
            <span class="input_syntax" >
                (valid email format)
            </span>
        </span>
        <input class="input_input_text" type="text" name="CSIRTEMAIL" value="<?php echo $site->getCsirtEmail() ?>" />
        
        <!--  Security telephone number -->
        <span class="input_name">
            Security Contact Telephone Number (CSIRT Telephone Number)
            <span class="input_syntax" >
                (optional + at the start, numbers, dots spaces or dashes, multiple comma separated numbers allowed)
            </span>
        </span>
        <input class="input_input_text" type="text" name="CSIRTTEL" value="<?php echo $site->getCsirtTel() ?>" />
        
        <!--  Alarm e-mail -->
        <span class="input_name">
            Alarm E-Mail (for LCG Tiers 1)
            <span class="input_syntax" >
                (valid email format)
            </span>
        </span>
        <input class="input_input_text" type="text" name="EMERGENCYEMAIL" value="<?php echo $site->getAlarmEmail() ?>" />
        
        <!-- Helpdesk email -->        
        <span class="input_name">
            Helpdesk E-Mail
            <span class="input_syntax" >
                (valid email format, multiple comma or semicolon separated addresses allowed)
            </span>
        </span>
        <input class="input_input_text" type="text" name="HELPDESKEMAIL" value="<?php echo $site->getHelpdeskEmail() ?>" />

        <span class="input_name">Scope(s)
            <span class="input_syntax">(Select at least <?php echo $params['numberOfScopesRequired']?>)</span>
        </span>
        <script type="text/javascript" src="<?php echo \GocContextPath::getPath()?>javascript/confirmScope.js"></script>
        <div style="margin-left: 2em">    
            <?php foreach ($params['scopes'] as $scopeArray){ ?>
                <?php
                $scopeName = $scopeArray['scope']->getName();
                $scopeId = $scopeArray['scope']->getId();
                $checkedParamater = '';
                if($scopeArray['applied']){
                    $checkedParamater = ' checked="checked"';
                }
                $onClick = '';
                if (!in_array($scopeId, $params["parentScopeIds"])){
                    $onClick = " onclick=\"return confirmScopeSelect('$scopeName', '$ngiName', '$siteName', this.checked)\"";
                } 
           ?>
                <br />
                <input type="checkbox" name="Scope_ids[]" value="<?php echo $scopeId;?>"<?php echo $checkedParamater;?> <?php echo $onClick;?>>
                <?php echo $scopeName;?>      
            <?php } ?>
        </div>  	
        
        <input class="input_input_hidden" type="hidden" name="ID" value="<?php echo $site->getId() ?>" />
        
        <input class="input_button" type="submit" value="Edit Site" />
           
    </form>
</div>