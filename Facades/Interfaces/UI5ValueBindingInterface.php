<?php
namespace exface\UI5Facade\Facades\Interfaces;

/**
 * Describes OpenUI5 facade elements, that support model binding for their value.
 * 
 * @author Andrej Kabachnik
 *
 */
interface UI5ValueBindingInterface {
    
    /**
     * Sets the path to be used in the value model binding.
     * 
     * If not set explicitly, the path will be generated automatically from the meta model references.
     * 
     * @return UI5ValueBindingInterface
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
    
    
    
    /**
     * Returns the model binding prefix: "/" by default.
     * 
     * @return string
     */
    public function getValueBindingPrefix() : string;
    
    /**
     * Changes the model binding prefix for this element.
     * 
     * For example, set to 'otherModel>/' to switch to another model.
     * 
     * @param string $value
     * @return UI5ValueBindingInterface
     */
    public function setValueBindingPrefix(string $value) : UI5ValueBindingInterface;
    
    
    /**
     *
     * @return bool
     */
    public function isValueBindingDisabled() : bool;
    
    /**
     *
     * @param bool $value
     * @return UI5ValueBindingInterface
     */
    public function setValueBindingDisabled(bool $value) : UI5ValueBindingInterface;
}