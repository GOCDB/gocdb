#!/bin/bash

#Script to count length of diff files
# Loops through each diff file, counting the number of lines
# Also counts the number of lines in the corresponding XML files
# Outputs the lengths of each diff files, counting the number of length 0

#Requires:
    # Directories for temp, oracle_dir, mariadb_dir and diff_dir
    # XML files in oracle_dir and mariadb_dir

# Default directory for temporary text files
temp_dir=${temp_dir:-temp}

# Default directories where XML is saved
oracle_dir=${oracle_dir:-oracle_xml}
mariadb_dir=${mariadb_dir:-mariadb_xml}

# Default directory for diffs of XML files to be saved
diff_dir=${diff_dir:-diff_xml}

# Add methods and file names to arrays based on flags
while test $# -gt 0; do
    case "$1" in
        -h|--help)
            echo "options:"
            echo "-h, --help                show brief help"
            echo "--temp_dir                temporary directory for text files"
            echo "--oracle_dir              directory where Oracle XML files are saved"
            echo "--mariadb_dir             directory where MariaDB XML files are saved"
            echo "--diff_dir                directory where diffs of XML files are saved"
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

# Temporary text file for list of diff files
diff_list=${temp_dir}/diffs.txt

# Create and count list of diffs
ls $diff_dir > $diff_list
length=`wc -l < $diff_list`

# Count files with no differences
num_files_pass=0
num_files_invalid=0

# Loop through diffs
for (( i = 1; i <= $length; i++ ))
do
    # Get each diff file and its length
    diff_file=`head -$i $diff_list | tail -1`
    diff_lines=`wc -l < ${diff_dir}/$diff_file`

    if [[ ${diff_lines} = 0 ]]; then
        num_files_pass=$((num_files_pass+1))
    fi

    # Get XML file name
    xml_file=`basename ${diff_dir}/$diff_file | cut -f 1 -d .`

    # Get length of XML files
    total_lines_oracle=`wc -l < ${oracle_dir}/${xml_file}.xml`
    total_lines_mariadb=`wc -l < ${mariadb_dir}/${xml_file}.xml`

    # Check XML files are non-zero in length
    if [[ ${total_lines_oracle} = 0 || ${total_lines_mariadb} = 0 ]]; then
        num_files_invalid=$((num_files_invalid+1))
        echo $diff_file
    else
        # Approx percentage difference (returns integer)
        percent=$((100 * $diff_lines / $total_lines_oracle))
        printf "\r%30s (%6d lines): %5d (%3d%%) lines different\n" $diff_file $total_lines_oracle $diff_lines $percent
    fi

done

num_files_fail=$((length - num_files_pass))

echo
echo Invalid files: $num_files_invalid
echo Files OK: $num_files_pass
echo Files with differences: $num_files_fail

rm $diff_list