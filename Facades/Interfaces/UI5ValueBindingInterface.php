<?php
namespace exface\UI5Facade\Facades\Interfaces;

/**
 * Describes OpenUI5 facade elements, that support model binding for their value.
 * 
 * @author Andrej Kabachnik
 *
 */
interface ui5ValueBindingInterface {
    
    /**
     * Sets the path to be used in the value model binding.
     * 
     * If not set explicitly, the path will be generated automatically from the meta model references.
     * 
     * @return ui5ValueBindingInterface
     */
    public function setValueBindingPath($string);
    
    /**
     * Returns the model binding configuration for this widget to be assigned to the property being bound.
     * 
     * E.g. "{somePath}" or {path: "somePath", type: "...", formatOptions: {...}}, etc.
     * 
     * @return string
     */
    public function buildJsValueBinding();
    
    /**
     * @return string
     */
    public function buildJsValueBindingOptions();
    
    /**
     * 
     * @return string
     */
    public function getValueBindingPath() : string;
    
    /**
     * 
     * @return string
     */
    public function buildJsValueBindingPropertyName() : string;
}