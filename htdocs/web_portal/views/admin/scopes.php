<div class="rightPageContainer">

    <div style="float: left;">
        <h1 style="float: left; margin-left: 0em;">
                Scopes
        </h1>
        <span style="clear: both; float: left; padding-bottom: 0.4em;">
            Click on the name of a scope to edit it and view objects with that scope tag.
        </span>
    </div>
   <!-- hide add when read only -->
   <?php if(!$params['portalIsReadOnly']):?>
        <div style="float: right;">
            <center>
                <a href="index.php?Page_Type=Admin_Add_Scope">
                <img src="<?php echo \GocContextPath::getPath()?>img/add.png" height="25px" />
                <br />
                <span>Add Scope</span>
                </a>
            </center>
        </div>
   <?php endif; ?>

    <?php $numberOfScopes = sizeof($params['Scopes'])?>
    <div class="listContainer">
        <span class="header listHeader">
            <?php echo $numberOfScopes ?> Scope<?php if($numberOfScopes) echo "s"?>
        </span>
        <table class="vSiteResults" id="selectedSETable">
            <tr class="site_table_row_1">
                <th class="site_table">Name</th>
                <?php if(!$params['portalIsReadOnly']):?>
                    <th class="site_table">Remove</th>
                <?php endif; ?>
            </tr>
            <?php
            $num = 2;
            if(sizeof($numberOfScopes > 0)) {
                foreach($params['Scopes'] as $scope) {
                ?>
                <tr class="site_table_row_<?php echo $num ?>">
                    <td class="site_table" style="width: 90%">
                        <div style="background-color: inherit;">
                            <span style="vertical-align: middle;">
                                <a href="index.php?Page_Type=Admin_Scope&amp;id=<?php echo $scope->getId() ?>">
                                    <?php xecho($scope->getName()); ?>
                                </a>
                            </span>
                        </div>
                    </td>
                    <?php if(!$params['portalIsReadOnly']):?>
                        <td class="site_table">
                             <script type="text/javascript" src="<?php echo \GocContextPath::getPath()?>javascript/confirm.js"></script>
                             <a onclick="return confirmSubmit()" href="index.php?Page_Type=Admin_Remove_Scope&id=<?php echo $scope->getId() ?>">
                                <img src="<?php echo \GocContextPath::getPath()?>img/cross.png" height="22px" style="vertical-align: middle;" />
                            </a>
                        </td>
                    <?php endif ?>
                </tr>
                <?php
                    if($num == 1) { $num = 2; } else { $num = 1; }
                    } // End of the foreach loop iterating over scopes
            }
            ?>
        </table>
    </div>
</div>