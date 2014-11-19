<?php
//use Doctrine\Common\Collections\ArrayCollection;

/**
 * @author James McCarthy 
 * @author David Meredith
 * @Entity @Table(name="ServiceGroup_Properties", uniqueConstraints={@UniqueConstraint(name="sgroup_keypairs", columns={"parentServiceGroup_id", "keyName", "keyValue"})}) 
 */
class ServiceGroupProperty {
   
    /** @Id @Column(type="integer") @GeneratedValue **/
	protected $id;

    /**
     * Bidirectional - Many ServiceGroupProperty (SIDE OWNING FK) can be linked 
     * to one ServiceGroup (OWNING ORM SIDE). 
     *   
     * @ManyToOne(targetEntity="ServiceGroup", inversedBy="serviceGroupProperties") 
     * @JoinColumn(name="parentServiceGroup_id", referencedColumnName="id", onDelete="CASCADE")	 
     */
    protected $parentServiceGroup = null; 
    
    /** @Column(type="string", nullable=false) */
    protected $keyName = null; 
    
    /** @Column(type="string", nullable=true) */
    protected $keyValue = null; 
   
    public function __construct() {
	}

    public function getParentServiceGroup(){
        return $this->parentServiceGroup; 
    }
	
    public function getKeyName(){
        return $this->keyName; 
    }
    public function getKeyValue(){
        return $this->keyValue; 
	}

    public function getId() {
        return $this->id;
    }

    /**
     * Do not call in client code, always use the opposite
     * <code>$site->addServiceGroupPropertyDoJoin($serviceGroupProperty)</code>
     * instead which internally calls this method to keep the bidirectional 
     * relationship consistent.  
     * <p>
     * This is the OWNING side of the ORM relationship so this method WILL 
     * establish the relationship in the database. 
     * 
     * @param \ServiceGroup $serviceGroup
     */
    public function _setParentServiceGroup($serviceGroup){
        $this->parentServiceGroup = $serviceGroup; 
    }

    public function setKeyName($keyName){
        $this->keyName = $keyName; 
    }
	
	public function setKeyValue($keyValue){
        $this->keyValue = $keyValue; 
    }
	
}

?>
