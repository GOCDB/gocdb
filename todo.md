
## Todo 
* The list below includes some todos for mostly 'internal' issues/cleanup. 
* Documented requirements are entered into the EGI request tracker: 
  * EGI RT <https://rt.egi.eu/rt/Dashboards/5541/GOCDB-Requirements> 
  * Also see Github issues (records issues/bugs not new requirements): <https://github.com/GOCDB/gocdb/issues>
###Cleanup/todos
* Add a unique constraint on the Doctrine annotation for the ServiceGroup name attribute. 
* Refactor page view/template logic and remove NASTY menu/header/footer php 
  rendering logic (an inherited legacy). A real MVC framework for the view 
  would be far preferable than the current home-cooked MVC layer which incurs
  a large overhead to develop.   
* Improve the forms to add/edit NGIs/Sites/Services to use the jquery form 
  validation plugin (see Edit_Service_Endpoint and Add_Service_Endpoint pages 
  which already use this plugin). 
* Add UserProperty entity and join with User, for persisting various un-determined 
  attributes such as AAA/SAML attributes provided by IdP on account registration. 
* Maybe - Introduce reserved keyNames for CUSTOM properties so that a user can't add/edit  
  a custom prop with a reserved name, e.g. 'GOC_RESERVED_PROPERTY1'. 
  These could be defined in the local_info.xml config file for each 
  type of custom prop (EndpointProperty, SiteProperty, ServiceProperty etc).   
* Add new ngi_cert_status entity to define NGI certification status rules and 
  link to each NGI. For details see https://rt.egi.eu/rt/Ticket/Display.html?id=9084 
* Check that after a downtime has started, the downtime edit/delete buttons are removed 
* Update role-approve notification email logic 
* Change `<CERTDN>` element in PI output to `<PRINCIPAL>` and consider adding the 
  `<AuthenticationRealm>` element and DB column. 
* Add instructions for deployment to MySQL/Mariadb and test on these RDBMS. 
* Maybe - Allow downtime to affect services across multiple sites (currently DT
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
* <strike>Better interface segregation needed in places - the IPIQuery.php interface 
  violates the single-responsibility principle by combining methods such as 'getXML()' 
  and 'getJSON()' with the other methods for creating/issuing a query. 
  This interface should probably be segregated into two.</strike>  
* Add a new view to display the list of service types and their descriptions. 
* Allow multiple serviceTypes to be specified when filtering services (GUI+PI). 
* Maybe: When filtering sites/services/SGs via the GUI, add a new GUI component to
  select zero or more custom properties and allow a value to be optionally 
  specified for the property with an AND/NOT selection pull-down.  
  Using the multi-select pull-down, when a custom prop is selected, a new row is entered into a table
  which allows the user to specify a value for the prop and selects the AND/NOT option. 
  The user should be able to edit/delete the added rows. The values entered into 
  the table can then be used to build an extensions expression as is used in the PI.  
* More comprehensive change logging: https://rt.egi.eu/rt/Ticket/Display.html?id=9431 
* Automatic freshness of data check: https://rt.egi.eu/rt/Ticket/Display.html?id=8240  
* Introduce automatic paging in the PI queries so that a PI call without filter params won't timeout 
  due to an excessive result-set size (esp get_service_endpoint() and get_service()).
  * Update: with v5.7, paging is optional on all queries. To enforce default paging, 
  specify defaultPaging = true in htdocs\PI\index.php. 
  * Done:<strike>Introduce a configurable default page size, for some useful background see: 
  https://developer.github.com/guides/traversing-with-pagination/</strike>   


## Maybe Todo 
* Add LoA attribute to AuthToken details  
* Add filtering of resources by 'project' ?  
* Add 'project' URL param to PI get_project, get_site, get_service, get_downtime ? 
* Introduce READ action for roles? - currently, once a user is authenticated, all info can 
  be viewed in GOCDB. We may need fine-grained READ and content-rendering permissions 
  based on user roles. 



