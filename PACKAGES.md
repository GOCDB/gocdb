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
