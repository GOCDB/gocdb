<?php
/*______________________________________________________
 *======================================================
 * File: delete_user_identifier.php
 * Author: Elliott Kasoar
 * Description: Removes a user's identifier
 *
 * License information
 *
 * Copyright  2021 STFC
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 /*======================================================*/
require_once __DIR__.'/../../../../lib/Gocdb_Services/Factory.php';
require_once __DIR__.'/../../components/Get_User_Principle.php';
require_once __DIR__ . '/../utils.php';

/**
 * Controller for a user identifier removal request
 * @global array $_POST only set if the browser has POSTed data
 * @return null
 */
function delete_identifier() {

    $serv = \Factory::getUserService();

    $currentIdString = Get_User_Principle();
    $currentUser = $serv->getUserByPrinciple($currentIdString);

    if ($currentUser === null) {
        throw new \Exception("Unregistered users cannot edit users");
    }

    // Check the portal is not in read only mode, returns exception if it is and user is not an admin
    checkPortalIsNotReadOnlyOrUserIsAdmin($currentUser);

    // Get the posted data
    $userId = $_REQUEST['id'];
    $identifierId = $_REQUEST['identifierId'];

    $user = $serv->getUser($userId);
    $identifier = $serv->getIdentifierById($identifierId);

    // Throw exception if not a valid user ID
    if (is_null($user)) {
        throw new \Exception("A user with ID '" . $userId . "' cannot be found");
    }

    $serv->editUserAuthorization($user, $currentUser);

    // Throw exception if not a valid identifier ID
    // Non-admins can't tell if a given identifier matches a specific user
    // but they can currently still tell how many identifiers exist
    // This could be changed to only give info about if the identifier matches one of theirs
    if (is_null($identifier)) {
        throw new \Exception("An identifier with ID '" . $identifierId . "' cannot be found");
    }

    // Throw exception if trying to remove identifier that current user is authenticated with
    if ($identifier->getKeyValue() === $currentIdString) {
        throw new \Exception("You cannot unlink your current ID string. Please log in using a different authentication mechanism and try again.");
    }

    $params = array('ID' => $user->getId());

    try {
        // Function will throw error if user does not have the correct permissions
        $serv->deleteUserIdentifier($user, $identifier, $currentUser);
        show_view("user/deleted_user_identifier.php", $params);
    } catch (Exception $e) {
        show_view('error.php', $e->getMessage());
        die();
    }
}