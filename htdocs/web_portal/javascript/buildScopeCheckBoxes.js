/*
 * Copyright (C) 2015 STFC
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * 
 * @author David Meredith <david.meredith@stfc.ac.uk> 
 */

/**
 * Define the global ScopeUtil object 
 * @type Object
 */
ScopeUtil = {}; 

/**
 * Call the 'index.php' page with the Page_Type URL param value to fetch a
 * JSON doc that encodes all the scope tag values for the entity 
 * identified by scopedEntityId, then build the scope check boxes.
 * @see {@link addScopeCheckBoxes}  
 * 
 * @param {string} page_type - The value of the 'Page_Type' URL parameter. 
 * @param {number} scopedEntityId - The ID/PK of a ScopedEntity.  
 * @param {string} appendToReservedContainer - The id value of an html container 
 *   element (normally a div) that will hold the reserved scope checkboxes, e.g. '#someDivId'. 
 * @param {string} appendToReservedOptionalContainer - The id value of an html container 
 *   element (normally a div) that will hold the reserved scope checkboxes that are/can be assigned, e.g. '#someDivId'.
 * @param {string} appendToReservedInheritableOptionalContainer - The id value of an html container 
 *   element (normally a div) that will hold the reserved inheritable scope checkboxes that are/can be assigned, e.g. '#someDivId'. 
 * @param {string} appendToOptionalContainer - The id value of an html container 
 *   element (normally a div) that will hold the optional scope checkboxes, e.g. '#someDivId'. 
 * @param {boolean} emptyContainers - If true, empty() is called on both container elements
 *   before the checkboxes are appended (normally true). 
 */
ScopeUtil.queryForJsonScopesAddScopeCheckBoxes = function(page_type, scopedEntityId, 
  appendToReservedContainer, 
  appendToReservedOptionalContainer, 
  appendToReservedInheritableOptionalContainer,
  appendToOptionalContainer, 
  emptyContainers) {
      
    if (!scopedEntityId) {
        console.log('Error - scopedEntityId was invalid ['+scopedEntityId+']');
        var errorDivElem = $('<span style="color: red"><b>An error occurred ' +
                'and the scope tags could not be rendered because the scopedEntityId was invalid. Please contact gocdb-admins.</b></span>');
        $(appendToReservedContainer).append(errorDivElem);
        return; 
    }
    console.log('requesting JSON for scope tags');
    // use ajax to get the selected ngi's scopes and update display+vars
    $.getJSON('index.php', {Page_Type: page_type, getAllScopesForScopedEntity: scopedEntityId}, 
        function (jsonRsp, status) {
            // status - contains a string containing request status ("success", "notmodified", "error", "timeout", or "parsererror")
            //console.log(status); 
            //console.log(jsonRsp);  
            if(status === 'success'){
                console.log('success on json response');
                addScopeCheckBoxes(jsonRsp, 
                  appendToReservedContainer, 
                  appendToReservedOptionalContainer, 
                  appendToReservedInheritableOptionalContainer,
                  appendToOptionalContainer, 
                  emptyContainers);
            } else {
                console.log('Error on JSON request '+status);
                var errorDivElem = $('<span style="color: red"><b>An error occurred '+
                        'and the scope tags could not be rendered. Please contact gocdb-admins.</b></span>'); 
                $(appendToReservedContainer).append(errorDivElem); 
            }
        }
    ); 
}

/**
 * From the given jsonScopes object, iterate every encoded scope tag and 
 * create and append input checkboxes to the relevant containers (e.g. divs). 
 * <p>
 * The JSON object has different keys to distinguish between different scope categories:
 * ('reserved', 'reserved_optional', 'reserved_optional_inheritable', 'optional'). 
 * <p>
 * <ul>
 *   <li>'reserved' checkboxes are created with corresponding hidden inputs. 
 *   The CBs and hidden inputs both have the input.name 'ReservedScope_ids[]' 
 *   (hidden inputs are created because these checkboxes are disabled and 
 *   disabled CBs are not submitted in a form POST)</li> 
 *   <li>'reserved_optional' and 'reserved_optional_inheritable' checkboxes have the 
 *     input.name 'ReservedScope_ids[]' (no corresponding hidden inputs are created as 
 *     they are not needed - these checkboxes are enabled and so will be POSTed)</li>
 *   <li>'optional' checkboxes have the input.name 'Scope_ids[]' (no hidden inputs)</li>  
 * </ul>
 * <p>
 * 
 * @param {object} jsonScopes - The JSON object that encodes the scope tags values, 
 *   their PK/ID and whether they should be pre-checked (or not). 
 * @param {string} appendToReservedContainer - The id value of an html container 
 *   element (normally a div) that will hold the reserved scope checkboxes, e.g. '#someDivId'. 
 * @param {string} appendToReservedOptionalContainer - The id value of an html container 
 *   element (normally a div) that will hold the reserved scope checkboxes that can be assigned, e.g. '#someDivId'.
 * @param {string} appendToReservedInheritableOptionalContainer - The id value of an html container 
 *   element (normally a div) that will hold the reserved inheritable scope checkboxes that are/can be assigned, e.g. '#someDivId'.
 * @param {string} appendToOptionalContainer - The id value of an html container
 *   element (normally a div) that will hold the optional scope checkboxes, e.g. '#someDivId'. 
 * @param {boolean} emptyContainers - If true, empty() is called on both container elements
 *   before the checkboxes are appended (normally true). 
 */
