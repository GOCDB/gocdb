API Comparison Scripts
===========================

* [Setup](#setup)
* [Running all scripts](#allscripts)
* [Running individual scripts](#individualscripts)
* [Notes](#notes)

## Setup <a id="setup"></a>
* URLs for two GOCDB hosts must be defined in `get_xml.sh` by `oracle_URL` and `mariadb_URL`
* A certificate and private key must be placed in `/etc/grid-security/hostcert/`, for accessing protected API methods
* Lists of API methods, their protection levels, and unique file names in `get_xml.sh` should be updated
  * Defined by `arr_methods_all`, `arr_permissions_all` and `arr_files_all`, and/or individual flags
  * Includes updating `get_downtime` and `get_downtime_nested_services`, which are filtered by date

Also required when comparing databases connected to the same host as the comparison scripts being run:

* `oracle_URL` and `mariadb_URL` set equal, to trigger "switching" between databases
* Database connection files for the two databases, with paths defined in `switch_to_oracle.sh` and `switch_to_mariadb.sh`
  * Current paths: `/etc/gocdb/database_connection.php` and `/etc/gocdb/maria_database_connection.php`
* `bootstrap_doctrine.php`, with path defined in `switch_to_oracle.sh` and `switch_to_mariadb.sh`
  * Current path: `/usr/share/GOCDB5/lib/Doctrine/bootstrap_doctrine.php`
* To allow "switching", `bootstrap_doctrine.php` must contain the following lines (or equivalent):
  * `include "/etc/gocdb/database_connection.php";` or `include "/etc/gocdb/maria_database_connection.php";`
  * `use Doctrine\DBAL\Event\Listeners\OracleSessionInit;` or `//use Doctrine\DBAL\Event\Listeners\OracleSessionInit;`

## Running all scripts <a id="allscripts"></a>
* The recommended way to perform a comparison is via `run_scripts.sh`
* Paths to other scripts are relative (`run_scripts.sh` is currently expected to be run from within this directory)
* Directories required will be created if necessary, with names defined in `run_scripts.sh` and passed to scripts called
* `run_scripts.sh` has two modes, defined by the presence or absence of `--reduced`:

1. `run_scripts.sh` (recommended)
    * Suitable when comparing databases connected to the same host as the comparison scripts being run
    * This allows every line of each pair of XML files to be compared
    * Calls `get_xml.sh`, `get_diff.sh` and `line_count.sh`
    * `switch_to_oracle.sh` and `switch_to_mariadb.sh` will also be called by `get_xml.sh`

2. `run_scripts.sh --reduced`
    * Necessary when comparing databases on different hosts, as their different URLs will appear as differences
    * This will remove lines containing the `GOCDB_PORTAL_URL` tag from XML files before a diff is performed
    * Calls `get_xml.sh`, `remove_tags.sh`, `get_diff.sh --reduced` and `line_count.sh`

## Running individual scripts <a id="individualscripts"></a>
* Although it is typically more convenient to run scripts via `run_scripts.sh`, each script may be run individually
* In this case, all directories required are assumed to exist
* Directory names may be passed via flags, otherwise must correspond to defaults specified within each script

1. `get_xml.sh`
  * Uses wget to save XML files by calling each (specified) API method for the two databases
  * By default, all API methods are accessed, but individual methods may be accessed instead via flags
  * Currently set up to compare databases on the same host that `get_xml.sh` is run on
    * Uses `switch_to_oracle.sh` and `switch_to_mariadb.sh` to change database, when the URLs are set equal
    * Paths to other scripts are relative (`get_xml.sh` is currently expected to be run from within this directory)

2. `remove_tags.sh` (optional)
  * Cuts out `GOCDB_PORTAL_URL` tags from XML files saved by `get_xml.sh`
  * Necessary when connecting to databases on different hosts to remove misleading "differences"
  * Original XML files are preserved (copies that have the tags removed are created in separate directories)

3. `get_diff.sh`
  * Carries out diffs between all pairs of saved XML files created by `get_xml.sh` (and `remove_tags.sh`)
  * Must be passed `--reduced` for files created by `remove_tags.sh` to be used

4. `line_count.sh`
  * Counts the length of the diff text files created by `get_diff.sh`

## Notes <a id="notes"></a>
* Temporary text files are created and removed automatically by some scripts
* Significant differences are likely to be found if the two databases are not sorted consistently
