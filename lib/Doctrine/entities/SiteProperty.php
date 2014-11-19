<?php
//use Doctrine\Common\Collections\ArrayCollection;

/**
 * @author James McCarthy 
 * @author David Meredith
 * @Entity @Table(name="Site_Properties",uniqueConstraints={@UniqueConstraint(name="site_keypairs", columns={"parentSite_id", "keyName", "keyValue"})}) 
 */
class SiteProperty {
   
    /** @Id @Column(type="integer") @GeneratedValue **/
	protected $id;

    /**
     * Bidirectional - Many SiteProperties (SIDE OWNING FK) can be linked to 
     * one Site (OWNING ORM SIDE). 
     *   
     * @ManyToOne(targetEntity="Site", inversedBy="siteProperties") 
     * @JoinColumn(name="parentSite_id", referencedColumnName="id", onDelete="CASCADE")	 
     */
    protected $parentSite = null; 
    
    /** @Column(type="string", nullable=false) */
    protected $keyName = null; 
    
    /** @Column(type="string", nullable=true) */
    protected $keyValue = null; 
   
    public function __construct() {
	}

    public function getParentSite(){
        return $this->parentSite; 
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
     * <code>$site->addSiteProperties($siteProperties)</code>
     * instead which internally calls this method to keep the bidirectional 
     * relationship consistent.  
     * <p>
     * This is the OWNING side of the ORM relationship so this method WILL 
     * establish the relationship in the database. 
     * 
     * @param \Site $site
     */
    public function _setParentSite($site){
        $this->parentSite = $site; 
    }

    public function setKeyName($keyName){
        $this->keyName = $keyName; 
    }
	
	public function setKeyValue($keyValue){
        $this->keyValue = $keyValue; 
    }
	
}

?>
