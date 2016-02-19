# GOCDB Src and Packages
A description of the main dir structure and packages is given below:

```
config/                    # Main config dir
docs/                      # docs dir 
htdocs/                    # Dynamically served portal content
    landing/               # Landing page, no auth needed
    PI/                    # Rest API queries 
        index.php          # **REST API FRONT CONTROLLER** routes all REST API requests  
        private            # private API REST methods
        public             # public API REST methods 
        xmlschema          # schema files for XML output 
    web_portal/            # Web UI root 
        components/        # Page rendering components
        controllers/       # Individual page-controllers (called from front controller)
        css/               # CSS assets 
        GocContextPath.php  
        GOCDB_monitor/     # Content for monitoring URL/endpoint
        img/               # Image assets 
        index.php          # **UI FRONT CONTROLLER** - routes all UI page requests
        javascript/        # JS assests 
        static_html/       
        static_php/
        views/             # Views for individual pages (called from page controllers)  
lib/
    Authentication/        # Auth lib/package
    DAOs/                  # Non-transactional Data Access Objects
    Doctrine/              # ORM package for domain/entity model and DB connection
    Gocdb_Services/        # Transactional Service Facade 
    MonologConf.php/
resources/                 # Useful resources/scripts, not part of portal
tests/                     # Test dir 
unused_resources/          # Archived stuff 
vendor/                    # 3rd party dependencies (excluded from .git) 

 
```

## Front Controllers
GOCDB has two main front controllers, the UI front controller (```htdocs/web_portal/index.php```) and the PI front controller (```htdocs/PI/index.php```). The role of a front controller is to intercept every request (either to UI or API), authenticate the user by invoking the authentication library, and to perform a URL mapping and route the request to the correct page controller.

## Page Controllers
Requests to specfic pages in GOCDB are routed to the relevant page controller from the front contoller. A page controller parses the request and prepares the view for rendering. The page controller typically peforms additional actions such as additional authorisation and invokes the service layer to fetch data from the DB. A page controller completes by invoking the relevant view and passes parameters down to the view for rendering. There is typcially one page controller per page/view (some page controllers handle multiple pages).

## Views
A view template renders a page. They are normally invoked from a page controller. Views are categorised into directories for logically related views, e.g. a single dir contains all views for viewing/editing a downtime.

## Libs
The lib dir contains sub-dirs for different packages, such as the Authentication package and DAOs package.    
   