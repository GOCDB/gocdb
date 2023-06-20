<div class="rightPageContainer">
    <div class="rightPageHolder">
        <div class="leftFloat">
            <img src="<?php echo \GocContextPath::getPath()?>img/add.png" class="pageLogo" />
        </div>
        <div class="leftFloat" style="width: 50em;">
            <h1 class="vSite">
                Add NGIs to <?php xecho($params['Name']) ?>
            </h1>
        </div>
        <div class="leftFloat">
            <?php if(sizeof($params['NGIs'])>0): ?>
                <!--  Services -->
                <form class="inputForm" method="post" action="index.php?Page_Type=Add_Project_NGIs" name="addNGIs">
                    <span class="input_name">
                        Please select the NGIs you wish to add to the
                        <?php xecho($params['Name'])?> project.
                    </span>
                    <select class="Downtime_Select" name="NGIs[]" size="20"  multiple id="NGIs" style="margin-left: 0em; width: 38em;">
                        <?php
                        foreach($params['NGIs'] as $ngi) {
                            echo "<option value=\"". $ngi->getId() . "\">"; xecho($ngi->getName()); echo "</option>";
                        }
                        ?>
                    </select>
                    <br />
                    <input class="input_input_hidden" type="hidden" name="ID" value="<?php echo $params['ID'] ?>" />
                    <input class="input_button" type="submit" value="Add selected NGIs">
                </form>
            <?php else: ?>
                There are either no NGIs in GOCDB or they are all a member of this project already.
            <?php endif; ?>

            <br />
            <br />
            Return to
            <a href="index.php?Page_Type=Project&amp;id=<?php echo $params['ID'] ?>">
                 <?php xecho($params['Name']) ?>
            </a>
        </div>
    </div>
</div>
