<?php

/*
 * Copyright (C) 2012 STFC
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace org\gocdb\security\authentication;

/**
 * Safe autoloader that a) registers any existing '__autoload' functions with
 * the 'spl_autoload_register' (eg __autoloadS defined in other component),
 * and b) registers an additional autoloader for this security component.
 *
 * Typically autoload code is included in a config file or some other file that
 * is included with each pageload and contains common code required for the site to run.
 *
 * @author David Meredith
 */

if(false === spl_autoload_functions()){
    if(function_exists('__autoload')){
        spl_autoload_register('__autoload', false);
    }
}


function securityLoader($className){
    // $className is namespace qualified, e.g. org\gocdb\security\authentication\GOCDBAuthProvider
    $parts = explode('\\', $className); //split out namespaces
    $classNameNoNS = end($parts);//get classname (DONT lowercase className, the file won't be located on *nix)

    //Folder handling which returns classfile
    $file = __DIR__.'/'.$classNameNoNS.'.php';
    if(file_exists($file)){
       require_once $file;
       return;
    }
    $file = __DIR__.'/AuthProviders/'.$classNameNoNS.'.php';
    if(file_exists($file)){
       require_once $file;
       return;
    }
    $file = __DIR__.'/AuthTokens/'.$classNameNoNS.'.php';
    if(file_exists($file)){
       require_once $file;
       return;
    }
    $file = __DIR__.'/Exceptions/'.$classNameNoNS.'.php';
    if(file_exists($file)){
       require_once $file;
       return;
    }
    $file = __DIR__.'/UserDetails/'.$classNameNoNS.'.php';
    if(file_exists($file)){
       require_once $file;
       return;
    }
    $file = __DIR__.'/UserDetailsServices/'.$classNameNoNS.'.php';
    if(file_exists($file)){
       require_once $file;
       return;
    }
}


spl_autoload_register(__NAMESPACE__ .'\securityLoader', false);

