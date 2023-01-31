<?php

/**
 * Handle command line options parsing for ManageAPICredentials.php script.
 *
 * If --help or -h are given no other options are processed or defined.
 */

namespace gocdb\scripts;

use InvalidArgumentException;

class ManageAPICredentialsOptions
{
    protected $showHelp = false;
    protected $warn;
    protected $delete;
    protected $dryRun;

    public function __construct()
    {
        $this->getOptions();
    }
    /**
     * @throws \InvInvalidArgumentException If errors found in argument processing
     */
    public function getOptions()
    {
        $shortOptions = 'hw:d:';

        $longOptions = [
            'help',
            'dry-run',
            'warning_threshold:',
            'deletion_threshold:'
        ];

        // Beware that getopt is not clever at spotting invalid/misspelled arguments
        $given = getopt($shortOptions, $longOptions);

        if ($given === false) {
            throw new InvalidArgumentException('failed to  parse command line arguments');
        }

        if ($this->getBoolOption($given, 'help', 'h')) {
            $this->usage();
            $this->showHelp = true;
            return;
        }

        $this->dryRun = isset($given['dry-run']);

        $this->delete = $this->getValOption($given, 'deletion_threshold', 'd');
        $this->warn = $this->getValOption($given, 'warning_threshold', 'w');

        if (!(is_null($this->delete) || is_null($this->warn))) {
            if ($this->delete < $this->warn) {
                throw new InvalidArgumentException(
                    "deletion_threshold must be greater than warning_threshold"
                );
            }
        }
        return;
    }
    private function getValOption($given, $long, $short)
    {
        if (isset($given[$long]) || isset($given[$short])) {
            $tValGiven = isset($given[$short]) ? $given[$short] : $given[$long];
            return $this->positiveInteger($tValGiven, $long);
        }
        return;
    }
    private function getBoolOption($given, $long, $short)
    {
        return isset($given[$long]) || isset($given[$short]);
    }
    private function positiveInteger($val, $txt)
    {
        if ((string)abs((int)$val) != $val) {
            throw new InvalidArgumentException(
                "$txt must be integer and greater than zero . Received: $val"
            );
        }
        return (int)$val;
    }
    public static function usage($message = '')
    {
        if ($message != '') {
            print
            (
                "Error: $message\n"
            );
        }
        print
        (
            "Usage: php ManageAPICredentials.php [--help | -h] [--dry-run] \\\ \n" .
            "                                    [[--warning_threshold | -w] MONTHS ] \\\ \n" .
            "                                    [[--deletion_threshold | -d ] MONTHS ] \n" .
            "Options: \n" .
            "        -h, --help                      Print this message.\n" .
            "        --dry-run                       Report but do nothing.\n" .
            "        -w, --warning_threshold MONTHS  Email the owning user about credentials \n" .
            "                                        which have not been used for MONTHS months.\n" .
            "        -d, --deletion_threshold MONTHS Delete credentials which have not been used\n" .
            "                                        for MONTHS months.\n"
        );
    }
    public function isShowHelp()
    {
        return $this->showHelp;
    }
    public function isDryRun()
    {
        return $this->dryRun;
    }
    public function isDeleteEnabled()
    {
        return !is_null($this->getDelete());
    }
    public function isWarnEnabled()
    {
        return !is_null($this->getWarn());
    }
    public function getWarn()
    {
        return $this->warn;
    }
    public function getDelete()
    {
        return $this->delete;
    }
}
