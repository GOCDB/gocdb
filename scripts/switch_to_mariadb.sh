#!/bin/bash

# Script used for swapping GOCDB database connections to MariaDB
# No changes made if already configured for MariaDB

# Requires:
    # /usr/share/GOCDB5/lib/Doctrine/bootstrap_doctrine.php
    # /etc/gocdb/maria_database_connection.php containing MariaDB connection details
    # ./current_db.sh containing echo of current database

echo "Switching to MariaDB"

# Comment out OracleSessionInit in bootstrap_doctrine.php
search="use\ Doctrine\\\\DBAL\\\\Event\\\\Listeners\\\\OracleSessionInit;"
replace="\/\/use\ Doctrine\\\\DBAL\\\\Event\\\\Listeners\\\\OracleSessionInit;"
sed -i "s/^$search/$replace/" /usr/share/GOCDB5/lib/Doctrine/bootstrap_doctrine.php

# Replace database_connection.php with maria_database_connection.php in bootstrap_doctrine.php
search="\/etc\/gocdb\/database_connection.php"
replace="\/etc\/gocdb\/maria_database_connection.php"
sed -i "s/$search/$replace/" /usr/share/GOCDB5/lib/Doctrine/bootstrap_doctrine.php

# Update text echoed by current_db.sh to show current DB is MariaDB
sed -i "s/Oracle/MariaDB/" ./current_db.sh

# Restart Apache
systemctl reload httpd