<?php
namespace exface\UI5Facade\Facades\Interfaces;

/**
 * 
 * @author Andrej Kabachnik
 *
 */
interface UI5CompoundControlInterface {
    
    /**
     * 
     * @return string
     */
    public function buildJsConstructor($oControllerJs = 'oController') : string;
    
    /**
     * 
     * @return string
     */
    public function buildJsConstructorForMainControl($oControllerJs = 'oController');
}