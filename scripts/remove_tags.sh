#!/bin/bash

# Script to remove tags from XML files
# Loops through all XML files and removes lines containing the tag specified
# Currently only used for GOCDB_PORTAL_URL, but could be generalised

#Requires:
    # Directories for temp, oracle_reduced_dir and mariadb_reduced_dir
    # XML files in oracle_dir and mariadb_dir

# Default directory for temporary text files
temp_dir=${temp_dir:-temp}

# Default directories where XML is saved
oracle_dir=${oracle_dir:-oracle_xml}
mariadb_dir=${mariadb_dir:-mariadb_xml}

# Default directories where "reduced" XML is saved
oracle_reduced_dir=${oracle_reduced_dir:-oracle_xml_reduced}
mariadb_reduced_dir=${mariadb_reduced_dir:-mariadb_xml_reduced}

# Add methods and file names to arrays based on flags
while test $# -gt 0; do
    case "$1" in
        -h|--help)
            echo "options:"
            echo "-h, --help                show brief help"
            echo "--temp_dir                temporary directory for text files"
            echo "--oracle_dir              directory where Oracle XML files are saved"
            echo "--mariadb_dir             directory where MariaDB XML files are saved"
            echo "--oracle_reduced_dir      directory for reduced Oracle XML files"
            echo "--mariadb_reduced_dir     directory for reduced MariaDB XML files"
            exit 0
            ;;
        --temp_dir=*)
            temp_dir="${1#*=}"
            shift
            ;;
        --oracle_dir=*)
            oracle_dir="${1#*=}"
            shift
            ;;
        --mariadb_dir=*)
            mariadb_dir="${1#*=}"
            shift
            ;;
        --oracle_reduced_dir=*)
            oracle_reduced_dir="${1#*=}"
            shift
            ;;
        --mariadb_reduced_dir=*)
            mariadb_reduced_dir="${1#*=}"
            shift
            ;;
        *)
            echo Invalid flag: "$1"
            exit 1
            ;;
    esac
done

# Temporary text file for list of XML files
xml_list=${temp_dir}/xmls.txt

# Create of list of XML files
ls ${oracle_dir} > $xml_list
length=`wc -l < $xml_list`

# Loop through XML files
for (( i = 1; i <= $length; i++ ))
do
    # Get each diff file and its length
    xml_file=`head -$i $xml_list | tail -1`

    # Create copies of XML files
    cp ${oracle_dir}/$xml_file ${oracle_reduced_dir}/$xml_file
    cp ${mariadb_dir}/$xml_file ${mariadb_reduced_dir}/$xml_file

    # Cut out lines
    sed -i '/<GOCDB_PORTAL_URL>/d' ${oracle_reduced_dir}/$xml_file
    sed -i '/<GOCDB_PORTAL_URL>/d' ${mariadb_reduced_dir}/$xml_file

    # Get length of original and new XML files
    original_lines=`wc -l < ${oracle_dir}/${xml_file}`
    new_lines=`wc -l < ${oracle_reduced_dir}/${xml_file}`

    # Number of lines deleted
    if [[ ${new_lines} < ${original_lines} && ${new_lines} > 0 ]]; then
        deleted_lines=$((original_lines - new_lines))
        printf "\r%5d lines deleted for %s\n" $deleted_lines $xml_file
    fi
done

rm $xml_list