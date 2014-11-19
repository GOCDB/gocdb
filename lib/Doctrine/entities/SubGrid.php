<?php
use Doctrine\Common\Collections\ArrayCollection;
/**
 * @Entity @Table(name="SubGrids")
 */
class SubGrid {
	/** @Id @Column(type="integer") @GeneratedValue **/
	protected $id;
	/** @Column(type="string", unique=true) **/
	protected $name;
	/** @OneToMany(targetEntity="Site", mappedBy="subGrid") **/
	protected $sites = null;
	/** @ManyToOne(targetEntity="NGI") 
     *  @JoinColumn(name="NGI_Id", referencedColumnName="id", onDelete="CASCADE")**/
	protected $ngi;
	
	public function getId() {
		return $this->id;
	}
	
	public function getName() {
		return $this->name;
	}
	
	public function getSites() {
		return $this->sites;
	}
	
	public function getNgi() {
		return $this->ngi;
	}
	
	public function setName($name) {
		$this->name = $name;
	}
	
	public function addSiteDoJoin($site) {
		$this->sites[] = $site;
		$site->setSubGrid($this);
	}
	
	public function setNgi($ngi) {
		$this->ngi = $ngi;
	}
	
	public function __construct() {
		$this->sites = new ArrayCollection();
	}
}