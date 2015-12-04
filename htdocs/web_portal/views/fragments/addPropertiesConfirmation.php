    <p>
        You are about to add the following properties:<br/><br/>
    </p>

    <table id="propertiesToBeAddedTable" class="table table-striped table-condensed tablesorter">
        <thead>
        <tr>
            <th>Name</th>
            <th>Value</th>
            <th><input type="checkbox" id="selectAllProps" checked/> Add?</th>
        </tr>
        </thead>
        <tbody>

        <?php
        foreach($propertyArray as $key => $value) {
            ?>

            <tr>
                <td style="width: 35%;"><?php xecho($key); ?></td>
                <td style="width: 35%;"><?php xecho($value); ?></td>
                <td style="width: 10%;"><input type='checkbox' class="propCheckBox" form="addPropertiesForm" name='selectedProps[<?php echo $key; ?>]' value="<?php echo $value;?>" checked/></td>
            </tr>
            <?php
        }
        ?>
        </tbody>
    </table>


    <form class="inputForm" method="post" action="index.php?Page_Type=<?php echo $addPropertiesPage;?>" name="addProperties" id="addPropertiesForm">
        <input class="input_input_hidden" type="hidden" name="UserConfirmed" value="true" />
        <input class="input_input_text" type="hidden" name ="PARENT" value="<?php echo $parent->getId();?>" />
        <input type="checkbox" id="preventOverwriteCheckbox" name="PREVENTOVERWRITE"/>
        Fail on duplicate property
        <br/>
        <br/>


        <input type="submit" value="Add properties" class="input_button">
    </form>

    <script>

        function confirmPropAction() {
            //number of checked properties
            var numPropsSelected = $('#extensionPropsTable').find('input[type=checkbox]:checked').length;
            //name of action
            if (numPropsSelected != 0){
                //confirmation box
                if (confirm("Do you wish to perform the action \"" + propAction + "\" on " + numPropsSelected + " property(s).") == true){
                    $("#Modify_Properties_Form").submit();
                }
            } else {
                alert("Please select at least one property to add")
            }
        }

        $(document).ready(function () {
            //register handler for the select/deselect all properties checkbox
            $("#selectAllProps").change(function(){
                $(".propCheckBox").prop('checked', $(this).prop("checked"));
            });
        });

    </script>
