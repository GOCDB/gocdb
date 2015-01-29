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

include_once __DIR__ . '/_autoload.php';

/**
 * Singleton to create and serve one or more <code>IFirewallComponent</code>
 * implementations. FirewallComponent instances are created once and are  
 * accessible via the getFirewallArray() method.  
 *
 * @author David Meredith 
 */
class FirewallComponentManager {

    private $firewallComponents;
    static $_instance;

    private function __construct() {
        //echo 'is only called once';
        $this->firewallComponents = array();
        $this->createFwC1(); 
        //$this->createFwC2();  // create/add as many FirewallComponentS as needed  
    }

    private function createFwC1(){
        // create dependent objects 
        $myConfig1 = new MyConfig1();
        $mySecurityContext = new MySecurityContext();
        $myAuthManager = new MyAuthenticationManager();
        
        // set dependencies 
        $mySecurityContext->setAuthManager($myAuthManager);
        $mySecurityContext->setConfigFirewallComponent($myConfig1);
        // set dependencies 
        $myAuthManager->setSecurityContext($mySecurityContext);
        $myAuthManager->setConfigFirewallComponent($myConfig1);
        
        // create FirewallComponent and add to our singleton array  
        $fwComponent1 = new FirewallComponent($myAuthManager, $mySecurityContext, $myConfig1);
        $this->firewallComponents['fwC1'] = $fwComponent1;
    }


    private function __clone() {
       // defining an empty clone closes small loophole in PHP that could make 
       // a copy of the object and defeat singletone responsibility 
    }

    public static function getInstance() {
        if (!(self::$_instance instanceof self)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Get an associative array of <code>IFirewallComponent</code> instances 
     * @return array 
     */
    public function getFirewallArray() {
        return $this->firewallComponents;
    }

}
