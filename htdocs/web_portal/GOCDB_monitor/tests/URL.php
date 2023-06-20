<?php
namespace org\gocdb\tests;

// TODO: Finish and use. This class isn't yet in use. Once it's finished
// it can will replace the procedural
// code in ../index.php and ../check.php to check a URL.
/**
 * Test a URL
 *
 * Connects to a passed URL and says whether it's reachable
 * @author John Casson, David Meredith
 *
 */
class URL {
    const CA_PATH = '/etc/grid-security/certificates/';
    private $url;
    private $error;
    private $hasRun = false;

    function __construct($url) {
        $this->url = $url;
    }

    /**
     * Is the test successful
     * @throws Exception If the test hasn't been run
     * @return boolean
     */
    public function isSuccessful() {
        if(!$this->hasRun) {
            throw new \Exception("Test hasn't been run");
        }

        if(empty($this->error)) {
            return true;
        }
    }

    /**
     * If the test wasn't successful, return the error
     */
    public function getError() {
        if(empty($this->error)) {
            throw new \Exception("No error reported");
        }
        return $this->error;
    }

    /**
     * Runs the test
     *
     */
    public function run() {
        $this->get_https($this->url);
        $this->hasRun = true;
    }

    /**
     * Performs an http request
     *
     * Side effect: sets $this->error if the request fails
     * @param string $url
     * @throws Exception
     * @return mixed
     */
    private function get_https($url){
        $curloptions = array (
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_HEADER         => false,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_MAXREDIRS      => 1,
            CURLOPT_SSL_VERIFYHOST => '1',
            CURLOPT_SSL_VERIFYPEER => '1',
            CURLOPT_USERAGENT      => 'GOCDB monitor',
            CURLOPT_VERBOSE        => false,
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => '1',
            CURLOPT_CAPATH => self::CA_PATH
        );

        $handle = curl_init();
        curl_setopt_array($handle, $curloptions);

        $return = curl_exec($handle);
        if (curl_errno($handle)) {
            $this->error = curl_error($handle);
            return;
        }
        curl_close($handle);

        if ($return == false) {
            $this->error = curl_getinfo($handle);
        }

        return;
    }
}
