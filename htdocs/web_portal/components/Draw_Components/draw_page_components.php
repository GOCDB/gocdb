<?php

/**
 * File: draw_page_components.php
 * Author: John Casson
 * Description: Provides components used to draw a web portal page.
 *
 * License information
 *
 * Copyright 2009 STFC
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
 * PHPMD does not parse use of $properties with :: scope resolution
 * @SuppressWarnings(PHPMD.UndefinedVariable)
 */

/* Draws the first part of the page (headers, left hand menu) */
function Get_Standard_Top_Section_HTML($title = null)
{
    require_once __DIR__ . "/../../static_php/standard_header.php";

    $configService = \Factory::getConfigService();

    $html = "";

    $html .= get_standard_header($title);

    // banner if requested
    $banner = $configService->getPageBanner();
    if (!empty($banner)) {
        $html .= Get_Banner($banner);
    }
    // container for the page
    $html .= "<div class=\"page_container\">";
    // menu bar
    $html .= "<div class=\"left_box_menu\">";
    $html .= Get_File_Contents(__DIR__ . "/../../static_html/goc5_logo.html");
    //Insert a portal is in read only warning message, if it is
    if ($configService->IsPortalReadOnly()) {
        $html .= Get_File_Contents(__DIR__ . "/../../static_html/read_only_warning.html");
    }
    require_once "menu.php";
    $html .= draw_menu("main_menu");
    $html .= "</div>";
    //$html .= "<h3 class=\"spacer\">Test</h3>";

    $html .= Get_Search_Box_HTML();
    $html .= Get_User_Info_Box_HTML();
    $html .= Get_bottom_logos_Box_HTML();

    // right side of the page
    $html .= "<div class=\"right_box\">";

    // logout button (if set - does not always need to be rendered)
    if (!empty(\Factory::$properties['LOGOUTURL'])) {
        $html .= "<div style='text-align: right;'>";
        $html .= '<a href="' . htmlspecialchars(\Factory::$properties['LOGOUTURL']) .
                    '"><b><font class="btn btn-danger btn-xs">Logout</font></b></a>';
        $html .= "</div>";
    }

    return $html;
}


/* Draws the bottom part of a standard page */
function Get_Standard_Bottom_Section_HTML()
{
    $html = "";
    //$html .= Get_File_Contents("static_html/stfc_footer.html");
    $html .= "</div>";
    // empty div so that page container is
    // correct size

    // end page container
    $html .= "</div>";
    $html .= Get_File_Contents(__DIR__ . "/../../static_html/standard_footer.html");
    return $html;
}


/* Returns the HTML for the left hand search box */
function Get_Search_Box_HTML()
{
    $html = "";
    $html .= '<div class="Left_Search_Box left_box_menu">';
    $html .= '<h3 class="Small_Bottom_Margin Standard_Padding">Search</h3>';
    $html .= Get_Search_Form_HTML();
    $html .= '</div>';
    return $html;
}


/* Returns the HTML for the search box's input form */
function Get_Search_Form_HTML()
{
    $html = '';
    $html .= '<form class="Indented" method="post" ' .
        'action="index.php?Page_Type=Search">';
    $html .= '<input type="text" name="SearchString" class="Search"/>';
    $html .= '<input type="submit" value="Submit" class="Search_Button"/>';
    $html .= '</form>';
    return $html;
}


/* Returns the HTML for the user status box */
function Get_User_Info_Box_HTML()
{
    require_once __DIR__ . '/draw_user_status.php';
    $html = "";
    $html .= '<div class="Left_User_Status_Box left_box_menu">';
    $html .= '<h3 class="Small_Bottom_Margin Standard_Padding">User Status' .
        '</h3>';
    $html .= Get_User_Status_HTML();
    $html .= '</div>';
    return $html;
}

/* Draws a box showing the EGI and other logos */
function Get_bottom_logos_Box_HTML()
{
    require_once __DIR__ . '/../../controllers/user/utils.php';
    $policyURLs = [];
    getPolicyURLs($policyURLs);

    $contextPath = (new GocContextPath())->getPath();

    $html = "";
    $html .= '<div class="Left_Logo_Box left_box_menu">';
    $html .= '<div class="Left_Logo_Row">';

    $html .= '<a href="https://stfc.ukri.org/" class="Sponsor_Link" target="_blank">' .
                /* Allow for STFC council symbol extending above the upper bound of the UKRI symbol */
                '<img style="height: 28px; margin: 2px; class="Sponsor_Logo" ' .
                'src="' . $contextPath . '/images/logos/ukri_stfc.png" ' .
                'alt="The logo of the Science and Technology Facilities Council" />' .
                '</a>';

    $html .= '<a href="https://europa.eu/european-union/index_en" class="Sponsor_Link" target="_blank">' .
                '<img class="Sponsor_Logo" ' .
                'src="' . $contextPath . '/images/flags/eu.png" ' .
                'alt="The logo of the European Union" />' .
                '</a>';

    /**
     * Force the following logos onto a new line, so one doesn't appear
     * above the others.
     */
    $html .= "<br>";

    $html .= '<a href="https://www.egi.eu" class="Sponsor_Link" target="_blank">' .
                '<img class="Sponsor_Logo" ' .
                'src="' . $contextPath . '/images/logos/egi.png" ' .
                'alt="The logo of the E G I Foundation" />' .
                '</a>';

    $html .= '<a href="https://eoscfuture.eu/" class="Sponsor_Link" target="_blank">' .
                '<img class="Sponsor_Logo" '.
                'src="' . $contextPath . '/images/logos/eosc_future.png" ' .
                'alt="The logo of the EOSC Future Horizon 20 20 project" />' .
                '</a>';

    $html .= '<a href="https://www.iris.ac.uk/" class="Sponsor_Link" target="_blank">' .
                '<img class="Sponsor_Logo" ' .
                'src="' . $contextPath . '/images/logos/iris_ac_uk.png" ' .
                'alt="The logo of the IRIS Community" />' .
                '</a>';

    $html .= '</div>';
    $html .= 'GOCDB is provided by <a href="https://stfc.ukri.org/">STFC</a> and is co-funded by:';
    $html .= '<br>- ';
    $html .= '<a href="https://egi.eu">EGI</a> via <a href="https://www.egi.eu/project/egi-ace/">EGI-ACE</a>';
    $html .= '<br>- ';
    $html .= '<a href="https://eoscfuture.eu/">EOSC-Future</a>';
    $html .= '<br>- ';
    $html .= 'The <a href="https://www.iris.ac.uk/">IRIS</a> community';


    $html .= '<br><br>End User Policy Notices:';
    $html .= '<br>- ';
    $html .= '<a title="' . $policyURLs['privacy_notice_title'] . '" href="' .
                $policyURLs['privacy_notice'] . '">Privacy Notice</a>.';
    $html .= '<br>- ';
    $html .= '<a title="' . $policyURLs['aup_title'] . '" href="' . $policyURLs['aup'] . '">Acceptable Use Policy</a>.';
    $html .= '</div>';

    return $html;
}
/**
* Opens the file specified in $fileName, gets the file contents and returns content
*/
function Get_File_Contents($fileName)
{
    //return 'hello';
    $fileHandle = fopen($fileName, "r");
    $fileContents = fread($fileHandle, filesize($fileName));
    fclose($fileHandle);
    return $fileContents;
}
/**
 * Returns the HTML for a top-of-page banner
 *
 * @param   string  $banner        Text of banner message
 * @return  string  HTML for banner
 */
function Get_Banner($banner)
{
    $html = "<div class = \"page_banner\">";
    $html .= $banner;
    $html .= "</div>";

    return $html;
}
