<?php
namespace exface\UI5Facade\Facades\Interfaces;

use exface\UI5Facade\Facades\Elements\ui5AbstractElement;
use exface\UI5Facade\Webapp;
use exface\Core\Exceptions\Facades\FacadeLogicError;
use exface\Core\Exceptions\OutOfBoundsException;

/**
 * 
 * @author Andrej Kabachnik
 *
 */
interface ui5ControllerInterface {
    
    /**
     * Returns the name of the controller method, that will handle the given event.
     * 
     * E.g. `onPress` for the event `press`.
     * 
     * @param string $eventName
     * @return string
     */
    public function buildJsEventHandlerMethodName(string $eventName) : string;
    
    /**
     * Returns the handler for the given event, that is usable within a view.
     * 
     * Use this method to obtain the values for constructor properties like `press`.
     * 
     * @param ui5AbstractElement $triggerElement
     * @param string $eventName
     * 
     * @return string
     */
    public function buildJsEventHandler(ui5AbstractElement $triggerElement, string $eventName) : string;
    
    /**
     * Adds a JS script to be performed on the given event.
     * 
     * The event object will be made available in the $js via `oEvent`.
     * 
     * @param ui5AbstractElement $triggerElement
     * @param string $eventName
     * @param string $js
     * 
     * @return ui5ControllerInterface
     */
    public function addOnEventScript(ui5AbstractElement $triggerElement, string $eventName, string $js) : ui5ControllerInterface;
    
    /**
     *
     * @param string $js
     * @throws FacadeLogicError
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
    
    /**
     * Returns the name of the controller: e.g. my.app.root.controller.my.app.widgetPage
     * 
     * @return string
     */
    public function getName() : string;
    
    /**
     * Returns the path to the controller: e.g. my/app/root/controller/my/app/widgetPage.controller.js
     * 
     * @return string
     */
    public function getPath() : string;
    
    public function getId() : string;
    
    public function getWebapp() : Webapp;
    
    /**
     * 
     * @param string $js
     * @param string $id
     * @return ui5ControllerInterface
     */
    public function addOnInitScript(string $js) : ui5ControllerInterface;
    
    /**
     * Adds a script to be performed before or after the view of this controller is shown.
     * 
     * @param string $js
     * @param bool $onBeforeShow
     * @return ui5ControllerInterface
     */
    public function addOnShowViewScript(string $js, bool $onBeforeShow = true) : ui5ControllerInterface;
    
    /**
     * Adds a script to be performed before or after the view of this controller is hidden.
     * 
     * @param string $js
     * @param bool $onBeforeHide
     * @return ui5ControllerInterface
     */
    public function addOnHideViewScript(string $js, bool $onBeforeHide = true) : ui5ControllerInterface;
    
    /**
     * Executes the provided script once a route to the view of this controller is matched
     * 
     * @param string $js
     * @param string $id
     * @return ui5ControllerInterface
     */
    public function addOnRouteMatchedScript(string $js, string $id) : ui5ControllerInterface;
    
    /**
     * Executes a script before the controller is initialized: before it's define()
     * 
     * @param string $js
     * @return ui5ControllerInterface
     */
    public function addOnDefineScript(string $js) : ui5ControllerInterface;
    
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
    public function addExternalModule(string $name, string $urlRelativeToAppRoot, string $var = null) : ui5ControllerInterface;
    
    /**
     * Returns an array with module names for keys and respecitve JS include paths for values (relative to site root)
     * 
     * @return string[]
     */
    public function getExternalModulePaths() : array;
    
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