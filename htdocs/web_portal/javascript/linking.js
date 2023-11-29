$(document).ready(function() {
    // Add the jQuery form change event handlers
    $('#linkIdentityForm').find(":input").change(function() {
        validate();
    });
});

/**
* Updates the authentication type message
* Message depends on whether the selected auth type is the same as the auth type currently in use
* If auth types are the same, different severity of warnings depending on which type
*
* @returns {null}
*/
function updateWarningMessage() {
    var selectedAuthType = $('#authType').val();
    var currentAuthType = $('#currentAuthType').text();
    var authTypeText1 = "";
    var authTypeText2 = "";
    var authTypeText3 = "";

    if (selectedAuthType !== null && selectedAuthType !== "") {
        $('.authTypeShared').removeClass("hidden");
    } else {
        $('.authTypeShared').addClass("hidden");
    }

    $('.authTextPlaceholder').addClass("hidden");
    $('.authTypeSelected').text(selectedAuthType);

    // Different warnings if selected auth type is same as method currently in use
    if (selectedAuthType === currentAuthType) {

        $('#linkingDetails').addClass("hidden");
        $('#recoveryDetails').removeClass("hidden");
        $('#requestPlaceholder').addClass("hidden");
        $('#authTypeRecoverPlaceholder').addClass("hidden");

        authTypeText1 = " is the same as your current authentication type.";
        authTypeText3 = "account recovery";

        // Stronger warning for certain types. Certificates will be less severe?
        if (selectedAuthType === "X.509") {
            authTypeText2 = "Are you sure you wish to continue?";
            $('#authTypeRecover').removeClass("hidden");
            $('#authTypeRecover').removeClass("auth-warning");
            $('#authTypeSelected').addClass("hidden");
        } else {
            authTypeText2 = "These identifiers rarely expire. Are you sure you wish to continue?";
            $('#authTypeRecover').removeClass("hidden");
            $('#authTypeRecover').addClass("auth-warning");
            $('#authTypeSelected').removeClass("hidden");
        }

    } else {

        $('#linkingDetails').removeClass("hidden");
        $('#recoveryDetails').addClass("hidden");
        $('#requestPlaceholder').removeClass("hidden");

        authTypeText1 = " is different to your current authentication type.";
        authTypeText3 = "identity linking";
        $('#authTypeRecover').addClass("hidden");
        $('#authTypeRecoverPlaceholder').removeClass("hidden");
    }

    $('#authTypeMsg1').text(authTypeText1);
    $('#authTypeMsg2').text(authTypeText2);
    $('.requestType').text(authTypeText3);
}

function getRegExAuthType() {
    return regExAuthType = /^[^`'\";<>]{0,4000}$/;
}

function getRegExIdString() {
    var inputAuthType = '#authType';
    var authType = $(inputAuthType).val();

    // Start with slash only
    if (authType === "X.509") {
        // var regExIdString = /^(\/[a-zA-Z]+=[a-zA-Z0-9\-\_\s\.@,'\/]+)+$/;
        var regExIdString = /^\/.+$/;

    // End with @iris.iam.ac.uk
    } else if (authType === "IRIS IAM - OIDC") {
        // var regExIdString = /^([a-f0-9]{8}\-[a-f0-9]{4}\-[a-f0-9]{4}\-[a-f0-9]{4}\-[a-f0-9]{12})@iris\-iam\.stfc\.ac\.uk$/;
        var regExIdString = /^.+@iris\-iam\.stfc\.ac\.uk\/$/;

    // End with @egi.eu
    } else if (authType === "EGI Proxy IdP") {
        var regExIdString = /^.+@egi\.eu$/;

    } else {
        var regExIdString = /^[^`'\";<>]{0,4000}$/;
    }
    return regExIdString;
}

function getRegExEmail() {
    return regExEmail = /^(([0-9a-zA-Z]+[-._])*[0-9a-zA-Z]+@([-0-9a-zA-Z]+[.])+[a-zA-Z]{2,6}){1}$/;
}

// Validate all inputs on any change
// Enable/disabled ID string input based on selection of auth type
// Enable/disable and format submit button based on all other inputs
function validate() {
    var idStringValid = false;
    var emailValid = false;
    var authTypeValid = false;

    // Validate auth type
    var regExAuthType = getRegExAuthType();
    var inputAuthType = '#authType';
    authTypeValid = isInputValid(regExAuthType, inputAuthType);
    authTypeEmpty = isInputEmpty(inputAuthType);

    // Validate ID string
    var regExIdString = getRegExIdString();
    var inputIdString = '#primaryIdString';
    idStringValid = isInputValid(regExIdString, inputIdString);
    idStringEmpty = isInputEmpty(inputIdString);

    // Validate email
    var regExEmail = getRegExEmail();
    var inputEmail = '#email';
    emailValid = isInputValid(regExEmail, inputEmail);
    emailEmpty = isInputEmpty(inputEmail);

    // Set the button based on validate status
    if (authTypeValid && idStringValid && emailValid && !authTypeEmpty && !idStringEmpty && !emailEmpty) {
        $('#submitRequest_btn').addClass("btn btn-success");
        $('#submitRequest_btn').prop("disabled", false);
    } else {
        $('#submitRequest_btn').removeClass("btn btn-success");
        $('#submitRequest_btn').addClass("btn btn-default");
        $('#submitRequest_btn').prop("disabled", true);
    }
}

