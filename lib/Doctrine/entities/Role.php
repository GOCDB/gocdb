<?php
//use Doctrine\Common\Collections\ArrayCollection;
/**
 * Role establishes that the User has the specified RoleType over the OwnedEntity.
 * The Role->status value indicates the status of the Role (status values are not defined here).
 * For example, a Role could double as a Role-Request if its status was e.g. 'STATUS_PENDING'
 * and become an official Role if updated to 'STATUS_GRANTED'.
 * <p>
 * A single Role has ManyToOne relationships with RoleType, User and OwnedEntity
 * (Role is on the many side).
 * <p>
 * A joined Role must be deleted before a related User, OwnedEntity or RoleType
 * can be deleted.
 *
 * NoDuplicateRoles: Don't allow creation of user + role type + ownedentity combinations that 
 * already exist  
 * @Entity @Table(name="Roles", uniqueConstraints={@UniqueConstraint(name="NoDuplicateRoles", columns={"user_id", "roleType_id", "ownedEntity_id"})})
 *
 */
class Role {
	/** @Id @Column(type="integer") @GeneratedValue **/
	protected $id;

	/** @ManyToOne(targetEntity="RoleType") **/
    protected $roleType;

    /** 
     * @ManyToOne(targetEntity="User", inversedBy="roles")
     * @JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $user;

    /**
     * Allows polymorphic queries - a role can be related to an OwnedEntity whose
     * type can be different, e.g. Site, NGI. The onDelete means that if the 
     * OwnedEntity is deleted, then all linked Roles are also deleted. 
     * 
     * @ManyToOne(targetEntity="OwnedEntity", inversedBy="roles")
     * @JoinColumn(name="ownedEntity_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $ownedEntity = null;

    /** 
     * Current status of this role, e.g. STATUS_GRANTED, STATUS_PENDING
     * @Column(type="string", nullable=false) 
     */
    protected $status;

    ///** @Column(type="string", nullable=false) */
    //protected $name;

    ///** @Column(type="integer") **/
	//protected $lastUpdatedByUserId;

    /*
     * A transient extension object used to decorate this instance with extra information. 
     * Note, this object is <b>NOT</b> persisted to the DB. It is intended for transient
     * operations such as holding extra view parameters for transer/rendering in the view layer.  
     */
    protected $decoratorObject; 
    
    /**
     * Create a new role which establishes that the specified User has the
     * RoleType over the OwnedEntity.
     *
     * @param \RoleType $roleType The role's type
     * @param strig $name The role name 
     * @param \User $user The role is for this user
     * @param \OwnedEntity $ownedEntity The role is over this entity
     * @param string $status The current Role status, e.g. 'STATUS_PENDING' for
     *   role requests and 'STATUS_GRANTED' for granted roles.
     */
    public function __construct(\RoleType $roleType, \User $user, \OwnedEntity $ownedEntity, $status) {
        $this->roleType = $roleType;
        $this->setStatus($status);
        //$this->setName($name); 
        $ownedEntity->addRoleDoJoin($this);
        $user->addRoleDoJoin($this);
        // @link http://www.doctrine-project.org/blog/doctrine-2-give-me-my-constructor-back.html Using constructors in Entities
    }

    public function getId() {
		return $this->id;
	}

	public function getRoleType() {
        return $this->roleType;
    }

    public function getUser() {
        return $this->user;
    }

    public function getOwnedEntity() {
    	return $this->ownedEntity;
    }

    public function setRoleType($roleType) {
        $this->roleType = $roleType;
    }

    public function setUser($user) {
        $this->user = $user;
    }

    /**
     * A Role can have a relationship with different types of OwnedEntity (e.g.
     * Site, NGI and other OwnedEntity sub-classes).
     *
     * @param \OwnedEntity $owned The associated entity
     */
    public function setOwnedEntity(\OwnedEntity $owned) {
    	$this->ownedEntity = $owned;
    }

    public function getStatus(){
        return $this->status;
    }

    public function setStatus($status){
        if(!is_string($status)){
            throw new RuntimeException('String expected for status value');
        }
        $this->status = $status;
    }

    /*public function getName(){
        return $this->name;
    }

    public function setName($name){
        if(!is_string($name)){
            throw new RuntimeException('String expected for name value');
        }
        $this->name = $name;
    }*/

    /**
     * Set a transient extension object used to decorate this instance with extra information. 
     * Note, this object is <b>NOT</b> persisted to the DB. It is intended for transient
     * operations such as holding extra view parameters for transer/rendering in the view layer.  
     *   
     * @param mixed $decoratorObject
     */
    public function setDecoratorObject($decoratorObject){
      $this->decoratorObject = $decoratorObject;     
    }

    /**
     * Get the transient extension object used to decorate this instance with extra information. 
     * Note, this object is <b>NOT</b> persisted to the DB. It is intended for transient
     * operations such as holding extra view parameters for transer/rendering in the view layer.   
     * @return mixed or null  
     */
    public function getDecoratorObject(){
       return $this->decoratorObject;  
    } 

}