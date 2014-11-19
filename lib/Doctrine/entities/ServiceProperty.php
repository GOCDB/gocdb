<?php
//use Doctrine\Common\Collections\ArrayCollection;
/**
 * @author James McCarthy 
 * @author David Meredith
 * @Entity @Table(name="Service_Properties", uniqueConstraints={@UniqueConstraint(name="serv_keypairs", columns={"parentService_id", "keyName", "keyValue"})}) 
 */
class ServiceProperty {
   
    /** @Id @Column(type="integer") @GeneratedValue **/
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

    public function getParentService(){
        return $this->parentService; 
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

    public function setKeyName($keyName){
        $this->keyName = $keyName; 
    }
	
	public function setKeyValue($keyValue){
        $this->keyValue = $keyValue; 
    }
	
}

?>
