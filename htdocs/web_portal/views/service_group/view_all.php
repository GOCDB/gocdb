<div class="rightPageContainer">
    <div style="float: left;">
        <img src="<?php echo \GocContextPath::getPath()?>img/virtualSite.png" class="pageLogo" />
    </div>
    <div style="float: left;">
        <h1 style="float: left; margin-left: 0em;">
                Service Groups
        </h1>
        <span style="clear: both; float: left; padding-bottom: 0.4em;">
            All Service Groups in GOCDB.
        </span>
        <span style="clear: both; float: left; padding-bottom: 0.4em;">
            <a style="float: left; padding-top: 0.3em;" href="https://wiki.egi.eu/wiki/GOCDB/Input_System_User_Documentation#Service_Groups">
                What is a service group?
            </a>
        </span>
        <!-- <span style="clear: both; float: left;">Intentionally Blank</span>  -->
    </div>

    <!-- Filter -->
    <div class="siteContainer">
        <form action="index.php?Page_Type=Service_Groups" method="GET" class="inline">
        <input type="hidden" name="Page_Type" value="Service_Groups" />

        <span class="header leftFloat">
            Filter <a href="index.php?Page_Type=Service_Groups">&nbsp;&nbsp;(clear)</a>
        </span>

        <div class="topMargin leftFloat siteFilter clearLeft">
            <span class="">
                <a href="index.php?Page_Type=Scope_Help">Scopes: </a>
            </span>
            <select id="scopeSelect" multiple="multiple" name="mscope[]" style="width: 200px">
                <?php foreach ($params['scopes'] as $scope) { ?>
                <option value="<?php xecho($scope->getName()); ?>"
                    <?php if(in_array($scope->getName(), $params['selectedScopes'])){ echo ' selected';}?> >
                    <?php xecho($scope->getName()); ?>
                </option>
                <?php } ?>
            </select>
            <span class="">Scope match: </span>
            <select id="scopeMatchSelect" name="scopeMatch">
                <option value="all"<?php if ($params['scopeMatch'] == "all") {
                    echo ' selected';
                } ?>>all (selected tags are AND'd)</option>
                <option value="any"<?php if ($params['scopeMatch'] == "any") {
                    echo ' selected';
                } ?>>any (selected tags are OR'd)</option>
            </select>
        </div>

        <div class="topMargin leftFloat siteFilter">
            <span class="middle">Extension Name: </span>
            <select name="extKeyNames">
                <option value="">(none)</option>
                <?php foreach ($params['extKeyName'] as $extensions) { ?>
                    <option value="<?php echo $extensions; ?>"
                <?php if ($params['selectedExtKeyName'] == $extensions){ echo " selected";} ?>>
                    <?php echo $extensions; ?>
                </option>
                <?php } ?>
            </select>
            <span class="middle">Extension Value: </span>
            <input class="middle" type="text" name="selectedExtKeyValue"
            <?php if (isset($params['selectedExtKeyValue'])){ echo "value=\"{$params['selectedExtKeyValue']}\""; } ?>/>
        </div>

        <div class="topMargin leftFloat siteFilter clearLeft">
            <input type="submit" value="Filter ServiceGroups">
        </div>
        </form>
    </div>

    <!--  Service Groups -->
    <div class="listContainer">
        <span class="header listHeader">
            <?php echo sizeof($params['sGroups']) ?> Service Group<?php if(sizeof($params['sGroups']) != 1) echo "s"?>
        </span>
        <img src="<?php echo \GocContextPath::getPath()?>img/grid.png" class="decoration" />
        <table id="selectedSgTable" class="table table-striped table-condensed tablesorter">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Scope(s)</th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach($params['sGroups'] as $sGroup) {
                ?>
                    <tr>
                        <td>
                            <a href="index.php?Page_Type=Service_Group&amp;id=<?php echo $sGroup->getId()?>">
                            <?php xecho($sGroup->getName()); ?>
                            </a>
                        </td>
                        <td>
                            <?php xecho($sGroup->getDescription()); ?>
                        </td>
                        <td>
                            <textarea readonly="true" style="height: 25px;"><?php
                                xecho($sGroup->getScopeNamesAsString());
                            ?></textarea>
                        </td>
                    </tr>
                <?php
                } // End of the foreach loop iterating over sGroups
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
    $("#selectedSgTable").tablesorter();

    $('#scopeSelect').multipleSelect({
        filter: true,
            placeholder: "SG Scopes"
        });
    });
</script>
