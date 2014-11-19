#!/bin/bash
#./recreate.sh

if [ $? == 2 ]
then
	echo ""
	echo ""
	echo "-----------Deployment cancelled-----------"
else
	echo ""

	echo -n "Adding Projects..."
	php AddProjects.php
	echo "Ok"

	echo -n "Adding Scopes..."
	php AddScopes.php $1
	echo "Ok"

	echo -n "Adding NGIs..."
	php AddNGIs.php $1
	echo "Ok"

    # Commented out sections below are fixture/required data and so are 
    # called from deployNew.sh script.    

	#echo -n "Adding Infrastructures..."
	#php AddInfrastructures.php $1
	#echo "Ok"

	#echo -n "Adding Certification Statuses..."
	#php AddCertificationStatuses.php $1
	#echo "Ok"

	#echo -n "Adding Countries..."
	#php AddCountries.php $1
	#echo "Ok"

	#echo -n "Adding Timezones..."
	#php AddTimezones.php $1
	#echo "Ok"

	#echo -n "Adding Tiers..."
	#php AddTiers.php $1
	#echo "Ok"

	echo -n "Adding Sites and JOINING associations..."
	php AddSites.php $1
	echo "Ok"

	#echo -n "Adding Service Types..."
	#php AddServiceTypes.php $1
	#echo "Ok"

	echo -n "Adding Service Endpoints, EndpointLocations and JOINING associations..."
	php AddServiceEndpoints.php $1
	echo "Ok"

	echo -n "Adding Users..."
	php AddUsers.php $1
	echo "Ok"

	#echo -n "Adding Role Types..."
	#php AddRoleTypes.php $1
	#echo "Ok"

	echo -n "Adding Site level roles..."
	php AddSiteRoles.php $1
	echo "Ok"

	echo -n "Adding NGI level roles..."
	php AddGroupRoles.php $1
	echo "Ok"

	echo -n "Adding EGI level roles..."
	php AddEgiRoles.php $1
	echo "Ok"

    # Since multiple endpoints version, the script/data to add downtimes 
    # needs work/fixing. 
	#echo -n "Adding Downtimes..."
	#php AddDowntimes.php $1
	#echo "Ok"

fi
