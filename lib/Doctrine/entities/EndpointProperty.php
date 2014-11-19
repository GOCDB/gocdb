<?php
//use Doctrine\Common\Collections\ArrayCollection;
/**
 * @author David Meredith
 * @Entity @Table(name="Endpoint_Properties", uniqueConstraints={@UniqueConstraint(name="endpointproperty_keypairs", columns={"parentEndpoint_id", "keyName", "keyValue"})}) 
 */
class EndpointProperty {
   
    /** @Id @Column(type="integer") @GeneratedValue **/
	protected $id;

    /**
     * Bidirectional - Many EndpointProperties (SIDE THAT OWNS FK) 
     * can be linked to one EndpointLocation (OWNING ORM SIDE). 
     *   
     * @ManyToOne(targetEntity="EndpointLocation", inversedBy="endpointProperties") 
     * @JoinColumn(name="parentEndpoint_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $parentEndpoint= null; 
    
    /** @Column(type="string", nullable=false) */
    protected $keyName = null; 
    /** @Column(type="string", nullable=true) */
    protected $keyValue = null; 
   
    public function __construct() {
	}

    public function getParentEndpoint(){
        return $this->parentEndpoint; 
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
     * <code>$site->addEndpointPropertyDoJoin($endpointProperty)</code>
     * instead which internally calls this method to keep the bidirectional 
     * relationship consistent.  
     * <p>
     * This is the OWNING side of the ORM relationship so this method WILL 
     * establish the relationship in the database. 
     * 
     * @param \EndpointLocation $endpoint
     */
    public function _setParentEndpoint(\EndpointLocation $endpoint){
        $this->parentEndpoint = $endpoint; 
    }

    public function setKeyName($keyName){
        $this->keyName = $keyName; 
    }
	
	public function setKeyValue($keyValue){
        $this->keyValue = $keyValue; 
    }
	
}

?>
