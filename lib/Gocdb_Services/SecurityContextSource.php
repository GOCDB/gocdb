<?php

/**
 * Storage for an external security context object that may be set to
 * authenticate users. This object is queried from within the
 * 'htdocs/web_portal/components/Get_User_Principle.php' to see
 * if a security context has been set for the current request.
 * The context would need to be set before the Gocdb front
 * controller is invoked.
 *
 * @author David Meredith
 */
class SecurityContextSource {
    private static $context = null;

    public static function setContext($context){
        self::$context = $context;
    }
    public static function getContext(){
        return self::$context;
    }
}