// Check if user input is valid based on regex
// Input is regex and a selector e.g. '#id'
// Returns boolean flag (true if valid)
function isInputValid(regEx, input) {
    var inputValue = $(input).val();
    var inputValid = false;
    if (regEx.test(inputValue) !== false) {
        inputValid=true;
    }
    return inputValid;
}

// Check if user input is empty
// Input is selector e.g. '#id'
// Returns boolean flag (true if empty)
function isInputEmpty(input) {
    var inputValue = $(input).val();
    var inputEmpty = true;
    if (inputValue) {
        inputEmpty=false;
    }
    return inputEmpty;
}

// Enable ID string input if auth type is valid
function enableIdString(valid, empty) {
    // Disable/enable ID string based on auth type validity
    if (valid && !empty) {
        $('#primaryIdString').prop("disabled", false);
    } else {
        $('#primaryIdString').prop("disabled", true);
    }
}

// Format authentication type input on selecting a value based on validation
// Selections should be successful, but invalid/empty formating retained
function formatAuthType() {
    var regEx = getRegExAuthType();
    var input = '#authType';
    var valid = isInputValid(regEx, input);
    var empty = isInputEmpty(input);

    if (valid && !empty) {
        $('#authTypeGroup').addClass("has-success");
        $('#authTypeGroup').removeClass("has-error");
    } else {
        $('#authTypeGroup').removeClass("has-success");
        $('#authTypeGroup').addClass("has-error");
    }

    // Enable ID string input if auth type is valid
    enableIdString(valid, empty);
}

// Format ID string input on selection of auth type based on validation
// Only apply if value has been entered (valid/invalid based on regex)
function formatIdStringFromAuth() {
    var regEx = getRegExIdString();
    var input = '#primaryIdString';
    var valid = isInputValid(regEx, input);
    var empty = isInputEmpty(input)

    if (!empty) {
        if (valid) {
            $('#primaryIdStringGroup').addClass("has-success");
            $('#primaryIdStringGroup').removeClass("has-error");
            $('#idStringError').addClass("hidden");
            $('#idStringPlaceholder').removeClass("hidden");
        } else {
            $('#primaryIdStringGroup').removeClass("has-success");
            $('#primaryIdStringGroup').addClass("has-error");
            $('#idStringError').removeClass("hidden");
            $('#idStringPlaceholder').addClass("hidden");
            $('#idStringError').text("You have entered an invalid ID string for the selected authentication method");
        }
    } else {
        $('#primaryIdStringGroup').removeClass("has-error");
        $('#idStringError').addClass("hidden");
        $('#idStringPlaceholder').removeClass("hidden");
    }
}

// Format ID string input on entering value based on validation
// Error if invalid (regex) format or if nothing entered
function formatIdString() {
    var regEx = getRegExIdString();
    var input = '#primaryIdString';
    var valid = isInputValid(regEx, input);
    var empty = isInputEmpty(input);

    if (valid && !empty) {
        $('#primaryIdStringGroup').addClass("has-success");
        $('#primaryIdStringGroup').removeClass("has-error");
        $('#idStringError').addClass("hidden");
        $('#idStringPlaceholder').removeClass("hidden");
    } else {
        $('#primaryIdStringGroup').removeClass("has-success");
        $('#primaryIdStringGroup').addClass("has-error");
        $('#idStringError').removeClass("hidden");
        $('#idStringPlaceholder').addClass("hidden");
    }
    if (!valid && !empty) {
        $('#idStringError').text("You have entered an invalid ID string for the selected authentication method");
    } else if (empty) {
        $('#idStringError').text("Please enter the account's ID string");
    }
}

// Format email input on entering a value based on validation
// Error if invalid (regex) format or if nothing entered
function formatEmail() {
    var regEx = getRegExEmail();
    var input = '#email';
    var valid = isInputValid(regEx, input);
    var empty = isInputEmpty(input);

    if (valid && !empty) {
        $('#emailGroup').addClass("has-success");
        $('#emailGroup').removeClass("has-error");
        $('#emailError').addClass("hidden");
        $('#emailPlaceholder').removeClass("hidden");
    } else {
        $('#emailGroup').removeClass("has-success");
        $('#emailGroup').addClass("has-error");
        $('#emailError').removeClass("hidden");
        $('#emailPlaceholder').addClass("hidden");
    }
    if (!valid && !empty) {
        $('#emailError').text("Please enter a valid email");
    } else if (empty) {
        $('#emailError').text("Please enter the account's email");
    }
}
