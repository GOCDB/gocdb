
## Todo
* Record user last login date in new datetime field. 
* After a downtime has started, remove downtime delete buttons
* Update role-approve notification email 
* Change `<CERTDN>` element in PI output to `<PRINCIPAL>` and consider adding the 
  `<AuthenticationRealm>` element and DB column. 
* Add instructions for deployment to MySQL 
* Modularise RoleLogic into one class and support `RoleActionPermissions.xml`, see below. 
* Refactor page view/template logic and remove nasty menu/header/footer php 
  rendering logic (an inherited legacy) 
* Add new regex and logic for filtering by custom properties (new regex is far
  better and can allow for any value, inc UTF8 in the property values, see 
  `PI_Extension_Param_ParsingTest.php`) 
* Add new gocdb website on github
* Scope pull down multiple select (HTML5 multiple keyword on select).  
* Update the datetime picker to latest version 
* Allow downtime to affect services across multiple sites (currently DT 
  can only affect services from a single site) 
* Improve the downtime service selection GUI by showing some extra tags/info 
  to distinguish that particular service (show id or fist x chars of description)  
* Introduce Monolog package

## Maybe Todo 
* Add LoA attribute to AuthToken details  
* Support returning multiple AuthTokens e.g. in SecurityContext and in 
  token resolution process? Something like this would be needed for account linking
  where a user would need to authenticate multiple times using the different 
  AAI supported methods in order to link those accounts.  
* Add filtering of resources by 'project' 
* Add 'project' URL param to PI get_project, get_site, get_service, get_downtime
* Add <scopes> elements to PI output
* Introduce READ action for roles? - currently, once a user is authenticated, all info can 
  be viewed in GOCDB. We may need fine-grained READ permissions on selected content. 


### Sample RoleActionPermissions.xml (TODO, see above) 
* Modularise RoleLogic into one class and support `RoleActionPermissions.xml`. 
* The actions that can be performed on the different OwnedEntity objects (Project, Ngi, Site, SG)
could be declared in an xml file. 
* The necessary role names that are needed to perform those actions can also be declared
alongside those actions. 
* These mappings could be defined per-project using multiple `<RoleActionMapping>`s. 
* A default fallback mapping could be made mandatory when a project-specific mapping is not defined. 
 
```xml
<RoleActionMappings>

    <RoleActionMapping forProjects="EGI,EUDAT"> 
 
       <!--
       The listed Roles held over the specified OwnedEntity type enable the 
       Actions over the target object(s). 
       --> 

       <RoleMapping forOwnedEntityType="Project">
           <Roles>COD_STAFF,COD_ADMIN,EGI_CSIRT_OFFICER,COO</RoleNames>  
           <EnabledActions>
                <RoleActions>EDIT_OBJECT,GRANT_ROLE,REJECT_ROLE,REVOKE_ROLE</RoleActions> 
                <ActionTargets>Project</ActionTargets> 
           </EnabledActions>
           <EnabledActions>
                <RoleActions>GRANT_ROLE,REJECT_ROLE,REVOKE_ROLE</RoleActions>
                <ActionTargets>Ngi</ActionTargets>
           </EnabledActions>
       </RoleMapping>

       <RoleMapping forOwnedEntityType="Ngi">
           <Roles>
              NGI_OPS_MAN,NGI_OPS_DEP_MAN,NGI_SEC_OFFICER,
              REG_STAFF_ROD,REG_FIRST_LINE_SUPPORT
           </Roles>
           <EnabledActions>
               <RoleActions>EDIT_OBJECT</RoleActions>
               <ActionTargets>Ngi</ActionTargets>
           </EnabledActions>
       </RoleMapping>

        <RoleMapping forOwnedEntityType="Ngi">
           <Roles>NGI_OPS_MAN,NGI_OPS_DEP_MAN,NGI_SEC_OFFICER</Roles>
           <EnabledActions>
               <RoleActions>NGI_ADD_SITE</RoleActions>
               <ActionTargets>Ngi</ActionTargets>
           </EnabledActions>
       </RoleMapping>

    </RoleActionRoleMapping> 

</RoleActionMappings>
```
