<div class="rightPageContainer">
    <script language="JavaScript" src="javascript/service_group/add_ses_to_vsite.js"></script>
    <script language="JavaScript" src="javascript/ajax.js"></script>
    <div class="rightPageHolder">
        <div class="leftFloat">
            <img src="img/add.png" class="pageLogo" />
        </div>
        <div class="leftFloat">
            <h1 class="vSite">
                Add Services to Service Group [<?php echo $params['sg']->getName() ?>]
            </h1>
            <br />
            <span class="vSiteDescription">
                Add the services to associate with this service group to the bottom list then click "Finish"
            </span>
            <span class="vSitesMoreInfo">
                For more information see
                <a href="https://wiki.egi.eu/wiki/GOCDB/Input_System_User_Documentation#Service_Groups">
                    Service Group Help
                </a>
            </span>
        </div>
        
        <?php require_once __DIR__.'/add_ses_body.php';?>
    
        <?php if($params['siteLessServices']) { ?>
        <div class="leftFloat topMargin2 leftMargin">
            <a href="index.php?Page_Type=Add_New_SE_To_Service_Group&id=<?php echo $params['sg']->getId() ?>">
                <img class="middleAlign" src="img/add.png" height="25px" width="25px" style="padding-right: 0.5em;" />
                <span class="middleAlign">
                    Add a new service to this service group
                </span>
            </a>
        </div>
        <?php } ?>
    
    <form id="sesToAdd" action="index.php?Page_Type=Add_Service_Group_SEs" method="POST" class="empty" style="margin-top: 1em; float: left;">
        <input class="input_button leftFloat topMargin2" type="submit" value="Add SEs to Service Group"  />
        <input type="hidden" name="id" value="<?php echo $params['sg']->getId(); ?>" />
    </form>
    </div>
</div>