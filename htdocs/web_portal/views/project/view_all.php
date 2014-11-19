<div class="rightPageContainer">
   <div style="float: left;">
        <img src="img/project.png" class="pageLogo" />
    </div>
    <div style="float: left;">
        <h1 style="float: left; margin-left: 0em;">
            Projects
        </h1>
        <span style="clear: both; float: left; padding-bottom: 0.4em;">
            All projects in GOCDB.
        </span>
        <span style="clear: both; float: left; padding-bottom: 0.4em;">
<!-- TODO: link            -->
            <a style="float: left; padding-top: 0.3em;" href="https://wiki.egi.eu/wiki/GOCDB/Input_System_User_Documentation#Projects">
                What is a project?
            </a>
        </span>
    </div>
    
    <!--  Projects -->
    <div class="listContainer">
        <span class="header listHeader">
            <?php echo sizeof($params['Projects']) ?> Project<?php if(sizeof($params['Projects']) != 1) echo "s"?>
        </span>
        <img src="img/grid.png" class="decoration" />
        <table class="vSiteResults" id="selectedSETable">
            <tr class="site_table_row_1">
                <th class="site_table">Name</th>
                <th class="site_table">Description</th>
            </tr>
            <?php           
            $num = 2;
            foreach($params['Projects'] as $project) {
            ?>
            <tr class="site_table_row_<?php echo $num ?>">
                <td class="site_table">
                    <div style="background-color: inherit;">
                        <span style="vertical-align: middle;">
                            <a href="index.php?Page_Type=Project&id=<?php echo $project->getId()?>">
                                <span>&raquo; </span><?php echo $project->getName(); ?>
                            </a>
                        </span>
                    </div>
                </td>
                    
                <td class="site_table">
                    <?php echo $project->getDescription(); ?>
                </td>
                
            </tr>
            <?php  
                if($num == 1) { $num = 2; } else { $num = 1; }
                } // End of the foreach loop iterating over SEs
            ?>
        </table>
    </div>
</div>
