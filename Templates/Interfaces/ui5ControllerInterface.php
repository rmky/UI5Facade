<?php
namespace exface\OpenUI5Template\Templates\Interfaces;

use exface\OpenUI5Template\Templates\Elements\ui5AbstractElement;
use exface\OpenUI5Template\Webapp;
use exface\Core\Exceptions\Templates\TemplateLogicError;
use exface\Core\Exceptions\OutOfBoundsException;

/**
 * 
 * @author Andrej Kabachnik
 *
 */
interface ui5ControllerInterface {
    
    /**
     *
     * @param string $methodName
     * @param string $jsFunction
     * @return string
     */
    public function buildJsViewEventHandler(string $methodName, ui5AbstractElement $callerElement, string $jsFunction) : string;
    
    /**
     *
     * @param string $js
     * @throws TemplateLogicError
     * @return ui5AbstractElement
     */
    public function addProperty(string $name, string $js) : ui5ControllerInterface;
    
    public function addMethod(string $methodName, ui5AbstractElement $methodOwner, string $params, string $body, $comment = '') : ui5ControllerInterface;
    
    /**
     * 
     * @param ui5AbstractElement $element
     * @param string $name
     * @return ui5ControllerInterface
     */
    public function addDependentControl(string $name, ui5AbstractElement $ownerElement, ui5AbstractElement $dependentElement) : ui5ControllerInterface;
    
    public function buildJsController() : string;
    
    public function getName() : string;
    
    public function getId() : string;
    
    public function getWebapp() : Webapp;
    
    /**
     * 
     * @param string $js
     * @param string $id
     * @return ui5ControllerInterface
     */
    public function addOnInitScript(string $js, string $id) : ui5ControllerInterface;
    
    /**
     * 
     * @param string $methodName
     * @param ui5AbstractElement $callerElement
     * @param string $oController
     * 
     * @throws OutOfBoundsException if method not found in the controller
     * 
     * @return string
     */
    public function buildJsMethodCallFromView(string $methodName, ui5AbstractElement $callerElement, $oController = 'oController') : string;
    
    /**
     * 
     * @param string $methodName
     * @param ui5AbstractElement $methodOwner
     * @param string $paramsJs
     * @param string $oControllerJsVar
     * 
     * @throws OutOfBoundsException if method not found in the controller
     * 
     * @return string
     */
    public function buildJsMethodCallFromController(string $methodName, ui5AbstractElement $methodOwner, string $paramsJs, string $oControllerJsVar = null) : string;
    
    /**
     * 
     * @param string $methodName
     * @param ui5AbstractElement $ownerElement
     * @return string
     */
    public function buildJsMethodName(string $methodName, ui5AbstractElement $ownerElement) : string;
    
    /**
     *
     * @param string $name
     * @param string $path
     * @param string $var
     * @return ui5ControllerInterface
     */
    public function addExternalModule(string $name, string $path, string $var = null) : ui5ControllerInterface;
    
    /**
     * 
     * @param string $path
     * @param string $id
     * @return ui5ControllerInterface
     */
    public function addExternalCss(string $path, string $id = null) : ui5ControllerInterface;
    
    /**
     * 
     * @param string $objectName
     * @param ui5AbstractElement $ownerElement
     * @return string
     */
    public function buildJsObjectName(string $objectName, ui5AbstractElement $ownerElement) : string;
    
    /**
     * 
     * @param string $objectName
     * @param ui5AbstractElement $ownerElement
     * @param string $construtorJs
     * @return ui5ControllerInterface
     */
    public function addDependentObject(string $objectName, ui5AbstractElement $ownerElement, string $construtorJs) : ui5ControllerInterface;
    
    /**
     * 
     * @param ui5AbstractElement $fromElement
     * @return string
     */
    public function buildJsControllerGetter(ui5AbstractElement $fromElement) : string;
    
    /**
     * 
     * @return ui5ViewInterface
     */
    public function getView() : ui5ViewInterface;
    
    /**
     * 
     * @param string $name
     * @return bool
     */
    public function hasProperty(string $name) : bool;
    
    /**
     * 
     * @param string $name
     * @param ui5AbstractElement $ownerElement
     * @return bool
     */
    public function hasMethod(string $name, ui5AbstractElement $ownerElement) : bool;
    
    /**
     * 
     * @param string $name
     * @param ui5AbstractElement $ownerElement
     * @return bool
     */
    public function hasDependent(string $name, ui5AbstractElement $ownerElement) : bool;
    
    /**
     * 
     * @param string $controlName
     * @param ui5AbstractElement $ownerElement
     * @param string $oControllerJsVar
     * 
     * @throws OutOfBoundsException if dependent control not found in controller
     * 
     * @return string
     */
    public function buildJsDependentControlSelector(string $controlName, ui5AbstractElement $ownerElement, string $oControllerJsVar = null) : string;
    
    /**
     * 
     * @return string
     */
    public function buildJsComponentGetter() : string;
}