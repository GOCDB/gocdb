<?php

/**
 * Get/set the directory path (context path) that is used as a prefix in
 * the relative path of web assests (e.g. in the 'src' value of javascript and images).
 * <p>
 * This is needed if deploying gocdb within another environment (such as a Symfony
 * component) which may require a context-path to that component.
 * If deploying GOCDB standalone, this path will normally be an empty string.
  </p>
 * Sample usage within an html view:
 * <code>
 *    <img src="<?php echo \GocContextPath::getPath()?>img/site.png"/>
 * </code>
 *
 * @author David Meredith
 */
class GocContextPath {
    private static $path = '';

    public static function getPath() {
        return self::$path;
    }
    public static function setPath($path){
        self::$path = $path;
    }

}
