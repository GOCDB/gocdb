<?php

/**
 * Controller for user to confirm their identity linking
 * @return null
 */
function validate_identity_link() {
    require_once __DIR__ . '/../../../../lib/Gocdb_Services/Factory.php';
    require_once __DIR__ . '/../../../../htdocs/web_portal/components/Get_User_Principle.php';
    require_once __DIR__ . '/utils.php';

    //Check the portal is not in read only mode, returns exception if it is
    checkPortalIsNotReadOnly();

    if (!isset($_REQUEST['c'])) {
        show_view('error.php', "A confirmation code must be specified.");
    }
    $code = $_REQUEST['c'];

    $currentIdString = Get_User_Principle();
    if (empty($currentIdString)) {
        show_view('error.php', "Could not authenticate user - null user principle");
        die();
    }

    try {
        $request = Factory::getLinkIdentityService()->confirmIdentityLinking($code, $currentIdString);
    } catch(\Exception $e) {
        show_view('error.php', $e->getMessage());
        die();
    }

    // Recovery or identity linking
    if ($request->getPrimaryAuthType() === $request->getCurrentAuthType()) {
        $params['requestText'] = 'recovered';
    } else {
        $params['requestText'] = 'linked';
    }

    show_view('user/linked_identity.php', $params);
}