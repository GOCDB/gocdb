<?php
// NGI.php
use Doctrine\Common\Collections\ArrayCollection;

require_once 'Site.php';
require_once 'IScopedEntity.php';

/**
 * @Entity @Table(name="NGIs")
 */
class NGI extends OwnedEntity implements IScopedEntity {

    //commented out as this is duplication - id is also defined in the Owned entity class
    /* 
     * @Id 
     * @Column(type="integer") 
     * @GeneratedValue 
     */
    //protected $id;

    /** @Column(type="string", unique=true) */
    protected $name;

    /** @Column(type="string", nullable=true) */
    protected $email;

    /** @Column(type="string", nullable=true) */
    protected $rodEmail;

    /** @Column(type="string", nullable=true) */
    protected $helpdeskEmail;

    /** @Column(type="string", nullable=true) */
    protected $securityEmail;
    
    /** @Column(type="string", nullable=true) */
    protected $description;

    /** @Column(type="string", nullable=true) */
    protected $ggus_Su;

    /** @OneToMany(targetEntity="Site", mappedBy="ngi") */
    protected $sites = null;
    
    /**
     * Bidirectional - Many NGIs (INVERSE SIDE) can link to many Projects  
     * 
     * @ManyToMany(targetEntity="Project", mappedBy="ngis")
     */
    protected $projects = null;
    
    /**    
     * Unidirectional - Scope tags associated with the NGI
     * 
     * @ManyToMany(targetEntity="Scope")
     * @JoinTable(name="Ngis_Scopes",
     *      joinColumns={@JoinColumn(name="ngi_Id", referencedColumnName="id")},
     *      inverseJoinColumns={@JoinColumn(name="scope_Id", referencedColumnName="id")}
     *      )
     */
    protected $scopes = null;
    
    /* DATETIME NOTE:
	 * Doctrine checks whether a date's been updated by doing a byreference comparison.
	 * If you just update an existing DateTime object, Doctrine won't persist it!
	 * Create a new DateTime object and reference that for it to persist during an update.
	 * http://docs.doctrine-project.org/en/2.0.x/cookbook/working-with-datetime.html
	 */
    
    /** @Column(type="datetime", nullable=false) **/
	protected $creationDate;


    public function __construct() {
        parent::__construct();
        
        // Set cretion date
        $this->creationDate =  new \DateTime("now");
        
        $this->sites = new ArrayCollection();
        $this->projects = new ArrayCollection();
        $this->scopes = new ArrayCollection();
    }

    //public function getId() {
    //    return $this->id;
    //}

    public function getName() {
        return $this->name;
    }

    public function getEmail() {
        return $this->email;
    }

    public function getRodEmail() {
        return $this->rodEmail;
    }

    public function getHelpdeskEmail() {
        return $this->helpdeskEmail;
    }

    public function getSecurityEmail() {
        return $this->securityEmail;
    }
    
    public function getDescription() {
    	return $this->description;
    }

    public function getGgus_Su(){
        return $this->ggus_Su; 
    }
    
    public function getSites() {
        return $this->sites;
    }

    public function getProjects() {
        return $this->projects;
    }
    
    public function getScopes() {
        return $this->scopes;
    }
    
    public function getCreationDate() {
        return $this->creationDate;
    }
        
     /**
     * provides a string containg a list of the names of scopes with which the 
     * object been tagged.
     * @return string  string containing ", " seperated list of the names 
     */ 
    public function getScopeNamesAsString() {
        //Get the scopes for the NGI
        $scopes = $this->getScopes();
        
        //Create an empty array to contain scope names
        $scopeNames= array();
        
        //populate the array
        foreach ($scopes as $scope){
            $scopeNames[]=$scope->getName();
        }
        
        sort($scopeNames);

        //Turn into a string
        $scopeNamesAsString = implode(", " , $scopeNames);
        
        return $scopeNamesAsString;
    }
    
    /**
     * Add the given site to the NGI list.  
     * 
     * @see Site::setNgiDoJoin()
     * @param Site $site
     */
    public function addSiteDoJoin(Site $site) {
        $this->sites[] = $site;
        $site->setNgiDoJoin($this);
    }
    
    /**
     * Note this is called by addNgi($ngi) in the project class. $prj->addNgi($ngi)
     * should always be used rather than $ngi->addProject($prj) 
     * @param \Project $project
     */
    public function addProject(\Project $project) {
        $this->projects[]=$project;
    }

    public function setEmail($email) {
        $this->email = $email;
    }

    public function setRodEmail($rodEmail) {
        $this->rodEmail = $rodEmail;
    }

    public function setHelpdeskEmail($helpdeskEmail) {
        $this->helpdeskEmail = $helpdeskEmail;
    }

    public function setSecurityEmail($securityEmail) {
        $this->securityEmail = $securityEmail;
    }
    
    public function setName($name) {
    	$this->name = $name;
    }
    
    public function setDescription($description) {
    	$this->description = $description;
    }

    public function setGgus_Su($ggus_su){
        $this->ggus_Su = $ggus_su; 
    }
    
    public function addScope(Scope $scope) {
        $this->scopes[] = $scope;
    }

    public function setCreationDate($creationDate) {
        $this->creationDate = $creationDate;
    }
    
    /**
     * Removes the association between this site and a scope
     *
     * @param Scope $removeScope The scope to be removed.
     */
    public function removeScope(Scope $removeScope) {
        $this->scopes->removeElement($removeScope);
    }

    /**
     * Returns value of {@link \OwnedEntity::TYPE_NGI}
     * @see \OwnedEntity::getType()
     * @return string 
     */
    public function getType() {
        return parent::TYPE_NGI; 
    }

}