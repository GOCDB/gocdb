<?php

/**
 * Handle command line options parsing for ManageAPICredentials.php script.
 *
 * If --help or -h are given no other options are processed or defined.
 */

namespace org\gocdb\scripts;

use InvalidArgumentException;

class ManageUnusedAPICredentialsOptions
{
    protected $showHelp = false;
    protected $warn;
    protected $delete;
    protected $dryRun;
    protected $propertyName;
    protected $isRenewalRequest;
    protected $isInactiveRequest;

    public function __construct()
    {
        $this->getOptions();
    }
    /**
     * @throws \InvInvalidArgumentException If errors found in argument processing
     */
    public function getOptions()
    {
        $shortOptions = 'hriw:d:';

        $longOptions = [
            'help',
            'dry-run',
            'renewals',
            'inactive',
            'warning_threshold:',
            'deletion_threshold:'
        ];

        // Beware that getopt is not clever at spotting invalid/misspelled arguments
        $given = getopt($shortOptions, $longOptions);

        if ($given === false || (is_array($given) && count($given) <= 0)) {
            throw new InvalidArgumentException('failed to  parse command line arguments');
        }

        if ($this->getBoolOption($given, 'help', 'h')) {
            $this->usage();
            $this->showHelp = true;
            return;
        }

        $this->dryRun = isset($given['dry-run']);
        $this->canFetchADatetime($given);
        $this->setPropertyName();
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
            "                                    [--renewals | -r] \\\ \n" .
            "                                    [--inactive | -i] \n" .
            "                                    [[--warning_threshold | -w] MONTHS ] \\\ \n" .
            "                                    [[--deletion_threshold | -d ] MONTHS ] \\\ \n" .
            "Options: \n" .
            "        -h, --help                      Print this message.\n" .
            "        --dry-run                       Report but do nothing.\n" .
            "        -r, --renewals                  Email the owning user " .
                                                     "about credentials\n" .
            "                                        which have not been " .
                                                     "renewed.\n" .
            "        -i, --inactive                  Email the owning user " .
                                                     "about credentials\n" .
            "                                        which have not been " .
                                                     "used.\n" .
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

    public function getPropertyName()
    {
        return $this->propertyName;
    }

    private function setPropertyName()
    {
        if ($this->hasRenewalsOptionProvided()) {
            $this->propertyName = 'lastRenewTime';
        } else {
            if ($this->hasInactiveOptionProvided()) {
                $this->propertyName = 'lastUseTime';
            }
        }
    }

    public function hasRenewalsOptionProvided()
    {
        return $this->isRenewalRequest;
    }

    public function hasInactiveOptionProvided()
    {
        return $this->isInactiveRequest;
    }

    /**
     * The delete threshold may not be given in which case the warning threshold should be used.
     * Note that it is an error is delete is greater than warning.
     */
    public function getThreshold()
    {
        return $this->isWarnEnabled() ? $this->getWarn() : $this->getDelete();
    }

    /**
     * Validates whether the user has passed the argument required
     * for obtaining the datetime.
     *
     * @param mixed $given Gets options from the command line argument list.
     *
     * @throws InvalidArgumentException When the user is NOT specifying
     *                                  which datetime to obtain.
     */
    private function canFetchADatetime($given)
    {
        $this->isRenewalRequest = $this->getBoolOption(
            $given,
            'renewals',
            'r'
        );

        if (!$this->isRenewalRequest) {
            $this->isInactiveRequest = $this->getBoolOption(
                $given,
                'inactive',
                'i'
            );
        }

        $optionProvided = (
            $this->isRenewalRequest
            || $this->isInactiveRequest
        );

        if (!$optionProvided) {
            throw new InvalidArgumentException(
                'failed to  parse command line arguments'
            );
        }
    }
}
