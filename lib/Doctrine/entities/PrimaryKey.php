<?php
//use Doctrine\Common\Collections\ArrayCollection;
/*
 * Used to create the PRIMARY_KEY field exposed through the PI.
 * This maintains backwards compatiblity with the v4 PRIMARY_KEY FIELDS. 
 * This sequence can be used when required to get the next primary key by 
 * creating and persisting an instance of PrimarKey and calling the getId() 
 * method. This value can then be used to programatically set the legacy 
 * PRIMARY_KEY fields when persisting new entities. 
 */
/**
 * @Entity @Table(name="PrimaryKeys")
 */
class PrimaryKey {
	/** @Id @Column(type="integer") @GeneratedValue **/
	protected $id;

	public function getId() {
		return $this->id;
	}

	public function __toString() {
	    return $this->id;
	}

}