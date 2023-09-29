<?php
namespace org\gocdb\services;
/* Copyright (c) 2011 STFC
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/**
 * GOCDB Stateless service facade (business routnes) for searching.
 * The public API methods are transactional.
 *
 * @author John Casson
 * @author David Meredith
 */
class Search {
    private $em;

    public function __construct() {
    }

    /**
     * Set the EntityManager instance used by all service methods.
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

    /*
     * All the public service methods in a service facade are typically atomic -
     * they demarcate the tx boundary at the start and end of the method
     * (getConnection/commit/rollback). A service facade should not be too 'chatty,'
     * ie where the client is required to make multiple calls to the service in
     * order to fetch/update/delete data. Inevitably, this usually means having
     * to refactor the service facade as the business requirements evolve.
     *
     * If the tx needs to be propagated across different service methods,
     * consider refactoring those calls into a new transactional service method.
     * Note, we can always call out to private helper methods to build up a
     * 'composite' service method. In doing so, we must access the same DB
     * connection (thus maintaining the atomicity of the service method).
     */

    public function getSites($searchTerm) {
        $dql = "SELECT s FROM Site s "
            . " WHERE UPPER(s.shortName) LIKE UPPER(concat(concat('%', :searchTerm), '%'))"
            . " OR UPPER(s.officialName) LIKE UPPER(concat(concat('%', :searchTerm), '%'))"
            . " OR UPPER(s.giisUrl) LIKE UPPER(concat(concat('%', :searchTerm), '%'))";

        $sites = $this->em->createQuery($dql)
                     ->setParameter(":searchTerm", $searchTerm)
                     ->getResult();
        return $sites;
    }

    public function getServices($searchTerm) {
        $dql = "SELECT s FROM Service s "
            . " WHERE UPPER(s.hostName) LIKE UPPER(concat(concat('%', :searchTerm), '%'))"
            . " OR UPPER(s.dn) LIKE UPPER(concat(concat('%', :searchTerm), '%'))"
            . " OR UPPER(s.description) LIKE UPPER(concat(concat('%', :searchTerm), '%'))";
        $services = $this->em
            ->createQuery($dql)
            ->setParameter(":searchTerm", $searchTerm)
            ->getResult();
        return $services;
    }

    public function getUsers($searchTerm) {
        $dql = "SELECT u FROM User u "
            . " WHERE UPPER(u.forename) LIKE UPPER(concat(concat('%', :searchTerm), '%'))"
            . " OR UPPER(u.surname) LIKE UPPER(concat(concat('%', :searchTerm), '%'))"
            . " OR UPPER(concat(concat(u.forename, ' '), u.surname)) LIKE UPPER(concat(concat('%', :searchTerm), '%'))";
        $users = $this->em
            ->createQuery($dql)
            ->setParameter(":searchTerm", $searchTerm)
            ->getResult();
        return $users;
    }

    public function getNgis($searchTerm) {
        $dql = "SELECT n FROM NGI n "
            . " WHERE UPPER(n.name) LIKE UPPER(concat(concat('%', :searchTerm), '%'))";
        $ngis = $this->em
            ->createQuery($dql)
            ->setParameter(":searchTerm", $searchTerm)
            ->getResult();
        return $ngis;
    }

    /**
     * When the user is admin, it retrieves the matching identifiers.
     */
    public function getSiteIdentifiers($user, $searchTerm)
    {
        if ($user->isAdmin()) {
            $dql = "SELECT ui FROM APIAuthentication ui "
                . " WHERE UPPER(ui.identifier) "
                . " LIKE UPPER(concat(concat('%', :searchTerm), '%'))";
            $siteIdentifiers = $this->em
                ->createQuery($dql)
                ->setParameter(":searchTerm", $searchTerm)
                ->getResult();

            return $siteIdentifiers;
        }
    }
}
