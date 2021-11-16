<?php
/*______________________________________________________
 *======================================================
 * File: menu.php
 * Author: John Casson, George Ryall, David Meredith
 * Description: Draws the left hand menu bar.
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
 /*====================================================== */


if(isset($_REQUEST['menu']))
    draw_menu($_REQUEST['menu']);


function draw_menu($menu_name)
{
    // Open the XML file of possible menus
    // as a SimpleXML object
    $menus_xml = simplexml_load_file(__DIR__.'/../../../../config/web_portal/menu.xml');
    $menu_html = xml_to_menu($menu_name, $menus_xml);
    return $menu_html;
}



// Reads a menu with the name $menu_name from the $menus_xml simplexml object
// and draws that menu as HTML
function xml_to_menu($menu_name, $menus_xml)
{
    $html = "";
    $html .= "<hr style=\"clear: both;\"/>";
    $html .= "<ul class=\"Smaller_Left_Padding Smaller_Top_Margin\">";
    foreach($menus_xml->$menu_name->children() as $key => $value)
    {
        // Check if display of menu is overridden in the local configuration
        if (\Factory::getConfigService()->showMenu($key)) {
            $html .= add_menu_item($value) . "\n";
        }
    }
    $html .= "</ul>";
    return $html;
}



function add_menu_item($menu_item)
{
    //Get user in order to correctly display GOCDB admin menu Items
    include_once __DIR__ . '/../Get_User_Principle.php';
    $dn = Get_User_Principle();
    $userserv = \Factory::getUserService();
    $user = $userserv->getUserByPrinciple($dn);
    if ($user == null){
        $userisadmin = false;
    }
    else {
        $userisadmin = $user->isAdmin();
    }

    //Find out if the portal is currently read only from local_info.xml
    $portalIsReadOnly = \Factory::getConfigService()->IsPortalReadOnly();

    foreach($menu_item->children() as $key => $value)
    {
        $html = "";
        switch($key)
        {
            case "show_on_instance":
                $show= strtolower($value);
                break;
            case "name":
                $name = $value;
                break;
            case "link":
                $link = $value;
                break;
            case "spacer":
                // John C: modified this so that we could use show_on_instance for spacers
                foreach($menu_item as $child_name => $child_value) {
                    if($child_name=="show_on_instance") {
                        // If the spacer has a show_on_instance type that we want to show, then show it
                        if($child_value == "all" or ($child_value=="write_enabled" and (!$portalIsReadOnly or $userisadmin)) or (($child_value == "admin") and ($userisadmin))){
                            return "</ul><h4 class='menu_title'>$value</h4><ul class=\"Smaller_Left_Padding Smaller_Top_Margin\">";
                        }
                    }
                }
                return;
        }

    }
    if ($show == "all" or ($show=="write_enabled" and (!$portalIsReadOnly or $userisadmin)) or (($show == "admin") and ($userisadmin))){
        $html .=    	"<li class=\"Menu_Item\">".
                        "<a href=\"".htmlspecialchars($link)."\"><span class=\"menu_link\">".
        htmlspecialchars($name)."</span></a></li>";
    }
    return $html;
}

?>
