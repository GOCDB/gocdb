#!/bin/bash
#./recreate.sh

if [ $? == 2 ]
then
	echo ""
	echo ""
	echo "-----------Deployment cancelled-----------"
else
	echo ""

	echo -n "Adding Infrastructures..."
	php AddInfrastructures.php $1
	echo "Ok"

	echo -n "Adding Countries..."
	php AddCountries.php $1
	echo "Ok"

	echo -n "Adding Timezones..."
	php AddTimezones.php $1
	echo "Ok"

	echo -n "Adding Tiers..."
	php AddTiers.php $1
	echo "Ok"

	echo -n "Adding Role Types..."
	php AddRoleTypes.php $1
	echo "Ok"
	
	echo -n "Adding Certification Statuses..."
	php AddCertificationStatuses.php $1
	echo "Ok"
	
	echo -n "Adding Service Types..."
	php AddServiceTypes.php $1
	echo "Ok"	


fi
