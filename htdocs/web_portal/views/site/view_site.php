<?php
$site = $params['site'];
$downtimes = $params['Downtimes'];
$parentNgiName = $site->getNgi()->getName();
$portalIsReadOnly = $params['portalIsReadOnly'];
$siteProperties = $site->getSiteProperties();
?>
<div class="rightPageContainer">
    <div style="float: left; text-align: center;">
        <img src="img/site.png" class="pageLogo" />
    </div>
    <div style="float: left; width: 50em;">
        <h1 style="float: left; margin-left: 0em;">Site: <?php echo $site->getShortName() ?></h1>
        <span style="clear: both; float: left; padding-bottom: 0.4em;"><?php echo $site->getOfficialName() ?><br /><?php echo $site->getDescription() ?></span>
    </div>

    <!--  Edit Site link -->
    <!--  only show this link if we're in read / write mode -->
    <?php if(!$portalIsReadOnly): ?>
    <div style="float: right;">
        <?php if($params['UserIsAdmin']):?>
            <div style="float: right; margin-left: 2em; text-align:center;">
                <script type="text/javascript" src="javascript/confirm.js"></script>
                <a onclick="return confirmSubmit()"
                    href="index.php?Page_Type=Delete_Site&id=<?php echo $site->getId();?>">
                    <img src="img/cross.png" height="25px"/>
                    <br/>
                    <span>Admin<br>Delete</span>
                </a>
            </div>
        <?php endif; ?>
        <div style="float: right; margin-left: 2em;">
            <a href="index.php?Page_Type=Edit_Site&id=<?php echo $site->getId() ?>">
                <img src="img/pencil.png" height="25px" style="float: right;" />
                <br />
                <br />
                <span>Edit</span>
            </a>
        </div>
  
    </div>
    <?php endif; ?>

    <!-- Contacts and Project Data -->
    <div style="float: left; width: 100%; margin-top: 2em;">
        <!--  Contacts -->
        <div class="tableContainer" style="width: 55%; float: left;" >
            <span class="header" style="vertical-align:middle; float: left; padding-top: 0.9em; padding-left: 1em;">Contact</span>
            <img src="img/contact_card.png" height="25px" style="float: right; padding-right: 1em; padding-top: 0.5em; padding-bottom: 0.5em;" />
            <table style="clear: both; width: 100%; table-layout: fixed;">
                <tr class="site_table_row_1">
                    <td class="site_table" style="width: 30%">E-Mail</td><td class="site_table">
						<a href="mailto:<?php echo $site->getEmail() ?>">
							<?php echo $site->getEmail() ?>
						</a>
					</td>
                </tr>
                <tr class="site_table_row_2">
                    <td class="site_table">Telephone</td><td class="site_table"><?php echo $site->getTelephone() ?></td>
                </tr>
                <tr class="site_table_row_1">
                    <td class="site_table">Emergency Telephone</td><td class="site_table"><?php echo $site->getEmergencyTel() ?></td>
                </tr>
                <tr class="site_table_row_2">
                    <td class="site_table">CSIRT Telephone</td><td class="site_table"><?php echo $site->getCsirtTel() ?></td>
                </tr>
                <tr class="site_table_row_1">
                    <td class="site_table">CSIRT E-Mail</td>
					<td class="site_table">
						<a href="mailto:<?php echo $site->getCsirtEmail()?>">
							<?php echo $site->getCsirtEmail() ?>
						</a>
					</td>
                </tr>
                <tr class="site_table_row_2">
                    <td class="site_table">Emergency E-Mail</td>
					<td class="site_table">
						<a href="mailto:<?php echo $site->getEmergencyEmail() ?>">
							<?php echo $site->getEmergencyEmail() ?>
						</a>
					</td>
                </tr>
                <tr class="site_table_row_1">
                    <td class="site_table">Helpdesk E-Mail</td>
					<td class="site_table">
						<a href="mailto:<?php echo $site->getHelpdeskEmail() ;?>">
							<?php echo $site->getHelpdeskEmail() ?>
						</a>
					</td>
                </tr>
            </table>
        </div>

        <!--  Project Data -->
        <div class="tableContainer" style="width: 42%; float: right;">
            <span class="header" style="vertical-align:middle; float: left; padding-top: 0.9em; padding-left: 1em;">Project Data</span>
            <img src="img/project.png" height="25px" style="float: right; padding-right: 0.5em; padding-top: 0.5em; padding-bottom: 0.5em;" />
            <table style="clear: both; width: 100%;">
                <tr class="site_table_row_1">
                    <td class="site_table">NGI/ROC</td><td class="site_table">
						<a href="index.php?Page_Type=NGI&id=<?php echo $site->getNgi()->getId() ?>">
						<?php echo $site->getNgi()->getName() ?>
						</a>
					</td>
                </tr>
                <tr class="site_table_row_2">
                    <td class="site_table">Infrastructure</td><td class="site_table"><?php echo $site->getInfrastructure()->getName() ?></td>
                </tr>
                <tr class="site_table_row_1">
                    <td class="site_table">Certification Status</td>
                    <td class="site_table">
                        <?php echo $site->getCertificationStatus()->getName() ?>
                        &nbsp;
                        <!--  only show this link if we're in read / write mode -->
                        <?php if(!$portalIsReadOnly): ?>
                            <a href="index.php?Page_Type=Edit_Certification_Status&id=<?php echo $site->getId() ?>">Change</a>
                        <?php endif; ?>
                        </td>
                </tr>

                <tr class="site_table_row_2">
                    <td class="site_table"><a href="index.php?Page_Type=Scope_Help" style="word-wrap: normal">Scope(s)</a></td>
                    <td class="site_table">
                        <?php $count = 0;
                              $numScopes = sizeof($params['Scopes']);
                        foreach ($params['Scopes'] as $scopeName => $sharedWithParent){ ?>
                            <?php if($sharedWithParent): ?>
                                <span>
                                    <?php echo $scopeName; if(++$count!=$numScopes){echo", ";}?>
                                </span>
                            <?php else: ?>
                                <span title="Info - The parent NGI <?php echo $parentNgiName ?> does not share this scope" style="color:mediumvioletred;">
                                     <?php echo $scopeName . 
                                "</span>".//Echoed span required to prevent space before comma
                                "<span>";
                                    if(++$count!=$numScopes){echo", ";}?>
                                </span>
                            <?php endif; ?>
                        <?php } ?>
                    </td>
                </tr>

            </table>
        </div>
    </div>

    <!-- Networking and Location -->
    <div style="float: left; width: 100%; margin-top: 3em;">
        <!--  Networking -->
        <div class="tableContainer" style="width: 55%; float: left;">
            <span class="header" style="vertical-align:middle; float: left; padding-top: 0.9em; padding-left: 1em;">Networking</span>
            <img src="img/network.png" height="25px" style="float: right; padding-right: 1em; padding-top: 0.5em; padding-bottom: 0.5em;" />
            <table style="clear: both; width: 100%;">
                <tr class="site_table_row_1">
                    <td class="site_table">Home URL</td>
					<td class="site_table">
						<a href="<?php echo $site->getHomeUrl() ?>">
							<?php echo $site->getHomeUrl() ?>
						</a>
					</td>
                </tr>
                <tr class="site_table_row_2">
                    <td class="site_table">GIIS URL</td><td class="site_table"><?php echo $site->getGiisUrl() ?></td>
                </tr>
                <tr class="site_table_row_1">
                    <td class="site_table">IP Range</td><td class="site_table"><?php echo $site->getIpRange() ?></td>
                </tr>
                <tr class="site_table_row_2">
                    <td class="site_table" style="width:20%">IP v6 Range</td><td class="site_table"><?php echo $site->getIpV6Range() ?></td>
                </tr>                 
                <tr class="site_table_row_1">
                    <td class="site_table">Domain</td><td class="site_table"><?php echo $site->getDomain() ?></td>
                </tr>
            </table>
        </div>

        <!-- Location Data -->
        <div class="tableContainer" style="width: 42%; float: right;">
            <span class="header" style="vertical-align:middle; float: left; padding-top: 0.9em; padding-left: 1em;">Location</span>
            <img src="img/pin.png" height="25px" style="float: right; padding-right: 0.5em; padding-top: 0.5em; padding-bottom: 0.5em;" />
            <table style="clear: both; width: 100%;">
                <tr class="site_table_row_1">
                    <td class="site_table">Country</td><td class="site_table"><?php echo $site->getCountry()->getName() ?></td>
                </tr>
                <tr class="site_table_row_2">
                    <td class="site_table">Latitude</td><td class="site_table"><?php echo $site->getLatitude() ?></td>
                </tr>
                <tr class="site_table_row_1">
                    <td class="site_table">Longitude</td><td class="site_table"><?php echo $site->getLongitude() ?></td>
                </tr>
                <tr class="site_table_row_2">
                    <td class="site_table">Time Zone</td><td class="site_table"><?php echo $site->getTimezone()->getName() ?></td>
                </tr>
                <tr class="site_table_row_1">
                    <td class="site_table">Location</td><td class="site_table"><?php echo $site->getLocation() ?></td>
                </tr>
            </table>
        </div>
    </div>

    <!--  Site Properties -->
    <div class="tableContainer" style="width: 99.5%; float: left; margin-top: 3em; margin-right: 10px;">
        <span class="header" style="vertical-align:middle; float: left; padding-top: 0.9em; padding-left: 1em;">Site Extension Properties</span>        
        <img src="img/keypair.png" height="25px" style="float: right; padding-right: 1em; padding-top: 0.5em; padding-bottom: 0.5em;" />
        <table style="clear: both; width: 100%;">
            <tr class="site_table_row_1">
                <th class="site_table">Name</th>
                <th class="site_table" >Value</th>  
                <?php if(!$portalIsReadOnly): ?>
                <th class="site_table" >Edit</th>  
                <th class="site_table" >Remove</th>  
                <?php endif; ?>              
            </tr>
            <?php
            $num = 2;
            foreach($siteProperties as $sp) {
	            ?>

	            <tr class="site_table_row_<?php echo $num ?>">
	                <td style="width: 35%;"class="site_table"><?php echo $sp->getKeyName(); ?></td>
	                <td style="width: 35%;"class="site_table"><?php echo $sp->getKeyValue(); ?></td>
	                <?php if(!$portalIsReadOnly): ?>	                
	               	<td style="width: 10%;"align = "center"class="site_table"><a href="index.php?Page_Type=Edit_Site_Property&propertyid=<?php echo $sp->getId();?>&id=<?php echo $site->getId();?>"><img height="25px" src="img/pencil.png"/></a></td>
	                <td style="width: 10%;"align = "center"class="site_table"><a href="index.php?Page_Type=Delete_Site_Property&propertyid=<?php echo $sp->getId();?>&id=<?php echo $site->getId();?>"><img height="25px" src="img/cross.png"/></a></td>
	                <?php endif; ?>
	            </tr>
	            <?php
	            if($num == 1) { $num = 2; } else { $num = 1; }
            }
            ?>
        </table>
        <!--  only show this link if we're in read / write mode -->
		<?php if(!$portalIsReadOnly): ?>
            <!-- Add new data Link -->
            <a href="index.php?Page_Type=Add_Site_Property&site=<?php echo $site->getId();?>">
                <img src="img/add.png" height="50px" style="float: left; padding-top: 0.9em; padding-left: 1.2em; padding-bottom: 0.9em;"/>
                <span class="header" style="vertical-align:middle; float: left; padding-top: 1.1em; padding-left: 1em; padding-bottom: 0.9em;">
                        Add Properties
                </span>
            </a>
		<?php endif; ?>
    </div>
    
    <!--  Services -->
    <div class="listContainer" style="width: 99.5%; float: left; margin-top: 3em; margin-right: 10px;">
        <span class="header" style="vertical-align:middle; float: left; padding-top: 0.9em; padding-left: 1em;">Services</span>
        <img src="img/service.png" height="25px" style="float: right; padding-right: 1em; padding-top: 0.5em; padding-bottom: 0.5em;" />
        <table style="clear: both; width: 100%;">
            <tr class="site_table_row_1">
                <th class="site_table">Hostname (service type)</th>
                <th class="site_table">URL</th>
                <th class="site_table">Production</th>
                <th class="site_table">Monitored</th>
                <th class="site_table"><a href="index.php?Page_Type=Scope_Help">Scope(s)</a></th>
            </tr>

            <?php
	            $num = 2;

				foreach($params['ServicesAndScopes'] as $serviceAndScopes) {
                    $se=$serviceAndScopes['Service'];
                    $scopes = $serviceAndScopes['Scopes'];
			?>

            <tr class="site_table_row_<?php echo $num ?>">
                <td class="site_table">
                    <div style="background-color: inherit;">
                        <span style="vertical-align: middle;">
                            <a href="index.php?Page_Type=Service&id=<?php echo $se->getId() ?>">
                                <?php echo $se->getHostname() . " (" . $se->getServiceType()->getName() . ")";?>
                            </a>
                        </span>
                    </div>
                </td>
                <td class="site_table"><?php echo (string) $se->getUrl() ?></td>

                <td class="site_table">
				<?php
				switch($se->getProduction()) {
					case true:
						?>
						<img src="img/tick.png" height="22px" style="vertical-align: middle;" />
						<?php
						break;
					case false:
						?>
						<img src="img/cross.png" height="22px" style="vertical-align: middle;" />
						<?php
						break;
				}
				?>
				</td>

				<td class="site_table">
				<?php
				switch($se->getMonitored()) {
					case true:
						?>
						<img src="img/tick.png" height="22px" style="vertical-align: middle;" />
						<?php
						break;
					case false:
						?>
						<img src="img/cross.png" height="22px" style="vertical-align: middle;" />
						<?php
						break;
				}
				?>
				</td>
                <td class="site_table">
                    <?php $count = 0;
                              $numScopes = sizeof($scopes);
                        foreach ($scopes as $scopeName => $sharedWithParent){ ?>
                            <?php if($sharedWithParent): ?>
                                <span>
                                    <?php echo $scopeName; if(++$count!=$numScopes){echo", ";}?>
                                </span>
                            <?php else: ?>
                                <span title="The parent site <?php echo $site->getName() ?> does not share this scope" style="color:mediumvioletred;">
                                     <?php echo $scopeName . 
                                "</span>".//Echoed span required to prevent space before comma
                                "<span>";
                                    if(++$count!=$numScopes){echo", ";}?>
                                </span>
                            <?php endif; ?>
                        <?php } ?>
                </td>
            </tr>
            <?php
				if($num == 1) { $num = 2; } else { $num = 1; }
            } // End of the foreach loop iterating over SEs
            ?>
        </table>
		<!--  only show this link if we're in read / write mode -->
		<?php if(!$portalIsReadOnly) :?>
            <!-- Add new Service Link -->
            <a href="index.php?Page_Type=Add_Service&siteId=<?php echo $site->getId();?>">
                <img src="img/add.png" height="50px" style="float: left; padding-top: 0.9em; padding-left: 1.2em; padding-bottom: 0.9em;"/>
                <span class="header" style="vertical-align:middle; float: left; padding-top: 1.1em; padding-left: 1em; padding-bottom: 0.9em;">
                        Add Service 
                </span>
            </a>
		<?php endif; ?>
    </div>


    <!--  Users -->
    <div class="tableContainer" style="width: 99.5%; float: left; margin-top: 3em; margin-right: 10px;">
        <span class="header" style="vertical-align:middle; float: left; padding-top: 0.9em; padding-left: 1em;">Users</span>
        <img src="img/people.png" height="25px" style="float: right; padding-right: 1em; padding-top: 0.5em; padding-bottom: 0.5em;" />
        <table style="clear: both; width: 100%;">
            <tr class="site_table_row_1">
                <th class="site_table">Name</th>
                <th class="site_table">Role</th>
            </tr>
            <?php
            $num = 2;
            foreach($params['roles'] as $role) {
            ?>

            <tr class="site_table_row_<?php echo $num ?>">
                <td class="site_table">
                    <div style="background-color: inherit;">
                        <a style="vertical-align: middle;" href="index.php?Page_Type=User&id=<?php echo $role->getUser()->getId() ?>">
                            <?php echo $role->getUser()->getFullName()/*.' ['.$role->getUser()->getId().']'*/ ?>
                        </a>
                    </div>
                </td>
                <td class="site_table">
                    <?php echo $role->getRoleType()->getName() ?>
                </td>
            </tr>
            <?php
            if($num == 1) { $num = 2; } else { $num = 1; }
            }
            ?>
        </table>

		<!-- Request Role Link -->
		<!--  only show this link if we're in read / write mode -->
        <?php if(!$portalIsReadOnly): ?>
            <div style="padding: 1em; padding-left: 1.4em; overflow: hidden;">
                <a href="index.php?Page_Type=Request_Role&id=<?php echo $site->getId();?>">
                    <img src="img/add.png" height="50px" style="float: left; padding-top: 0.9em; padding-left: 1.2em; padding-bottom: 0.9em;"/>
                    <span class="header" style="vertical-align:middle; float: left; padding-top: 1.1em; padding-left: 1em; padding-bottom: 0.9em;">
                        Request Role
                    </span>
                </a>
            </div>
		<?php endif; ?>
    </div>

    <!--  Downtimes -->
    <div class="tableContainer" style="width: 99.5%; float: left; margin-top: 3em; margin-right: 10px;">
        <span class="header" style="vertical-align:middle; float: left; padding-top: 0.9em; padding-left: 1em;">Recent Downtimes Affecting <?php echo $site->getShortName()?>'s SEs </span>
        <a href="index.php?Page_Type=Site_Downtimes&id=<?php echo $site->getId(); ?>" style="vertical-align:middle; float: left; padding-top: 1.3em; padding-left: 1em; font-size: 0.8em;">(View all Downtimes)</a>
        <img src="img/down_arrow.png" height="25px" style="float: right; padding-right: 1em; padding-top: 0.5em; padding-bottom: 0.5em;" />
        <table style="clear: both; width: 100%;">
            <tr class="site_table_row_1">
                <th class="site_table">Description</th>
                <th class="site_table" style="width: 9em;">From</th>
                <th class="site_table" style="width: 9em;">To</th>
            </tr>
            <?php
            $num = 2;
            foreach($downtimes as $dt) {
	            ?>

	            <tr class="site_table_row_<?php echo $num ?>">
	                <td class="site_table">
	                	<a style="padding-right: 1em;" href="index.php?Page_Type=Downtime&id=<?php echo $dt->getId() ?>">
	                    	<?php echo $dt->getDescription() ?>
	                    </a>
	                </td>
	                <td class="site_table"><?php echo $dt->getStartDate()->format($dt::DATE_FORMAT) ?></td>
	                <td class="site_table"><?php echo $dt->getEndDate()->format($dt::DATE_FORMAT) ?></td>
	            </tr>
	            <?php
	            if($num == 1) { $num = 2; } else { $num = 1; }
            }
            ?>
        </table>
        <!--  only show this link if we're in read / write mode -->
		<?php if(!$portalIsReadOnly): ?>
            <!-- Add new Downtime Link -->
            <a href="index.php?Page_Type=Add_Downtime&site=<?php echo $site->getId();?>">
                <img src="img/add.png" height="50px" style="float: left; padding-top: 0.9em; padding-left: 1.2em; padding-bottom: 0.9em;"/>
                <span class="header" style="vertical-align:middle; float: left; padding-top: 1.1em; padding-left: 1em; padding-bottom: 0.9em;">
                        Add Downtime
                </span>
            </a>
		<?php endif; ?>
    </div>
</div>