<?php $users = $params["Users"] ?>
<?php $numUsers = sizeof($users) ?>

<div class="rightPageContainer">
    <div style="float: left; text-align: center;">
        <img src="<?php echo \GocContextPath::getPath()?>img/user.png" class="pageLogo" />
    </div>
    <h1>
        Users
    </h1>
    <span>
        All users in GOCDB
        <br />
    </span>

    <!-- Filter -->
    <div class="siteContainer">
        <form action="index.php?Page_Type=Admin_Users" method="GET" class="inline">
            <input type="hidden" name="Page_Type" value="Admin_Users" />

            <span class="header leftFloat">
                Filter <a href="index.php?Page_Type=Admin_Users">&nbsp;&nbsp;(clear)</a>
            </span>

            <br />

            <div class="topMargin leftFloat siteFilter">
                <span class="middle" style="margin-right: 0.4em">Forename: </span>
                <input class="middle" style="width: 5.5em;" type="text" name="Forename"
                    <?php if(isset($params['Forename']))
                        xecho("value=".$params['Forename']);
                    ?>
                />
            </div>

            <div class="topMargin leftFloat siteFilter">
                <span class="middle" style="margin-right: 0.4em">Surname: </span>
                <input class="middle" style="width: 5.5em;" type="text" name="Surname"
                    <?php if(isset($params['Surname']))
                       xecho("value=".$params['Surname']);
                    ?>
                />
            </div>

            <div class="topMargin leftFloat siteFilter">
                <span class="">GOCDB Admin.: </span>
                <select name="IsAdmin" onchange="form.submit()">
                    <option value=""<?php if($params['IsAdmin'] == null) echo " selected" ?>>(all)</option>
                    <option value="true"<?php if($params['IsAdmin'] == true) echo " selected" ?>>Yes</option>
                    <option value="false"<?php if($params['IsAdmin'] == false and !is_null($params['IsAdmin'])) echo " selected" ?>>No</option>
                </select>
            </div>

            <div class="topMargin leftFloat siteFilter">
                <span class="middle" style="margin-right: 0.4em">Certificate DN: </span>
                <input class="middle" style="width: 11em;" type="text" name="DN"
                    <?php if(isset($params['DN']))
                        xecho("value=".$params['DN']);
                    ?>
                />
            </div>


            <div class="topMargin leftFloat siteFilter">
                <input class="middle" type="image" src="<?php echo \GocContextPath::getPath()?>img/enter.png" name="image" width="20" height="20">
            </div>

        </form>
    </div>

    <!--  Users -->
    <div class="listContainer">
        <span class="header listHeader">
            <?php if($numUsers == 0)    {echo "Your search returned no results";}
                  elseif ($numUsers ==1){echo "1 User";}
                  else                  {echo $numUsers . "Users";} ?>
        </span>
        <img src="<?php echo \GocContextPath::getPath()?>img/user.png" class="decoration" />
        <?php if ($numUsers!=0): ?>
            <table class="vSiteResults" id="selectedSETable">
                <tr class="site_table_row_1">
                    <th class="site_table">Name</th>
                    <th class="site_table">Certificate DN</th>
                    <th class="site_table">GOCDB<br>Admin.?</th>
                </tr>
                <?php
                $num = 2;
                if($numUsers > 0) {
                foreach($users as $user) {
                ?>
                <?php if($user->isAdmin()) { $style = " style=\"background-color: #A3D7A3;\""; } else { $style = ""; } ?>
                <tr class="site_table_row_<?php echo $num ?>" <?php echo $style ?>>
                    <td class="site_table">
                        <div style="background-color: inherit;">
                            <span style="vertical-align: middle;">
                                <a href="index.php?Page_Type=User&amp;id=<?php echo $user->getId() ?>">
                                    <?php echo xssafe($user->getSurname()).", ".xssafe($user->getForename()); ?>
                                </a>
                            </span>
                        </div>
                    </td>

                    <td class="site_table">
                        <div style="background-color: inherit;">
                            <span style="vertical-align: middle;">
                                <a href="index.php?Page_Type=Admin_Edit_User_DN&amp;id=<?php echo $user->getId() ?>">
                                    <?php xecho($user->getCertificateDn()); ?>
                                </a>
                            </span>
                        </div>
                    </td>

                    <td class="site_table">
                        <!--<a href="index.php?Page_Type=Admin_Change_User_Admin_Status&amp;id=<?php echo $user->getId() ?>">-->
                            <?php
                                switch($user->isAdmin()) {
                                    case true:
                                        ?>
                                        <img src="<?php echo \GocContextPath::getPath()?>img/tick.png" height="22px" style="vertical-align: middle;" />
                                        <?php
                                        break;
                                    case false:
                                        ?>
                                        <img src="<?php echo \GocContextPath::getPath()?>img/cross.png" height="22px" style="vertical-align: middle;" />
                                        <?php
                                        break;
                                }
                              ?>
                        <!--</a>-->
                    </td>

                </tr>
                <?php
                    if($num == 1) { $num = 2; } else { $num = 1; }
                    } // End of the foreach loop iterating over sites
                }
                ?>
            </table>

            <div style="margin-right: 0.4em">
                <br>
                &nbsp; Click on a user's name to view more details, or to edit or
                delete them. Click on their DN to update it.
                <!-- Click on the tick or cross to promote them to or demote them from GOCDB admin status-->
            </div>

        <?php endif; ?>
    </div>
</div>
