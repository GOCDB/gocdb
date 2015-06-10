
## Todo
* When authenticating via SAML, need to map certain attributes to GocDB account
  fields, and when editing. Also, what to do with all the attributes received from IdP. 
* After a downtime has started, remove downtime delete/edit buttons
* Look at the retrieve account logic/page 
* Update role-approve notification email 
* Change <CertificateDN> in PI output to <Principle> and consider adding the 
  <AuthenticationRealm> element and DB column. 
* Add instructions for deployment to MySQL 
* Add LoA attribute to AuthToken details  
* Modularise RoleLogic into one class and support RoleActionPermissions.xml, see below. 
* Refactor page view/template logic and remove nasty menu/header/footer php 
  rendering logic (an inherited legacy) 
* Add new regex and logic for filtering by custom properties (new regex is far
  better and can allow for any value, inc UTF8 in the property values) 
* Add new gocdb website on github
* Scope pull down multiple select (HTML5 multiple keyword on select).  
* Update the datetime picker to latest version 
* Improve the edit/define downtime logic in JS/Client (e.g. prevent extending DT 
  and updating the start date to a time in the past) 
* Allow downtime to affect services across multiple sites (currently DT 
  can only affect services from a single site) 
* Improve the downtime service selection GUI by showing some extra tags/info 
  to distinguish that particular service (show id or fist x chars of description)  

## Maybe Todo 
* Support returning multiple AuthTokens e.g. in SecurityContext and in 
  token resolution process? Something like this would be needed for account linking.  
* Add filtering of resources by 'project' 
* Add 'project' URL param to PI get_project, get_site, get_service, get_downtime
* Add <scopes> elements to PI output

## Recently Done
* Show all/additional SAML attributes retrieved from IdP after successful user 
  authentication. Show attributes in view user page.  
* Define downtime in sites local timezone 
* Replace timezone lookup table with php Olson timezonedb values and create 
  migration scripts (if a legacy timezone can't be mapped to new value, then 
  default to UTC). 

### Sample RoleActionPermissions.xml (TODO) 

* The actions that can be performed on the different OwnedEntity objects (Project, Ngi, Site, SG)
could be declared in an xml file. 
* The necessary role names that are needed to perform those actions can also be declared
alongside those actions. 
* These mappings could be defined per-project using multiple <ProjectMapping> elements. 
* A default fallback mapping could be made mandatory when a project-specific mapping is not defined. 
* Note that the elements named after owned entities declare different child 
<RequiredXXXRole> elements. This allows roles defined over parent owning objects  
to enable the action(s) on the target object. 
 
```xml
<RoleActionMappings>

    <ProjectMapping projectName="EGI"> <!--(*)-->
      <Project> (1)
        <ActionsAndRoles><!--(*)-->
           <Actions>EDIT_OBJECT</Actions>(1)
           <RequiredProjectRole andOr="OR">COD_ADMIN,COD_STAFF,
               EGI_CSIRT_OFFICER,COO<RequiredProjectRole>(*)
        </ActionsAndRoles>

        <ActionsAndRoles>
           <Actions>GRANT_ROLE,REJECT_ROLE,REVOKE_ROLE</Actions>
           <RequiredProjectRole andOr="OR">COD_ADMIN,COD_STAFF,
               EGI_CSIRT_OFFICER,COO<RequiredProjectRole>
        </ActionsAndRoles>
      </Project>

      <Ngi>
        <ActionsAndRoles>
           <Actions>EDIT_OBJECT</Actions>
           <RequiredProjectRoles><RequiredProjectRoles>
           <RequiredNgiRole andOr="OR">NGI_OPS_MAN,NGI_OPS_DEP_MAN,
             NGI_SEC_OFFICER,REG_STAFF_ROD,REG_FIRST_LINE_SUPPORT</RequiredNgiRole>
        </ActionsAndRoles>

        <ActionsAndRoles>
           <Actions>NGI_ADD_SITE</Actions>
           <RequiredProjectRoles><RequiredProjectRoles>
           <RequiredNgiRole andOr="OR">NGI_OPS_MAN,NGI_OPS_DEP_MAN,
              NGI_SEC_OFFICER</RequiredNgiRole>
        </ActionsAndRoles>

        <ActionsAndRoles>
           <Actions>GRANT_ROLE,REJECT_ROLE,REVOKE_ROLE</Actions>
           <RequiredProjectRoles andOr="OR">COD_STAFF,COD_ADMIN,
              EGI_CSIRT_OFFICER,COO<RequiredProjectRoles>
           <RequiredNgiRole andOr="OR">NGI_OPS_MAN,NGI_OPS_DEP_MAN,
              NGI_SEC_OFFICER</RequiredNgiRole>
        </ActionsAndRoles>
      </Ngi>

      <Site>
        <ActionsAndRoles> <!--(*)-->
           <Actions> <!--(1)-->
           <RequiredProjectRoles> <!--(*)-->
           <RequiredNgiRole andOr="OR"> <!--(*)-->
           <RequiredSiteRoles andOr="OR"> <!--(*)-->
        </ActionsAndRoles>
      </Site>

      <ServiceGroup>
        <ActionsAndRoles> <!--(*)-->
           <Actions> <!--(1)-->
           <RequiredServiceGroupRoles> <!--(*)-->
        </ActionsAndRoles>
      </ServiceGroup>

    </ProjectMapping>

</RoleActionMappings>
```