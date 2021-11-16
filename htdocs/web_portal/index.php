<?php
/*______________________________________________________
 *======================================================
 * File: index.php
 * Author: John Casson, David Meredith
 * Description: Entry point for the web interface
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
//phpinfo();
//die();
require_once __DIR__ . "/../../lib/Doctrine/bootstrap.php";
require_once __DIR__.'/../../lib/Gocdb_Services/Factory.php';
// Require GocContextPath which is used in most of the views scripts
require_once __DIR__.'/GocContextPath.php';

// Set the timezone
date_default_timezone_set("UTC");

/**
 * Safely escape and return the data string (xss mitigation function).
 * The string is esacped using htmlspecialchars.
 * @see see https://www.owasp.org/index.php/PHP_Security_Cheat_Sheet
 * @param string $data to encode
 * @param string $encoding
 * @return string
 */
function xssafe($data,$encoding='UTF-8') {
   //return htmlspecialchars($data,ENT_QUOTES | ENT_HTML401,$encoding);
    return htmlspecialchars($data);
}
/**
 * Safely escape then echo the given string (xss mitigation function).
 * @see see https://www.owasp.org/index.php/PHP_Security_Cheat_Sheet
 * @param string $data to encode
 */
function xecho($data) {
   echo xssafe($data);
}

$Page_Type = Get_Page_Type();

// Used to get the current user principle (if authenticated)
require_once __DIR__ . '/components/Get_User_Principle.php';

/**
 * Dies if the request can't be authenticated.
 * @param string $message If not specified, a default message is used.
 */
function rejectIfNotAuthenticated($message = null){
    $authPrincipleStr = Get_User_Principle();
    if(empty($authPrincipleStr)){
        // prob better to do a re-direct here to error page.
        if($message == null){
            die('Access Denied, authentication failed - A valid user certificate was not found');
            //or your EGI SSO user account is not associated with a valid certificate.');
        } else {
            die($message);
        }
    }
}
// Uncomment to invoke for any page (can instead do this selectively
// on per-page basis as below)
//rejectIfNotAuthenticated();

// Initialise the configuration service with the host url of the incoming request.
// Allows the overriding of configuration values. Do not use 'new' to create a new 
// instance after this.

\Factory::getConfigService()->setLocalInfoOverride($_SERVER['SERVER_NAME']);

try {
    Draw_Page($Page_Type);

} catch (ErrorException $e) {
    /* ErrorExceptions may be thrown by an invalid configuration so it is
       not safe to try to give a pretty output. Set 'raw' to true. */
    show_view('error.php', $e->getMessage(), NULL, TRUE);
    die();
} catch(Exception $e) {
    show_view('error.php', $e->getMessage(), NULL, FALSE);
    die();
}
/**
 * If a page type has been set then return this value. If it hasn't,
 * return an empty string.
 */
function Get_Page_Type() {
    if(!isset($_REQUEST['Page_Type'])){
        return "default";
    } else {
        return $_REQUEST['Page_Type'];
    }
}

function testForHtmlSpecialChars($value, $key){
    if(is_string($key) && strcmp($key, htmlspecialchars($key)) !== 0) {
        throw new \Exception("Invalid chars detected in URL parameter keys");
    }
    if(is_string($value) && strcmp($value, htmlspecialchars($value)) !== 0) {
        throw new \Exception("Invalid chars detected in URL parameter values");
    }
}

