<div class="rightPageContainer">
   <div style="float: left;">
        <img src="<?php echo \GocContextPath::getPath()?>img/project.png" class="pageLogo" />
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
        <img src="<?php echo \GocContextPath::getPath()?>img/grid.png" class="decoration" />

        <table id="selectedProjTable" class="table table-striped table-condensed tablesorter">
        <thead>
            <tr>
                <th>Name</th>
                <th>Description</th>
            </tr>
        </thead>
            <?php
            //$num = 2;
            foreach($params['Projects'] as $project) {
            ?>
            <tr>
                <td>
            <a href="index.php?Page_Type=Project&id=<?php echo $project->getId()?>">
            <?php xecho($project->getName()); ?>
            </a>
                </td>

                <td>
                    <?php xecho($project->getDescription()); ?>
                </td>

            </tr>
            <?php
                //if($num == 1) { $num = 2; } else { $num = 1; }
                } // End of the foreach loop iterating over Projs
            ?>
        </table>
    </div>
</div>
