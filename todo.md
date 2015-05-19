
## Todo
* Add LoA attribute to AuthToken 
* Show all/additional SAML attributes retrieved from IdP after successful user 
  authentication. Show attributes in view user page.  
* Modularise RoleLogic into one class and support RoleActionPermissions.xml
* Refactor page view/template logic and remove nasty menu/header/footer php 
  rendering logic (an inherited legacy) 
* Add instructions for deployment to MySQL 
* Add new regex and logic for filtering by custom properties (new regex is far
  better and can allow for any value, inc UTF8 in the property values) 
* Update role-approve notification email 
* Add new gocdb website on github
* Scope pull down multiple select (HTML5 multiple keyword on select).  
* Update the datetime picker to latest version and show the time in UTC 
  when confirming.  
* Improve the edit/define downtime logic in JS/Client (e.g. prevent extending DT 
  and updating the start date to a time in the past) 
* Allow downtime to affect services across multiple sites (currently DT 
  can only affect services from a single site) 
* Improve the downtime service selection GUI by showing some extra tags/info 
  to distinguish that particular service (show id or fist x chars of description)  

* Define downtime in sites local timezone 
* Replace timezone lookup table with php Olson timezonedb values and create 
  migration scripts (if a legacy timezone can't be mapped to new value, then 
  default to UTC). 

## Maybe Todo 
* Support returning multiple AuthTokens e.g. in SecurityContext and in 
  token resolution process? 
* Add filtering of resources by 'project' 
* Add 'project' URL param to PI get_project, get_site, get_service, get_downtime
* Add <scopes> elements to PI output
