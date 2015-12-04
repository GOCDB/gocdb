    <form name="Add_Property" action="index.php?Page_Type=<?php echo $addPropertiesURL;?>" method="post" class="inputForm" id="Property_Form">

        <br />

        <span class="input_name">
            Property Name            
        </span>
        <input class="input_input_text" type="text" name="KEYPAIRNAME" />
        <span class="input_name">
            Property Value            
        </span>
        <input class="input_input_text" type="text" name="KEYPAIRVALUE" />
        <input class="input_input_text" type="hidden" name ="PARENT" value="<?php echo $parentID;?>" />

    	<input class="input_button" type="submit" value="Add Property" />
        <br/>
        <br/>

    </form>
    <p class="expandMulti" style="cursor: pointer;">Adding multiple properties?</p>
    <div class="multiInput">
    <form name="Add_Properties" action="index.php?Page_Type=<?php echo $addPropertiesURL;?>" method="post" class="inputForm" id="Properties_Form">

        <textarea name="PROPERTIES" id="propertiesTextArea" rows="10" style="width: 100%" placeholder="Input your properties in 'name = value' form, separated by newlines. You can also browse and upload a text file below"></textarea>

        <input class="input_input_text" type="hidden" name="PARENT" value="<?php echo $parentID;?>" />

        <input class="input_button" type=file id=files style="display: inline;" />
        <input type="button" class="input_button" id="upload" value="Upload"/>
        <br/>
        <input class="input_button" type="submit" value="Add Properties" />

    </form>
    </div>

    <script type="text/javascript">

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
//                $('#upload').hide();

                $('.expandMulti').click(function(){
                    $('.multiInput').slideToggle('fast');
                });
            }
        );
    </script>
