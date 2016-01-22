<div class="rightPageContainer">
    <h1>Resource Scoping</h1>
    <br />

    <div>
        <h2>What are scope tags?</h2>
        <ul>
            <li>
                Scope tags are used to selectively tag Services, ServiceGroups, 
                Sites and NGIs so that API queries and users of the UI can filter for
                objects that define the required set of tags. 
            </li>
            <li>
                The available tags are controlled by the GOCDB admins allowing 
                users can to select relevant tags from the list. New tags can 
                be requested if required.  
            </li>
            <li>No special meaning are placed on scope tags within GOCDB itself
                - they are simply tags. </li>
            <li>
                In EGI, scope tags are used to name different 
                Grids/Projects/Groupings. For example, a Site could be tagged with both
                the 'EGI' and 'ProjX' tags. 
                Conversely, a single 'Local' tag can be used to declare that
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
            <li>Some tags may be 'Reserved' which means they are protected.</li>
            <li>New Reserved tags can only be directly assigned to resources by the gocdb-admins 
            (includes NGIs, Sites, Services and ServiceGroups).</li>
            <li>When creating a new child resource (e.g. a child Site or child Service), 
              the scopes that are assigned to the parent are automatically inherited and assigned to the child.</li>
            <li>Reserved tags assigned to a resource are optional and can be de-selected if required.</li>
            <li>Users can reapply Reserved tags to a resource ONLY if the tag can be 
              inherited from the parent Scoped Entity (parents include NGIs/Sites).</li>
            <li>For Sites: If a Reserved tag is removed from a Site, then the same tag is also removed
              from all the child Services - a Service can't have a reserved tag that 
              is not supported by its parent Site.</li>
            <li>For NGIs: If a Reserved tag is removed from an NGI, then the same tag is NOT 
              removed from all the child Sites - this is intentionally different from the Site->Service relationship.</li>
        </ul>
        <h2>How are scope tags used in the API?</h2>
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
    </div>

    <div>
        <h2>What scope tags are available?</h2>
        <div>
            <table class="table table-striped table-condensed">
                <thead>
                    <tr>
                        <th>Tag name</th>
                        <th>Description</th>
                        <th>Reserved?</th>
                    </tr>
                </thead>
                
               <?php foreach($params['optionalScopes'] as $scope){ ?>
                <tr>
                    <td><?php xecho($scope->getName());?></td>
                    <td><?php xecho($scope->getDescription()); ?></td>
                    <td>&cross;</td>
                </tr>
               <?php } ?>
                
               <?php foreach($params['reservedScopes'] as $scope){ ?>
                <tr>
                    <td><?php xecho($scope->getName());?></td>
                    <td><?php xecho($scope->getDescription()); ?></td>
                    <td>&check;</td>
                </tr>
               <?php } ?>
                
            </table>
        </div>
    </div>
  

    
    <div>
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