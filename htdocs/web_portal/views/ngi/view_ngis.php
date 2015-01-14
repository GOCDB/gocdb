<div class="rightPageContainer">
    <div style="float: left;">
        <img src="<?php echo \GocContextPath::getPath()?>img/ngi.png" class="pageLogo" />
    </div>
    <div style="float: left;">
        <h1 style="float: left; margin-left: 0em;">
                NGIs
        </h1>
        <span style="clear: both; float: left; padding-bottom: 0.4em;">
            National Grid Initiatives
        </span>
        <span style="clear: both; float: left; padding-bottom: 0.4em;">
            <a style="float: left; padding-top: 0.3em;" href="http://www.egi.eu/about/glossary/glossary_N.html">What is an NGI?</a>
        </span>
    </div>
    
        <!-- Filter -->
    <div class="siteContainer">
        <form action="index.php?Page_Type=NGIs" method="GET" class="inline">
        <input type="hidden" name="Page_Type" value="NGIs" />
        
        <span class="header leftFloat">
            Filter <a href="index.php?Page_Type=NGIs">&nbsp;&nbsp;(clear)</a>
        </span>
        <br />      
        <div class="topMargin leftFloat clearLeft">
            <span class="">Scope: </span>
                <select name="scope" onchange="form.submit()">
                    <option value="">(all)</option>
                    <?php foreach ($params['scopes'] as $scope){ ?>
                        <option value="<?php echo $scope->getName(); ?>"<?php if($params['selectedScope'] ==  $scope->getName()) echo " selected" ?>><?php echo $scope->getName(); ?></option>
                    <?php } ?>          
                </select>
        </div>       
        </form>
    </div>
    
    <!--  NGIs -->
    <div class="listContainer">
        <span class="header listHeader">
            <?php echo sizeof($params['ngis']) ?> NGI<?php if(sizeof($params['ngis']) != 1) echo "s"?>
        </span>
        <img src="<?php echo \GocContextPath::getPath()?>img/grid.png" class="decoration" />
        <table class="vSiteResults" id="selectedSETable">
            <tr class="site_table_row_1">
                <th class="site_table">Name</th>
                <th class="site_table">E-Mail</th>
                <th class="site_table"><a href="index.php?Page_Type=Scope_Help">Scope(s)</a></th>
            </tr>
            <?php           
            $num = 2;
            if(sizeof($params['ngis'] > 0)) {
                foreach($params['ngis'] as $ngi) {
                ?>
                <tr class="site_table_row_<?php echo $num ?>">
                    <td class="site_table" style="width: 30%">
                        <div style="background-color: inherit;">
                            <span style="vertical-align: middle;">
                                <a href="index.php?Page_Type=NGI&id=<?php echo $ngi->getId() ?>">
                                    <img class="flag" style="vertical-align: middle" src="<?php echo \GocContextPath::getPath()?>img/ngi/<?php echo $ngi->getName() ?>.jpg">                            
                                    <span>&nbsp;&nbsp;</span><?php echo $ngi->getName(); ?>
                                </a>
                            </span>
                        </div>
                    </td>
                    
                    <td class="site_table">
                        <?php echo $ngi->getEmail(); ?>
                    </td> 
                    
                    <td class="site_table">
                        <?php echo $ngi->getScopeNamesAsString(); ?>
                    </td>
                </tr>
                <?php  
                    if($num == 1) { $num = 2; } else { $num = 1; }
                    } // End of the foreach loop iterating over sites
            }
            ?>
        </table>
    </div>
</div>