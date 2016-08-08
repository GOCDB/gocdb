<?php
/*
*http_response_code() is implemented in php from php 5.4.0 onwards, when we
* upgrade, this file can be deleted (having checked that  http_response_code
* implments all the status codes that we may wish to use).
* See http://stackoverflow.com/questions/3258634/php-how-to-send-http-response-code
* and http://php.net/http_response_code#107261
*/
if (!function_exists('http_response_code')) {
    #The response code really shouldn't be null
    function http_response_code($code = NULL) {
        if(is_null($code)) {
            throw new \exception("http_response_code not defined");
        }

        #define the text of the http status message (see: https://en.wikipedia.org/wiki/List_of_HTTP_status_codes)
        #Note only the codes we are likely to use have been defined.
        switch ($code) {
            case 200:
                $httpStatusMessage = "OK";
                break;
            case 201:
                $httpStatusMessage = "Created";
                break;
            case 204:
                $httpStatusMessage = "No Content";
                break;
            case 400:
                $httpStatusMessage = "Bad Request";
                break;
            # Removed following conversation with JJ
            #case 401:
            #    $httpStatusMessage = "Unauthorised";
            #    break;
            case 403:
                $httpStatusMessage = "Forbidden";
                break;
            case 404:
                $httpStatusMessage = "Not Found";
                break;
            case 405:
                $httpStatusMessage = "Method Not Allowed";
                break;
            case 409:
                $httpStatusMessage = "Conflict";
                break;
            case 418:
                $httpStatusMessage = "I'm a Teapot";
                break;
            case 500:
                $httpStatusMessage = "Internal Server Error";
                break;
            case 501:
                $httpStatusMessage = "Not Implemented";
                break;

            default:
                throw new \exception(
                    "Unrecognised http response code \""
                    . $code . "\""
                );
                break;
        }
        #Get the server protocol for the header
        $protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');
        #Set the header
        header($protocol. ' ' . $code . ' ' . $httpStatusMessage);
    }
}
