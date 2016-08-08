<?php

namespace org\gocdb\services;

/**
 * Support different rendering styles for an {@link IPIQuery}.
 * <p> 
 * The implementation should support/return a default rendering style
 * if not specifically set with {@link setSelectedRendering($renderingStyle)}.
 * Different rendering styles could include XML GLUE2 rendering or 
 * JSON GLUE2 for example.   
 *
 * @author David Meredith <david.meredith@stfc.ac.uk> 
 */
interface IPIQueryRenderable {

    /**
     * Gets the current or default rendering output style. 
     */
    public function getSelectedRendering();
    
    /**
     * Return an array of renderings supported by the implementation. 
     * The returned values can be used with {@link getRenderingOutput()} 
     * @return array
     */
    public function getSupportedRenderings(); 
    
    /**
     * Set the required rendering output style. 
     * @param string $renderingStyle
     * @throws \InvalidArgumentException If the requested rendering style is not supported 
     */
    public function setSelectedRendering($renderingStyle);
    
    /**
     * @return string Query output as a string according to the current rendering style.  
     */
    public function getRenderingOutput(); 
    

    
}