/* Decides which type of page to draw based on the passed $Page_Type */
function Draw_Page($Page_Type) {

    // We call this to account for URL params that have an array for the value
    array_walk_recursive($_GET, 'testForHtmlSpecialChars');

    // Read only pages - these pages don't strictly require user authentication.
    // Therefore, to enable permit-all page viewing, comment out the call
    // to rejectIfNotAuthenticated() in the relevant case block. Note, some of
    // these pages will replace sensitive info such as telephone/email with
    // the string 'PROTECTED'
    // ************************************************************************
    switch($Page_Type) {
        case "default" :
            //rejectIfNotAuthenticated();
            require_once __DIR__.'/controllers/start_page.php';
            startPage();
            break;
        case "View_Service_Endpoint" :
            //rejectIfNotAuthenticated();
            require_once __DIR__ . '/controllers/service/view_service_endpoint.php';
            view_endpoint();
            break;
        case "Service_Groups":
            //rejectIfNotAuthenticated();
            require_once __DIR__ . '/controllers/service_group/view_all.php';
            showAllServiceGroups();
            break;
        case "Service_Group":
            //rejectIfNotAuthenticated();
            require_once __DIR__ . '/controllers/service_group/view_sgroup.php';
            showServiceGroup();
            break;
        case "Site":
            //rejectIfNotAuthenticated();
            require_once __DIR__ . '/controllers/site/view_site.php';
            view_site();
            break;
        case "NGI":
            //rejectIfNotAuthenticated();
            require_once __DIR__ . '/controllers/ngi/view_ngi.php';
            view_ngi();
            break;
        case "Service":
            //rejectIfNotAuthenticated();
            require_once __DIR__ . '/controllers/service/view_service.php';
            view_se();
            break;
        case "Services":
            //rejectIfNotAuthenticated();
            require_once __DIR__ . '/controllers/service/view_all.php';
            drawSEs();
            break;
        case "NGIs":
            //rejectIfNotAuthenticated();
            require_once __DIR__ . '/controllers/ngi/view_ngis.php';
            view_ngis();
            break;
        case "Sites":
            //rejectIfNotAuthenticated();
            require_once __DIR__ . '/controllers/site/view_all.php';
            showAllSites();
            break;
        case "Projects":
            //rejectIfNotAuthenticated();
            require_once __DIR__ . '/controllers/project/view_all.php';
            show_all_projects();
            break;
        case "Project":
            //rejectIfNotAuthenticated();
            require_once __DIR__ . '/controllers/project/view_project.php';
            show_project();
            break;
        case "Scope_Help":
            //rejectIfNotAuthenticated();
            require_once __DIR__ . '/controllers/scope_help.php';
            show_help();
            break;
        case "Site_Geo_xml" :
            //rejectIfNotAuthenticated();
            require_once __DIR__ . '/controllers/sitesForMapXML.php';
            show_xml ();
            break;
        case "Error_Redirect":
            //rejectIfNotAuthenticated();
            show_view('error.php', $_REQUEST['error']);
            break;
        case "Static_HTML":
            //rejectIfNotAuthenticated();
            Draw_Static_HTML();
            break;
        case "Search":
            //rejectIfNotAuthenticated();
            require_once __DIR__.'/controllers/search.php';
            search();
            break;
        case "View_Role_Action_Mappings":
                //rejectIfNotAuthenticated();
            require_once __DIR__.'/controllers/political_role/view_role_action_mappings.php';
            view_role_action_mappings();
            break;

        // CrUD Pages - These pages MUST have authentication enabled so
        // the calls to rejectIfNotAuthenticated() must be used.
        // *********************************************************************
        case "Revoke_Role":
            rejectIfNotAuthenticated();
            require_once __DIR__.'/controllers/political_role/revoke_request.php';
            view_revoke_request();
            break;
        case "Accept_Role_Request":
            rejectIfNotAuthenticated();
            require_once __DIR__.'/controllers/political_role/accept_request.php';
            view_accept_request();
            break;
        case "Deny_Role_Request":
            rejectIfNotAuthenticated();
            require_once __DIR__.'/controllers/political_role/deny_request.php';
            view_deny_request();
            break;
        case "Role_Requests":
            rejectIfNotAuthenticated();
            require_once __DIR__.'/controllers/political_role/view_requests.php';
            view_requests();
            break;
        case "Request_Role":
            rejectIfNotAuthenticated();
            require_once __DIR__.'/controllers/political_role/request_role.php';
            request_role();
            break;
        case "Edit_Site":
            rejectIfNotAuthenticated();
            require_once __DIR__.'/controllers/site/edit_site.php';
            edit_site();
            break;
        case "Edit_Service":
            rejectIfNotAuthenticated();
            require_once __DIR__.'/controllers/service/edit_service.php';
            edit_service();
            break;
        case "SE_Downtimes":
            rejectIfNotAuthenticated();
            require_once __DIR__.'/controllers/service/se_downtimes.php';
            se_downtimes();
            break;
        case "Add_Service":
            rejectIfNotAuthenticated();
            require_once __DIR__.'/controllers/service/add_service.php';
            add_service();
            break;
        case "Add_Service_Endpoint":
            rejectIfNotAuthenticated();
            require_once __DIR__.'/controllers/service/add_service_endpoint.php';
            add_service_endpoint();
            break;
        case "Delete_Service":
            rejectIfNotAuthenticated();
            require_once __DIR__.'/controllers/service/delete_service.php';
            delete();
            break;
        case "Edit_User":
            rejectIfNotAuthenticated();
            require_once __DIR__.'/controllers/user/edit_user.php';
            edit_user();
            break;
        case "User":
            rejectIfNotAuthenticated();
            require_once __DIR__.'/controllers/user/view_user.php';
            view_user();
            break;
        case "Downtime":
            rejectIfNotAuthenticated();
            require_once __DIR__.'/controllers/downtime/view_downtime.php';
            view();
            break;
        case "My_Sites":
            rejectIfNotAuthenticated();
            require_once __DIR__.'/controllers/my_sites.php';
            my_sites();
            break;
        case "Edit_NGI":
            rejectIfNotAuthenticated();
            require_once __DIR__.'/controllers/ngi/edit_ngi.php';
            edit_ngi();
            break;
        case "Edit_Service_Group":
            rejectIfNotAuthenticated();
            require_once __DIR__.'/controllers/service_group/edit_service_group.php';
            edit_service_group();
            break;
        case "Add_Service_Group_SEs":
            rejectIfNotAuthenticated();
            require_once __DIR__.'/controllers/service_group/add_ses.php';
            add_ses();
            break;
        case "Search_SEs":
            rejectIfNotAuthenticated();
            require_once __DIR__.'/controllers/service_group/search_ses.php';
            search_ses();
            break;
        case "Remove_Service_Group_SEs":
            rejectIfNotAuthenticated();
            require_once __DIR__.'/controllers/service_group/remove_ses.php';
            remove_ses();
            break;
        case "Add_Site":
            rejectIfNotAuthenticated();
            require_once __DIR__.'/controllers/site/add_site.php';
            add_site();
            break;
        case "SGroup_Downtimes":
            rejectIfNotAuthenticated();
            require_once __DIR__.'/controllers/service_group/view_sgroup_downtimes.php';
            view_sgroup_downtimes();
            break;
        case "Add_Service_Group":
            rejectIfNotAuthenticated();
            require_once __DIR__.'/controllers/service_group/add_service_group.php';
            add_service_group();
            break;
        case "Site_Downtimes":
            rejectIfNotAuthenticated();
            require_once __DIR__.'/controllers/site/site_downtimes.php';
            site_downtimes();
            break;
        case "Register":
            rejectIfNotAuthenticated('Access denied - '
                    . 'you need to be pre-authenticated before you can register a new account');
            require_once __DIR__.'/controllers/user/register.php';
            register();
            break;
        case "Add_Downtime":
            rejectIfNotAuthenticated();
            require_once __DIR__.'/controllers/downtime/add_downtime.php';
            //require_once __DIR__.'/controllers/downtime/add_downtime_old.php';
            add();
            break;
        case "Edit_Downtime":
            rejectIfNotAuthenticated();
            require_once __DIR__.'/controllers/downtime/edit_downtime.php';
            //require_once __DIR__.'/controllers/downtime/edit_downtime_old.php';
            edit();
            break;
        case "End_Downtime":
            rejectIfNotAuthenticated();
            require_once __DIR__.'/controllers/downtime/end_downtime.php';
            endDt();
            break;
        case "Downtime_view_endpoint_tree":
            rejectIfNotAuthenticated();
            require_once __DIR__.'/controllers/downtime/view_endpoint_tree.php';
            getServiceandEndpointList();
            break;
        case "Edit_Downtime_view_endpoint_tree":
            rejectIfNotAuthenticated();
            require_once __DIR__.'/controllers/downtime/view_endpoint_tree.php';
            editDowntimePopulateEndpointTree();
            break;
        case "Downtime_View_Services":
            rejectIfNotAuthenticated();
            require_once __DIR__.'/controllers/downtime/view_services.php';
            getSitesServices();
            break;
        case "Delete_Site":
            rejectIfNotAuthenticated();
            require_once __DIR__.'/controllers/site/delete_site.php';
            delete();
            break;
        case "Delete_Downtime":
            rejectIfNotAuthenticated();
            require_once __DIR__.'/controllers/downtime/delete_downtime.php';
            delete();
            break;
        case "Downtimes_Overview":
            rejectIfNotAuthenticated();
            require_once __DIR__.'/controllers/downtime/downtimes_overview.php';
            view();
            break;
        case "Downtimes_Calendar":
            rejectIfNotAuthenticated();
            require_once __DIR__.'/controllers/downtime/downtimes_calendar.php';
            view();
            break;
        case "Delete_Service_Group":
            rejectIfNotAuthenticated();
            require_once __DIR__.'/controllers/service_group/delete_service_group.php';
            delete();
            break;
        case "Delete_User":
            rejectIfNotAuthenticated();
            require_once __DIR__.'/controllers/user/delete_user.php';
            delete();
            break;
        case "Edit_Certification_Status":
            rejectIfNotAuthenticated();
            require_once __DIR__.'/controllers/site/edit_cert_status.php';
            edit();
            break;
        case "Retrieve_Account":
            rejectIfNotAuthenticated();
            require_once __DIR__.'/controllers/user/retrieve_account.php';
            retrieve();
            break;
        case "Remove_Project_NGIs":
            rejectIfNotAuthenticated();
            require_once __DIR__.'/controllers/project/remove_ngis.php';
            remove_ngis_project();
            break;
        case "Add_Project_NGIs":
            rejectIfNotAuthenticated();
            require_once __DIR__.'/controllers/project/add_ngis.php';
            add_ngis_to_project();
            break;
        case "Edit_Project":
            rejectIfNotAuthenticated();
            require_once __DIR__.'/controllers/project/edit_project.php';
            edit_project();
            break;
        case "Delete_Project":
            rejectIfNotAuthenticated();
            require_once __DIR__.'/controllers/project/delete_project.php';
            delete_project();
            break;
        case "Admin_Move_Site":
            rejectIfNotAuthenticated();
            require_once __DIR__.'/controllers/admin/move_site.php';
            move_site();
            break;
        case "Admin_Move_SEP":
            rejectIfNotAuthenticated();
            require_once __DIR__.'/controllers/admin/move_service_end_point.php';
            move_service_end_point();
            break;
        case "Admin_Service_Types":
            rejectIfNotAuthenticated();
            require_once __DIR__.'/controllers/admin/view_service_types.php';
            show_all();
            break;
        case "Admin_Service_Type":
            rejectIfNotAuthenticated();
            require_once __DIR__.'/controllers/admin/view_service_type.php';
            view_service_type();
            break;
        case "Admin_Edit_Service_Type":
            rejectIfNotAuthenticated();
            require_once __DIR__.'/controllers/admin/edit_service_type.php';
            edit_type();
            break;
        case "Admin_Add_Service_Type":
            rejectIfNotAuthenticated();
            require_once __DIR__.'/controllers/admin/add_service_type.php';
            add_type();
            break;
        case "Admin_Delete_Service_Type":
            rejectIfNotAuthenticated();
            require_once __DIR__.'/controllers/admin/delete_service_type.php';
            delete_service_type();
            break;
        case "Admin_Delete_Service_Type_Denied":
            rejectIfNotAuthenticated();
            require_once __DIR__.'/controllers/admin/delete_service_type_denied.php';
            deny_delete_type();
            break;
        case "Admin_Add_NGI":
            rejectIfNotAuthenticated();
            require_once __DIR__.'/controllers/admin/add_ngi.php';
            add_ngi();
            break;
        case "Admin_Users":
            rejectIfNotAuthenticated();
            require_once __DIR__.'/controllers/admin/users.php';
            show_users();
            break;
        case "Admin_Edit_User_DN":
            rejectIfNotAuthenticated();
            require_once __DIR__.'/controllers/admin/edit_user_dn.php';
            edit_dn();
            break;
//        case "Admin_Change_User_Admin_Status":
//            rejectIfNotAuthenticated();
//            require_once __DIR__.'/controllers/admin/edit_user_isadmin.php';
//            make_admin();
//            break;
        case "Admin_Add_Project":
            rejectIfNotAuthenticated();
            require_once __DIR__.'/controllers/admin/add_project.php';
            add_project();
            break;
        case "Admin_Scopes":
            rejectIfNotAuthenticated();
            require_once __DIR__.'/controllers/admin/scopes.php';
            show_scopes();
            break;
        case "Admin_Remove_Scope":
            rejectIfNotAuthenticated();
            require_once __DIR__.'/controllers/admin/delete_scope.php';
            remove_scope();
            break;
        case "Admin_Add_Scope":
            rejectIfNotAuthenticated();
            require_once __DIR__.'/controllers/admin/add_scope.php';
            add_scope();
            break;
        case "Admin_Scope":
            rejectIfNotAuthenticated();
            require_once __DIR__.'/controllers/admin/scope.php';
            view_scope();
            break;
        case "Admin_Edit_Scope":
            rejectIfNotAuthenticated();
            require_once __DIR__.'/controllers/admin/edit_scope.php';
            edit_scope();
            break;
        case "Admin_Delete_NGI":
            rejectIfNotAuthenticated();
            require_once __DIR__.'/controllers/admin/delete_ngi.php';
            delete_ngi();
            break;
        case "User_Validate_DN_Change" :
            rejectIfNotAuthenticated();
            require_once __DIR__ . '/controllers/user/retrieve_account_user_validate.php';
            validate_dn_change ();
            break;
        case "Add_Site_Properties" :
            rejectIfNotAuthenticated();
            require_once __DIR__ . '/controllers/site/add_site_properties.php';
            add_site_properties ();
            break;
        case "Export_Properties" :
            rejectIfNotAuthenticated();
            require_once __DIR__ . '/controllers/export_properties.php';
            export ();
            break;
        case "Add_Service_Properties" :
            rejectIfNotAuthenticated();
            require_once __DIR__ . '/controllers/service/add_service_properties.php';
            add_service_properties ();
            break;
        case "Add_Endpoint_Properties" :
            rejectIfNotAuthenticated();
            require_once __DIR__ . '/controllers/service/add_endpoint_properties.php';
            add_endpoint_properties ();
            break;
        case "Delete_Service_Properties" :
            rejectIfNotAuthenticated();
            require_once __DIR__ . '/controllers/service/delete_service_properties.php';
            delete ();
            break;
        case "Delete_Site_Properties" :
            rejectIfNotAuthenticated();
            require_once __DIR__ . '/controllers/site/delete_site_properties.php';
            delete ();
            break;
        case "Service_Properties_Controller" :
            rejectIfNotAuthenticated();
            require_once __DIR__ . '/controllers/service/service_properties_controller.php';
            control ();
            break;
        case "Endpoint_Properties_Controller" :
            rejectIfNotAuthenticated();
            require_once __DIR__ . '/controllers/service/endpoint_properties_controller.php';
            control ();
            break;
        case "Site_Properties_Controller" :
            rejectIfNotAuthenticated();
            require_once __DIR__ . '/controllers/site/site_properties_controller.php';
            control ();
            break;
        case "Service_Group_Properties_Controller" :
            rejectIfNotAuthenticated();
            require_once __DIR__ . '/controllers/service_group/service_group_properties_controller.php';
            control ();
            break;
        case "Delete_Endpoint_Properties" :
            rejectIfNotAuthenticated();
            require_once __DIR__ . '/controllers/service/delete_endpoint_properties.php';
            delete ();
            break;
        case "Edit_Site_Property" :
            rejectIfNotAuthenticated();
            require_once __DIR__ . '/controllers/site/edit_site_property.php';
            edit_property ();
            break;
        case "Edit_Service_Property" :
            rejectIfNotAuthenticated();
            require_once __DIR__ . '/controllers/service/edit_service_property.php';
            edit_property ();
            break;
        case "Edit_Endpoint_Property" :
            rejectIfNotAuthenticated();
            require_once __DIR__ . '/controllers/service/edit_endpoint_property.php';
            edit_property ();
            break;
        case "Add_Service_Group_Properties" :
            rejectIfNotAuthenticated();
            require_once __DIR__ . '/controllers/service_group/add_service_group_properties.php';
            add_service_group_properties ();
            break;
        case "Edit_Service_Group_Property" :
            rejectIfNotAuthenticated();
            require_once __DIR__ . '/controllers/service_group/edit_service_group_property.php';
            edit_property ();
            break;
        case "Delete_Service_Group_Properties" :
            rejectIfNotAuthenticated();
            require_once __DIR__ . '/controllers/service_group/delete_service_group_properties.php';
            delete ();
            break;
        case "Delete_Service_Endpoint" :
            rejectIfNotAuthenticated();
            require_once __DIR__ . '/controllers/service/delete_service_endpoint.php';
            delete_endpoint();
            break;
        case "Edit_Service_Endpoint" :
            rejectIfNotAuthenticated();
            require_once __DIR__ . '/controllers/service/edit_service_endpoint.php';
            edit_endpoint();
            break;
        case "Add_API_Authentication_Entity" :
            rejectIfNotAuthenticated();
            require_once __DIR__ . '/controllers/site/add_api_auth.php';
            add_entity();
            break;
        case "Edit_API_Authentication_Entity" :
            rejectIfNotAuthenticated();
            require_once __DIR__ . '/controllers/site/edit_api_auth.php';
            edit_entity();
            break;
        case "Delete_API_Authentication_Entity" :
            rejectIfNotAuthenticated();
            require_once __DIR__ . '/controllers/site/delete_api_auth.php';
            delete_entity();
            break;
        default:
        // require auth by default
        rejectIfNotAuthenticated();
        require_once __DIR__.'/controllers/start_page.php';
        startPage();
        break;
    }
}

