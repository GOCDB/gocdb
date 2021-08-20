#!/bin/bash

# Script to set up directories and calls all other scripts used for comparing database API outputs

# Names of required directories are defined by $dirs and passed to all other scripts

# Directories that must exist (created if not):
    # temp: stores temporary text files e.g. lists of XML files
    # oracle_xml: stores XML results from accessing Oracle's API methods
    # mariadb_xml: stores XML results from accessing MariaDB's API methods
    # diff_xml: stores text files resulting from the diff of pairs of XML files

# Directories that may exist (created if needed):
    # oracle_xml_reduced: stores oracle_xml files with certain tags cut out
    # mariadb_xml_reduced: stores mariadb_xml files with certain tags cut out

# If true, diff "reduced" files which have tags (e.g. GOCDB_PORTAL_URL) removed
reduced_xml=${reduced_xml:-false}

# Define flags
while test $# -gt 0; do
    case "$1" in
        -h|--help)
            echo "options:"
            echo "-h, --help           show brief help"
            echo "--reduced            diff XML files with certain tags moved if set to true"
            exit 0
            ;;
        --reduced=*)
            reduced_xml="${1#*=}"
            shift
            ;;
        *)
            echo Invalid flag: "$1"
            exit 1
            ;;
    esac
done

# Directories that must always exist
dirs=('temp' 'oracle_xml' 'mariadb_xml' 'diff_xml')

# Directories must exist if using "reduced" XML
if $reduced_xml; then
    dirs+=('oracle_xml_reduced' 'mariadb_xml_reduced')
fi

# Create directories if necessary
for i in "${!dirs[@]}"
do
    if [ ! -d "${dirs[i]}" ]; then
        echo "Creating directory ${dirs[i]}"
        mkdir ./${dirs[i]}
        echo "Directory created"
    fi
done

# Get XML files and save in directories defined by $dirs
xml_flags="--oracle_dir=${dirs[1]} --mariadb_dir=${dirs[2]}"
echo
echo Calling get_xml.sh
echo Options: $xml_flags
echo
./get_xml.sh $xml_flags

# Reduce XML files and save in directories defined by $dirs
if $reduced_xml; then
    remove_flags="--temp_dir=${dirs[0]} --oracle_dir=${dirs[1]} --mariadb_dir=${dirs[2]}"
    remove_flags+=" --oracle_reduced_dir=${dirs[4]} --mariadb_reduced_dir=${dirs[5]}"
    echo
    echo Calling remove_tags.sh
    echo Options: $remove_flags
    echo
    ./remove_tags.sh $remove_flags

    diff_flags="--oracle_reduced_dir=${dirs[4]} --mariadb_reduced_dir=${dirs[5]}"

else
    diff_flags="--oracle_dir=${dirs[1]} --mariadb_dir=${dirs[2]}"
fi

diff_flags+=" --temp_dir=${dirs[0]} --diff_dir=${dirs[3]} --reduced=${reduced_xml}"

# diff paris of XML files and save in directory defined by $dirs
echo
echo Calling get_diff.sh
echo Options: $diff_flags
echo
./get_diff.sh $diff_flags

# Count differences in XML files from length of diff files
count_flags="--temp_dir=${dirs[0]} --oracle_dir=${dirs[1]} --mariadb_dir=${dirs[2]} --diff_dir=${dirs[3]}"
echo
echo Calling line_count.sh
echo Options: $count_flags
echo
./line_count.sh $count_flags