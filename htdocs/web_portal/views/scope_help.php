<div class="rightPageContainer">
    <h1>Data Scoping</h1>
    <br />

    <div class="Help_And_Documentation">
        <h2>What are scope tags?</h2>
            <p>
                Scope tags are used to selectively tag service, service groups, 
                sites, and NGIs. In doing this, PI queries can return only those
                sites, services, service groups, or NGIs that define a 
                particular scope tag or set of scope tags. 
            </p>
            <p>
                Scope tags are non-exclusive, allowing a single object to be 
                tagged zero or many times. Scope tags are added and removed by 
                the GOCDB admins while normal users can only select scope tags
                from the available list. New scope tags can be requested by
                [TODO]. 
            </p>
            <p>No special semantics are placed on scope tags within GOCDB itself
                - they are simply tags. Rather, it is up to dependent systems to
                interpret the implications of tagging an object with scopeX 
                and/or scopeY. 
            </p>
            <p>
                In EGI, scope tags are currently used to name different 
                Grids/Projects. For example, a Site could be tagged with both
                the 'EGI' and 'ProjX' tags. Dependent systems should interpret
                this to mean that the site delivers resources to both projects.
                Conversely, a single 'Local' tag can be used to declare that
                this site does not provide any resources to either EGI or Projx. 
            </p>
            <p>
                Scope tags should not be confused with projects. Projects 
                provide a means to cascade project level roles/permissions over
                child NGIs grouped under the project. Scope tags have no effect
                on permissions. 
            </p>
            <p>
                The following are some examples of scope tags in use in PI 
                queries: 
                <ul>
                    <li>
                        <pre>?method=get_site&scope=EGI</pre>
                        (Fetch all sites tagged as 'EGI') 
                    </li>   
                    <li>
                        <pre>?method=get_site&scope=EGI,ProjX&scope_match=all</pre>
                        (Fetch all sites tagged with <b>both</b> 'EGI' and ProjX) 
                    </li> 
                    <li>
                        <pre>?method=get_site&scope=EGI,ProjX&scope_match=any</pre>
                        (Fetch all sites tagged with <b>either</b> 'EGI' or ProjX) 
                    </li> 
                     <li>
                        <pre>?method=get_site&scope=</pre>
                        (Fetch <b>all sites</b> regardless of scope tags) 
                    </li> 
                </ul>
            </p>

    </div>
    <!--Scopes-->
    <div class="Help_And_Documentation">
        <h2>What scope tags are available?</h2>
        <div class="listContainer">
            <span class="header listHeader">
                The following scopes are available in GOCDB:
            </span>
            <img src="<?php echo \GocContextPath::getPath()?>img/grid.png" class="decoration" />
            <table class="vSiteResults" id="selectedSETable">
                <tr class="site_table_row_1">
                    <th class="site_table">Name</th>
                    <th class="site_table">Description</th>
                </tr>
                <?php           
                $num = 2;
                foreach($params['Scopes'] as $scope) {
                ?>
                <tr class="site_table_row_<?php echo $num ?>">
                    <td class="site_table">
                        <div style="background-color: inherit;">
                            <span style="vertical-align: middle;">
                               <?php echo $scope->getName(); ?>
                            </span>
                        </div>
                    </td>

                    <td class="site_table">
                        <?php echo $scope->getDescription(); ?>
                    </td>

                </tr>
                <?php  
                    if($num == 1) { $num = 2; } else { $num = 1; }
                    } // End of the foreach loop iterating over SEs
                ?>
            </table>
        </div>
    </div>
    <br/>&nbsp;
    
    <div class="Help_And_Documentation">
        <h2>What does having the EGI scope applied mean?</h2>
        <p>
            If a site, service, service group, or NGI is scoped as ‘EGI’ then it
            will be exposed to the central operational tools for monitoring and 
            will appear in the operations portal. 
        </p>
        <p>
            The 'EGI' scope not being selected for a given object makes the 
            object invisible to EGI and the central operation tools (it will not
            show in the central dashboard and it will not be monitored 
            centrally). This can be useful if you wish to hide certain parts of 
            your infrastructure from EGI but still have the information stored
            and accessed from the same GOCDB instance. In this case you should 
            use the 'Local' scope tag.
        </p>
        <p>
            Note that scoping a site / service endpoint as EGI does not override
            the production status or certification status fields. For example if
            a site is not marked as production it won't be monitored centrally
            even if it's marked as visible to EGI. 
        </p> 
    </div>
    
</div>