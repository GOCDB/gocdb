<?php
// Project.php
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity @Table(name="Projects")
 */
class Project extends OwnedEntity {
    /*
     * @Id
     * @Column(type="integer") 
     * @GeneratedValue
     */
    //protected $id;

    /** @Column(type="string", unique=true) */
    protected $name;

    /** @Column(type="string", length=2000, nullable=true) */
    protected $description;
    
    /**
     * Bidirectional - Many Projects (OWNING SIDE) can link to many NGIs 
     * 
     * @ManyToMany(targetEntity="NGI", inversedBy="projects")
     * @JoinTable(name="Projects_NGIs",
     *      joinColumns={@JoinColumn(name="project_Id", referencedColumnName="id")},
     *      inverseJoinColumns={@JoinColumn(name="ngi_Id", referencedColumnName="id")}
     *      )
     */
    protected $ngis=null;
    
    /* DATETIME NOTE:
	 * Doctrine checks whether a date's been updated by doing a byreference comparison.
	 * If you just update an existing DateTime object, Doctrine won't persist it!
	 * Create a new DateTime object and reference that for it to persist during an update.
	 * http://docs.doctrine-project.org/en/2.0.x/cookbook/working-with-datetime.html
	 */
    
    /** @Column(type="datetime", nullable=false) **/
	protected $creationDate;

    /**
     * Create a new project
     * @param string $name The project's name
     */
    public function __construct($name) {
        parent::__construct(); 
        
        // Make sure all dates are treated as UTC!
	    date_default_timezone_set("UTC");
        
        // Set cretion date
        $this->creationDate =  new \DateTime("now");
        
    	$this->setName($name);
        $this->ngis = new ArrayCollection();
    }

    
    //public function getId() {
    //    return $this->id;
    //}

    public function getName() {
        return $this->name;
    }

    public function getDescription() {
        return $this->description;
    }
    
    public function getNgis() {
        return $this->ngis;
    }

    public function setName($name) {
        $this->name = $name;
    }

    public function setDescription($description) {
        $this->description = $description;
    }
    
    public function addNgi(\NGI $ngi){
        $this->ngis[]= $ngi;
        $ngi->addProject($this);
    }

    /**
     * Returns value of {@link \OwnedEntity::TYPE_PROJECT}
     * @see \OwnedEntity::getType()
     * @return string 
     */
    public function getType() {
        return parent::TYPE_PROJECT; 
    }
    
}
