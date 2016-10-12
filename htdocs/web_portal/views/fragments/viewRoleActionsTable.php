<div class="listContainer">
    <!-- if there are many records, then set table height to sensible value with
    horizontal and vertical overflow -->
    <?php if (sizeof($params['RoleActionRecords']) > 5){ ?>
    <div style="height: 500px; overflow: auto;">
    <?php } else { ?>
    <!-- if there are fewer records, don't bother setting height and still allow
    horizontal overflow -->
    <div style="overflow: auto;">
    <?php } ?>

        <span class="header listHeader">
            Role Request Log (Only shown if you have the necessary permissions)
        </span>
        <table class="table table-striped table-condensed" id="roleActionTable">
            <thead>
                <tr>
                    <th class="site_table">Requested</th>
                    <th class="site_table">By</th>
                    <th class="site_table">Occurred On</th>
                    <th class="site_table">OldStatus</th>
                    <th class="site_table">NewStatus</th>
                    <th class="site_table">Updated By</th>
                </tr>
            </thead>
            <tbody>
                <?php
                    foreach ($params['RoleActionRecords'] as $ra) {
                        ?>
                        <tr>
                            <td>
                                <?php xecho($ra->getRoleTypeName()); ?>
                            </td>
                            <td>
                                <a href="index.php?Page_Type=User&id=<?php echo $ra->getRoleUserId(); ?>">
                                    <?php xecho($ra->getRoleUserPrinciple()); ?>
                                </a>
                            </td>
                            <td>
                                <?php echo($ra->getActionDate()->format('Y-m-d H:i:s')); ?>
                            </td>
                            <td>
                                <?php xecho($ra->getRolePreStatus()); ?>
                            </td>
                            <td>
                                <?php xecho($ra->getRoleNewStatus()); ?>
                            </td>
                            <td>
                                <a href="index.php?Page_Type=User&id=<?php echo $ra->getUpdatedByUserId(); ?>">
                                    <?php xecho($ra->getUpdatedByUserPrinciple()); ?>
                                </a>
                            </td>
                        </tr>
                        <?php
                    } // End of the foreach loop iterating over RoleActions
                ?>
            </tbody>
        </table>
    </div>
</div>