/* Draws a static HTML page */
function Draw_Static_HTML() {
    $Page_Name = Get_Static_Page_Name();
    $Page_Content = Get_Static_Page_Contents($Page_Name);
    Draw_Standard_Page($Page_Content);
}

/* Finds out if a static page has been requested. If it has, return
 * the page name, otherwise return a blank string. */
function Get_Static_Page_Name() {
    if(!isset($_REQUEST['Page'])) {
        return "";
    } else {
        return $_REQUEST['Page'].'.html';
    }
}


/* Get the contents of the static HTML page specified in $Page_Name
 * if the page name isn't specified then return a blank string */
function Get_Static_Page_Contents($Page_Name) {
    require_once __DIR__.'/components/Draw_Components/draw_page_components.php';
    $htmlDir = __DIR__."/static_html";
    $Available_Static_Pages = Get_Directory_Contents($htmlDir);
    if(!isset($Available_Static_Pages[$Page_Name])) {
        return "";
    }
    $HTML = Get_File_Contents($htmlDir."/".$Page_Name);
    return $HTML;
}


/* Returns the contents of a specified directory name */
function Get_Directory_Contents($Directory_Name) {
    if ($File_Handle = opendir($Directory_Name))
    {
        while (false !== ($Filename = readdir($File_Handle)))
        $File_List[$Filename] = true;
    }

    closedir($File_Handle);
    return $File_List;
}


