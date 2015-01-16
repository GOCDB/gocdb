<?php
	/*______________________________________________________
	 *======================================================
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
	 *====================================================== */

	/* Draws the first part of the page (headers, left hand menu) */
	function Get_Standard_Top_Section_HTML($title=null)
	{
		require_once __DIR__."/../../static_php/standard_header.php";
		$HTML = "";

		$HTML .= get_standard_header($title);

		// container for the page
		$HTML .= "<div class=\"page_container\">";

		// menu bar
		$HTML .= "<div class=\"left_box_menu\"\">";
    	$HTML .= Get_File_Contents(__DIR__."/../../static_html/goc5_logo.html");
		//Insert a portal is in read only warning message, if it is
        if(\Factory::getConfigService()->IsPortalReadOnly()){
            $HTML.= Get_File_Contents(__DIR__."/../../static_html/read_only_warning.html");
        }
        require_once "menu.php";
		$HTML .= draw_menu("main_menu");
		$HTML .= "</div>";
		//$HTML .= "<h3 class=\"spacer\">Test</h3>";

		$HTML .= Get_Search_Box_HTML();
		$HTML .= Get_User_Info_Box_HTML();
		$HTML .= Get_bottom_logos_Box_HTML();

		// right side of the page
		$HTML .= "<div class=\"right_box\">";
        
        // logout button (if set - does not always need to be rendered)  
        if(!empty(\Factory::$properties['LOGOUTURL'])){
            $HTML .= "<div style='text-align: right;'>"; 
            $HTML .= '<a href="'.htmlspecialchars(\Factory::$properties['LOGOUTURL']).'">Logout</a>';
            $HTML .= "</div>"; 
        }
       

		return $HTML;
	}


	/* Draws the bottom part of a standard page */
	function Get_Standard_Bottom_Section_HTML()
	{
		$HTML = "";
		//$HTML .= Get_File_Contents("static_html/stfc_footer.html");
		$HTML .= "</div>";
		// empty div so that page container is
		// correct size

		// end page container
		$HTML .= "</div>";
		$HTML .= Get_File_Contents(__DIR__."/../../static_html/standard_footer.html");
		return $HTML;
	}


	/* Returns the HTML for the left hand search box */
	function Get_Search_Box_HTML()
	{
		$HTML = "";
		$HTML .= '<div class="Left_Search_Box left_box_menu">';
		$HTML .= '<h3 class="Small_Bottom_Margin Standard_Padding">Search</h3>';
		$HTML .= Get_Search_Form_HTML();
		$HTML .= '</div>';
		return $HTML;
	}


	/* Returns the HTML for the search box's input form */
	function Get_Search_Form_HTML()
	{
		$HTML = '';
		$HTML .= '<form class="Indented" method="post" '.
			'action="index.php?Page_Type=Search">';
		$HTML .= '<input type="text" name="SearchString" class="Search"/>';
		$HTML .= '<input type="submit" value="Submit" class="Search_Button"/>';
		$HTML .= '</form>';
		return $HTML;
	}


	/* Returns the HTML for the user status box */
	function Get_User_Info_Box_HTML()
	{
		require_once __DIR__.'/draw_user_status.php';
		$HTML = "";
		$HTML .= '<div class="Left_User_Status_Box left_box_menu">';
		$HTML .= '<h3 class="Small_Bottom_Margin Standard_Padding">User Status'.
			'</h3>';
		$HTML .= Get_User_Status_HTML();
		$HTML .= '</div>';
		return $HTML;
	}

	/* Draws a box showing the EGI and other logos */
	function Get_bottom_logos_Box_HTML()
	{
		$HTML = "";
		$HTML .= '<div class="Left_Logo_Box left_box_menu">';
		$HTML .= '<div align="center"><a href="http://www.stfc.ac.uk" target="_blank"><img src="'.\GocContextPath::getPath().'img/STFC.jpg" height="25"/></a>&nbsp;&nbsp;';
		$HTML .= '<a href="http://europa.eu" target="_blank"><img src="'.\GocContextPath::getPath().'img/eu.jpg" height="25"/></a>&nbsp;&nbsp;';
		//$HTML .= '<a href="http://www.egi.eu" target="_blank"><img src="img/egi.gif" height="25"/></a></div>';
		$HTML .= '<a href="http://www.egi.eu" target="_blank"><img src="'.\GocContextPath::getPath().'img/egi_logo.jpg" height="25"/></a></div>';
		$HTML .= '</div>';

		return $HTML;
	}


       /**
        * Opens the file specified in $Filename, gets the file contents and returns content
        */
       function Get_File_Contents($Filename) {
           //return 'hello';
           $File_Handle = fopen($Filename, "r");
           $File_Contents = fread($File_Handle, filesize($Filename));
           fclose($File_Handle);
           return $File_Contents;
       }

?>