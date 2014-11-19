#!/bin/bash

usage()
{
    printf "
	Warning: Using this script will drop all current data held in your database and deploy a new schema.
	\nusage: options: <-n|-s>\n Supply -n to deploy a new un-populated GocDB\n Supply -s to deploy GocDB with sample data for familiarization\n"
}

deployNew()
{
    echo "Recreating DB:"; ./recreate.sh
	echo "Inserting requiredData:"; ./deployNew.sh requiredData/
	echo ""
	echo "------------------New Instance of GocDB created------------------"
	echo ""
	echo "A new empty instance of gocdb has been created."; 
    echo "Please update your local_info.xml for your deployment."
}

deploySample()
{
    echo "Recreating DB:"; ./recreate.sh
    echo "Inserting requiredData"; ./deployNew.sh requiredData/
	echo "Inserting sampleData"; ./deploySample.sh sampleData/	
	echo ""
	echo "------------------Sample Instance of GocDB created------------------"
	echo ""
	echo "A sample instance of gocdb has been created with a example data." 
    echo "Please update your local_info.xml for your deployment."
}

echo ""

new=0
sample=0

while getopts ":sn" option
do
    case $option in
        n) deployNew; new=1;;
        s) deploySample; sample=1;;
        \?) echo "Unknown option: -$OPTARG"; usage; exit 1;;
    esac
done

if [ $new -eq $sample ];
then
	echo "Error: No flag supplied"; usage; exit 1;
fi

exit 2;