/* Draws a standard GOCDB layout with the string $Page_Content in the
 * right frame */
function Draw_Standard_Page($Page_Content, $title=null) {
    require_once __DIR__.'/components/Draw_Components/draw_page_components.php';
    $HTML = "";
    $HTML .= Get_Standard_Top_Section_HTML($title);
    $HTML .= $Page_Content;
    $HTML .= Get_Standard_Bottom_Section_HTML();
    echo $HTML;
}



/* Given the name of a file in the view directory, include it
 * as the body of a standard GOCDB page */
function show_view($view, $params=null, $title=null, $rawOutput=null) {
    if($rawOutput == true) {
        require_once __DIR__.'/views/'.$view;
        return;
    }

    require_once __DIR__.'/components/Draw_Components/draw_page_components.php';
    echo Get_Standard_Top_Section_HTML($title);
    require_once __DIR__.'/views/'.$view;
    echo Get_Standard_Bottom_Section_HTML();

}

 /**
  * Redirect the browser to the index.php page to render the specified logical view,
  * OR redirect directly to the requested absolute view (bypassing index.php).
  * <p>
  * The '$logical_view' takes precidence and is the value of the 'Page_Type'
  * request parameter which is resolved by 'index.php' file as follows:
  * <code>'/index.php?Page_Type='.$logical_view&paramsKey=paramsValue</code>.
  * <p>
  * If '$logical_view' is null, then redirect to the '$absolute_view' which
  * is the relative path to the view template file (relative to the
  * app context root and must not start with a leading '/').
  * <p>
  * This function can be used by controllers to redirect to an idepotent page after
  * form submission (preventing duplicate form re-submissions when clicking refresh).
  * <p>
  * Examples:
  * <ul>
  *   <li>To Redirect to: <pre>'index.php?Page_Type=New_Site'</pre>
  *   use <code>redirect_view('New_Site'));</code>
  *   </li>
  *   <li>To Redirect to: <pre>'index.php?Page_Type=Static_HTML&Page=Help_And_Contact'</pre>
  *   use <code>redirect_view('Static_HTML', null, array("Page" => "Help_And_Contact"));</code>
  *   </li>
  *   <li>To Redirect to: <pre>'static_html/somepage.html'</pre>
  *   use <code>redirect_view(null, 'static_html/somepage.html');</code>
  *   </li>
  * <ul>
  *
  * @see http://en.wikipedia.org/wiki/Post/Redirect/Get
  * @param string $logical_view A logical view name to render by index.php, e.g. 'New_Site' or 'Static_HTML'
  *   (this takes precidence over $absolute_view)
  * @param string $absolute_view Relative path to a view template file (only if $logical_view is null),
  *   e.g. 'static_html/somepage.html'
  * @param array $params associative array of key value pairs for HTTP GET request params.
  *   The params are converted into a suitable URL-encoded query string.
  * @throws RuntimeException if $params is not null and not an array
  */
 function redirect_view($logical_view = null, $absolute_view = null, $params = null) {
    if ($params != null && !is_array($params)) {
        throw new RuntimeException('Params must be an associative array');
    }

    if ($logical_view != null) {
        // redirect to the specified logical view
        $path_params = 'index.php?Page_Type=' . $logical_view;
        if ($params != null) {
          $path_params .= '&'. http_build_query($params);
        }
    } else if ($absolute_view != null) {
        // redirect directly to the specified absolute view
        $path_params = $absolute_view;
        if ($params != null) {
            $path_params .= '?'. http_build_query($params);
        }
    } else {
        // show the home page by default
        $path_params = '';
    }

    $host = $_SERVER['HTTP_HOST'];
    $uri = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');

    // Send the 'Location' header to send a REDIRECT (302) header back to the browser
    header("Location: https://$host$uri/$path_params");
    exit; // ensure no code gets executed after the redirect
 }

?>
