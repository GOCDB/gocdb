#!/bin/bash

# Script to carry out diffs between all pairs of XML files
# Loops through each pair of XML files, performing and saving a diff
# Outputs the number of diffs carried out successfully

# A string to sanity check the XML download success, defined by $search_string

#Requires:
    # Directories for temp, oracle_dir/oracle_reduced_dir, mariadb_dir/mariadb_reduced_dir and diff_dir
    # XML files in oracle_dir/oracle_reduced_dir and mariadb_dir/mariadb_reduced_dir

# If true, diff "reduced" files which have tags (e.g. GOCDB_PORTAL_URL) removed
reduced_xml=${reduced_xml:-false}

# Default directories where XML is saved
oracle_dir=${oracle_dir:-oracle_xml}
mariadb_dir=${mariadb_dir:-mariadb_xml}

# Default directories where "reduced" XML is saved
oracle_reduced_dir=${oracle_reduced_dir:-oracle_xml_reduced}
mariadb_reduced_dir=${mariadb_reduced_dir:-mariadb_xml_reduced}

# Default directory for diffs of XML files to be saved
diff_dir=${diff_dir:-diff_xml}

# Default directory for temporary text files
temp_dir=${temp_dir:-temp}

# String to check XML files look ok
search_string="<?xml version=\"1.0\" encoding=\"UTF-8\"?>"

# Define flags
while test $# -gt 0; do
    case "$1" in
        -h|--help)
            echo "options:"
            echo "-h, --help                show brief help"
            echo "--reduced                 diff reduced XML files, which certain tags removed"
            echo "--temp_dir                temporary directory for text files"
            echo "--oracle_dir              directory for Oracle XML files are saved"
            echo "--mariadb_dir             directory where MariaDB XML files are saved"
            echo "--oracle_reduced_dir      directory where reduced Oracle XML files are saved"
            echo "--mariadb_reduced_dir     directory where reduced MariaDB XML files are saved"
            echo "--diff_dir                directory for diff of XML files"
            exit 0
            ;;
        --reduced=*)
            reduced_xml="${1#*=}"
            shift
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
        --diff_dir=*)
            diff_dir="${1#*=}"
            shift
            ;;
        *)
            echo Invalid flag: "$1"
            exit 1
            ;;
    esac
done

# Directories where XML is saved
if $reduced_xml; then
    oracle_dir=$oracle_reduced_dir
    mariadb_dir=$mariadb_reduced_dir
fi

# Number of diff files created
diff_count=0

# Array for diff files that were not created
arr_file_failures=()

# Temporary text file for list of XML files
xml_list=${temp_dir}/xmls.txt

# Create and count list of XML files
ls $oracle_dir > $xml_list
length=`wc -l < $xml_list`

# Loop through each XML file
for (( i = 1; i <= $length; i++ ))
do
    # Get XML file name
    xml_file=`head -$i $xml_list | tail -1`

    # Get path for Oracle and MariaDB XML files
    oracle_xml_file=${oracle_dir}/${xml_file}
    mariadb_xml_file=${mariadb_dir}/${xml_file}

    # Get XML file name
    xml_file_no_extension=`basename $xml_file | cut -f 1 -d .`
    diff_file=${diff_dir}/${xml_file_no_extension}.txt

    # Check if XML file contains the search string for basic validation
    if grep -q "$search_string" "$oracle_xml_file" && grep -q "$search_string" "$mariadb_xml_file"; then
        diff $oracle_xml_file $mariadb_xml_file > $diff_file
        diff_count=$((diff_count+1))
        echo $xml_file diff performed!
    else
        echo "Error: Cannot perform diff for $xml_file"
        arr_file_failures+=($diff_file)
    fi

done

echo
echo diffs created successfully: $diff_count
echo

if [[ ${#arr_file_failures[@]} = 0 ]]; then
    echo All diffs successful!
else
    echo XML download failures:
    for i in "${!arr_file_failures[@]}"
    do
        echo ${arr_file_failures[i]}
    done
fi

rm $xml_list