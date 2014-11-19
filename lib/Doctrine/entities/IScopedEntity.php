<?php
/**
 * Interface for scoped entities
 */
interface IScopedEntity {
    public function getScopes();
    public function getScopeNamesAsString();
    public function addScope(Scope $scope);
    public function removeScope(Scope $removeScope);
            
}