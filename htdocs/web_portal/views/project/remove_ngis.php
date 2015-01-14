<div class="rightPageContainer">
    <div class="rightPageHolder">
        <div class="leftFloat">
            <img src="<?php echo \GocContextPath::getPath()?>img/cross.png" class="pageLogo" />
        </div>
        <div class="leftFloat" style="width: 50em;">
            <h1 class="vSite">
                Remove NGIs from <?php echo $params['Name'] ?>
            </h1>
        </div>
        <div class="leftFloat">
            <!--  Services -->
            <form class="inputForm" method="post" action="index.php?Page_Type=Remove_Project_NGIs" name="removeNGIs">
                <span class="input_name">
                    Please select the NGIs you wish to remove from the 
                    <?php echo $params['Name']?> project.
                </span>
                <select class="Downtime_Select" name="NGIs[]" size="20"  multiple id="NGIs" style="margin-left: 0em; width: 38em;">
                    <?php
                    foreach($params['NGIs'] as $ngi) {
                        echo "<option value=\"". $ngi->getId() . "\">" . $ngi->getName(). "</option>";
                    }	
                    ?>
                </select>
                <br />
                <input class="input_input_hidden" type="hidden" name="ID" value="<?php echo $params['ID'] ?>" />
                <input class="input_button" type="submit" value="Remove selected NGIs">
            </form>
            <br/>
            <br/>
            Return to 
            <a href="index.php?Page_Type=Project&id=<?php echo $params['ID'] ?>">
                 <?php echo $params['Name'] ?>
            </a>
        </div>
    </div>
</div>

