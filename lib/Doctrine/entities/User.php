<?php
use Doctrine\Common\Collections\ArrayCollection;
/**
 * @Entity @Table(name="Users", options={"collate"="utf8_bin"})
 */
class User {
	/** @Id @Column(type="integer") @GeneratedValue **/
	protected $id;
	/** @Column(type="string") **/
	protected $forename;
    /** @Column(type="string") **/
	protected $surname;
	/** @Column(type="string", nullable=true) **/
	protected $title = null;
    /** @Column(type="string", nullable=true) **/
	protected $email = null;
    /** @Column(type="string", nullable=true) **/
	protected $telephone = null;
    /** @Column(type="string", nullable=true) **/
	protected $workingHoursStart = null;
    /** @Column(type="string", nullable=true) **/
	protected $workingHoursEnd = null;
    /** @Column(type="string", unique=true) **/
	protected $certificateDn = null;
    /** @Column(type="string", nullable=true) **/
    protected $username1 = null; 
	/** @Column(type="boolean") */
	protected $isAdmin;
	/** @OneToMany(targetEntity="Role", mappedBy="user") **/
	protected $roles = null;

    /**
     * @ManyToOne(targetEntity="Site", inversedBy="users")
     * @JoinColumn(name="homeSite_id", referencedColumnName="id", onDelete="SET NULL")
     */
	protected $homeSite = null;

    /** @Column(type="datetime", nullable=false) **/
	protected $creationDate;
    
	public function __construct() {
        
        // Set cretion date
        $this->creationDate =  new \DateTime("now");
        
        $this->sites = new ArrayCollection();
		$this->roles = new ArrayCollection();
	}
    
	public function getId() {
		return $this->id;
	}

	public function getForename() {
		return $this->forename;
	}

    public function getSurname() {
		return $this->surname;
	}

    public function getTitle() {
		return $this->title;
	}

    public function getEmail() {
		return $this->email;
	}

    public function getTelephone() {
		return $this->telephone;
	}

    public function getWorkingHoursStart() {
		return $this->workingHoursStart;
	}

    public function getWorkingHoursEnd() {
		return $this->workingHoursEnd;
	}
    
    public function getCertificateDn() {
		return $this->certificateDn;
	}

    public function getUsername1(){
        return $this->username1; 
    }

    public function getHomeSite() {
        return $this->homeSite;
    }

    public function getCreationDate() {
        return $this->creationDate;
    }
    
    public function isAdmin() {
        return $this->isAdmin;
    }

	public function setForename($forename) {
		$this->forename = $forename;
	}

	public function setSurname($surname) {
        $this->surname = $surname;
    }

    public function setTitle($title) {
        $this->title = $title;
    }

    public function setEmail($email) {
        $this->email = $email;
    }

    public function setTelephone($telephone) {
        $this->telephone = $telephone;
    }

    public function setWorkingHoursStart($workingHoursStart) {
        $this->workingHoursStart = $workingHoursStart;
    }

    public function setWorkingHoursEnd($workingHoursEnd) {
        $this->workingHoursEnd = $workingHoursEnd;
    }

    public function setCertificateDn($certificateDn) {
        $this->certificateDn = $certificateDn;
    }

    public function setUsername1($username1){
        $this->username1 = $username1; 
    }

    public function setHomeSiteDoJoin(Site $homeSite) {
        $this->homeSite = $homeSite;
    }

    public function setAdmin($isAdmin) {
        $this->isAdmin = $isAdmin;
    }
    
    public function setCreationDate($creationDate) {
        $this->creationDate = $creationDate;
    }
    
    public function getFullName() {
        return $this->forename . " " . $this->surname;
    }

    public function addRoleDoJoin(\Role $role) {
    	$this->roles[] = $role;
    	$role->setUser($this);
    }

    public function getRoles() {
    	return $this->roles;
    }


}