<?php

/**
 * Class for defining enum vals for the <code>Role.status</code> class attribute.
 * <p>
 * Note, these enum vals should NOT be referenced from within entities (entities
 * should use strings only). Intended for use by higher level business logic only.
 * @author David Meredith
 */
class RoleStatus {

    const GRANTED = 'STATUS_GRANTED';
    const PENDING = 'STATUS_PENDING';
    const REVOKED = 'STATUS_REVOKED';
    const REJECTED = 'STATUS_REJECTED';

    /**
     * private constructor to limit instantiation
     */
    private function __construct() {
    }

    /**
     * Get an associative array of the class constants. Array keys are the constant
     * names, array values are the constant values.
     * @return array
     */
    public static function getAsArray() {
        $tmp = new ReflectionClass(get_called_class());
        $a = $tmp->getConstants();
        return $a;
    }
}

/**
 * Class for defining enum vals for role type definitions.
 * <p>
 * @deprecated since version 5.5 RoleTypes are now defined in role action mapping xml file
 * Note, these enums should NOT be referenced from within the DB entities (entities
 * should use strings only). Intended for use by higher level business logic only.
 * @author David Meredith
 */
class RoleTypeName {

    const GOCDB_ADMIN = 'GOCDB_ADMIN';

    // Roles for Sites
    const SITE_ADMIN = 'Site Administrator'; // C

    const SITE_SECOFFICER = 'Site Security Officer'; // C'
    const SITE_OPS_DEP_MAN = 'Site Operations Deputy Manager'; // C'
    const SITE_OPS_MAN = 'Site Operations Manager'; // C'

    // Roles for NGIs
    const REG_FIRST_LINE_SUPPORT = 'Regional First Line Support'; // D
    const REG_STAFF_ROD = 'Regional Staff (ROD)'; // D

    const NGI_SEC_OFFICER = 'NGI Security Officer'; // D'
    const NGI_OPS_DEP_MAN = 'NGI Operations Deputy Manager'; // D'
    const NGI_OPS_MAN = 'NGI Operations Manager'; // D'

    // Roles for Projects
    const COD_STAFF = 'COD Staff'; // E
    const COD_ADMIN = 'COD Administrator'; // E
    const EGI_CSIRT_OFFICER = 'EGI CSIRT Officer'; // E
    const COO = 'Chief Operations Officer'; // E

    // Roles for ServiceGroups
    const SERVICEGROUP_ADMIN = 'Service Group Administrator'; // ServiceGroupC'

    // "Other" roles that have slipped by us (see AddRoleTypes.php)
    const CIC_STAFF = 'CIC Staff'; // Pretty sure this role is not required anymore: https://rt.egi.eu/rt/Ticket/Display.html?id=931
    const REG_STAFF = 'Regional Staff';


    /*public static function getRoleTypeClass($roleName) {
        if ($roleName == RoleTypeName::SITE_ADMIN) {
            return 'C';
        }
        if ($roleName == RoleTypeName::SITE_SECOFFICER ||
                $roleName == RoleTypeName::SITE_OPS_DEP_MAN ||
                $roleName == RoleTypeName::SITE_OPS_MAN) {
            return "C'";
        }
        if ($roleName == RoleTypeName::REG_FIRST_LINE_SUPPORT ||
                $roleName == RoleTypeName::REG_STAFF_ROD ) {
            return "D";
        }
        if ($roleName == RoleTypeName::NGI_SEC_OFFICER ||
                $roleName == RoleTypeName::NGI_OPS_DEP_MAN ||
                $roleName == RoleTypeName::NGI_OPS_MAN ) {
            return "D'";
        }
        if ($roleName == RoleTypeName::COD_STAFF ||
                $roleName == RoleTypeName::COD_ADMIN ||
                $roleName == RoleTypeName::EGI_CSIRT_OFFICER ||
                $roleName == RoleTypeName::COO) {
            return "E";
        }
        if ($roleName == RoleTypeName::SERVICEGROUP_ADMIN) {
            return "ServiceGroupC'";
        }
        return null;
    }*/

    /**
     * private constructor to limit instantiation
     */
    private function __construct() {
    }

    /**
     * Get an associative array of the class constants. Array keys are the constant
     * names, array values are the constant values.
     * @return array
     */
    public static function getAsArray() {
        $tmp = new ReflectionClass(get_called_class());
        $a = $tmp->getConstants();        //$b = array_flip($a)
        return $a;
    }

}

/**
 * Define a list of different actions that apply over a target object.
 * <p>
 * A user's granted Role enables a particular action over the target object.
 * For example, RoleTypeName::SITE_OPS_DEP_MAN enables the ObjectAction::ACTION_EDIT_CERT_STATUS action.
 */
class Action {

    // Generic actions that can apply to a particular target object.
    const EDIT_OBJECT = 'ACTION_EDIT_OBJECT';
    const READ_OBJECT = 'ACTION_READ_OBJECT';
    const DELETE_OBJECT = 'ACTION_DELETE_OBJECT';
    const GRANT_ROLE = 'ACTION_GRANT_ROLE';
    const REJECT_ROLE = 'ACTION_REJECT_ROLE';
    const REVOKE_ROLE = 'ACTION_REVOKE_ROLE';

    // Actions that apply to NGIs
    const NGI_ADD_SITE = 'ACTION_NGI_ADD_SITE'; // maybe EDIT_OBJECT will be sufficient

    // Actions that apply to Sites
    const SITE_ADD_SERVICE = 'ACTION_SITE_ADD_SERVICE';
    const SITE_DELETE_SERVICE = 'ACTION_SITE_DELETE_SERVICE';
    const SITE_EDIT_CERT_STATUS = 'ACTION_SITE_EDIT_CERT_STATUS';

    // Actions that apply to Service
    //const SE_ADD_DOWNTIME = 'ACTION_SE_ADD_DOWNTIME';

    //
    const READ_PERSONAL_DATA = 'ACTION_READ_PERSONAL_DATA';

    /**
     * private constructor to limit instantiation
     */
    private function __construct() {
    }

    /**
     * Get an associative array of the class constants. Array keys are the constant
     * names, array values are the constant values.
     * @return array
     */
    public static function getAsArray() {
        $tmp = new ReflectionClass(get_called_class());
        $a = $tmp->getConstants();        //$b = array_flip($a)
        return $a;
    }
}

?>
