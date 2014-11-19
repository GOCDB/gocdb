<?php

/**
 * Abstract class for DAOs. Contains entity managment functions.
 *
 * @author George Ryall
 */
class AbstractDAO {
    protected $em;

    public function __construct() {
    }

    /**
     * Set the EntityManager instance used by all DAO. 
     * @param \Doctrine\ORM\EntityManager $em
     */
    public function setEntityManager(\Doctrine\ORM\EntityManager $em){
        $this->em = $em;  
    }
    
    /**
     * @return \Doctrine\ORM\EntityManager 
     */
    public function getEntityManager(){
        return $this->em; 
    }
}