#!/bin/bash
cd ..
echo "Deploying a database [forces drop re-create of DB tables]"
read -p "This operation will drop all data in the database, are you sure you want to continue? (Y/N): " -n 1 -r
if [[ $REPLY =~ ^[Yy]$ ]]
then
	# These command lines require that the following files exist: 
	# bootstrap.php  
	# cli-config.php
    echo ""
    output=$(uname -s | grep -i "cygwin")
    if [ $? -eq 0 ] ; then
        echo "invoking doctrine.bat"
        doctrine.bat orm:schema-tool:drop --force
        doctrine.bat orm:schema-tool:create
    else
        echo "invoking doctrine (no .bat extension)"
        doctrine orm:schema-tool:drop --force
        doctrine orm:schema-tool:create
    fi
	exit 1
else
    echo ""
	exit 2
fi
cd deploy
