<?php
// ServiceType.php
use Doctrine\Common\Collections\ArrayCollection;
/**
 * @Entity @Table(name="ServiceTypes")
 */
class ServiceType {
	/** @Id @Column(type="integer") @GeneratedValue **/
	protected $id;
	/** @Column(type="string", unique=true) **/
	protected $name;
	/** @Column(type="string") **/
	protected $description;
	/** @OneToMany(targetEntity="Service", mappedBy="serviceType") **/
	protected $services = null;
        
        	
	public function __construct() {
		$this->services = new ArrayCollection();
	}
	
	public function getId() {
		return $this->id;
	}
	
	public function getName() {
		return $this->name;
	}
	
	public function getDescription() {
		return $this->description;
	}
	
	public function setName($name) {
		$this->name = $name;
	}
	
	public function setDescription($description) {
		$this->description = $description;
	}
	
	public function getServices() {
		return $this->services;
	}
	
	public function addService($service) {
		$this->services[] = $service;
		$service->setServiceType($this);
	}
	
	public function __toString() {
		return $this->getName();
	}

}