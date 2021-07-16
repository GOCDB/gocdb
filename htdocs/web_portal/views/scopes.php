<div class="rightPageContainer">

    <div style="float: left;">
        <h1 style="float: left; margin-left: 0em;">
            Scopes
        </h1>
        <span style="clear: both; float: left; padding-bottom: 0.4em;">
            Click on the name of a scope to <?=$params['UserIsAdmin'] ? "edit it and " : "";?> view objects with that scope tag.
        </span>
    </div>
    <!-- hide add when read only or user is not admin -->
    <?php if(!$params['portalIsReadOnly'] && $params['UserIsAdmin']):?>
        <div style="float: right;">
            <center>
                <a href="index.php?Page_Type=Admin_Add_Scope">
                <img src="<?php echo \GocContextPath::getPath()?>img/add.png" height="25px" />
                <br />
                <span>Add Scope</span>
                </a>
            </center>
        </div>
    <?php endif; ?>

    <?php $numberOfScopes = count($params['Scopes'])?>
    <div class="listContainer">
        <span class="header listHeader">
            <?php echo $numberOfScopes ?> Scope<?php if ($numberOfScopes !== 1) echo "s"?>
        </span>
        <table class="vSiteResults" id="selectedSETable">
            <tr class="site_table_row_1">
                <th class="site_table">Name</th>
                <th class="site_table">Description</th>
                <th class="site_table">Reserved?</th>
                <?php if(!$params['portalIsReadOnly'] && $params['UserIsAdmin']):?>
                    <th class="site_table" style="width: 10%">Remove</th>
                <?php endif; ?>
            </tr>
            <?php
            $num = 2;
            if($numberOfScopes > 0) {
                foreach($params['Scopes'] as $scope) {
                ?>
                <tr class="site_table_row_<?php echo $num ?>">
                    <td class="site_table">
                        <div style="background-color: inherit;">
                            <span style="vertical-align: middle;">
                                <a href="index.php?Page_Type=Scope&amp;id=<?php echo $scope->getId() ?>">
                                    <?php xecho($scope->getName()); ?>
                                </a>
                            </span>
                        </div>
                    </td>
                    <td class="site_table"><?php xecho($scope->getDescription()); ?></td>
                    <td class="site_table"><?= in_array($scope, $params['reservedScopes']) ? '&check;': '&cross;';?></td>
                    <?php if(!$params['portalIsReadOnly'] && $params['UserIsAdmin']):?>
                        <td class="site_table"  style="width: 10%">
                            <script type="text/javascript" src="<?php echo \GocContextPath::getPath()?>javascript/confirm.js"></script>
                            <a onclick="return confirmSubmit()" href="index.php?Page_Type=Admin_Remove_Scope&id=<?php echo $scope->getId() ?>">
                                <img src="<?php echo \GocContextPath::getPath()?>img/trash.png" height="22px" style="vertical-align: middle;" />
                            </a>
                        </td>
                    <?php endif ?>
                </tr>
                <?php
                    if($num == 1) { $num = 2; } else { $num = 1; }
                    } // End of the foreach loop iterating over scopes
            }
            ?>
        </table>
    </div>

    <span style="clear: both; float: left; padding-top: 1em;">

    <div>
        <h2>What are scope tags?</h2>
        <ul>
            <li>
                Scope tags are used to selectively tag Services, ServiceGroups,
                Sites and NGIs so that API queries and users of the UI can filter for
                objects that define the required set of tags.
            </li>
            <li>
                The available tags are controlled by the GOCDB admins, allowing
                users to select relevant tags from the list. New tags can
                be requested if required.
            </li>
            <li>
                In EGI, scope tags are used to categorise resources,
                e.g. a Site could be tagged with the 'EGI' and 'ProjX' tags
                while a single 'Local' tag can be used to declare that
                this site does not provide any resources to EGI or ProjX.
            </li>
            <li>
                Scope tags should not be confused with projects. Projects
                provide a means to cascade roles/permissions over
                child resources (NGIs, Sites, Services) grouped under the project.
                Scope tags have no effect on permissions.
            </li>

        </ul>
        <h2>What are Reserved tags?</h2>
        <ul>
            <li>Some tags may be 'Reserved' which means they are protected - they are used to restrict tag usage
            and prevent non-authorised sites/services from using tags not intended for them.</li>
            <li>Reserved tags are initially assigned to resources by the gocdb-admins, and can then be optionially
              inherited by child resources (tags can be initially assigned to NGIs, Sites, Services and ServiceGroups).</li>
            <li>When creating a new child resource (e.g. a child Site or child Service),
              the scopes that are assigned to the parent are automatically inherited and assigned to the child.</li>
            <li>Reserved tags assigned to a resource are optional and can be de-selected if required.</li>
            <li>Users can reapply Reserved tags to a resource ONLY if the tag can be
              inherited from the parent Scoped Entity (parents include NGIs/Sites).</li>
            <li>For Sites: If a Reserved tag is removed from a Site, then the same tag is also removed
              from all the child Services - a Service can't have a reserved tag that
              is not supported by its parent Site.</li>
            <li>For NGIs: If a Reserved tag is removed from an NGI, then the same tag is NOT
              removed from all the child Sites - this is intentionally different from the Site&rarr;Service relationship.</li>
        </ul>
        <h2>How are scope tags used in the API?</h2>
        The following are some examples of scope tags in use in PI
        queries:
        <ul>
            <li>
                <pre>?method=get_site&amp;scope=EGI</pre>
                (Fetch all sites tagged as 'EGI')
            </li>
            <li>
                <pre>?method=get_site&amp;scope=EGI,ProjX&amp;scope_match=all</pre>
                (Fetch all sites tagged with <b>both</b> 'EGI' and ProjX)
            </li>
            <li>
                <pre>?method=get_site&amp;scope=EGI,ProjX&amp;scope_match=any</pre>
                (Fetch all sites tagged with <b>either</b> 'EGI' or ProjX)
            </li>
             <li>
                <pre>?method=get_site&amp;scope=</pre>
                (Fetch <b>all sites</b> regardless of scope tags)
            </li>
        </ul>

        <h2>What does having the EGI scope applied mean?</h2>
        <ul>
            <li>
                If a site, service, service group, or NGI is scoped as ‘EGI’ then it
                will be exposed to the central operational tools for monitoring and
                will appear in the operations portal.
            </li>
            <li>
                The 'EGI' scope not being selected for a given object makes the
                object invisible to EGI and the central operation tools (it will not
                show in the central dashboard and it will not be monitored
                centrally). This can be useful if you wish to hide certain parts of
                your infrastructure from EGI but still have the information stored
                and accessed from the same GOCDB instance. In this case you should
                use the 'Local' scope tag.
            </li>
            <li>
                Note that scoping a site / service endpoint as EGI does not override
                the production status or certification status fields. For example if
                a site is not marked as production it won't be monitored centrally
                even if it's marked as visible to EGI.
            </li>
        </ul>
    </div>
</div>
