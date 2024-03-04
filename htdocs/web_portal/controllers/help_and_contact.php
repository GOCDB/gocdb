<?php
/*______________________________________________________
 *======================================================
 * License information
 *
 * Copyright 2023 UK Research and Innovation
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

/*
 * Declares varibales to be used in /../views/help_and_contact.php
 */

function help_and_contact() {
    require_once __DIR__.'/../../../lib/Gocdb_Services/Factory.php';

    $params = array();

    $configService = \Factory::getConfigService();

    $communityDocs = $configService->getCommunityDocs();
    if (!empty($communityDocs)) {
        $params['communityDocs'] = $communityDocs;
    }

    $helpdeskLink = $configService->getHelpdeskLink();
    if (!empty($helpdeskLink)) {
        $params['helpdeskLink'] = $helpdeskLink;
    }

    $requestTracker = $configService->getRequestTracker();
    if (!empty($requestTracker)) {
        $params['requestTracker'] = $requestTracker;
    }

    $title = "Doc, Help and Support";
    show_view('help_and_contact.php', $params, $title);
}
