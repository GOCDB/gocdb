<div class="rightPageContainer">
    <div style="float: left;">
        <img src="<?php echo \GocContextPath::getPath()?>img/search.png" class="pageLogo" />
    </div>
    <div style="float: left;">
        <h1 style="float: left; margin-left: 0em;">
            Results for &#8220;<?php echo $params['searchTerm']?>&#8221;
        </h1>
        <span style="clear: both; float: left; padding-bottom: 0.4em;">
            Searching sites, services and users
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
            <table class="vSiteResults" style="table-layout: fixed">
                <tr class="site_table_row_1">
                    <th class="site_table">Name</th>
                    <th class="site_table">Description</th>
                </tr>
                <?php           
                $num = 2;
                foreach($params['ngiResults'] as $ngi) {
                ?>
                <tr class="site_table_row_<?php echo $num ?>">
                    <td class="site_table" style="width: 25%">
                        <div style="background-color: inherit;">
                            <span style="vertical-align: middle;">
                                <a href="index.php?Page_Type=NGI&id=<?php echo $ngi->getId()?>">
                                    <img class="flag" src="<?php echo \GocContextPath::getPath()?>img/ngi/<?php echo $ngi->getName() ?>.jpg" style="vertical-align: middle">
                                    <span> </span><?php echo $ngi->getName(); ?>
                                </a>
                            </span>
                        </div>
                    </td>
                        
                    <td class="site_table">
                        <?php echo $ngi->getDescription(); ?>
                    </td>
                </tr>
                <?php  
                    if($num == 1) { $num = 2; } else { $num = 1; }
                    } // End of the foreach loop iterating over users
                ?>
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
            <table class="vSiteResults">
                <tr class="site_table_row_1">
                    <th class="site_table">Short Name</th>
                    <th class="site_table">Official Name</th>
                </tr>
                <?php           
                $num = 2;
                if(sizeof($params['siteResults'] > 0)) {
                foreach($params['siteResults'] as $site) {
                ?>
                <tr class="site_table_row_<?php echo $num ?>">
                    <td class="site_table" style="width: 30%">
                        <div style="background-color: inherit;">
                            <span style="vertical-align: middle;">
                                <a href="index.php?Page_Type=Site&id=<?php echo $site->getId() ?>">
                                    <span>&raquo; </span><?php echo $site->getShortName(); ?>
                                </a>
                            </span>
                        </div>
                    </td>
                        
                    <td class="site_table">
                        <?php echo $site->getOfficialName(); ?>
                    </td>
                </tr>
                <?php  
                    if($num == 1) { $num = 2; } else { $num = 1; }
                    } // End of the foreach loop iterating over sites
                }
                ?>
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
            <table class="vSiteResults">
                <tr class="site_table_row_1">
                    <th class="site_table">Hostname</th>
                    <th class="site_table">Service Type</th>
                    <th class="site_table">Description</th>
                </tr>
                <?php           
                $num = 2;
                foreach($params['serviceResults'] as $ser) {
                ?>
                <tr class="site_table_row_<?php echo $num ?>">
                    <td class="site_table" style="width: 30%">
                        <div style="background-color: inherit;">
                            <span style="vertical-align: middle;">
                                <a href="index.php?Page_Type=Service&id=<?php echo $ser->getId() ?>">
                                    <span>&raquo; </span><?php echo $ser->getHostName(); ?>
                                </a>
                            </span>
                        </div>
                    </td>
                        
                    <td class="site_table">
                        <?php echo $ser->getServiceType()->getName(); ?>
                    </td>
                    
                    <td class="site_table">
                        <?php echo $ser->getDescription(); ?>
                    </td>
                </tr>
                <?php  
                    if($num == 1) { $num = 2; } else { $num = 1; }
                    } // End of the foreach loop iterating over services 
                ?>
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
            <table class="vSiteResults" style="table-layout: fixed">
                <tr class="site_table_row_1">
                    <th class="site_table">Name</th>
                    <th class="site_table">E-Mail</th>
                </tr>
                <?php           
                $num = 2;
                foreach($params['userResults'] as $user) {
                ?>
                <tr class="site_table_row_<?php echo $num ?>">
                    <td class="site_table" style="width: 25%">
                        <div style="background-color: inherit;">
                            <span style="vertical-align: middle;">
                                <a href="index.php?Page_Type=User&id=<?php echo $user->getId() ?>">
                                    <span>&raquo; </span><?php echo $user->getFullName(); ?>
                                </a>
                            </span>
                        </div>
                    </td>
                        
                    <td class="site_table">
                        <?php echo $user->getEmail(); ?>
                    </td>
                </tr>
                <?php  
                    if($num == 1) { $num = 2; } else { $num = 1; }
                    } // End of the foreach loop iterating over users
                ?>
            </table>
        </div>
    <?php } // end of "if users is > 0"?>
    
    <?php if(sizeof($params['siteResults']) == 0 && sizeof($params['serviceResults']) == 0 && sizeof($params['userResults']) == 0 && sizeof($params['ngiResults'] == 0))  { ?>
        <div class="listContainer" style="padding: 0.5em; width: 97%;">
            <span style="float: left;">No results found</span>
        </div>
    <?php }?>
</div>

