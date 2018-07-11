<?php
$service = $params['se'];
$serviceTypes = $params['serviceTypes'];
$serviceType = $params['serviceType'];
?>
<div class="rightPageContainer">
    <form action="index.php?Page_Type=Add_Service_Endpoint" method="post"
          id="Add_Service_Endpoint" name="Add_Service_Endpoint"> <!-- class="inputForm"-->

        <h1>Add Endpoint</h1>
         <a href="index.php?Page_Type=Service&amp;id=<?php echo $service->getId();?>">
            &LeftArrow;View Parent Service</a>
        <br />
        <br />
        <ul>
            <li>A single Service may define optional Endpoint objects.</li>
            <li>Endpoints model network locations for different service-functionalities
                that can't be described by the main ServiceType and URL alone.</li>
            <li>When declaring a Service Downtime, different Endpoints can be
                selectively put into downtime.</li>
        </ul>
        <br/>
        <p><span class="text-muted">* Required field</span></p>
        <div class="form-group">
            <label class="control-label">*Endpoint Name</label>
            <br/>
            <input class="form-control" style="width: 50%; display: inline;" type="text"
                   name="ENDPOINTNAME" id="ENDPOINTNAME" />
        </div>

        <div class="form-group">
            <label class="control-label">Description</label>
            <br/>
            <input class="form-control"  style="width: 50%; display: inline;" type="text"
                   name="DESCRIPTION" id="DESCRIPTION" />
        </div>


        <div class="form-group">
            <label class="control-label">*Endpoint URL</label>
            <br/>
            <input class="form-control" style="width: 50%; display: inline;" type="text"
                   name="ENDPOINTURL" id="ENDPOINTURL" />
        </div>

        <div class="form-group">
            <label class="control-label">Contact E-mail</label>
            <br/>
            <input class="form-control" style="width: 50%; display: inline;" type="text"
                   name="EMAIL" id="EMAIL" />
        </div>

        <div class="form-group">
            <label class="control-label">Monitored</label>
            <br/>
            <input type="checkbox" name="IS_MONITORED" id="IS_MONITORED"/>
        </div>

        <div class="form-group">
            <label class="control-label">
                *Endpoint Interface Name
                <span class="text-muted">
                    (Often same value as parent service type as pre-selected in the pull-down)
                </span>
            </label>
            <br/>
            <input class="form-control" style="width: 40%; float: left;" type="text"
                   id="ENDPOINTINTERFACENAME" name="ENDPOINTINTERFACENAME"
                   value="<?php echo $serviceType; ?>" />
            <select  name="serviceType" id="selectInterfaceName" class="form-control"
                     style="width: 40%; float: left; "
                     onchange="updateServiceTypeFromSelection();" >
                     <?php foreach($serviceTypes as $type) { ?>
                        <option value="<?php echo $type->getName() ?>"
                            <?php if($service->getServiceType() == $type){echo " selected=\"selected\"";}?>>
                                <?php echo $type->getName() ?>
                        </option>
                    <?php } ?>
            </select>
            <br/>
            <br/>
            <label for="ENDPOINTINTERFACENAME" class="error"></label>
        </div>


        <input type="hidden" name ="SERVICE" value="<?php echo $service->getId();?>" />
        <br/>
        <input class="btn btn-default" type="submit" value="Add Service Endpoint" />
    </form>
</div>

