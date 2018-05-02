<?php
namespace exface\OpenUI5Template\Templates\Interfaces;

use exface\OpenUI5Template\Templates\Elements\ui5AbstractElement;
use exface\OpenUI5Template\Webapp;
use exface\Core\Exceptions\Templates\TemplateLogicError;

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
    
    public function addControl(ui5AbstractElement $element, $name = null) : ui5ControllerInterface;
    
    public function buildJsController() : string;
    
    public function getName() : string;
    
    public function getWebapp() : Webapp;
    
    public function addOnInitScript(string $js) : ui5ControllerInterface;
    
    public function buildJsMethodCallFromView(string $methodName, ui5AbstractElement $callerElement, $oController = 'oController') : string;
    
    public function buildJsMethodCallFromController(string $methodName, ui5AbstractElement $methodOwner, string $paramsJs) : string;
    
    public function buildJsMethodName(string $methodName, ui5AbstractElement $ownerElement) : string;
}