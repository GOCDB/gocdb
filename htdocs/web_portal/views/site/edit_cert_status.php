<?php
$site = $params['site'];
$certStatus = $site->getCertificationStatus();
$allStatuses = $params['statuses'];
?>
<div class="rightPageContainer">
    <div class=Form_Holder>
        <div class=Form_Holder_2>
            <h1><?php xecho($site) ?></h1>
            <br />
            <form action="index.php?Page_Type=Edit_Certification_Status" method="post" class="inputForm">
            <span class="input_name">Certification Status</span>
            <select class="add_edit_form"name="CERTSTATUSID">
                <?php
                    foreach($allStatuses as $status) {
                        if($certStatus != $status) {
                            echo "<option value=\"{$status->getId()}\">$status</option>";
                        } else {
                            echo "<option value=\"{$status->getId()}\" selected=\"selected\">$status</option>";
                        }
                    }
                ?>
            </select>
            <span class="input_name">Reason for Change (Max 300 Chars)</span>
            <input class="input_input_text" type="text"name="COMMENT" />
            <input class="input_input_hidden" type="hidden" name="SITEID" value="<?php echo $site->getId() ?>" />
            <input class="input_button" type="submit" value="Execute" />
            </form>
        </div>
    </div>
</div>
