
<span class="input_name">
    Optional Scopes 
</span>

<!-- Create enabled checkboxes for all OPTIONAL scope-tags -->
<div id="optionalScopesDIV" style="margin-left: 2em">    
    <?php 
    foreach ($params['scopes'] as $scopeArray) { ?>
    <input type="checkbox" name="Scope_ids[]" class="cb-optionalscope-element"
               id="optionalScopeCB<?php echo($scopeArray['scope']->getId()); ?>" 
               value="<?php echo $scopeArray['scope']->getId(); ?>"
               <?php if ($scopeArray['applied']) { echo ' checked="checked"'; }?>
    >
    <?php xecho($scopeArray['scope']->getName()); ?>
<?php } ?>
</div>

<br />  	

<span class="input_name">
    Reserved Scopes
    <span class="input_syntax">(Can only be assigned on request by Goc-Admin and users with special roles)</span>
</span>

<!-- 
Create checkboxes for all RESERVED scope-tags 
If user has permission, create enabled checkboxes for all reserved scope-tags. 
If user don't have perm, create disabled checkboxes and add a hidden input to submit value
-->
<div id="requiredScopesDIV" style="margin-left: 2em">    
    <?php
        // If user don't have permission, reserved scopes need to be disabled in the UI 
        $disabledTag = ""; 
        if ($params['disableReservedScopes'] === TRUE) {
            $disabledTag = 'disabled="disabled"'; 
        }
        foreach ($params['reservedScopes'] as $reservedScopeArray) {
            // show a disabled checkbox for view (disabled checkboxes aren't submitted)
            echo '<input type="checkbox" '.$disabledTag.' class="cb-reservedscope-element" ';
            if ($reservedScopeArray['applied']) {
                echo ' checked="checked" ';
            }
            echo ' >';
            echo($reservedScopeArray['scope']->getName()) . '&nbsp;&nbsp;';
            // Since disbled checkboxes aren't submitted, use a hidden param to submit the value 
            if ($reservedScopeArray['applied'] && $params['disableReservedScopes'] === TRUE) {
                echo '<input type="hidden" class="inputhidden-reserved" name="ReservedScope_ids[]" value="' . $reservedScopeArray['scope']->getId() . '"/>';
            }
        }
    ?>
</div>

