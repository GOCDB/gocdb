<?php
/*
 * Copyright (C) 2015 STFC
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

/**
 * A scoped entity is one that can be tagged with a {@see Scope} tag 
 * for the purposes of filtering based on the object's related scopes. 
 * <p>
 * Implementations include {@see Site}, {@see Service}, {@see ServiceGroup} and 
 * {@see NGI}. These resources can then be selected according to the 
 * scopes they are joined with. 
 * 
 * @author David Meredith <david.meredithh@stfc.ac.uk> 
 * @author John Casson 
 */
interface IScopedEntity {
    
    /**
     * @return Doctrine\Common\Collections\ArrayCollection List of {@see Scope} entities.  
     */
    public function getScopes();

    /**
     * A string of comma-separated scope names which tag this object.   
     * @return string  
     */
    public function getScopeNamesAsString();

    /**
     * Create a relationship between this entity and the given scope object.    
     * @param Scope $scope
     */
    public function addScope(Scope $scope);

    /**
     * Remove the relationship between the given scope and this entity.  
     * @param Scope $removeScope
     */
    public function removeScope(Scope $removeScope);
            
}