<script>
    // just for the demos, avoids form submit
    jQuery.validator.setDefaults({
        debug: false,
        success: "valid"
    });

    $.validator.addMethod("validateDescription", function(value){
        var regEx = /^[A-Za-z0-9\s._:-]{0,255}$/;
        return regEx.test(value);
    }, "Invalid description.");

    $.validator.addMethod("validateInterfaceName", function(value) {
        var regEx = /^[A-Za-z0-9\s._:-]{0,255}$/;
        return regEx.test(value);
    }, 'Invalid Interface Name.');

    $.validator.addMethod("validateMyUrl", function(value) {
        var regEx = /^[0-9a-zA-Z:\/\.\&\?\_\$\+\!\*\'\(\)\,\-#\[\]@;=~%]*$/i;
        return regEx.test(value);
    }, "Invalid endpoint URI.")

    $("#Add_Service_Endpoint").validate({
        rules: {
            ENDPOINTNAME: {
                required: true
            },
            ENDPOINTURL: {
                required: true,
                validateMyUrl: ""
                //url: true
            },
            ENDPOINTINTERFACENAME: {
                required: true,
                validateInterfaceName: ""
            },
            DESCRIPTION: {
                validateDescription: "",
                required: false
            }
        }
    });

    function updateServiceTypeFromSelection() {
        $('#ENDPOINTINTERFACENAME').val($('#selectInterfaceName').val());
        //http://stackoverflow.com/questions/1479255/how-to-manually-trigger-validation-with-jquery-validate
        //$("#Edit_Service_Endpoint").valid(); // to validate the whole form
        //$('#ENDPOINTINTERFACENAME').valid(); // to validate just the element (but doc says you need to call validate() on form first)
        $( "#Add_Service_Endpoint" ).validate().element( "#ENDPOINTINTERFACENAME" );
    }


    /*function updateServiceTypeFromSelection(){
       $('#ENDPOINTINTERFACENAME').val($('#selectInterfaceName').val());
    }

    function enableSubmit(){
       if(validateEndpointName() & validateEndpointUrl() & validateInterfaceName()){
           return true;
       } else {
           alert('Invalid input');
           return false;
       }
    }

    function validateInterfaceName(){
       var intefaceName =  $('#ENDPOINTINTERFACENAME').val();
       var regEx = /^[A-Za-z0-9\s._:-]{0,255}$/;
       var valid = 'false';
         if(intefaceName === ''){ //If field is empty then show no errors or colours
             valid = 'empty';
         } else {
            if(regEx.test(intefaceName) === false){
              valid = 'false';
            } else {
                valid = 'true';
            }
         }

         if(valid === 'false' || valid === 'empty') {
            $('#interfaceNameGroup').removeClass("has-success");
            $('#interfaceNameGroup').addClass("has-error");
            $("#interfaceNameError").removeClass("hidden");
            $("#interfaceNameError").text("Invalid");
            return false;
         } else if(valid === 'true'){
            $("#interfaceNameError").addClass("hidden");
            $('#interfaceNameGroup').addClass("has-success");
            return true;
         }
    }


    function validateEndpointUrl(){
       var endpointURL = $('#ENDPOINTURL').val();
       // regEx copied from jquery validator, see:
       // http://stackoverflow.com/questions/1303872/trying-to-validate-url-using-javascript
       //var regEx = /^(https?|ftp):\/\/(((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:)*@)?(((\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5]))|((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?)(:\d*)?)(\/((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)+(\/(([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)*)*)?)?(\?((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|[\uE000-\uF8FF]|\/|\?)*)?(\#((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|\/|\?)*)?$/i;
       var regEx = /^[a-z]+:\/\/(((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:)*@)?(((\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5]))|((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?)(:\d*)?)(\/((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)+(\/(([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)*)*)?)?(\?((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|[\uE000-\uF8FF]|\/|\?)*)?(\#((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|\/|\?)*)?$/i;
       var valid = 'false';
       if(endpointURL === ''){
          valid = 'empty';
       } else {
          if(regEx.test(endpointURL) === false){
              valid = 'false';
            } else {
                valid = 'true';
            }
       }

        if(valid === 'false') {
            $('#endpointUrlGroup').removeClass("has-success");
            $('#endpointUrlGroup').addClass("has-error");
            $("#endpointUrlError").removeClass("hidden");
            $("#endpointUrlError").text("Invalid URL");
            return false;
         } else if(valid === 'true' || valid === 'empty'){
            $("#endpointUrlError").addClass("hidden");
            $('#endpointUrlGroup').addClass("has-success");
            return true;
         }
    }

    function validateEndpointName(){
         var endpointName = $('#ENDPOINTNAME').val();
         var regEx = /^[A-Za-z0-9\s._:]{0,255}$/;
         var valid = 'false';
         if(endpointName === ''){ //If field is empty then show no errors or colours
             valid = 'empty';
         } else {
            if(regEx.test(endpointName) === false){
              valid = 'false';
            } else {
                valid = 'true';
            }
         }

          if(valid === 'false' || valid === 'empty') {
            $('#endpointNameGroup').removeClass("has-success");
            $('#endpointNameGroup').addClass("has-error");
            $("#endpointNameError").removeClass("hidden");
            $("#endpointNameError").text("Invalid");
            return false;
         } else if(valid === 'true'){
            $("#endpointNameError").addClass("hidden");
            $('#endpointNameGroup').addClass("has-success");
            return true;
         }
    }*/
</script>
