<?php

namespace org\gocdb\services;

require_once __DIR__ . '/AbstractEntityService.php';
require_once __DIR__ . '/Factory.php';

/**

 */
class EmailService extends AbstractEntityService {
    /**
     * Depending on the configuration, either send an email or print what would
     * have been sent.
     * @param string $emailAddress    A single email address to send to
     * @param string $subject         The subject of the email
     * @param string $body            The body of the email
     * @param string $headers         The headers of the email

     */
    public function send($emailAddress, $subject, $body, $headers) {
        if ($this->getConfigSendEmail()) {
            mail($emailAddress, $subject, $body, $headers);
        } else {
            $this->mockMail($emailAddress, $subject, $body, $headers);
      }
    }

    /**
    * Return whether send_email is enabled in the config file
    */
    private function getConfigSendEmail() {
        return \Factory::getConfigService()->getSendEmails();
    }

    private function mockMail($to, $subject, $message, $additionalHeaders = "", $additionalParameters = "") {
        echo "<!--\n";
        echo "Sending mail disabled, but would have sent:\n";
        echo "$additionalHeaders\n";
        echo "To: $to\n";
        echo "Subject: $subject\n";
        echo "\n$message\n";
        echo "\nAdditional Parameters: $additionalParameters\n";
        echo "-->\n";
        return True;
    }
}
