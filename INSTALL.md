Prerequisites and Deployment
===========================
This file is best viewed using a browser-plugin for markdown `.md` files.

* [Prerequisites](#prerequisites)
* [Deployment](#deployment)
* [First Use Config](#firstuse)
* [Updating the DB Schema](#updateddl)
* [Updating From Old Versions](#versionupdate)

## Prerequisites <a id="prerequisites"></a>

* [GOCDB website content](#gocdb-website-content-)
  * 'Git-cloned' or by archive from https://github.com/GOCDB/gocdb

* [PHP](#php)
  * v5.3.3 (newer versions should be fine, but are untested)
  * If using Oracle: PHP oci8 extension (needs to be compiled using the Oracle Instant client v10 or higher
    downloadable from Oracle website, see "Compiling OCI8" section below).
  * libxml2 and DOM support for PHP (Note: On RHEL, PHP requires the php-xml RPM to be installed for this component to function).
  * OpenSSL Extension for PHP

* [Apache Http](#apache-and-x509-host-cert)
  * Version 2.2 or higher with `mod_ssl` module
  * X509 host certificate.

* [Database server](#database-server)
  * Oracle 11g+ or MariaDB/MySQL
    * (note: the free Oracle 11g XE Express Editions which comes with a free license is perfectly suitable)
  * MariaDB/MySQL
    * package `mariadb-server`

* [Doctrine and DBAL](#doctrine)
  * doctrine 2.4.8 (newer versions should be fine but are untested)
  * Note, for doctine 2.3.3 and older there is a bug in the paging code, which affects the GetDowntime API result. The fix is detailed [below](#doctrineFix)
  * dbal 2.5.4 - a DB abstraction layer that doctine depends on (newer versions should be fine but are untested)

* PhpUnit and PDO driver for selected DB (optional, required for running DBUnit tests only, see `tests/README.md` for more info)

### GOCDB website content <a id="gocdb"></a>

GOCDB web content and configuration - html,css,php etc. and configuration samples should be downloaded from [Github](https://github.com/GOCDB/gocdb) either by 'git-cloning' or from a downloaded archive. The standard location is under /usr/share/gocdb e.g.-

```bash
cd /usr/share
git-clone https://github.com/GOCDB/gocdb.git
```

### PHP

Php needs to be installed and configured to run under apache and on the command
line. A sample configuration is copied below:

```
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
```
If you are planning to use Oracle, compile and install the php oci8 extension as described [below](#compilinginstalling-oci8).</br>
oci8 is not required for MariaDB/MySQL
```
$ php -i | grep -i oci8
oci8
OCI8 Support => enabled

$ php -i | grep DOM
DOM/XML => enabled
DOM/XML API Version => 20031129
```
The PDO driver for MariaDB/MySQL is provided by package `php-mysql`
```
$ php -i | grep PDO
PDO
PDO support => enabled
PDO drivers => mysql, oci, sqlite
PDO Driver for MySQL => enabled
PDO_OCI
PDO Driver for OCI 8 and later => enabled
PDO Driver for SQLite 3.x => enabled

$ php -i | grep 'timezone'
Default timezone => Europe/London
timezonedb
```

Notes:

* Note, to keep PHP up to date with the IANA Olson timezone database, you will
need to periodically install/update `timezonedb.so|dll` as described at:
[daylight-saving best practices](http://stackoverflow.com/questions/2532729/daylight-saving-time-and-time-zone-best-practices).
To do this, download the timezonedb lib to your extensions directory and
update your php.ini by adding `extension=[php_]timezonedb.so|dll` (note, Win prefix `php_`).
* All dates are stored as UTC in the DB and converted from local timezones.
* Do not forget to configure your timezone settings correctly.

### Apache and x509 Host cert <a id="apache"></a>

A sample Apache config file is provided `config/gocdbssl.conf`. This file
defines a sample apache virtual host for serving your GocDB portal, including URL mappings/aliases and SSL settings.
For GocDB, three URL alias/directory-mappings are needed, one for the portal GUI page-controller, one for the public REST endpoints and one for the private REST endpoints. See the sample config file for details.

Note that, depending on Apache/httpd version, the "Require all granted" statements in gocdbssl.conf may cause an HTTP Error "500 - Invalid configuration..." and can be commented out.

### Database Server
GOCDB uses a DB abstraction layer (Doctrine) and with some configuration should be deployable on different RDBMS platforms that are supported for Doctrine. Instructions are provided here for Oracle (the free Oracle 11g is perfectly suitable) and MySQL/MariaDB.

#### Oracle 11g
The free to use XE/11g Oracle DB can be used to host run GOCDB on Win/nix. To use Oracle on nix systems, the OCI8 extension/driver needs to be compiled and installed.

##### Compiling/Installing OCI8
The OCI8 extension/driver for php needs to be installed, see: http://php.net/oci8
This can be most easily installed with the free Oracle Instant Client libs which can be installed in a number of ways (http://php.net/manual/en/oci8.installation.php), but the most easy is via PECL as descibed below:

Install the basic, devel and sqlplus instantclient rpms from Oracle (http://www.oracle.com/technetwork/database/features/instant-client/index-097480.html) and install GCC, PHP dev and pear packages:

```bash
rpm -i oracle-instanclient*
yum install gcc php-pear php-devel
```

Optionally, set the pear http proxy if necessary:

```bash
pear config-set http_proxy http://pro.xy:port
```

Install the oci8 module using pecl:

```bash
pecl install oci8-2.0.10
```
This will download and compile the module, and place it in your php extension dir.

Add the ```extension=oci8.so``` line to your php.ini or create a configuration file:

```bash
echo 'extension=oci8.so' > /etc/php.d/oci8.ini
```

Confirm it is working with ```php -i | grep -i oci8```

#### MariaDB/MySQL
The following instructions are to set up a MariaDB/MySQL database for GOCDB. They have not been tested in a production enviroment and are currently intended for test instances only.

##### Install the MariaDB server
Install package `mariadb-server`, start the service, enable at boot and configure the root user with a password:
```
$ yum install mariadb-server
$ systemctl start mariadb
$ systemctl enable mariadb
$ mysql_secure_installation
```
If the database instance is not local to the GOCDB webserver host, ensure that port 3306 is open for connection.

You are now good to continue the installation.

### Doctrine <a id="doctrine"></a>

Install Doctrine ORM and DBAL using one of the methods below and make sure doctrine is available on the command
line. Note, Doctrine can be installed either globally using PEAR or as a project
specific dependency using composer.

#### Install Doctrine Via Composer (Recommended)

* See: [composer](https://getcomposer.org/)
* Download composer.phar into the GOCDB root directory.
  * If you are behind a proxy, you may need to set your `http_proxy` and `https_proxy` env vars e.g. (use SET on Win):

    ```bash
    export http_proxy=http://wwwcache.dl.ac.uk:8080
    export https_proxy=http://wwwcache.dl.ac.uk:8080
    ```
    Note, you may need to unset `https_proxy` and play with the `HTTPS_PROXY_REQUEST_FULLURI` value,
    see following links:
    * [http-proxy](https://getcomposer.org/doc/03-cli.md#http-proxy-or-http-proxy)
    * [composer stopped working behind proxy](https://github.com/composer/composer/issues/3611)
  * To run composer you can use `php composer.phar --version` or rename it, e.g. `mv composer.phar composer`
  * Use the `composer diag` option to test that it has connectivity to download packages (see below)
  * If you are on Windows you can simply download a .exe file to the GOCDB directory, execute it and make the appropriate change to your PATH variable (see [the composer website](https://getcomposer.org/doc/00-intro.md#installation-windows)). Alternativly, you can create a `composer.bat` file with the following content: `php "%~dp0composer.phar" %*`

    ```
    $ composer --version
    Composer version 1.0-dev (6d76142907fca2478a1b8867ee6c86b04a3f4ff5) 2015-04-30 10:04:17
    $
    $ composer diag
    ...blah...
    Checking git settings: OK
    Checking http connectivity to packagist: OK
    Checking https connectivity to packagist: OK
    Checking HTTP proxy: OK
    Checking HTTP proxy support for request_fulluri: OK
    Checking HTTPS proxy support for request_fulluri: OK
    Checking github.com rate limit: OK
    Checking disk free space: OK
    Checking composer version: OK
    ```

* The composer.json and lock files are provided for you so you can simply run `composer install`
* Running `composer install` will create a `vendor` directory in the GOCDB root
directory and will download doctrine into that dir.
* Add full path to your `vendor/bin` dir to your `$PATH` environment variable.

#### Install Doctrine Via Pear

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
  $
  $ echo 'to list channels'
  $ pear list-channels
  $
  $ echo 'to list packages installed in a particular channel:'
  $ pear list -c pear.doctrine-project.org
  $
  $ echo 'to uninstall a package'
  $ pear uninstall pear.doctrine-project.org/DoctrineORM
  ```

#### Check Doctrine installation

  Ensure that your `$PATH` environment variable is updated to run the doctrine command line client (which must be run from the `Doctrine` directory). Assuming the default location of `/usr/share/gocdb` was used, use the following commands to check the installation -

  ```bash
  $ cd /usr/share/gocdb
  $ export PATH=$PATH:`pwd`/vendor/bin
  $ cd lib/Doctrine
  $ doctrine --version
  Doctrine Command Line Interface version 2.4.8
  $ doctrine-dbal --version
  Doctrine Command Line Interface version 2.5.4
  ```

#### Paginator fix <a id="doctrineFix"></a>

When using doctrine 2.3.3 on an oracle database, returning an ordered list of results using the Paginator will not honour the specified ordering. e.g. instead of returning the 100 most recent downtimes when using `orderby START_TIME descending`, it will return the first hundred downtimes in the table, which have then been ordered by start_time descending. See https://github.com/doctrine/doctrine2/issues/2456 for more details.

The fix involves editing the file `/vendor/doctrine/orm/lib/Doctrine/ORM/Tools/Pagination/LimitSubqueryOutputWalker.php`. The fix is detailed in this pull request: https://github.com/doctrine/doctrine2/pull/645/files

However only the two changes at line 17 (adding  `use Doctrine\DBAL\Platforms\OraclePlatform;`) and 144 (adding `|| $this->platform instanceof OraclePlatform` to the if conditonal) are needed.

---

## Deployment <a id="deployment"></a>

Once you have all the pre-requisites installed, you are ready to move ahead with the
deployment of your GOCDB instance:

* [Create DB User/Account](#create-db)
* [Configure Doctrine DB Connection](#configure-doctrine-db)
* [Deploy Tables/Schema via Doctrine](#deploy-schema)
* [Deploy Required Data](#deploy-required-data)
* [Deploy Sample Data](#deploy-sample-data) (optional)

### Create DB User/Account <a id="create-db"></a>
#### Oracle

If you intend to populate the database from a dump of an existing GOCDB5 instance you do NOT need to create the GOCDB5 user. Simply deploy the data as described at ["Deploy and existing DB"](#deploy-existing-dump) below remembering that you might want to ALTER the password for the GOCDB5 user after the import.

Create a dedicated GOCDB5 user using the following script (substitute GOCDB5 for your username and a secure password). Run this script as the Oracle admin/system user:

```
-- Manage GOCDB5 user if already exists (optional) --
drop user gocdb5 cascade;

-- CREATE USER SQL
CREATE USER GOCDB5 IDENTIFIED BY <PASSWORD>
DEFAULT TABLESPACE "USERS"
QUOTA UNLIMITED ON "USERS"
TEMPORARY TABLESPACE "TEMP";
-- ROLES - GRANT "RESOURCE" TO GOCDB5
-- SYSTEM PRIVILEGES
GRANT CREATE TRIGGER TO GOCDB5 ;
GRANT CREATE SEQUENCE TO GOCDB5 ;
GRANT CREATE TABLE TO GOCDB5 ;
GRANT CREATE JOB TO GOCDB5 ;
GRANT CREATE PROCEDURE TO GOCDB5 ;
GRANT CREATE TYPE TO GOCDB5 ;
GRANT CREATE SESSION TO GOCDB5 ;
```

If you are using sqlplus to connect to the database remotely you will need also -

```
GRANT CONNECT TO GOCDB5;
```

By default, Oracle 11g will expire a password in 180 days. In previous versions
of Oracle, the default policy was UNLIMITED, so please be aware of this change!
As a system user, you can see your password expiry settings by looking at the
PASSWORD_LIFE_TIME and PASSWORD_GRACE_TIME parameters in the DBA_PROFILES table:

```
-- select the profile for the GOCDB user (e.g. will return DEFAULT)
SELECT profile FROM dba_users WHERE username = 'GOCDB5';

-- select the password expiry settings for the profile assigned to the GOCDB5 user
SELECT resource_name, resource_type, limit FROM dba_profiles WHERE profile='DEFAULT';
```

If you prefer, you can update the default expiry from 180days to UNLIMITED using
the following (assumng GOCDB5 user profile is DEFAULT):

```
-- requires system privilege
ALTER PROFILE DEFAULT LIMIT PASSWORD_LIFE_TIME UNLIMITED;
```

#### MySQL

##### Create the GOCDB database and user
On the MariaDB server, using the root user and password assigned in the previous step:
```
$ mysql -u root -p <<EOF
> CREATE DATABASE gocdb DEFAULT CHARACTER SET utf8 DEFAULT COLLATE utf8_bin;
> CREATE USER 'GOCDB5'@'WEBSERVER-HOSTNAME-HERE' IDENTIFIED BY 'PASSWORD-HERE';
> GRANT ALL PRIVILEGES ON gocdb.* TO 'GOCDB5'@'WEBSERVER-HOSTNAME-HERE';
> FLUSH PRIVILEGES;
> EOF
```
* **_These permissions are wider than is strictly required and deployment into a production environment would first require revising these permissions more in line with those given for Oracle in the set up instructions._**
* If the database instance is local to the GOCDB webserver host, use 'localhost' for 'WEBSERVER-HOSTNAME-HERE'
`
##### Test client/webserver access
Optionally, but recommended, on the GOCDB webserver host, install the MariaDB client tools:
```
$ yum install mariadb
```
Check connectivity from the client:
```
$ mysql -u GOCDB5 -h WEBSERVER-HOSTNAME-HERE -p <<EOF
> show databases;
> EOF
```
The client should connect to the database and show the 'gocdb' database in the response from the server.</br>
The `mariadb` package can, optionally, be uninstalled after testing the connection.

### Configure Doctrine DB Connection <a id="configure-doctrine-db"></a>

The database schema is deployed to your database using Doctrine.

* Navigate to the `lib/Doctrine` folder (e.g. /usr/share/gocdb/lib/Doctrine).
* Copy the file `bootstrap_doctrine_TEMPLATE.php` to `bootstrap_doctrine.php` in the same directory.
* Modify `bootstrap_doctrine.php`:
* Comment out the "die" statement at the top of the file.
* For MariaDB/MySQL comment out the line -</br>`use Doctrine\DBAL\Event\Listeners\OracleSessionInit;`</br>- also at the top of the file.
* Uncomment one of the three blocks of code relevant to the target database - SQLite, Oracle or MariaDB/MySQL.
* Optionally, follow the instructions in the file for how to compile Doctrine proxy objects for better performance (for production usage).
* Check that doctrine can connect to the DB running the following (still in the Doctrine directory):

  ```
  $ doctrine orm:schema-tool:update
  ```

### Deploy Tables/Schema via Doctrine<a id="deploy-schema"></a>

Use the doctrine command line tool to test doctrine's connection to the DB, then
drop and create the DB schema using the `doctrine orm:schema-tool`:

```
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

#### Compiled Doctrine Proxies

For better performance, Doctrine automatically compiles the objects located
in `lib/Doctrine/entities` and places these compiled objects into a directory.
By default, these are compiled into the system's TMP dir. This is not recommended for production.
For production deployments, you can specify where these proxy objects should be stored
using `$config->setProxyDir('pathToYourProxyDir');` in your `bootstrap_doctrine.php` file.
If you specify the ProxyDir, then you also need to manually compile your proxy objects
into the specified ProxyDir using the doctrine command line:

```bash
$ cd lib/Doctrine
$ mkdir compiledEntities
$ doctrine orm:generate-proxies compiledEntities/
```

GocDB can then be deployed as a blank instance with only required lookup data or
as a sample instance with a small amount of example data to demonstrate GocDB.

### Deploy Required Data<a id="deploy-required-data"></a>

The required data includes common lookup values such as countries,
common service types, default role types, default site certification statuses.

```bash
$ cd lib/Doctrine
$ php deploy/DeployRequiredDataRunner.php requiredData
```

### OPTIONAL: Deploy Sample Data<a id="deploy-sample-data"></a>

You can choose to deploy some sample data to seed your DB with sample users,
sites and services. Two sample data sets are available. Choose one of -

1. Minimal - just enough to get going with no real-world associations.

    ```bash
    $ cd lib/Doctrine
    $ php deploy/DeploySampleDataRunner.php simpleSampleData
    ```
1. "Real World" - a small subset derived from real data.

    ```bash
    $ cd lib/Doctrine
    $ php deploy/DeploySampleDataRunner.php sampleData
    ```

### ORACLE ONLY: Deploy an existing DB .dmp file to populate your DB<a id="deploy-existing-dump"></a>

You may want to deploy an existing dump/backup of the DB rather than deploying the
DDL and seeding the empty DB with required data and sample data. Oracle provides the
`expdp` and `impdp` command line tools to export and import a `.dmp` file.
The impdp tool requires a directory object to have already been created in the DB.
This directory object defines the directory where the .dmp file is loaded from.

* Create a new directory object, as the system user:

  ```
  sqlplus system
  SQL> create or replace DIRECTORY dmpdir AS '<Directroy path>';
  SQL> exit
  ```

* Import your dmp file. Note, the example below assumes the 'gocdb5' user/schema does not exist in the db - the import actually creates this user with all its permissions/roles.
If you want to use a different schema/username, then specify this in the value of the remap_schema argument on the right of the colon.
You may need to change different arguments for your install such as modifying the remap_tablespace:

  ```
  $impdp system/******** schemas=gocdb5 directory=dmpdir dumpfile=goc5dump.dmp  REMAP_SCHEMA=gocdb5:gocdb5 remap_tablespace=GOCDB5:users  table_exists_action=replace logfile=gocdbv5deploy.log
  ```

  Note: If you get the following error, there is a file permissionsissue of some kind.
  Try creating a new directory for the dump-file, possibly within your Oracle directory.

  ```
  ORA-39002: invalid operation
  ORA-39070: Unable to open the log file.
  ORA-29283: invalid file operation
  ORA-06512: at "SYS.UTL_FILE", line 536
  ORA-29283: invalid file operation
  ```

* To generate statistics after importing the dmp file (this improves performance):

    ```
    SQL> EXEC DBMS_STATS.gather_schema_stats('GOCDB5');
    ```

impdp can export the DDL of a dmp backup for you so you can inspect it, see schema name, table names etc.
For example:

```
  impdp system/***** dumpfile=goc5dump.dmp logfile=import_log.txt sqlfile=ddl_dump.txt directory=dmpdir
```

To export an existing DB to create the `.dmp` file:

```
expdp system/****** schemas=gocdb5 dumpfile=gocdb5.dmp directory=dmpdir
```

## First Use Config <a id="firstuse"></a>

You should now be able to navigate to your GocDB webportal on your host using
the URL/alias mappings (see [gocdb apache config](#apache)).

```
https://localhost/portal
https://localhost/portal/GOCDB_monitor/index.php
```

### Local_Info.xml

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

### Setup Admin User

To get started with GocDB you will need an admin user. This is done by first
registering as a user on GocDB by clicking the 'Register' link on the left menu
bar at the bottom. Once you have registered yourself you will then need to set
yourself as an admin. To do this you need to change the user record directly in
your database (for security reasons, is the only way to setup an admin user).
The users table has a field called `isAdmin` which by default is set to 0.
To change a user to admin set this to 1. Below is a sample of the SQL query:

```
SQL> update Users set isadmin=1 where forename='John' and surname='Doe';
1 row updated.
SQL> commit;
Commit complete.
```

### PermitAll vs Protected Pages

There are two types of page in the web portal; permitAll pages which
do not require any user-authentication,
and protected pages which require user authentication. You can specify which
pages are permitAll and which are protected by editing `htdocs/web_portal/index.php`
and editing which URL page mappings use the `rejectIfNotAuthenticated()` invocation
(see the switch/case block in the index page for details).

### Authentication

Authentication is handled by the `lib/Authentication` package, and is configured
for x509 client certificate authentication by default. Different authentication
schemes can be configured using the abstractions in this package such as SAML2
for integration with Federated Identity Management.
See `lib/Authentication/README.md` for details.

## DBUnit Tests

A comprehensive test suite is provided and can be used to assert that the GocDB
runs as expected against your chosen DB. It is therefore recommended you run the DBUnit tests
before running a production GocDB instance. See `tests/README.md` for details.


## Updating the DB Schema <a id="updateddl"></a>

If you modify the `lib/Doctrine/entities` objects, to add new values for example,
you will need to update your DB schema. This can be done using the doctrine
command line tool (first stop GOCDB running so there are no active DB connections). For example:

```
$ cd lib/Doctrine
$ vim entities/Site.php     # modify the Site entity object, e.g. add a new 'timezoneId' value
$ doctrine orm:schema-tool:update --dump-sql
ALTER TABLE SITES ADD (timezoneId VARCHAR2(255) DEFAULT NULL)
```
For production environments you should now sanity check the outputted SQL before running it against your database using the relevant CLI tool. Alternatively, in testing environments, you can simply run the following to execute the code:

```
$ doctrine orm:schema-tool:update --force
Updating database schema...
Database schema updated successfully! "1" queries were executed
```

If you are using compiled entities, these will need regenerating

```
$ cd lib/Doctrine
$ rm -rf compiledEntities/*
$ doctrine orm:generate-proxies compiledEntities/
```

If you are using the unit tests, you will need to drop the existing tables and recreate them. See the "Deploy Tables/Schema via Doctrine" section of tests/README.md.

## Updating from old versions (5.2+) <a id="versionupdate"></a>

Newer releases of Gocdb require updating the DB schema and updating the legacy
data to be compliant with the newer version. A number of scripts are provided
to assist with this. Please see `lib/Doctrine/versionUpdateScripts/README.md` for details.
