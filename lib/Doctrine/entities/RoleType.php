<?php
//use Doctrine\Common\Collections\ArrayCollection;
/**
 * A <code>RoleType</code> defines the type of <code>Role</code>. 
 * The <code>name</code> is required and must be unique in the database. 
 * 
 * @Entity @Table(name="RoleTypes")
 */
class RoleType {
	/** @Id @Column(type="integer") @GeneratedValue **/
	protected $id;
	/** @Column(type="string", unique=true) **/
	protected $name;
    
	/** @  Column(type="string", nullable=true)  
     * The <code>classification</code> is an extra tag that can be used to group different   
     * role types with a common classification and is nullable. For example, role 
     * types with the following name 'RoleRunner,' 'RoleSwimmer' and 'RoleWalker' could all share 
     * the same 'SportRole' classification. Deprecated so commented out. 
     */
	//protected $classification;

    /**
     * Create a new instance. The given name must have a value and be unique in the database. 
     * 
     * @param string $name The roleType name. 
     * @throws RuntimeException if the given args are not strings 
     */
    public function __construct($name/*, $classification*/) {
        $this->setName($name); 
        //$this->setClassification($classification);  
	}
    
	public function getId() {
		return $this->id;
	}
	
	public function getName() {
		return $this->name;
	}
    
    //public function getClassification() {
	//	return $this->classification;
	//}
    
    /**
     * Set/update the name of the RoleType. It must be unique in the DB. 
     * @param string $name
     * @throws RuntimeException if the given param is not a string 
     */	
	public function setName($name) {
        if(!is_string($name)){
            throw new RuntimeException('Required string for $name');
        }
		$this->name = $name;
	}
	
    /**
     * Set/update the roletype classification. 
     * @param string $classification or null
     * @throws RuntimeException if the given param is not null and not a string. 
     */
	//public function setClassification($classification) {
    //    if($classification != null && !is_string($classification)){
    //        throw new RuntimeException('Required string for $classification');
    //    }
    //    $this->classification = $classification;
    //}
	

}