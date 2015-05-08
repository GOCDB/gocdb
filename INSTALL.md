Prerequisites and Deployment
===========================
This file is best viewed using a browser-plugin for markdown `.md` files. 

* [Prerequisites](#prerequisites)
* [Deployment](#deployment)
* [First Use Config](#firstuse)

##Prerequisites <a id="prerequisites"></a>
* [PHP](#php) 
  * v5.3.3 (newer versions should be fine, but are untested)
  * If using Oracle: PHP oci8 extension (installed with Oracle Instant client v10 or higher 
    downloadable from Oracle website). 
  * libxml2 and DOM support for PHP (Note: On RHEL, PHP requires the PHP XML RPM to be installed for this component to function).
  * OpenSSL Extension for PHP 

* [Apache Http](#apache) 
  * Version 2.2 or higher 
  * X509 host certificate. 

* Database server, Oracle 11g+ or MySQL 
  * (note: the free Oracle 11g XE Express Editions which comes with a free license is perfectly suitable)

* [Doctrine](#doctrine) 
  * 2.3.3 (newer versions should be fine but are untested)

* PhpUnit (optional, for running DBUnit tests only, see `tests/INSTALL.md` for more info) 
  * PDO driver for selected DB 



###PHP <a id="php"></a>  
Php needs to be installed and configured to run under apache and on the command 
line. A sample configuration is copied below. 
If you are planning to use Oracle, you need the php oci8 extension, which is included 
since php 5.3+, see: [php oci8](http://php.net/manual/en/book.oci8.php)  
```bash
$ php --version
PHP 5.3.3 (cli) (built: Oct 23 2014 06:58:46)
Copyright (c) 1997-2010 The PHP Group
Zend Engine v2.3.0, Copyright (c) 1998-2010 Zend Technologies

$ php --ini
Configuration File (php.ini) Path: /etc
Loaded Configuration File:         /etc/php.ini
Scan for additional .ini files in: /etc/php.d
Additional .ini files parsed:      /etc/php.d/curl.ini,
/etc/php.d/dom.ini,
...

$ php -i | grep 'extension_dir' 
extension_dir => /usr/lib64/php/modules/ => /usr/lib64/php/modules/

$ php -i | grep libxml2
libxml2 Version => 2.7.6

$ php -i | grep -i oci8
oci8
OCI8 Support => enabled

$ php -i | grep DOM
DOM/XML => enabled
DOM/XML API Version => 20031129

$ php -i | grep PDO
PDO
PDO support => enabled
PDO drivers => mysql, oci, sqlite
PDO Driver for MySQL => enabled
PDO_OCI
PDO Driver for OCI 8 and later => enabled
PDO Driver for SQLite 3.x => enabled
```

###Apache and x509 Host cert <a id="apache"></a> 
* A sample Apache config file is provided `config/gocdbssl.conf`. This file 
defines a sample apache virtual host for serving your GocDB portal, including 
URL mappings/aliases and SSL settings. 


###Doctrine <a id="doctrine"></a>   
Install Doctrine ORM 2.3.3+ and make sure doctrine is available on the command
line. Note, Doctrine can be installed either globally using PEAR or as a project
specific dependency using composer. Either way, ensure your `$PATH` environment 
variable is updated to run the doctrine command line client:    
```bash
$ doctrine --version
Doctrine Command Line Interface version 2.3.3
```
####Install Doctrine Via Composer (Recommended)
* See: [composer](https://getcomposer.org/)
* Download composer.phar into the GOCDB root directory. 
  * If you are behind a proxy, you may need to set your `http_proxy` and `https_proxy` env vars. 
  * If you are on Win, you can create a `composer.bat` file with the following content: `php "%~dp0composer.phar" %*`
```bash
$ composer --version
Composer version 1.0-dev (6d76142907fca2478a1b8867ee6c86b04a3f4ff5) 2015-04-30 10:04:17
```
* The composer.json and lock files are provided for you so you can simply run `composer install` 
* Running `composer install` will create a `vendor` directory in the GOCDB root 
directory and will download doctrine into that dir.  
* Add full path to your `vendor/bin` dir to your `$PATH` environment variable.    

####Install Doctrine Via Pear
* First install pear, which can be installed on RH systems using yum or manually, 
see: [pear installation](http://pear.php.net/manual/en/installation.getting.php)
* Add the Doctrine and Symfony channels to PEAR and install Doctrine, see: 
  * [doctrine config](http://docs.doctrine-project.org/en/latest/reference/configuration.html)
  * [pear project](http://pear.doctrine-project.org/)
```
$ pear version
PEAR Version: 1.9.4
PHP Version: 5.3.3
$ pear channel-discover pear.doctrine-project.org
$ pear channel-discover pear.symfony.com
$ pear install --alldeps doctrine/DoctrineORM
``` 

---

##Deployment <a id="deployment"></a> 
Once you have all the pre-requisites installed, you are ready to move ahead with the 
deployment of your GOCDB instance: 

* [Create DB User/Account](#create-db)  
* [Configure Doctrine DB Connection](#configure-doctrine-db) 
* [Deploy Tables/Schema via Doctrine](#deploy-schema)  
* [Deploy Required Data](#deploy-required-data)
* [Deploy Sample Data](#deploy-sample-data) (optional) 

### Create DB User/Account <a id="create-db"></a>
####Oracle
We advise that you create a dedicated GOCDB5 user. For Oracle, you can create 
the user with the following script (substitute GOCDB5 for your username and 
a sensible password). Run this script as the Oracle admin/system user: 
```
-- Manage GOCDB5 user if already exists (optional) --
drop user gocdb5 cascade;
ALTER USER gocdb5 IDENTIFIED BY new_password;

-- CREATE USER SQL
CREATE USER GOCDB5 IDENTIFIED BY <PASSWORD> 
DEFAULT TABLESPACE "USERS"
TEMPORARY TABLESPACE "TEMP";
-- ROLES
GRANT "RESOURCE" TO GOCDB5 ;
-- SYSTEM PRIVILEGES
GRANT CREATE TRIGGER TO GOCDB5 ;
GRANT CREATE SEQUENCE TO GOCDB5 ;
GRANT CREATE TABLE TO GOCDB5 ;
GRANT CREATE JOB TO GOCDB5 ;
GRANT CREATE PROCEDURE TO GOCDB5 ;
GRANT CREATE TYPE TO GOCDB5 ;
GRANT CREATE SESSION TO GOCDB5 ;
```
By default, Oracle 11g will expire a password in 180 days. In previous versions 
of Oracle, the default policy was UNLIMITED, so please be aware of this change! 
As a system user, you can see your password expiry settings by looking at the 
PASSWORD_LIFE_TIME and PASSWORD_GRACE_TIME parameters in the DBA_PROFILES table: 
```
-- select the profile for the GOCDB user (e.g. will return DEFAULT)
SELECT profile FROM dba_users WHERE username = 'GOCDB5'; 

-- select the password expiry settings for the profile assigned to the GOCDB5 user
select resource_name,resource_type, limit from dba_profiles where profile=DEFAULT; 
```
If you prefer, you can update the default expiry from 180days to UNLIMITED using 
the following (assumng GOCDB5 user profile is DEFAULT): 
```
-- requires system privilege
ALTER PROFILE DEFAULT LIMIT PASSWORD_LIFE_TIME UNLIMITED;
```

####MySQL
Coming soon

###Configure Doctrine DB Connection <a id="configure-doctrine-db"></a>
The database schema is deployed to your database using Doctrine. 

* Navigate to to `<gocDBSrcHome>/lib/Doctrine` folder
* Locate the provided template file: `bootstrap_doctrine_TEMPLATE.php`. In this 
file you will find three blocks of code commented out, once for each of the 
supported database, SQLite, Oracle and MySQL. 
* Copy this file to `bootstrap_doctrine.php` in the same dir and modify to 
specify your chosen DB connection details (see file for more details, including 
how to compile Doctrine proxy objects for better performance for production usage). 
* Check that doctrine can connect to the DB using: 
```
$ doctrine orm:schema-tool:update 
```

###Deploy Tables/Schema via Doctrine<a id="deploy-schema"></a>
Use the doctrine command line tool to test doctrine's connection to the DB, then
drop and create the DB schema using the `doctrine orm:schema-tool`: 
```bash
$ cd lib/Doctrine
$ doctrine orm:schema-tool:drop --dump-sql
... sql shown here
$ doctrine orm:schema-tool:drop --force
Database schema dropped successfully!
$ doctrine orm:schema-tool:create
Creating database schema...
Database schema created successfully!
```
Your tables and sequences should now have been created. 

GocDB can then be deployed as a blank instance with only required lookup data or 
as a sample instance with a small amount of example data to demonstrate GocDB.

###Deploy Required Data<a id="deploy-required-data"></a>
The required data includes common lookup values such as countries, timezones
common service types, default role types, default site certification statuses. 
```bash
$ cd lib/Doctrine
$ php deploy/DeployRequiredDataRunner.php requiredData
```

###Deploy Sample Data<a id="deploy-sample-data"></a>
Optional - you can deploy some sample data to seed your DB with sample users, 
sites and services. 
```bash
$ cd lib/Doctrine
$ php deploy/DeploySampleDataRunner.php sampleData 
```


##First Use Config <a id="firstuse"></a>
You should now be able to navigate to the GocDB webportal on your host using 
the URL/alias mappings. 
```
https://localhost/portal
https://localhost/portal/GOCDB_monitor/index.php
```

###Local_Info.xml
GocDB uses a number of its settings and variables from the `config/local_info.xml` 
file. The `web_portal_url` will be output in the PI to create links from PI 
objects back to Portal views. The `pi_url` and `server_base_url` will both be 
used by the monitor. 
The monitor is a quick look status check for GocDB. The monitor can be found at 
your `web_portal_url/GOCDB_monitor/`. If these URL's are not set in the local 
config this feature will not work correctly.

`default_scope` defines which scope if any will be used if no scope is 
specified when running PI queries. This can be left blank or set to a scope 
of your choosing. `default_scope_match` is again used if no `scope_match` URL 
parameter is supplied with PI queries. This can be either `all` or `any`. 
The `minimum_scopes` section declares how many scopes an NGI, site, service or 
service group must have at creation. This can be left as 0 or set as 1 if you 
want all your users entities to belong to at least one scope or more as dictated by your use of GocDB.
It's important at this point to understand how the scopes work with GocDB 
especially in relation to the output of the PI. If you specify a default scope 
within your local_info but none of your entities have this scope then nothing 
will show in the PI. For an in-depth look at the scopes mechanism the section please 
read the section 'Multiple Scopes and Projects' in the [GOCDB5 Grid Topology 
Information System document](https://wiki.egi.eu/w/images/d/d3/GOCDB5_Grid_Topology_Information_System.pdf).

###Setup Admin User
To get started with GocDB you will need an admin user. This is done by first 
registering as a user on GocDB by clicking the 'Register' link on the left menu 
bar at the bottom. Once you have registered yourself you will then need to set 
yourself as an admin. To do this you need to change the user record directly in 
your database (this is by design and is the only way to setup an admin user). 
The users table has a field called `isAdmin` which by default is set to 0. 
To change a user to admin set this to 1. Below is a sample of the SQL query:

```SQL
UPDATE users SET isadmin=1 WHERE id=X AND forename='John' AND surname='Doe' 
```

###PermitAll vs Protected Pages
There are two types of page in the web portal; permitAll pages which 
do not require any user-authentication, 
and protected pages which require user authentication. You can specify which 
pages are permitAll and which are protected by editing `htdocs/web_portal/index.php`
and editing which URL page mappings use the `rejectIfNotAuthenticated()` invocation 
(see the switch/case block in the index page for details).  

###Authentication 
Authentication is handled by the `lib/Authentication` package, and is configured
for x509 client certificate authentication by default. Different authentication 
schemes can be configured using the abstractions in this package such as SAML2. 
See `lib/Authentication/README.md` for details. 
 