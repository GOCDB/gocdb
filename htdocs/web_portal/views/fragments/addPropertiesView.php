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
    <p class="expandMulti">Adding multiple properties?</p>
    <form name="Add_Properties" action="index.php?Page_Type=<?php echo $addPropertiesURL;?>" method="post" class="multiInput inputForm" id="Properties_Form" style="display: none">

        <textarea name="PROPERTIES" rows="10" style="width: 100%"></textarea>

        <input class="input_input_text" type="hidden" name ="PARENT" value="<?php echo $parentID;?>" />

        <input class="input_button" type="submit" value="Add Properties" />
    </form>

    <script type="text/javascript">
        $(document).ready(function() {

                $('.expandMulti').click(function(){
                    $('.multiInput').slideToggle('fast');
                });
            }
        );
    </script>
