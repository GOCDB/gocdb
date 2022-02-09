<?php

namespace org\gocdb\services;

/*
 * Copyright 2011 STFC Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software distributed under
 * the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND,
 * either express or implied. See the License for the specific language governing permissions
 * and limitations under the License.
 */

use Doctrine\ORM\EntityManager;

/**
 * @author James McCarthy
 */
class ParameterBuilder {

    private $binds = null;
    private $qb = null;
    private $bc;
    private $em;

    public function getBinds() {
    return $this->binds;
    }

    private function setBindCount($bc) {
    $this->bc = $bc;
    }

    public function getBindCount() {
    return $this->bc;
    }

    private function setQB($qb) {
    $this->qb = $qb;
    }

    public function getQB() {
    return $this->qb;
    }

    /**
     * Initialize variables, convert then store the new query
     * @param Array $parameters
     * @param QueryBuilder $qb
     * @param EntityManager $em
     */
    public function __construct($parameters, $qb, $em, $bc) {
    $this->em = $em;
    $this->setQB($qb);
    $this->setBindCount($bc);
    if ($parameters != null){
        $this->ifSet($parameters, $qb);
    }
    }

    private function ifSet($parameters, $qb) {
    $bc = $this->getBindCount();
    if (isset($parameters ['sitename'])) {
        $qb->andWhere($qb->expr()->like('s.shortName', '?' . ++$bc));
        $this->binds[] = array($bc, $parameters['sitename']);
    }

    if (isset($parameters ['roc'])) {
        $qb->andWhere($qb->expr()->like('n.name', '?' . ++$bc));
        $this->binds[] = array($bc, $parameters['roc']);
    }

    if (isset($parameters ['country'])) {
        $qb->andWhere($qb->expr()->like('c.name', '?' . ++$bc));
        $this->binds[] = array($bc, $parameters['country']);
    }

    if (isset($parameters ['certification_status'])) {
        $qb->andWhere($qb->expr()->like('cs.name', '?' . ++$bc));
        $this->binds[] = array($bc, $parameters['certification_status']);
    }

    if (isset($parameters ['exclude_certification_status'])) {
        $qb->andWhere($qb->expr()->not($qb->expr()->like('cs.name', '?' . ++$bc)));
        $this->binds[] = array($bc, $parameters['exclude_certification_status']);
    }

    if (isset($parameters ['production_status'])) {
        $qb->andWhere($qb->expr()->like('i.name', '?' . ++$bc));
        $this->binds[] = array($bc, $parameters['production_status']);
    }

    if (isset($parameters ['site'])) {
        $qb->andWhere($qb->expr()->like('s.shortName', '?' . ++$bc));
        $this->binds[] = array($bc, $parameters['site']);
    }

    if (isset($parameters ['subgrid'])) {
        $qb->andWhere($qb->expr()->like('s.name', '?' . ++$bc));
        $this->binds[] = array($bc, $parameters ['subgrid']);
    }

    if (isset($parameters ['hostname'])) {
        $qb->andWhere($qb->expr()->like('se.hostName', '?' . ++$bc));
        $this->binds[] = array($bc, $parameters ['hostname']);
    }

    // http://www.doctrine-project.org/jira/browse/DDC-1683
    // http://stackoverflow.com/questions/11413752/doctrine2-and-postgres-invalid-input-syntax-for-boolean
    // Specifying TRUE instead of 1 works as expected, but specifying FALSE
    // instead of 0 does not work as expected. This looks to be due to a
    // Doctrine bug related the one listed above.
    if (isset($parameters ['monitored'])) {
        if ($parameters['monitored'] == 'Y' || $parameters['monitored'] == 'y') {
        // we can't bind a literal using the ? syntax so do it directly here
        $qb->andWhere($qb->expr()->eq('se.monitored', $qb->expr()->literal(true)));
        // below works, but is probably not DB agnostic
        //$qb->andWhere($qb->expr()->eq('se.monitored', '?'.++$bc ));
        //$this->binds[] = array($bc, 1);
        } else if ($parameters['monitored'] == 'N' || $parameters['monitored'] == 'n') {
        // we can't bind a literal using the ? sytnax so do it directly here
        $qb->andWhere($qb->expr()->eq('se.monitored', $qb->expr()->literal(false)));
        // below works, but is probably not DB agnostic
        //$qb->andWhere($qb->expr()->eq('se.monitored', '?'.++$bc ));
        //$this->binds[] = array($bc, 0);
        }
    }

    if (isset($parameters ['service_type'])) {
        $qb->andWhere($qb->expr()->like('st.name', '?' . ++$bc));
        $this->binds[] = array($bc, $parameters ['service_type']);
    }

    if (isset($parameters ['forename'])) {
        $qb->andWhere($qb->expr()->like('u.forename', '?' . ++$bc));
        $this->binds[] = array($bc, $parameters ['forename']);
    }

    if (isset($parameters ['surname'])) {
        $qb->andWhere($qb->expr()->like('u.surname', '?' . ++$bc));
        $this->binds[] = array($bc, $parameters ['surname']);
    }

    if (isset($parameters ['dn'])) {
        ++$bc;
        $qb->leftJoin('u.userIdentifiers', 'up');
        $qb->andWhere($qb->expr()->orX(
            $qb->expr()->like('up.keyValue', '?' . $bc),
            $qb->expr()->like('u.certificateDn', '?' . $bc)
        ));
        $this->binds[] = array($bc, $parameters ['dn']);
    }

    if (isset($parameters ['dnlike'])) {
        ++$bc;
        $qb->leftJoin('u.userIdentifiers', 'up');
        $qb->andWhere($qb->expr()->orX(
            $qb->expr()->like('up.keyValue', '?' . $bc),
            $qb->expr()->like('u.certificateDn', '?' . $bc)
        ));
        $this->binds[] = array($bc, $parameters ['dnlike']);
    }

    if (isset($parameters ['service_group_name'])) {
        $qb->andWhere($qb->expr()->like('sg.name', '?' . ++$bc));
        $this->binds[] = array($bc, $parameters ['service_group_name']);
    }

    if (isset($parameters ['project'])) {
        $qb->where($qb->expr()->like('p.name', '?' . ++$bc));
        $this->binds[] = array($bc, $parameters ['project']);
    }

    //finally replace original query with updated joined query
    $this->setBindCount($bc);
    $this->setQB($qb);
    }

}
