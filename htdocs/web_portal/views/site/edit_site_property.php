<?php
$site = $params['site'];
$prop = $params['prop'];
?>
<div class="rightPageContainer">
    <form name="Edit_Site_Property" action="index.php?Page_Type=Edit_Site_Property" method="post" class="inputForm" id="Site_Property_Form" name="Edit_Site_Property_Form">

        <h1>Edit Site Property</h1>
        <br />

        <span class="input_name">
            Property Name
        </span>
        <input class="input_input_text" type="text" name="KEYPAIRNAME" value="<?php xecho($prop->getKeyName());?>" />
        <span class="input_name">
            Property Value
        </span>
        <input class="input_input_text" type="text" name="KEYPAIRVALUE" value="<?php xecho($prop->getKeyValue());?>"/>
        <input class="input_input_text" type="hidden" name ="SITE" value="<?php echo($site->getId());?>" />
        <input class="input_input_text" type="hidden" name ="PROP" value="<?php echo($prop->getId());?>" />

        <input class="input_button" type="submit" value="Edit Site Property" />
    </form>
</div>
