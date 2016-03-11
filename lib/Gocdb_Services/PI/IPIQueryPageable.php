<?php

namespace org\gocdb\services;


/**
 * Defines functions for queries that support paging. 
 * 
 * @author David Meredith <david.meredith@stfc.ac.uk>
 * @author Tom Byrne 
 *
 */
interface IPIQueryPageable {
   
    /**
     * Does the query page by default? 
     * If true, the query will return the first page of results even if the
     * the <pre>page</page> URL param is not provided.
     *   
     * @return bool 
     */   
    public function getDefaultPaging(); 
    
    /**
     * @param boolean $pageTrueOrFalse Set if this query pages by default
     */
    public function setDefaultPaging($pageTrueOrFalse);
   
    /**
     * @return int The page size (number of results per page)
     */
    public function getPageSize(); 
    
    /**
     * Set the size of a single page. 
     * @param int $pageSize
     */
    public function setPageSize($pageSize); 
    
}