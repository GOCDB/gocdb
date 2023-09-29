<?php
$name = xssafe($params['Name']);
$description = xssafe($params['Description']);
$allowMonitoringException = xssafe($params['AllowMonitoringExtension']);
$id = $params['ID'];
$services = $params['Services'];
$SEsCount = sizeof($services);
$isUserAdmin = $params['isUserAdmin'];
?>


<div class="rightPageContainer">

    <!--Headings-->
    <div style="float: left; width: 50em;">
        <h1 style="float: left; margin-left: 0em;">Service Type: <?php echo $name?></h1>
        <span style="clear: both; float: left; padding-bottom: 0.4em;">
            <br /><?php require_once __DIR__ . '/../fragments/serviceTypeInfo.php'; ?>
        </span>
    </div>

    <!--Edit/Delete buttons-->
    <!--Enable edit and delete functionality ONLY when
        the web portal is accessed by an Admin.-->
    <?php if ($isUserAdmin) :?>
        <div style="float: right;">
            <div style="float: right; margin-left: 2em;">
                <a href="index.php?Page_Type=Admin_Edit_Service_Type&amp;id=<?php echo $id ?>">
                    <img src="<?php echo \GocContextPath::getPath()?>img/pencil.png" class="pencil" />
                    <br />
                    <br />
                    <span>Edit</span>
                </a>
            </div>
            <div style="float: right;">
                <script type="text/javascript"
                        src="<?php echo \GocContextPath::getPath()?>javascript/confirm.js">
                </script>
                <a onclick="return confirmSubmit()"
                   href="index.php?Page_Type=Admin_Delete_Service_Type<?php if ($SEsCount != 0) {
                        echo'_Denied';
                                                                      } ?>&id=<?php echo $id?>">
                    <img src="<?php echo \GocContextPath::getPath()?>img/trash.png" class="trash" />
                    <br />
                    <br />
                    <span>Delete</span>
                </a>
            </div>
        </div>
    <?php endif; ?>


    <!--  Services -->
    <div class="listContainer" style="width: 99.5%; float: left; margin-top: 3em; margin-right: 10px;">
        <span class="header" style="vertical-align:middle; float: left; padding-top: 0.9em; padding-left: 1em;">
            There <?php if ($SEsCount == 0) {
                echo 'are no services';
                  } elseif ($SEsCount == 1) {
                      echo 'is one service';
                  } else {
                      echo 'are ' . $SEsCount . ' services';
                  }?>
            with <?php echo $name ?> service type<?php if ($SEsCount == 0) {
                echo '.';
                 } else {
                     echo ':';
                 }?>
        </span>
        <img src="<?php echo \GocContextPath::getPath()?>img/service.png" class="decoration" />
        <?php if ($SEsCount != 0) : ?>
            <table style="clear: both; width: 100%;">
                <tr class="site_table_row_1">
                    <th class="site_table">Hostname</th>
                    <th class="site_table">Production</th>
                    <th class="site_table">Monitored</th>
                    <th class="site_table"><a href="index.php?Page_Type=Scopes">Scope(s)</a></th>
                </tr>


                <?php $num = 2;

                foreach ($services as $se) {
                    ?>

                    <tr class="site_table_row_<?php echo $num ?>">
                        <td class="site_table">
                            <div style="background-color: inherit;">
                                <img src="<?php echo \GocContextPath::getPath()?>img/server.png" class="decoration" />
                                <span style="vertical-align: middle;">
                                    <a href="index.php?Page_Type=Service&amp;id=<?php echo $se->getId() ?>">
                                        <?php xecho($se->getHostname());?>
                                        (<?php xecho($se->getServiceType()->getName());?>)
                                    </a>
                                </span>
                            </div>
                        </td>

                        <td class="site_table">
                        <?php
                        switch ($se->getProduction()) {
                            case true:
                                ?>
                                <img src="<?php echo \GocContextPath::getPath()?>img/tick.png"
                                            height="22px" style="vertical-align: middle;" />
                                <?php
                                break;
                            case false:
                                ?>
                                <img src="<?php echo \GocContextPath::getPath()?>img/cross.png"
                                            height="22px" style="vertical-align: middle;" />
                                <?php
                                break;
                        }
                        ?>
                        </td>

                        <td class="site_table">
                        <?php
                        switch ($se->getMonitored()) {
                            case true:
                                ?>
                                <img src="<?php echo \GocContextPath::getPath()?>img/tick.png"
                                            height="22px" style="vertical-align: middle;" />
                                <?php
                                break;
                            case false:
                                ?>
                                <img src="<?php echo \GocContextPath::getPath()?>img/cross.png"
                                            height="22px" style="vertical-align: middle;" />
                                <?php
                                break;
                        }
                        ?>
                        </td>

                        <td class="site_table">
                        <?php xecho($se->getScopeNamesAsString()); ?>
                        </td>
                    </tr>
                    <?php
                    if ($num == 1) {
                        $num = 2;
                    } else {
                        $num = 1;
                    }
                } // End of the foreach loop iterating over SEs
                ?>

            </table>

        <?php endif;?>
    </div>

</div>
