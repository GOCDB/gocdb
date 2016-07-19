<div class="rightPageContainer">
    <form name="New_Service" action="index.php?Page_Type=Add_New_SE_To_Service_Group" method="post" class="inputForm">
        <h1>Add New Services to Service Group</h1>
        <br />
        <span>
            Please note: Adding a new service to a service group ties the
            new service to the group.
            <br />
            If the service group is deleted then the service
            will also be deleted.
            <br />
            Modifications to the service can only be performed by
            users who hold a role over the creating service group.
        </span>

        <br /><br />

        <input type="hidden" name="vSiteId" value="<?php echo $params['vSite']['COBJECTID'] ?>">
        <input type="hidden" name="gridId" value="<?php echo $params['gridId'] ?>">

        <span class="input_name">Hosting Service Group</span>
        <input class="input_input_text" type="text" name="" value="<?php echo $params['vSite']['NAME'] ?>" disabled="disabled" />

        <span class="input_name">Service Type</span>
        <select class="add_edit_form" name="Service_Type">
            <?php
            foreach($params['Service_Types'] as $type) {
                echo "<option value=\"$type\">$type</option>";
            }
            ?>
        </select>

        <span class="input_name">
            Service URL
            <span class="input_syntax" >(Alphanumeric and $-_.+!*'(),:)</span>
        </span>
        <input class="input_input_text" type="text" name="EndpointURL" value="" />

        <span class="input_name">Host name *
            <span class="input_syntax" >(valid FQDN format)</span>
        </span>
        <input class="input_input_text" type="text" name="HOSTNAME" />

        <span class="input_name">Host IP
            <span class="input_syntax" >(a.b.c.d)</span>
        </span>
        <input class="input_input_text" type="text" name="HOST_IP" />

        <span class="input_name">Host DN
            <span class="input_syntax" >(/C=.../OU=.../...)</span>
        </span>
        <input class="input_input_text" type="text" name="HOST_DN" />

        <span class="input_name">Description
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

        <br />

        <div style="display: inline; float: none;">
            <span class="input_name" style="">
               Is this service visible to EGI?
               <a href=""index.php?Page_Type=Scope_Help"">
                   (Help)
               </a>
            </span>
            <input class="add_edit_form" style="width: auto; display: inline;" type="checkbox" name="egi_data" value="" checked="checked"/>
        </div>

        <div style="display: inline; float: none;">

        </div>

        <br />
        <input class="input_button" type="submit" value="Execute" />
    </form>
</div>