ScopeUtil.addScopeCheckBoxes = function(jsonScopes, 
    appendToReservedContainer, 
    appendToReservedOptionalContainer, 
    appendToReservedInheritableOptionalContainer, 
    appendToOptionalContainer, 
    emptyContainers) {
        
    if (emptyContainers) {
        if(appendToReservedContainer){
            $(appendToReservedContainer).empty();
        }
        if(appendToOptionalContainer){
            $(appendToOptionalContainer).empty();
        }
        if(appendToReservedOptionalContainer){
            $(appendToReservedOptionalContainer).empty();
        }
        if(appendToReservedInheritableOptionalContainer){
            $(appendToReservedInheritableOptionalContainer).empty(); 
        }
    }
    //console.log(jsonScopes);
    var reservedScopeArr = jsonScopes.reserved;
    var reservedOptionalScopeArr = jsonScopes.reserved_optional;
    var reservedOptionalInheritableScopeArr = jsonScopes.reserved_optional_inheritable; 
    var optionalScopeArr = jsonScopes.optional;
    var disableReserved = jsonScopes.disableReserved;

    if(reservedOptionalScopeArr){ 
        var added = false; 
        for (i = 0; i < reservedOptionalScopeArr.length; i++) {
            added = true; 
            //console.log('reserved'+i+': '+reservedScopeArr[i][0]+', '+reservedScopeArr[i][1]+', '+reservedScopeArr[i][2])
            // reserved0: 26, alice, false
            var checkbox = document.createElement('input');
            checkbox.type = "checkbox";
            checkbox.name = "ReservedScope_ids[]";
            checkbox.value = reservedOptionalScopeArr[i][0];
            checkbox.id = "reservedOptionalScopeCbId" + reservedOptionalScopeArr[i][0];
            if (reservedOptionalScopeArr[i][2]) {
                checkbox.checked = "checked";
            }

            var label = document.createElement('label');
            label.htmlFor = "reservedOptionalScopeCbId" + reservedOptionalScopeArr[i][0];
            label.appendChild(document.createTextNode(reservedOptionalScopeArr[i][1]));

            // add the CBs to the correct div '#reservedScopeCheckBoxDIV' 
            $(appendToReservedOptionalContainer).append(checkbox).append(label).append('&nbsp;&nbsp;');
        }
        if(added === false){
           $(appendToReservedOptionalContainer).append('none');  
        }
    }

    if(reservedOptionalInheritableScopeArr){ 
        var added = false; 
        for (i = 0; i < reservedOptionalInheritableScopeArr.length; i++) {
            added = true; 
            var checkbox = document.createElement('input');
            checkbox.type = "checkbox";
            checkbox.name = "ReservedScope_ids[]";
            checkbox.value = reservedOptionalInheritableScopeArr[i][0];
            checkbox.id = "reservedOptionalInheritableScopeCbId" + reservedOptionalInheritableScopeArr[i][0];
            if (reservedOptionalInheritableScopeArr[i][2]) {
                checkbox.checked = "checked";
            }

            var label = document.createElement('label');
            label.htmlFor = "reservedOptionalInheritableScopeCbId" + reservedOptionalInheritableScopeArr[i][0];
            label.appendChild(document.createTextNode(reservedOptionalInheritableScopeArr[i][1]));

            $(appendToReservedInheritableOptionalContainer).append(checkbox).append(label).append('&nbsp;&nbsp;');
        }
        if(added === false){
           $(appendToReservedInheritableOptionalContainer).append('none');  
        }
    }

    if(reservedScopeArr){
        // Render the RESERVED scope tag checkboxes (may/may-not be disabled) 
        // If CB is disabled, also add a hidden input to submit the value 
        // (since disabled checkboxes are not submitted) 
        var added = false; 
        for (i = 0; i < reservedScopeArr.length; i++) {
            added = true; 
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
        if(added === false){
           $(appendToReservedContainer).append('none');  
        }
    }

    if(optionalScopeArr){
        // Render the OPTIONAL scope tag checkboxes, only those 
        // checked will be submitted 
        var added = false; 
        for (i = 0; i < optionalScopeArr.length; i++) {
            added = true; 
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
        if(added === false){
           $(appendToOptionalContainer).append('none');  
        }
    }

}






