<?php
$service = $params['service'];
$endpoint = $params['endpoint'];
$configService = \Factory::getConfigService();
$serviceTypes = $params['serviceTypes'];
?>

  <div class="rightPageContainer">

    <form action="index.php?Page_Type=Edit_Service_Endpoint" method="post"
           id="Edit_Service_Endpoint" name="Edit_Service_Endpoint">

        <h1>Edit Endpoint</h1>
        <a href="index.php?Page_Type=Service&id=<?php echo $service->getId();?>">
            &LeftArrow;View Parent Service</a>
        <br/>
        <a href="index.php?Page_Type=View_Service_Endpoint&id=<?php echo $endpoint->getId();?>">
            &LeftArrow;View Endpoint</a>
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
                   name="ENDPOINTNAME" id="ENDPOINTNAME"
                   value="<?php xecho($endpoint->getName()) ?>" />
        </div>

        <div class="form-group">
            <label class="control-label">Description</label>
            <br/>
            <input class="form-control"  style="width: 50%; display: inline;" type="text"
                   name="DESCRIPTION" id="DESCRIPTION"
                   value="<?php xecho($endpoint->getDescription()); ?>"/>
        </div>

        <div class="form-group">
            <label class="control-label">*Endpoint URL</label>
            <br/>
            <input class="form-control" style="width: 50%; display: inline;" type="text"
                   name="ENDPOINTURL" id="ENDPOINTURL"
                   value="<?php xecho($endpoint->getUrl()) ?>" />
        </div>

        <div class="form-group">
            <label class="control-label">
                *Endpoint Interface Name
                <span class="text-muted">
                    (Often same value as *Parent Service Type* as pre-selected in the pull-down)
                </span>
            </label>
            <br/>
            <input class="form-control" style="width: 40%; float: left;" type="text"
                   id="ENDPOINTINTERFACENAME" name="ENDPOINTINTERFACENAME"
                   value="<?php echo( $endpoint->getInterfaceName()); ?>" />
            <select  name="serviceType" id="selectInterfaceName" class="form-control"
                     style="width: 40%; float: left; "
                     onchange="updateServiceTypeFromSelection();" >
                     <?php foreach($serviceTypes as $type) { ?>
                        <option value="<?php echo($type->getName()) ?>"
                            <?php if($service->getServiceType() == $type){echo " selected=\"selected\"";}?>>
                                <?php echo($type->getName()) ?>
                        </option>
                    <?php } ?>
            </select>
            <br/>
            <br/>
            <label for="ENDPOINTINTERFACENAME" class="error"></label>
        </div>

        <input type="hidden" name ="SERVICE" value="<?php echo $service->getId(); ?>" />
        <input type="hidden" name ="ENDPOINTID" value="<?php echo $endpoint->getId(); ?>" />
        <br/>
        <input class="btn btn-default" type="submit" value="Edit Endpoint" />
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
         var regEx = /^([a-z])+:\/\/(((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:)*@)?(((\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5]))|((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?)(:\d*)?)(\/((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)+(\/(([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)*)*)?)?(\?((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|[\uE000-\uF8FF]|\/|\?)*)?(\#((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|\/|\?)*)?$/i;
         return regEx.test(value);
    }, "Invalid endpoint URI.")

    $("#Edit_Service_Endpoint").validate({
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
        $( "#Edit_Service_Endpoint" ).validate().element( "#ENDPOINTINTERFACENAME" );
    }
</script>