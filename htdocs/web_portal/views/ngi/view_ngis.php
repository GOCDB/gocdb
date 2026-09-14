<?php
    $showPD = $params['authenticated'];
?>
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
            <a style="float: left; padding-top: 0.3em;" href="<?php echo $params['ngiDocLink'] ?>">What is an NGI?</a>
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

        <div class="topMargin leftFloat siteFilter">
        <span class=""><a href="index.php?Page_Type=Scopes">Scopes:</a> </span>
        <select id="scopeSelect" multiple="multiple" name="mscope[]" style="width: 200px">
            <?php foreach ($params['scopes'] as $scope) { ?>
            <option value="<?php xecho($scope->getName()); ?>"
                <?php if(in_array($scope->getName(), $params['selectedScopes'])){ echo ' selected';}?> >
                <?php xecho($scope->getName()); ?>
            </option>
            <?php } ?>
        </select>
        </div>

        <div class="topMargin leftFloat siteFilter">
        <input type="submit" value="Filter NGIs">
        </div>
        </form>
    </div>

    <!--  NGIs -->
    <div class="listContainer">
        <span class="header listHeader">
            <?php echo sizeof($params['ngis']) ?> NGI<?php if(sizeof($params['ngis']) != 1) echo "s"?>
        </span>
        <img src="<?php echo \GocContextPath::getPath()?>img/grid.png" class="decoration" />
        <table id="selectedNgisTable" class="table table-striped table-condensed tablesorter">
            <thead>
                <tr>
                    <th></th>
                    <th>Name</th>
                    <th>E-Mail</th>
                    <th>Scope(s)</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $num = 2;
                foreach($params['ngis'] as $ngi) {
                ?>
                    <tr>
                        <td style="width: 10%">
                            <img class="flag" style="vertical-align: middle" src="<?php echo \GocContextPath::getPath()?>img/ngi/<?php echo $ngi->getName() ?>.jpg">
                        </td>
                        <td>
                            <a href="index.php?Page_Type=NGI&amp;id=<?php echo $ngi->getId() ?>">
                                <?php xecho($ngi->getName()); ?>
                            </a>
                        </td>
                        <td class="site_table">
                            <?php
                                if ($showPD) {
                                    xecho($ngi->getEmail());
                                } else
                                    echo(getInfoMessage());
                            ?>
                        </td>
                        <td class="site_table">
                            <textarea readonly="true" style="height: 25px;"><?php xecho($ngi->getScopeNamesAsString()); ?></textarea>
                        </td>
                    </tr>
                <?php
                } // End of the foreach loop iterating over ngis
                ?>
            </tbody>
        </table>
    </div>
    <br>&nbsp;
    <br>&nbsp;
    <br>&nbsp;
    <br>&nbsp;
    <br>&nbsp;
    <br>&nbsp;
    <br>&nbsp;
    <br>&nbsp;
</div>

<script type="text/javascript" src="<?php GocContextPath::getPath()?>javascript/jquery.multiple.select.js"></script>

<script>
    $(document).ready(function()
    {

    // sort on first and second table cols only
    $("#selectedNgisTable").tablesorter({
        // pass the headers argument and assing a object
        headers: {
        // assign the third column (we start counting zero)
        0: {
            sorter: false
        }
        }
    });

    $('#scopeSelect').multipleSelect({
        filter: true,
            placeholder: "NGI Scopes"
        });
    });
</script>
