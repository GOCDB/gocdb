
## Todo
* Record user last login date in new datetime field (is problematic if user authenticates
  with x509 as there is no session started which means field would need to be 
  updated in DB on each/every page request - desirable?). 
* After a downtime has started, remove downtime delete buttons
* Update role-approve notification email 
* Change `<CERTDN>` element in PI output to `<PRINCIPAL>` and consider adding the 
  `<AuthenticationRealm>` element and DB column. 
* Add instructions for deployment to MySQL 
* Modularise RoleLogic into one class and support `RoleActionMappings.xml`, see below. 
* Refactor page view/template logic and remove nasty menu/header/footer php 
  rendering logic (an inherited legacy) 
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
* Support account linking where a user would need to authenticate multiple times using the different 
  AAI supported methods in order to link those accounts:   
  * Update DB schema so that a user account has one-to- many identities rather than a single ID 
  * Record additional information about which login-route/security-realm is associated with each ID 
  * Modify the authentication lib so that the authentication-context can handle 
a collection of AuthTokens rather than a single AuthToken during the same HTTP session 
  * Enable linking a new/unregistered ID to an existing account: On registering, 
provide an option to allow the new ID to be associated with an existing account 
rather than creating a new/separate account. 
  * Link two existing accounts together: Provide interface to allow joining/merging 
two existing accounts (will need to merge existing roles, remove duplicate roles etc) 
  * To perform either of these account linking scenarios, user will be required to 
authenticate for all the authentication-mechanisms during the same HTTP session 
(e.g. authenticate with x509, then re-authenticate via IdP). Only after successfully 
authenticating with the multiple login mechanisms, should they be able to link those accounts together. 
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
       Define the Role names and which of the owned entity types they apply to. 
       Note, role name and alias values must be unique (names have a DB unique constraint).  
       Aliases are used as a convenient shorthand to define the XML rules. 
       --> 
       <RoleNames over="Project">
              <Role alias="COD_STAFF">COD Staff</Role>
              <Role alias="COD_ADMIN">COD Administrator</Role>
              <Role alias="EGI_CSIRT_OFFICER">EGI CSIRT Officer</Role>
              <Role alias="COO">Chief Operations Officer</Role>
       </RoleNames>  

       <RoleNames over="Ngi">
              <Role alias="NGI_OPS_MAN">NGI Operations Manager</Role>
              <Role alias="NGI_OPS_DEP_MAN">NGI Operations Deputy Manager</Role>
              <Role alias="NGI_SEC_OFFICER">NGI Security Officer</Role>
              <Role alias="REG_STAFF_ROD">Regional Staff (ROD)</Role>
              <Role alias="REG_FIRST_LINE_SUPPORT">Regional First Line Support</Role>
       </RoleName>

       <RoleNames over="Site"> 
             <Role alias="SITE_ADMIN">Site Administrator</Role>
             <Role alias="SITE_SECOFFICER">Site Security Officer</Role>
             <Role alias="SITE_OPS_DEP_MAN">Site Operations Deputy Manager</Role>
             <Role alias="SITE_OPS_MAN">Site Operations Manager</Role>
       </RoleNames> 

       <RoleNames over="ServiceGroup">
           <Role alias="SERVICE_GROUP_ADMIN">Service Group Administrator</Role>
       </RoleNames>


       <!--
       The listed Roles enable the Actions over the target object(s). 
       --> 

       <RoleMapping>
           <Roles>
              COD_STAFF,
              COD_ADMIN,
              EGI_CSIRT_OFFICER,
              COO
           </Roles>  
           <EnabledActions>
                <Actions>ACTION_EDIT_OBJECT,ACTION_GRANT_ROLE,ACTION_REJECT_ROLE,ACTION_REVOKE_ROLE</Actions> 
                <Targets>Project</Targets> 
           </EnabledActions>
           <EnabledActions>
                <Actions>ACTION_GRANT_ROLE,ACTION_REJECT_ROLE,ACTION_REVOKE_ROLE</Actions>
                <Targets>Ngi</Targets>
           </EnabledActions>
       </RoleMapping>

       <RoleMapping>
           <Roles>
              NGI_OPS_MAN,
              NGI_OPS_DEP_MAN,
              NGI_SEC_OFFICER,
              REG_STAFF_ROD,
              REG_FIRST_LINE_SUPPORT
           </Roles>
           <EnabledActions>
               <Actions>ACTION_EDIT_OBJECT</Actions>
               <Targets>Ngi</Targets>
           </EnabledActions>
       </RoleMapping>

        <RoleMapping>
           <Roles>
              NGI_OPS_MAN,
              NGI_OPS_DEP_MAN,
              NGI_SEC_OFFICER
           </Roles>
           <EnabledActions>
               <Actions>ACTION_NGI_ADD_SITE,ACTION_GRANT_ROLE,ACTION_REJECT_ROLE,ACTION_REVOKE_ROLE</Actions>
               <Targets>Ngi</Targets>
           </EnabledActions>
       </RoleMapping>

       <RoleMapping> 
          <Roles>
            SITE_ADMIN, 
            SITE_SECOFFICER, 
            SITE_OPS_DEP_MAN, 
            SITE_OPS_MAN
            REG_FIRST_LINE_SUPPORT, 
            REG_STAFF_ROD, 
            NGI_SEC_OFFICER, 
            NGI_OPS_DEP_MAN, 
            NGI_OPS_MAN 
          </Roles>  
          <EnabledActions> 
            <Actions>ACTION_EDIT_OBJECT, ACTION_SITE_ADD_SERVICE, ACTION_SITE_DELETE_SERVICE</Actions> 
            <Targets>Site</Targets>   
          </EnabledActions> 
       </RoleMapping> 


       <RoleMapping> 
          <Roles>
            SITE_SECOFFICER, 
            SITE_OPS_DEP_MAN, 
            SITE_OPS_MAN
            NGI_SEC_OFFICER, 
            NGI_OPS_DEP_MAN, 
            NGI_OPS_MAN 
          </Roles> 
          <EnabledActions> 
            <Actions>ACTION_GRANT_ROLE, ACTION_REJECT_ROLE, ACTION_REVOKE_ROLE</Actions> 
            <Targets>Site</Targets>   
          </EnabledActions> 
       </RoleMapping> 

 
       <RoleMapping> 
          <Roles>
              NGI_SEC_OFFICER, 
              NGI_OPS_DEP_MAN, 
              NGI_OPS_MAN  
              COD_STAFF,
              COD_ADMIN,
              EGI_CSIRT_OFFICER,
              COO
          </Roles>  
          <EnabledActions> 
            <Actions>ACTION_SITE_EDIT_CERT_STATUS</Actions> 
            <Targets>Site</Targets>   
          </EnabledActions> 
       </RoleMapping> 


       <RoleMapping>
          <Roles>SERVICE_GROUP_ADMIN</Roles>
          <EnabledActions>
             <Actions>ACTION_EDIT_OBJECT,ACTION_GRANT_ROLE, ACTION_REJECT_ROLE, ACTION_REVOKE_ROLE</Actions>
             <Target>ServiceGroup</Target> 
          </EnabledActions>
       </Roles> 

       <!-- 
       For the newly proposed edit NGI cert status: 
       <RoleMapping> 
          <Roles>
              COD_STAFF,
              COD_ADMIN,
              EGI_CSIRT_OFFICER,
              COO
          </Roles>  
          <EnabledActions> 
            <Actions>ACTION_NGI_EDIT_CERT_STATUS</Actions> 
            <Targets>Ngi</Targets>   
          </EnabledActions> 
       </RoleMapping> 
       -->
 
    </RoleActionMapping> 

