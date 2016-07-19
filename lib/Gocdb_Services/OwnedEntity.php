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
require_once __DIR__ . '/AbstractEntityService.php';

/**
 * Service class that contain generic functions for working with doctrine
 * database entities.
 *
 * @author David Meredith
 */
class OwnedEntity  extends AbstractEntityService{
    /**
     * Get the OwnedEntity that has the given id.
     * @param integer $id
     * @return \OwnedEntity
     */
    public function getOwnedEntityById($id){
        // will only load a lazy loading proxy until method on the proxy is called
        //$entity = $this->em->find('OwnedEntity', (int)$id);
        //return $entity;
        $dql = "SELECT oe from OwnedEntity oe where oe.id = :id";
        $oe = $this->em->createQuery($dql)->setParameter(":id", $id)->getSingleResult();
        return $oe;
    }

    /**
     * Get the class name of the class that extends the given OwnedEntity, e.g.
     * NGI, Site, ServiceGroup, Project.
     * @param \OwnedEntity $entity
     * @return string Class name
     * @throws LogicException if the OwnedEntity is not supported/configured
     */
    public function getOwnedEntityDerivedClassName(\OwnedEntity $entity){
        // Would be better to use the get_class method to determine the instance
        // class name, however, there have been instances where this has returned
        // a class name of the following form: 'DoctrineProxies\__CG__\Site'
        // which would cause issues.
        //
        //$entityClassName= get_class($entity);

         if($entity instanceof \Site){
           $entityClassName = 'Site';
        } else if($entity instanceof \NGI){
            $entityClassName = 'NGI';
        } else if($entity instanceof \Project){
            $entityClassName = 'Project';
        } else if($entity instanceof \ServiceGroup){
            $entityClassName = 'ServiceGroup';
        } else {
            throw new LogicException("Coding error - OwnedEntity type is not mapped");
        }
        return $entityClassName;
    }



}

?>
