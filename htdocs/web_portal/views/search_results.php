<div class="rightPageContainer">
    <div style="float: left;">
        <img src="<?php echo \GocContextPath::getPath()?>img/search.png" class="pageLogo" />
    </div>
    <div style="float: left;">
        <h1 style="float: left; margin-left: 0em;">
            Results for &#8220;<?php xecho( $params['searchTerm'])?>&#8221;
        </h1>
        <span style="clear: both; float: left; padding-bottom: 0.4em;">
            <ul>
                <li>Searching sites, hostnames, descriptions and users</li>
                <li>Please note, these search results are case-insensitive unlike the API parameters which must be case-sensitive</li>
            </ul>
        </span>
    </div>

    <!--  NGI Results -->
    <?php if(sizeof($params['ngiResults']) > 0) { ?>
        <div class="listContainer" style="width: 97%;">
            <div style="padding: 0.5em;">
                <img style="vertical-align: middle; clear: both; height: 35px; width: 35px;" src="<?php echo \GocContextPath::getPath()?>img/ngi.png" />
                <h3 style="vertical-align: middle; clear: both; display: inline; margin-left: 0.3em; font-size: 1.3em; padding-bottom: 0em;">
                    NGIs
                </h3>
            </div>

            <table id="ngisTable" class="table table-striped table-condensed tablesorter">
        <thead>
            <tr>
            <th>Name</th>
            <th>Description</th>
            </tr>
        </thead>
        <tbody>
                <?php
                $num = 2;
                foreach($params['ngiResults'] as $ngi) {
                ?>
                <tr>
                    <td style="width: 25%">
            <a href="index.php?Page_Type=NGI&amp;id=<?php echo $ngi->getId()?>">
                <img class="flag" src="<?php echo \GocContextPath::getPath()?>img/ngi/<?php xecho($ngi->getName()) ?>.jpg" style="vertical-align: middle">
                &nbsp;&nbsp;&nbsp;<?php xecho($ngi->getName()); ?>
            </a>
                    </td>

                    <td>
                        <?php xecho($ngi->getDescription()); ?>
                    </td>
                </tr>
                <?php
                    //if($num == 1) { $num = 2; } else { $num = 1; }
                    } // End of the foreach loop iterating over users
                ?>
        </tbody>
            </table>
        </div>
    <?php } // end of "if NGIs is > 0"?>

    <!--  Site Results -->
    <?php if(sizeof($params['siteResults']) > 0) { ?>
        <div class="listContainer" style="width: 97%;">
            <div style="padding: 0.5em;">
                <img style="vertical-align: middle; clear: both; height: 35px; width: 35px;" src="<?php echo \GocContextPath::getPath()?>img/site.png" />
                <h3 style="vertical-align: middle; clear: both; display: inline; margin-left: 0.3em; font-size: 1.3em; padding-bottom: 0em;">
                    Sites
                </h3>
            </div>

            <table id="sitesTable" class="table table-striped table-condensed tablesorter">
        <thead>
                <tr>
                    <th>Short Name</th>
                    <th>Official Name</th>
                </tr>
        </thead>
        <tbody>
                <?php
                $num = 2;
                if(sizeof($params['siteResults'] > 0)) {
                foreach($params['siteResults'] as $site) {
                ?>
                <tr>
                    <td style="width: 30%">
            <a href="index.php?Page_Type=Site&amp;id=<?php echo $site->getId() ?>">
                <?php xecho($site->getShortName()); ?>
            </a>
                    </td>

                    <td>
                        <?php xecho($site->getOfficialName()); ?>
                    </td>
                </tr>
                <?php
                    //if($num == 1) { $num = 2; } else { $num = 1; }
                    } // End of the foreach loop iterating over sites
                }
                ?>
        </tbody>
            </table>
        </div>
    <?php } // end of "if sites is > 0"?>

    <!--  Service results -->
    <?php if(sizeof($params['serviceResults']) > 0) { ?>
        <div class="listContainer" style="width: 97%;">
            <div style="padding: 0.5em;">
                <img style="vertical-align: middle; clear: both; height: 35px; width: 35px;" src="<?php echo \GocContextPath::getPath()?>img/service.png" />
                <h3 style="vertical-align: middle; clear: both; display: inline; margin-left: 0.3em; font-size: 1.3em; padding-bottom: 0em;">
                    Services
                </h3>
            </div>
            <table id="servicesTable" class="table table-striped table-condensed tablesorter">
        <thead>
                <tr>
                    <th>Hostname</th>
                    <th>Service Type</th>
                    <th>Description</th>
                </tr>
        </thead>
        <tbody>
                <?php
                $num = 2;
                foreach($params['serviceResults'] as $ser) {
                ?>
                <tr>
                    <td style="width: 30%">
            <a href="index.php?Page_Type=Service&amp;id=<?php echo $ser->getId() ?>">
                <?php xecho($ser->getHostName()); ?>
            </a>
                    </td>

                    <td>
                        <?php xecho($ser->getServiceType()->getName()); ?>
                    </td>

                    <td>
                        <?php xecho($ser->getDescription()); ?>
                    </td>
                </tr>
                <?php
                    //if($num == 1) { $num = 2; } else { $num = 1; }
                    } // End of the foreach loop iterating over services
                ?>
        </tbody>
            </table>
        </div>
    <?php } // end of "if services is > 0"?>

    <!--  User Results -->
    <?php if(sizeof($params['userResults']) > 0) { ?>
        <div class="listContainer" style="width: 97%;">
            <div style="padding: 0.5em;">
                <img style="vertical-align: middle; clear: both; height: 35px; width: 35px;" src="<?php echo \GocContextPath::getPath()?>img/user.png" />
                <h3 style="vertical-align: middle; clear: both; display: inline; margin-left: 0.3em; font-size: 1.3em; padding-bottom: 0em;">
                    Users
                </h3>
            </div>
            <?php if($params['authenticated']) { ?>
                <table id="usersTable" class="table table-striped table-condensed tablesorter">
            <thead>
                    <tr>
                        <th>Name</th>
                        <th>E-Mail</th>
                    </tr>
            </thead>
            <tbody>
                    <?php
                    $num = 2;
                    foreach($params['userResults'] as $user) {
                    ?>
                    <tr >
                        <td style="width: 25%">
                <a href="index.php?Page_Type=User&amp;id=<?php echo $user->getId() ?>">
                <?php xecho($user->getFullName()); ?>
                </a>
                        </td>

                        <td>
                            <?php if($params['authenticated']){ xecho($user->getEmail()); } else {echo 'PROTECTED - Authentication required'; } ?>
                        </td>
                    </tr>
                    <?php
                        if($num == 1) { $num = 2; } else { $num = 1; }
                        } // End of the foreach loop iterating over users
                    ?>
            </tbody>
                </table>
            <?php } else {echo 'PROTECTED'; } ?>
        </div>
    <?php } // end of "if users is > 0"?>

    <?php if(sizeof($params['siteResults']) == 0 && sizeof($params['serviceResults']) == 0 && sizeof($params['userResults']) == 0 && sizeof($params['ngiResults'] == 0))  { ?>
        <div class="listContainer" style="padding: 0.5em; width: 97%;">
            <span style="float: left;">No results found</span>
        </div>
    <?php }?>
</div>

<script>
   $(document).ready(function()
    {
    $("#ngisTable").tablesorter();
    $("#sitesTable").tablesorter();
    $("#servicesTable").tablesorter();
    $("#usersTable").tablesorter();
    });
</script>

