Updating from previous versions of GOCDB
========================================
This file is best viewed using a markdown viewer/plugin in your browser. 

The DB schema will be updated periodically between versions. This directory 
contains scripts that are used to update your version of gocdb to the latest 
version. Updating from versions before v5.2 is **not** catered for.

1) First Always Create a clone of your DB
-----------------------------------------
Before running any of the update scripts described below, I **strongly** recommend 
that you clone your DB and run the update procedures on the clone. For example, 
if using OracleXE this can be done using the `impdp` and `expdb` command line tools, 
a sample is shown below: 

```bash
$ expdp system/******** schemas=gocdb dumpfile=gocdb.dmp directory=dmpdir
$ impdp SYSTEM/******** schemas=gocdb directory=dmpdir dumpfile=gocdb.dmp remap_tablespace=gocdb:users remap_schema=gocdb:gocdbClone logfile=importLog.txt
```

Note, the `dmpdir` is an oracle directory data object. 
You need to create this directory object first (one time only, run as SYSTEM user) using: 

```
SQL> create or replace directory DATA_PUMP_DIR as 'c:\oraclexe\app\tmp\dir\to\export\dmpfile'; 
SQL> grant read,write on directory DATA_PUMP_DIR to gocdb;
```

2) Then update the tables/schema of the DB clone
--------------------------------------------
* Download/configure the latest version of GOCDB to run against your newly cloned DB. 
See `<GOCDB_SRC>/INSTALL.md` for details. Do not yet attempt to run the 
GOCDB as you first need to update the DB schema to be compatible with the latest 
version, and then you will need to run the scripts detailed below to update the 
legacy data. 

* Run the Doctrine DDL schema update tool to update the DB tables/sequences: 

```bash
$ cd lib/Doctrine
$ doctrine orm:schema-tool:update --dump-sql
ALTER TABLE SITES ADD (timezoneId VARCHAR2(255) DEFAULT NULL)
...DDL that will be executed is printed here...
$
$ doctrine orm:schema-tool:update --force
Updating database schema...
Database schema updated successfully! "n" queries were executed 
```

Continue to below. 

3) Update from v5.2 to current
------------------------------
Version 5.3 introduced multiple endpoints per service. Therefore, after running 
the DDL update tool as described above, you will need to update the data 
to fit this new model. The `MEPS_update_scriptRunner.php` will 
perform this update. 

* Please see this file for details and how to run the script.   
* Continue to below. 

4) Update from v5.3 to current
-------------------------------
Version 5.4 introduced new Site timezoneId values and deprecated the legacy 
`Timezone.php` entity. The `TranserLegacySiteTimezoneRunner.php` script 
will perform this update. 

* Please see this file for details and how to run the script.   
