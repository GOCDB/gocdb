<?php
namespace org\gocdb\services;
/* Copyright © 2011 STFC
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
require_once __DIR__ . '/AbstractEntityService.php';;

/**
 * GOCDB Stateless service facade (business routines) for Extension objects.
 *
 * @author James McCarthy
 */
class ExtensionsService extends AbstractEntityService{

    /**
     * This method will return all site extensions from the siteProperties
     * table.
     */
    public function getSiteExtensionsKeynames() {

        $qb = $this->em->createQueryBuilder();
        $qb ->select('DISTINCT sp')
            ->from('SiteProperty', 'sp');

        return $qb->getQuery()->execute();
    }

    /**
     * This method will return all service extensions from the serviceProperties
     * table.
     */
    public function getServiceExtensionsKeyNames() {

        $qb = $this->em->createQueryBuilder();
        $qb ->select('DISTINCT sp')
            ->from('ServiceProperty', 'sp');

        return $qb->getQuery()->execute();
    }

    /**
     * This method will return all service group extensions from the serviceGroupProperties
     * table.
     */
    public function getServiceGroupExtensionsKeyNames() {

        $qb = $this->em->createQueryBuilder();
        $qb ->select('DISTINCT sp')
        ->from('ServiceGroupProperty', 'sp');

        return $qb->getQuery()->execute();
    }
}