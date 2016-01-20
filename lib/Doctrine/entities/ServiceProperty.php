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
//use Doctrine\Common\Collections\ArrayCollection;

/**
 * Custom Key=Value pairs (extension properties) used to augment a {@see Service}
 * object with additional attributes. These properties can also be used for 
 * the purposes of resource matching.
 * <p>
 * A unique constraint is defined on the DB preventing key=value duplicates. 
 * Note, duplicate key names with different values are allowed for the purpose 
 * of defning multi-valued properties. 
 * <p>
 * When the owning parent Service is deleted, its ServiceProperties 
 * are also cascade-deleted. 
 * 
 * @author James McCarthy 
 * @author David Meredith
 * @Entity @Table(name="Service_Properties", uniqueConstraints={@UniqueConstraint(name="serv_keypairs", columns={"parentService_id", "keyName", "keyValue"})}) 
 */
class ServiceProperty {
   
    /** @Id @Column(type="integer") @GeneratedValue */
    protected $id;

    /**
     * Bidirectional - Many ServiceProperties (SIDE THAT OWNS FK) can be 
     * linked to one Service (OWNING ORM SIDE). 
     *   
     * @ManyToOne(targetEntity="Service", inversedBy="serviceProperties") 
     * @JoinColumn(name="parentService_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $parentService = null; 
    
    /** @Column(type="string", nullable=false) */
    protected $keyName = null; 
    
    /** @Column(type="string", nullable=true) */
    protected $keyValue = null; 
   
    public function __construct() {
    }

    /**
     * Get the owning {@see Service} entity. When the service is deleted, 
     * the ServiceProperties are also cascade-deleted. 
     * @return \Service
     */
    public function getParentService(){
        return $this->parentService; 
    }

    /**
     * Get the key name, usually a simple alphaNumeric name, but this is not 
     * enforced by the entity. 
     * @return string
     */
    public function getKeyName(){
        return $this->keyName; 
    }

    /**
     * Get the key value, can contain any char. 
     * @return String
     */
    public function getKeyValue(){
        return $this->keyValue; 
    }

    /**
     * @return int The PK of this entity or null if not persisted
     */
    public function getId() {
        return $this->id;
    }
    
    /**
     * Do not call in client code, always use the opposite
     * <code>$site->addServicePropertyDoJoin($serviceProperty)</code>
     * instead which internally calls this method to keep the bidirectional 
     * relationship consistent.  
     * <p>
     * This is the OWNING side of the ORM relationship so this method WILL 
     * establish the relationship in the database. 
     * 
     * @param \Service $service
     */
    public function _setParentService(\Service $service){
        $this->parentService = $service; 
    }

    /**
     * The custom keyname of this key=value pair. 
     * This value should be a simple alphanumeric name without special chars, but 
     * this is not enforced here by the entity.   
     * @param string $keyName
     */
    public function setKeyName($keyName){
        $this->keyName = $keyName; 
    }

    /**
     * The custom value of this key=value pair. 
     * This value can contain any chars. 
     * @param string $keyValue
     */
    public function setKeyValue($keyValue){
        $this->keyValue = $keyValue; 
    }
    
}

?>
