    <form name="Add_Property" action="index.php?Page_Type=<?php echo $addPropertiesURL;?>" method="post" class="inputForm" id="Property_Form"">

        <br />
        <div class="form-group">
            <input class="form-control singleProp" type="text" placeholder="Property Name" name="KEYPAIRNAME" id="KEYPAIRNAME" />
        </div>

        <br />
        <div class="form-group">
            <input class="form-control singleProp" type="text" placeholder="Property Value" name="KEYPAIRVALUE" id="KEYPAIRVALUE" />
        </div>

        <br />

        <input class="input_input_text " type="hidden" name ="PARENT" value="<?php echo $parentID;?>" />

    	<input class="btn btn-default singleProp" type="submit" value="Add Property" style="display: inline;"/>



    </form>
    <input class="btn btn-default expandMulti" value="Add multiple properties" style="float: right;" />

    <br/>
    <br/>
    <div class="multiInput">
    <form name="Add_Properties" action="index.php?Page_Type=<?php echo $addPropertiesURL;?>" method="post" class="inputForm" id="Properties_Form">

        <textarea name="PROPERTIES" id="propertiesTextArea" class="form-control" rows="10" style="width: 100%" placeholder="Input your properties in 'name = value' form, separated by newlines. You can also browse and upload a text file below"></textarea>

        <input class="input_input_text" type="hidden" name="PARENT" value="<?php echo $parentID;?>" />
        <br/>

        <input class="btn btn-default" type=file id=files style="display: inline;" />
        <input type="button" class="btn btn-default" id="upload" value="Upload"/>
        <input class="btn btn-default" type="submit" value="Add Properties" style="float: right;"/>

    </form>
    </div>

    <script type="text/javascript">

        //This chunk helps the jquery validator integrate with the bootstrap ui bits
        $.validator.setDefaults({
            highlight: function(element) {
                $(element).closest('.form-group').addClass('has-error');
            },
            unhighlight: function(element) {
                $(element).closest('.form-group').removeClass('has-error');
            },
            errorElement: 'span',
            errorClass: 'help-block',
            errorPlacement: function(error, element) {
                if(element.parent('.input-group').length) {
                    error.insertAfter(element.parent());
                } else {
                    error.insertAfter(element);
                }
            }
        });

        $.validator.addMethod("validateKeyName", function(value) {
            var regEx = /^[a-zA-Z0-9\s@_\-\[\]\+\.]+$/;
            return regEx.test(value);
        }, 'Invalid Key Name.');

        $.validator.addMethod("validateKeyValue", function(value) {
            var regEx = /^[^`'\"><]+$/;
            return regEx.test(value);
        }, 'Invalid Key Value.');

        $("#Property_Form").validate({
            rules: {
                KEYPAIRNAME: {
                    required: true,
                    validateKeyName: "",
                    maxlength: 255
                },
                KEYPAIRVALUE: {
                    required: true,
                    validateKeyValue: "",
                    maxlength: 255
                }
            }
        });

        $("#Properties_Form").validate({
            rules: {
                PROPERTIES: {
                    required: true
                }
            }
        });

        var fileInput = $('#files');
        var uploadButton = $('#upload');

        uploadButton.on('click', function() {
            if (!window.FileReader) {
                alert('Your browser is not supported')
            }
            var input = fileInput.get(0);

            // Create a reader object
            var reader = new FileReader();
            if (input.files.length) {
                var textFile = input.files[0];
                reader.readAsText(textFile);
                $(reader).on('load', processFile);
            } else {
                alert('Please upload a file before continuing')
            }
        });

        function processFile(e) {
            var file = e.target.result,
                results;
            if (file && file.length) {
                results = file.split("\n");

                //console.log(results);
                $('#propertiesTextArea').val(file);
                //$('#age').val(results[1]);
            }
        }

        $(document).ready(function() {

                $('.multiInput').hide();

                $('.expandMulti').click(function(){
                    $('.multiInput').slideToggle('fast');
                    $('#Property_Form :input').prop("disabled", function(i,v){return !v;});
                });
            }
        );
    </script>
