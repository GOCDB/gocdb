<?php

namespace org\gocdb\services;

/**
 * Defines the default functions for PI Query classes.
 * Typical usage is (where $query is an IPIQuery implementation):
 * <code>
 *  $query->validateParameters($params);
 *  $query->createQuery();
 *  $results = $query->executeQuery();
 * </code>
 *
 * @author David Meredith <david.meredith@stfc.ac.uk>
 * @author James McCarthy
 */
interface IPIQuery {

    /**
     * Validates the parameters passed to the class.
     * @param array $parameters
     *   An associatative array of string pairs (parameterKey => parameterValue)
     * @throws \InvalidArgumentException if the parameters are invalid.
     */
    public function validateParameters($parameters);

    /**
     * Creates and returns the query using validated parameters.
     * Typically, the query is cached locally as a class parameter for subsequent re-use.
     */
    public function createQuery();

    /**
     * Executes the query that has been built with {@link createQuery()} and
     * returns the query results.
     * <p>
     * Important: Typically the results are stored/cached in the class
     * for subsequent re-use without repeating a call to the database.
     * This is not enforced, and implementations may differ (refer
     * to implementation docs).
     * <p>
     * Unless otherwise specified by the implementation, the returned array 
     * is normally populated using Doctrine's HYDRATE_OBJECT. It is possible that 
     * implementations return an array graph using HYDRATE_ARRAY.  
     * @see http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/dql-doctrine-query-language.html#query-result-formats  
     *
     * @return array Either an object graph populated using HYDRATE_OBJECT or and array graph populated with HYDRATE_ARRAY 
     */
    public function executeQuery();
    

}
