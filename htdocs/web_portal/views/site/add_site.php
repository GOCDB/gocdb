<div class="rightPageContainer">
    <form name="New_Site" action="index.php?Page_Type=Add_Site" method="post" class="inputForm">
        <h1>Add Site</h1>
        <br />
        <span class="input_name">NGI</span>
        <select class="add_edit_form" name="NGI" id="ngiSelectPullDown">
            <?php
            foreach ($params['ngis'] as $ngi) {
                // If this site is the same as the one from the passing page
                echo "<option value=\"" . $ngi->getId() . "\">" . $ngi->getName() . "</option>";
            }
            ?>
        </select>

        <span class="input_name">Country</span>
        <select class="add_edit_form"name="Country">
            <?php
            foreach ($params['countries'] as $country) {
                echo "<option value=\"" . $country->getId() . "\">" . $country->getName() . "</option>";
            }
            ?>
        </select>

        <span class="input_name">Timezone</span>
        <select class="add_edit_form"name="TIMEZONE">
            <?php
            //foreach($params['timezones'] as $timezone) {
            //echo "<option value=\"" . $timezone->getId() . "\">" . $timezone->getName() . "</option>";
            //}
            foreach ($params['timezones'] as $key => $tz) {
                echo "<option value=\"" . $tz . "\">" . $tz . "</option>";
            }
            ?>
        </select>

        <span class="input_name">Infrastructure</span>
        <select class="add_edit_form" name="ProductionStatus">
            <?php
            foreach ($params['prodStatuses'] as $prodStatus) {
                echo "<option value=\"" . $prodStatus->getId() . "\">" . $prodStatus->getName() . "</option>";
            }
            ?>
        </select>

        <span class="input_name">Certification Status</span>
        <select class="add_edit_form"name="Certification_Status">
            <?php
            foreach ($params['certStatuses'] as $certStatus) {
                echo "<option value=\"" . $certStatus->getId() . "\">" . $certStatus->getName() . "</option>";
            }
            ?>
        </select>

        <span class="input_name">
            Domain *
            <span class="input_syntax" >(Alphanumeric, dot dash and underscore)</span>
        </span>
        <input class="input_input_text" type="text"name="DOMAIN"value="" />

        <span class="input_name">
            Short Name * 
            <span class="input_syntax" >(Alphanumeric, dot dash and underscore)</span>
        </span>
        <input class="input_input_text" type="text" name="SHORT_NAME" />

        <span class="input_name">
            Official Name 
            <span class="input_syntax" >(Alphanumeric and basic punctuation)</span>
        </span>
        <input class="input_input_text" type="text" name="OFFICIAL_NAME" />

        <span class="input_name">
            Home URL 
            <span class="input_syntax" >(http(s)://url_format)</span>
        </span>
        <input class="input_input_text" type="text" name="HOME_URL" />

        <span class="input_name">
            GIIS URL 
            <span class="input_syntax" >(ldap://giis_url_format)</span>
        </span>
        <input class="input_input_text" type="text" name="GIIS_URL" />

        <span class="input_name">
            IP Range 
            <span class="input_syntax" >(a.b.c.d/e.f.g.h)</span>
        </span>
        <input class="input_input_text" type="text" name="IP_RANGE" />

        <span class="input_name">
            IPv6 Range 
            <span class="input_syntax" >(0000:0000:0000:0000:0000:0000:0000:0000[/int]) (optional [/int] range)</span>
        </span>        
        <input class="input_input_text" type="text" name="IP_V6_RANGE" />        

        <span class="input_name">
            Location 
            <span class="input_syntax" >(Alphanumeric and basic punctuation)</span>
        </span>
        <input class="input_input_text" type="text" name="LOCATION" />

        <span class="input_name">
            Latitude 
            <span class="input_syntax" >(-90 <= number <= 90)</span>
        </span>
        <input class="input_input_text" type="text" name="LATITUDE" />

        <span class="input_name">
            Longitude 
            <span class="input_syntax" >(-180 <= number <= 180)</span>
        </span>
        <input class="input_input_text" type="text" name="LONGITUDE" />

        <span class="input_name">
            Description *
            <span class="input_syntax" >(Alphanumeric and basic punctuation)</span>
        </span>
        <input class="input_input_text" type="text" name="DESCRIPTION" />

        <span class="input_name">
            E-Mail * 
            <span class="input_syntax" >(valid email format)</span>
        </span>
        <input class="input_input_text" type="text" name="EMAIL" />

        <span class="input_name">
            Contact Telephone Number *
            <span class="input_syntax" >(optional + at the start, numbers, dots spaces or dashes, multiple comma separated numbers allowed)</span>
        </span>
        <input class="input_input_text" type="text" name="CONTACTTEL" />

        <span class="input_name">
            Emergency Telephone Number 
            <span class="input_syntax" >(optional + at the start, numbers, dots spaces or dashes, multiple comma separated numbers allowed)</span>
        </span>
        <input class="input_input_text" type="text" name="EMERGENCYTEL" />

        <span class="input_name">
            Security Contact E-mail (CSIRT E-Mail)
            <span class="input_syntax" >(valid email format)</span>
        </span>
        <input class="input_input_text" type="text" name="CSIRTEMAIL" />

        <span class="input_name">
            Security Contact Telephone Number (CSIRT Telephone Number)
            <span class="input_syntax" >(optional + at the start, numbers, dots spaces or dashes, multiple comma separated numbers allowed)</span>
        </span>
        <input class="input_input_text" type="text" name="CSIRTTEL" />

        <span class="input_name">
            Alarm E-Mail (for LCG Tiers 1)
            <span class="input_syntax" >(valid email format)</span>
        </span>
        <input class="input_input_text" type="text" name="EMERGENCYEMAIL" />

        <span class="input_name">
            Helpdesk E-Mail
            <span class="input_syntax" >(valid email format, multiple comma or semicolon separated addresses allowed)</span>
        </span>
        <input class="input_input_text" type="text" name="HELPDESKEMAIL" />

        <br>
        <br>
        <!-- Scope Tags-->
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

        <br>

        <input class="input_button" type="submit" value="Add Site" />
    </form>
</div>

<script type="text/javascript" src="<?php echo \GocContextPath::getPath() ?>javascript/buildScopeCheckBoxes.js"></script>
<script type="text/javascript">
    $(document).ready(function () {
        var entityId = $('#ngiSelectPullDown').val();
        //console.log(ajaxText); 
        buildScopeCheckBoxes('Add_Site', entityId, '#reservedScopeCheckBoxDIV', '#optionalScopeCheckBoxDIV', true);

        $('#ngiSelectPullDown').change(function () {
            var entityId = $('#ngiSelectPullDown').val();
            buildScopeCheckBoxes('Add_Site', entityId, '#reservedScopeCheckBoxDIV', '#optionalScopeCheckBoxDIV', true);
        });

    });

</script>    
