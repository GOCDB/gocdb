
## Todo (https://rt.egi.eu/rt/Dashboards/5541/GOCDB-Requirements) 
* Add a unique constraint on the Doctrine annotation for the ServiceGroup name attribute. 
* Refactor page view/template logic and remove NASTY menu/header/footer php 
  rendering logic (an inherited legacy). A real MVC framework for the view 
  would also be far preferable than the current home-cooked MVC layer.   
* Improve the forms to add/edit NGIs/Sites/Services to use the jquery form 
  validation plugin (see Edit_Service_Endpoint and Add_Service_Endpoint pages 
  which already use this plugin). 
* Add a Downtime-Calendar view to show downtimes on a time-line, e.g. using the 
  Google calendar API - also check with Ops portal because they may have already done this.  
* Record user last login date in new datetime field (note, if user authenticates
  with x509 there is no HTTP session started which may mean this field would need to be 
  updated in DB on each/every page request - is this desirable?, or we could 
  start a new GOC session if required). Needed to delete inactive accounts. 
* Add UserProperty entity and join with User, for persisting various un-determined 
  attributes such as AAA/SAML attributes provided by IdP on account registration. 
* Introduce reserved keyNames for custom properties so that a user can't add/edit  
  a custom prop with a reserved name, e.g. 'GOC_RESERVED_PROPERTY1'. 
  These could be defined in the local_info.xml config file for each 
  type of custom prop (EndpointProperty, SiteProperty, ServiceProperty etc).   
* Add new ngi_cert_status entity to define NGI certification status rules and 
  link to each NGI. For details see https://rt.egi.eu/rt/Ticket/Display.html?id=9084 
* Check that after a downtime has started, the downtime edit/delete buttons are removed 
* Update role-approve notification email logic 
* Change `<CERTDN>` element in PI output to `<PRINCIPAL>` and consider adding the 
  `<AuthenticationRealm>` element and DB column. 
* Add instructions for deployment to MySQL/Mariadb 
* Update the datetime picker to latest version 
* Allow downtime to affect services across multiple sites (currently DT 
  can only affect services from a single site). Check this is actually needed.  
* Improve the downtime service selection GUI by showing some extra tags/info 
  to better distinguish the services (show id or first ~10 chars of description). 
  The add/edit downtime page also needs improving to refine the logic.    
* Introduce Monolog package for logging 
* Add new gocdb website on github: https://gocdb.github.io/ 
* There is a general over-reliance on using setters to inject dependencies, 
  especially objects in the 'lib/Doctrine/entities' and 'lib/Gocdb_Services'. 
  Instead, constructor injection should be used for required dependencies so that
  objects can't be created in an inconsistent state. 
* Better interface segregation needed in places - the IPIQuery.php interface 
  violates the single-responsibility principle by combining methods such as 'getXML()' 
  and 'getJSON()' with the other methods for creating/issuing a query. 
  This interface should probably be segregated into two.  
* Add bulk upload of custom extension properties (key=value) pairs as a .properties file, 
  and better rendering of extension properties in a sortable table with selectAll, 
  deselectAll, and actions such as delete, duplicate. 
* Add a new view to display the list of service types and their descriptions. 
* When filtering sites/services/SGs via the GUI, add a new GUI component to
  select zero or more custom properties and allow a value to be optionally 
  specified for the property with a select for AND or NOT. Perhaps a multi-select 
  pull-down so when a custom prop is selected, a new row is entered into a table
  which allows the user to specify a value for the prop and provides the AND/NOT option. 
  The user should be able to edit/delete the added rows. The values entered into 
  the table can then be used to build an extensions expression as is used in the PI.  
* More comprehensive change logging: https://rt.egi.eu/rt/Ticket/Display.html?id=9431 
* Automatic freshness of data check: https://rt.egi.eu/rt/Ticket/Display.html?id=8240  


## Maybe Todo 
* Add LoA attribute to AuthToken details  
* Support account linking where a user would need to authenticate multiple times using the different 
  AAI supported methods in order to link those identities to a single (possibly existing) account:
  * Update DB schema so that a user account has one-to-many identities rather than a single ID 
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
  * Or use Unity / Perun to do the account linking for us? 

* Add filtering of resources by 'project' ?  
* Add 'project' URL param to PI get_project, get_site, get_service, get_downtime ? 
* Introduce READ action for roles? - currently, once a user is authenticated, all info can 
  be viewed in GOCDB. We may need fine-grained READ and content-rendering permissions 
  based on user roles. 



