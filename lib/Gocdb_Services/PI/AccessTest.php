<?php

/**
 * Class to manage output of the Access Test result
 */

namespace org\gocdb\services;

use SimpleXMLElement;

class AccessTest
{
    /**
     * @return  string  XML used to signal successful authorization return
     */
    public function getRenderingOutput()
    {
        $xmlElem = new SimpleXMLElement("<results />");
        $xmlElem->addAttribute('identifier', Get_User_Principle_PI());
        $xmlElem->addChild('authorized', 'true');

        $domSxe = dom_import_simplexml($xmlElem);

        $dom = new \DOMDocument('1.0');
        $dom->encoding = 'UTF-8';
        $domSxe = $dom->importNode($domSxe, true);
        $domSxe = $dom->appendChild($domSxe);
        $dom->formatOutput = true;

        return $dom->saveXML();
    }
}
