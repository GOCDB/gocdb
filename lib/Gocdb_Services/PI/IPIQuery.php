<?php

namespace org\gocdb\services;

/**
 * Defines the default functions for PI Query classes.
 * Typical usage is (where $query is an IPIQuery implementation):
 * <code>
 *  $query->validateParameters($params);
 *  $query->createQuery();
 *  $results = $query->executeQuery();
 *  $xml = $query->getXML();
 * </code>
 *
 * @author James McCarthy
 * @author David Meredith
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
     * This is not enforced, and different implementations may differ (refer
     * to implementation docs).
     *
     * @return DoctrineResultSet or ArrayResults
     */
    public function executeQuery();

    /**
     * Not yet implemented, will return JSON data for query results
     * @return JSON
     */
    public function getJSON();

    /**
     * Returns XML of the query results
     * @return XMLString
     */
    public function getXML();

    /**
     * Returns XML in Glue2 format for the query results
     * @return XMLString
     */
    public function getGlue2XML();
}
