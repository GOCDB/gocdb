Full install instructions for GocDB V5 can be found at:

https://wiki.egi.eu/wiki/GOCDB/Regional_Module_Technical_Documentation




$doctrine orm:schema-tool:update --help
$...
$doctrine orm:schema-tool:update --dump-sql
ALTER TABLE ENDPOINTLOCATIONS MODIFY (description  VARCHAR2(2000) DEFAULT NULL)
$
$doctrine orm:schema-tool:update --force
Updating database schema...
Database schema updated successfully! "1" queries were executed


doctrine orm:generate-proxies compiledEntities/