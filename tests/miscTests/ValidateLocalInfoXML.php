<?php

/**
 * Check that local_info.xml matches its .xsd schema
 * definition.
 */

require __DIR__ . '/../../htdocs/web_portal/GOCDB_monitor/validate_local_info_xml.php';

 // Enable user error handling
libxml_use_internal_errors(true);

$xml = new DOMDocument();

$xml->load('../../config/local_info.xml');

if (!$xml->schemaValidate('../../config/local_info.xsd')) {
    print '<p>Errors found.</p>';
    print libxml_display_errors();
} else {
    print '<p>Validated. No errors found.<p/>';
}
