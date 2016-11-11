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
    
    /**
     * Returns an associative array of paging related parameters that 
     * are available AFTER executing the query. 
     * <p>
     * This includes the number of results returned in the current page of results ('count') 
     * and for cursor based paging, this includes the current values for the 'next_cursor'
     * and 'prev_cursor' values. Unless otherwise specified by the implementations, 
     * the format of the returned array is: 
     *  
     * <code>
     * $array = (
     *   'count' => int or null, 
     *   'next_cursor' => int or null (null if no next results), 
     *   'prev_cursor' => int or null (null if no prev results) 
     * </code>
     * @return array Associative array. 
     */
    public function getPostExecutionPageInfo();

}