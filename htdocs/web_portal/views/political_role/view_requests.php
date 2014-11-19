<script type="text/javascript" src="javascript/confirm.js"></script>
<!-- onclick="return confirmSubmit()" -->
<div class="rightPageContainer">
    <div style="float: left; text-align: center;">
        <img src="img/user.png" class="pageLogo" />
    </div>
    <div style="float: left;">
        <h1 style="float: left; margin-left: 0em; padding-bottom: 0.3em;">
            Role Requests and Approvals
        </h1>
		<span style="clear: both; float: left; padding-bottom: 0.4em;">
		  <a href="https://wiki.egi.eu/wiki/GOCDB/Input_System_User_Documentation#Understanding_and_manipulating_roles">
		      How to Manage Roles in GOCDB
		  </a>
		 </span>
    </div>
    <!-- do not show Apply for a role when portal is read only -->
    <?php if(!$params['portalIsReadOnly']):?>
        <!-- Apply for a new role -->
        <div style="float: left; width: 100%; margin-top: 2em;">
            <h3 style="float: left;">
                  Apply for a New Role
            </h3>
            <span style="float: left; clear: both;">On which entity do you want you want to request a role?</span>
            <form action="index.php?Page_Type=Request_Role" method="post" style="clear: both; padding-top: 1em; padding-bottom: 1em;">
                <select name="id">
                    <option>-- Please Select --</option>
                    <?php foreach($params['entities'] as $entity) {  
                        if(!empty($entity['Object_ID'])) {
                            echo "<option value=\"{$entity['Object_ID']}\">{$entity['Name']}</option>";
                        } else {
                            echo "<option class=\"sectionTitle\">{$entity['Name']}</option>";
                        }
                    }?>
                </select>
                <input type="submit">
            </form>

            <span>Newly requested roles will be queued for approval</span>
        </div>
    <?php endif; ?>
        
    <!-- Roles you've requested awaiting approval-->
    <div style="float: left; width: 100%; margin-top: 2em;">
        <h3>
              Your requests awaiting approval 
        </h3>

        <div class="listContainer" style="margin-top: 0em">
            <span class="header listHeader">
                Your Pending Requests
            </span>
            <img src="img/user.png" class="decoration" />
            <table class="vSiteResults" id="selectedSETable">
                <tr class="site_table_row_1">
                    <th class="site_table">Role Request</th>
                    <th class="site_table">Target Entity</th>
                    <!-- Do not show delte request when portal is read only -->
                    <?php if(!$params['portalIsReadOnly']):?>
                        <th class="site_table">Delete My Request</th>
                    <?php endif; ?>
                </tr>
                <?php           
                $num = 2;
                if(sizeof($params['myRequests'] > 0)) {
                foreach($params['myRequests'] as $request) {
                ?> 
                <tr class="site_table_row_<?php echo $num ?>">
                    <td class="site_table" style="width: 50%">
                       <?php echo $request->getRoleType()->getName()/*.' ['.$request->getId().']'*/?> 
                    </td> 
                    <td class="site_table">
                       <?php 
                         $entityId = $request->getOwnedEntity()->getId();
                         $entityName = $request->getOwnedEntity()->getName();  
                         if($request->getOwnedEntity() instanceof \ServiceGroup){
                             $entityViewLinkName = 'Service_Group';
                         } elseif($request->getOwnedEntity() instanceof \Site){
                             $entityViewLinkName = 'Site'; 
                         } elseif($request->getOwnedEntity() instanceof \NGI){
                             $entityViewLinkName = 'NGI'; 
                         } elseif($request->getOwnedEntity() instanceof \Project){
                             $entityViewLinkName = 'Project'; 
                         }
                         echo  " <a href='index.php?Page_Type=$entityViewLinkName&id=$entityId'>$entityName [$entityViewLinkName]</a>"; 
                  
                       ?> 
                    </td>
                    <!-- Do not show delte request when portal is read only -->
                    <?php if(!$params['portalIsReadOnly']):?>
                        <td class="site_table">
                            <a href="index.php?Page_Type=Revoke_Role&id=<?php echo $request->getId()?>" 
                               onclick="return confirmSubmit()">
                                Delete
                            </a> 
                        </td>
                    <?php endif; ?>
                </tr> 
                <?php  
                    if($num == 1) { $num = 2; } else { $num = 1; }
                    } // End of the foreach loop iterating over roles
                }
                ?>
            </table>
            
    </div>
    
    <!-- Roles you can approve -->
    <div style="float: left; width: 100%; margin-top: 2em;">
        <h3>
              Roles requests that you can approve 
        </h3>
        
        <!--  Sites -->
        <div class="listContainer" style="margin-top: 0em">
            <span class="header listHeader">
                Requests To Approve
            </span>
            <img src="img/user.png" class="decoration" />
            <table class="vSiteResults" id="selectedSETable">
                <tr class="site_table_row_1">
                    <th class="site_table">Requestor</th>
                    <th class="site_table">Role Request Type</th>
                    <th class="site_table">Target Entity</th>
                    <!-- Do not show approvals when portal is read only -->
                    <?php if(!$params['portalIsReadOnly']):?>
                        <th class="site_table">Approval</th>
                    <?php endif; ?>
                </tr>
                <?php           
                $num = 2;
                if(sizeof($params['allRequests'] > 0)) {
                foreach($params['allRequests'] as $request) {
                ?>
                <tr class="site_table_row_<?php echo $num ?>">
                    <td class="site_table">
                       <?php 
                         $requestingUser = $request->getUser();  
                         $requestingUserId = $requestingUser->getId(); 
                         $surname = $requestingUser->getSurname(); 
                         $forename = $requestingUser->getForename(); 
                         echo "<a href='index.php?Page_Type=User&id=$requestingUserId'>$forename $surname</a>";
                        ?> 
                       
                    </td> 
                    <td class="site_table" style="width: 40%">
                        <?php 
                         echo $request->getRoleType()->getName()/*.' ['.$request->getId().']'*/; 
                        ?>
                    </td>
                    <td class="site_table" >
                        <?php 
                         $entityId = $request->getOwnedEntity()->getId();
                         $entityName = $request->getOwnedEntity()->getName();  
                         if($request->getOwnedEntity() instanceof \ServiceGroup){
                             $entityClassName = 'Service_Group';
                         } elseif($request->getOwnedEntity() instanceof \Site){
                             $entityClassName = 'Site'; 
                         } elseif($request->getOwnedEntity() instanceof \NGI){
                             $entityClassName = 'NGI'; 
                         } elseif($request->getOwnedEntity() instanceof \Project){
                             $entityClassName = 'Project'; 
                         }
                         echo  " <a href='index.php?Page_Type=$entityClassName&id=$entityId'>$entityName [$entityClassName]</a>"; 
                        ?>
                    </td> 
                    <td class="site_table">
                       <!-- Do not show approvals when portal is read only -->
                        <?php if(!$params['portalIsReadOnly']):?>
                            <span class="action">
                                <a href="index.php?Page_Type=Accept_Role_Request&amp;Request_ID=<?php echo $request->getId() ?>" 
                                   onclick="return confirmSubmit()">
                                   Accept
                                </a>
                            </span>
                            <span class="site_table">
                                <a href="index.php?Page_Type=Deny_Role_Request&amp;Request_ID=<?php echo $request->getId() ?>" 
                                   onclick="return confirmSubmit()">
                                   Deny
                                </a>
                            </span>
                       <?php endif; ?>
                   </td>
                    
                </tr>
                <?php  
                    if($num == 1) { $num = 2; } else { $num = 1; }
                    } // End of the foreach loop iterating over roles
                }
                ?>
            </table>
        </div>
    </div>

</div>