</RoleActionMappings>
```

```php 
/**
 * Pseudo code to process the role-action mapping rules that are defined in RoleActionMappings.xml 
 */
public function authoriseAction($action, \OwnedEntity $entity, \User $user){
    // validate the RoleActionMappings.xml file against its schema 
    // validateRoleActionMappingsXML();  
   
    // validate the requested action against RoleActionMappings.xml. The specified 
    // action must be defined and associated with the entity.  
    validateAction($action, $entity); 

    // get list of role names needed to perform the action on the entity from RoleActionMappings. 
    $requiredRoles = getRequiredRolesForTargetedAction($action, $entity);     

    // get all the user's roles linked to entities that are in the same project as the specified entity 
    $userRoles = getRolesInProject($user, $entity); 

    // just return the enabling role names or full roles (could pass a param to decide or sub-function) 
    if($returnRoles == FALSE){ 
        // extract a list of role names from the user's roles 
        $usersActualRoleNames = getRoleNamesFromRoles($userRoles); 
        
        // get intersecting role names   
        $enablingRoles = array_intersect($requiredRoles, array_unique($usersActualRoleNames));

        // remove duplicates  
        return array_unique($enablingRoles); 
    }
    // return the enabling role entities  
    else {
        $enablingRoles = array();  
        foreach($userRoles as $role){
            foreach($requiredRoles as $requiredRole){
                if($role->getType()->getName() == $requiredRole){
                    $enablingRoles[] = $role; 
                }
            }
        }  
        return $enablingRoles; 
    } 
}

private function getRequiredRolesForActionTargetedAction($action, \OwnedEntity $entity){
  // XPath query for the relevant RoleActionMapping element (defined per-project or default)  
  // XPath query for all child RoleMapping elements where Target element value == $entity type 
  // Iterate RoleMapping elements and drop those that don't have an EnabledActions->Actions 
  //   value that lists $action   
  // Iterate remaining RoleMapping elements and build a list of role names from 
  //    the values of Role elements 
  // return array of role names   
}


private function getRolesInProject(\User $user, \OwnedEntity $entity){
  // DQL query the DB for all user's roles 
  // Find $parentProject => From $entity, move up the data-model hierarchy until we 
  //   reach the Project that hosts $entity 
  // Limit roles to those in the $parentProject - iterate each user Role and 
  //   check to see that each role can reach $parentProject (side note, we 
  //   could store projId on each Role to simplify this?)
  // return role array subset   
}

```
