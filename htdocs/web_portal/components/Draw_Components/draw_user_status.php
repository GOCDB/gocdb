<?php
    /*______________________________________________________
     *======================================================
     * File: draw_add_site.php
     * Author: John Casson, David Meredith
     * Description: Draws a web portal page for adding a new site.
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

    /* Returns some HTML for the "User Status" web portal section */
    function Get_User_Status_HTML()
    {
        require_once __DIR__.'/../../../../lib/Gocdb_Services/Factory.php';
        require_once __DIR__.'/../Get_User_Principle.php';
        $HTML = "";
        $HTML .= "<div class=\"Indented\">";
        $dn = Get_User_Principle();
        $user = \Factory::getUserService()->getUserByPrinciple($dn);
        if($user == null) {
            $HTML .= "Unregistered user<br />";
            $HTML .= 	"<br/><a href=\"index.php?Page_Type=Register\">".
                "Register</a><br/>".
                "<a href=\"index.php?Page_Type=Retrieve_Account\">".
                "Retrieve Old Account</a><br/>";

            $HTML .="</div>";
            return $HTML;
        }
        $HTML .= "Registered as: <br />".$user->getForename() . " " . $user->getSurname() . "<br /><br />";
        $HTML .= Get_User_Info_HTML($user);
        $HTML .= "</div>";
        return $HTML;
    }

    /* Builds bottom HTML for the user status box */
    function Get_User_Info_HTML($user)
    {
        $Roles_HTML = "";

        $Link = "index.php?Page_Type=User&id={$user->getId()}";
        $Roles_HTML .= "<a href=\"$Link\">View Details</a><br/>";
        $Link = "index.php?Page_Type=Role_Requests";
        $Roles_HTML .= "<a href=\"$Link\">Manage Roles</a>";

        return $Roles_HTML;
    }
?>