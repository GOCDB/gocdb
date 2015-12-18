
function buildScopeCheckBoxes(page, scopedEntityId, appendToReservedContainer, appendToOptionalContainer, emptyContainers) {
// This function is not finished yet, need to use the $.ajax method with 
// error handling (via :success() and :error() functions)
    if (!scopedEntityId) {
        return;
    }
    // use ajax to get the selected ngi's scopes and update display+vars
    $.get('index.php', {Page_Type: page, getAllScopesForScopedEntity: scopedEntityId},
            function (data) {
                //console.log(data); 
                // TODO - error check 
                var jsonRsp = JSON.parse(data);
                //console.log(jsonRsp); //Object { reserved: Array[7], optional: Array[2] }
                addScopeCheckBoxes(jsonRsp, appendToReservedContainer, appendToOptionalContainer, emptyContainers);

            });
}


function addScopeCheckBoxes(jsonRsp, appendToReservedContainer, appendToOptionalContainer, emptyContainers) {
    if (emptyContainers) {
        $(appendToReservedContainer).empty();
        $(appendToOptionalContainer).empty();
    }
    var reservedScopeArr = jsonRsp.reserved;
    var optionalScopeArr = jsonRsp.optional;
    var disableReserved = jsonRsp.disableReserved;

    // Render the RESERVED scope tag checkboxes (may/may-not be disabled) 
    // If CB is disabled, also add a hidden input to submit the value 
    // (since disabled checkboxes are not submitted) 
    for (i = 0; i < reservedScopeArr.length; i++) {
        //console.log('reserved'+i+': '+reservedScopeArr[i][0]+', '+reservedScopeArr[i][1]+', '+reservedScopeArr[i][2])
        // reserved0: 26, alice, false
        var checkbox = document.createElement('input');
        checkbox.type = "checkbox";
        checkbox.name = "ReservedScope_ids[]";
        checkbox.value = reservedScopeArr[i][0];
        checkbox.id = "reservedScopeCbId" + reservedScopeArr[i][0];
        if (reservedScopeArr[i][2]) {
            checkbox.checked = "checked";
        }
        if (disableReserved) {
            // disable the checkbox so user can't click/edit it 
            checkbox.disabled = "disabled";
            if (reservedScopeArr[i][2]) {
                // If CB is selected, also need to create a hidden form input to submit the value 
                // (as disabled CBs aren't submitted)
                var hidden = document.createElement('input');
                hidden.type = "hidden";
                hidden.value = reservedScopeArr[i][0];
                hidden.name = "ReservedScope_ids[]";
                // remember to append the hidden to the form
                $(appendToReservedContainer).append(hidden);
            }
        }

        var label = document.createElement('label');
        label.htmlFor = "reservedScopeCbId" + reservedScopeArr[i][0];
        label.appendChild(document.createTextNode(reservedScopeArr[i][1]));

        // add the CBs to the correct div '#reservedScopeCheckBoxDIV' 
        $(appendToReservedContainer).append(checkbox).append(label).append('&nbsp;&nbsp;');
    }
    // Render the OPTIONAL scope tag checkboxes, only those 
    // checked will be submitted 
    for (i = 0; i < optionalScopeArr.length; i++) {
        var checkbox = document.createElement('input');
        checkbox.type = "checkbox";
        checkbox.name = "Scope_ids[]";
        checkbox.value = optionalScopeArr[i][0];
        checkbox.id = "optionalScopeCbId" + optionalScopeArr[i][0];
        if (optionalScopeArr[i][2]) {
            checkbox.checked = "checked";
        }

        var label = document.createElement('label');
        label.htmlFor = "optionalScopeCbId" + optionalScopeArr[i][0];
        label.appendChild(document.createTextNode(optionalScopeArr[i][1]));

        // add the CBs to the correct div '#optionalScopeCheckBoxDIV' 
        $(appendToOptionalContainer).append(checkbox).append(label).append('&nbsp;&nbsp;');
    }

}






