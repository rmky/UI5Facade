<?php
namespace exface\OpenUI5Template\Template\Interfaces;

/**
 * 
 * @author Andrej Kabachnik
 *
 */
interface ui5CompoundControlInterface {
    
    /**
     * 
     * @return string
     */
    public function buildJsConstructor();
    
    /**
     * 
     * @return string
     */
    public function buildJsConstructorForMainControl();
}