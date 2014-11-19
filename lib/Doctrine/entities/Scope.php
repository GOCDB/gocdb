<?php
//use Doctrine\Common\Collections\ArrayCollection;
/**
 * @Entity @Table(name="Scopes")
 */
class Scope {
	/** @Id @Column(type="integer") @GeneratedValue **/
	protected $id;
	/** @Column(type="string", unique=true) **/
	protected $name;
    /** @Column(type="string", nullable=true) **/
	protected $description;

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

	public function __toString() {
	    return $this->getName();
	}

}