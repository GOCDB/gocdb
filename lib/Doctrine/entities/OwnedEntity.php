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
abstract class OwnedEntity {
	/** @Id @Column(type="integer") @GeneratedValue **/
	protected $id;

    const TYPE_NGI = 'ngi'; 
    const TYPE_SITE = 'site'; 
    const TYPE_SERVICEGROUP = 'servicegroup'; 
    const TYPE_PROJECT = 'project'; 
	
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

    /**
     * Get the entity type as a string.  
     * The value is same as the discriminator column of the database (case may be different). 
     * <p>
     * Returns one of the following depending on the extending class type:
     * <ul>
     * <li>OwnedEntity::TYPE_NGI</li>
     * <li>OwnedEntity::TYPE_PROJECT</li>
     * <li>OwnedEntity::TYPE_SERVICEGROUP</li>
     * <li>OwnedEntity::TYPE_SITE</li>
     * </ul>
     * @return string The entity type as a string. 
     */
    abstract public function getType();
}