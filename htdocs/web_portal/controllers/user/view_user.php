<?php

/*______________________________________________________
 *======================================================
 * File: view_user.php
 * Author: John Casson, David Meredith
 * Owner: GOCDB DEV Team, STFC.
 * Description: Retrieves and draws the data for a user
 *
 * License information
 *
 * Copyright 2013 STFC
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 /*====================================================== */
use Exception;

function view_user()
{
    require_once __DIR__ . '/utils.php';
    require_once __DIR__ . '/../../../../lib/Gocdb_Services/User.php';
    require_once __DIR__ . '/../../../../lib/Gocdb_Services/Factory.php';
    require_once __DIR__ . '/../../components/Get_User_Principle.php';

    $params = [];

    validateUserID();

    list($user, $callingUser, $params) = getUserAndUserService();
    // Check if user only has one identifier to disable unlinking
    $params['lastIdentifier'] = (count($user->getUserIdentifiers()) === 1);
    // Add the display locations of the policy files.
    getPolicyURLs($params);

    $params['user'] = $user;
    $params['callingUser'] = $callingUser;

    list($roleProjectIds) = getRoleProjectIDs($user, $callingUser);

    $currentIdString = Get_User_Principle();
    $params['currentIdString'] = $currentIdString;

    /**
     * Get a list of the projects and their Ids for grouping roles
     * by project in view.
     */
    $projectNamesIds = array();
    $projects = \Factory::getProjectService()->getProjects();

    foreach ($projects as $project) {
        $projectNamesIds[$project->getId()] = $project->getName();
    }

    /**
     * Check to see if the current calling user has permission to
     * edit the target user.
     */
    $params['projectNamesIds'] = $projectNamesIds;
    $params['role_ProjIds'] = $roleProjectIds;
    $params['portalIsReadOnly'] = \Factory::getConfigService()
                                    ->IsPortalReadOnly();
    $params['APIAuthEnts'] = $user->getAPIAuthenticationEntities();
    $title = $user->getFullName();

    show_view("user/view_user.php", $params, $title);
}

function validateUserID()
{
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        throw new Exception("An id must be specified");
    }
}

/**
 * Helper function to get user(All User's) information, viewing/calling user,
 * and few parameters for display.
 *
 * @return array An array containing user, viewing/calling user, and
 *               additional parameters.
 */
function getUserAndUserService()
{
    $childParams = [];
    $userService = \Factory::getUserService();
    $user = $userService->getUser($_GET['id']);

    if ($user === null) {
        throw new Exception("No user with that ID");
    }

    $callingUser = isUserAuthorisedToView($user, $userService);

    try {
        $userService->editUserAuthorization($user, $callingUser);
        $childParams['ShowEdit'] = true;
    } catch (Exception $e) {
        $childParams['ShowEdit'] = false;
    }

    $childParams['idString'] = $userService->getDefaultIdString($user);

    return [$user, $callingUser, $childParams];
}

/**
 * Validates whether the viewing/calling user is authorised to perform
 * or viewing the user's details.
 *
 * @param mixed $userDetails The user details to be viewed.
 * @param \org\gocdb\services\User $userServ The user service.
 *
 * @return mixed `$callingUser` The viewing/calling user if authorised.
 * @throws Exception If the viewing/calling user is not authorised.
 */
function isUserAuthorisedToView($userDetails, $userServ)
{
    /** @var \User $callingUser */
    $callingUser = $userServ->getUserByPrinciple(Get_User_Principle());

    /**
     * Restrict users to see only their own data unless authorised.
     * User objects are not 'owned' so we check their authz at connected sites.
     */
    if (
        is_null($callingUser)
        || (
            !$callingUser->isAdmin()
            && $userDetails !== $callingUser
            && !$userServ->isAllowReadPD($callingUser)
        )
    ) {
        throw new Exception(
            "You are not authorised to read other users\' personal data."
        );
    }

    return $callingUser;
}

/**
 * Helper to retrieve `Role` information and project IDs for a given user.
 *
 * @param \User $user User's Details.
 * @param \User $callingUser Viewing/Calling user.
 */
function getRoleProjectIDs($user, $callingUser)
{
    $roleProjectIds = array();
    // Get the targetUser's roles.
    $roleService = \Factory::getRoleService();
    $roles = $roleService->getUserRoles($user, \RoleStatus::GRANTED);

    // Can the calling user revoke the targetUser's roles?
    /** @var \Role $role */
    foreach ($roles as $role) {
        /** @var \OwnedEntity $roleOwnedEntity */
        $roleOwnedEntity = $role->getOwnedEntity();

        /**
         * Check that revoking this role will not leave an API credential owned
         * by a user at a site over which they no longer have a role.
         */
        $blockingSites = $roleService->checkOrphanAPIAuth($role);

        /**
         * Assign the decorator revokeMessage key value to be either the list
         * of roles permitting the role revocation or the list of sites with
         * potentially orphaned API credentials blocking the revocation.
         */
        if (count($blockingSites) > 0) {
            $role->setDecoratorObject(
                array(
                    "revokeButton" => "disabled",
                    "revokeMessage" => implode(
                        ', ',
                        array_keys($blockingSites)
                    )
                )
            );
        } else {
            $authorisingRoles = \Factory::getRoleActionAuthorisationService()
                ->authoriseAction(
                    \Action::REVOKE_ROLE,
                    $roleOwnedEntity,
                    $callingUser
                )
                ->getGrantingRoles();

            $role->setDecoratorObject(
                array(
                    "revokeButton" => "",
                    "revokeMessage" => getRevokeMessage(
                        $user,
                        $callingUser,
                        $authorisingRoles
                    )
                )
            );
        }

        /**
         * Get the names of the parent project(s) for this role so we can
         * group by project in the view.
         */
        $roleParentProjects = \Factory::getRoleActionAuthorisationService()
                        ->getReachableProjectsFromOwnedEntity(
                            $role->getOwnedEntity()
                        );

        $projIds = array();
        foreach ($roleParentProjects as $proj) {
            $projIds[] = $proj->getId();
        }

        // Store role and parent projIds in a 2D array for viewing.
        $roleProjectIds[] = array($role, $projIds);
    }

    return [$roleProjectIds];
}

/**
 * Helper to generate a relevant message for viewing/calling user.
 */
function getRevokeMessage($individualUser, $viewingUser, $userRoles)
{
    $decoratorString = '';

    if ($individualUser == $viewingUser) {
        if ($viewingUser->isAdmin()) {
            $decoratorString .= 'GOCDB_ADMIN';

            if (count($userRoles) >= 1) {
                $decoratorString .= ': ';
            }
        }
        if (count($userRoles) >= 1) {
            /** @var \Role $authRole */
            $roleNames = array();
            foreach ($userRoles as $authRole) {
                $roleNames[] = $authRole->getRoleType()->getName();
            }

            $decoratorString .= '[' . implode(', ', $roleNames) . '] ';
        }

        return $decoratorString;
    }
    $decoratorString = '[Self revoke own role]';

    return $decoratorString;
}
