<?php
use Doctrine\Common\Collections\ArrayCollection;
/**
 * A parent class for entites that a user can have a role over.
 * 
 * @Entity  @Table(name="OwnedEntities")
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({"site" = "Site", "ngi" = "NGI", "project" = "Project",
 * 						"serviceGroup" = "ServiceGroup"})
 */
class OwnedEntity {
	/** @Id @Column(type="integer") @GeneratedValue **/
	protected $id;
	
	/** 
     * @OneToMany(targetEntity="Role", mappedBy="ownedEntity") 
     * @OrderBy({"id" = "ASC"})
     */
	protected $roles = null;
	
	public function getRoles() {
		return $this->roles;
	}
	
	public function getId() {
		return $this->id;
	}
	
	public function addRoleDoJoin(\Role $role) {
		$this->roles[] = $role;
		$role->setOwnedEntity($this);
	}
	
	public function __construct() {
		$this->roles = new ArrayCollection();
	}
}