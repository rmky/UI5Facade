<?php
namespace exface\UI5Facade\Facades\Interfaces;

use exface\UI5Facade\Facades\Elements\UI5AbstractElement;
use exface\UI5Facade\Webapp;
use exface\Core\Exceptions\Facades\FacadeLogicError;
use exface\Core\Exceptions\OutOfBoundsException;

/**
 * 
 * @author Andrej Kabachnik
 *
 */
interface UI5ControllerInterface {
    
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
     * @param UI5AbstractElement $triggerElement
     * @param string $eventName
     * 
     * @return string
     */
    public function buildJsEventHandler(UI5AbstractElement $triggerElement, string $eventName) : string;
    
    /**
     * Adds a JS script to be performed on the given event.
     * 
     * The event object will be made available in the $js via `oEvent`.
     * 
     * @param UI5AbstractElement $triggerElement
     * @param string $eventName
     * @param string $js
     * 
     * @return UI5ControllerInterface
     */
    public function addOnEventScript(UI5AbstractElement $triggerElement, string $eventName, string $js) : UI5ControllerInterface;
    
    /**
     *
     * @param string $js
     * @throws FacadeLogicError
     * @return UI5AbstractElement
     */
    public function addProperty(string $name, string $js) : UI5ControllerInterface;
    
    public function addMethod(string $methodName, UI5AbstractElement $methodOwner, string $params, string $body, $comment = '') : UI5ControllerInterface;
    
    /**
     * 
     * @param UI5AbstractElement $element
     * @param string $name
     * @return UI5ControllerInterface
     */
    public function addDependentControl(string $name, UI5AbstractElement $ownerElement, UI5AbstractElement $dependentElement) : UI5ControllerInterface;
    
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
     * @return UI5ControllerInterface
     */
    public function addOnInitScript(string $js) : UI5ControllerInterface;
    
    /**
     * Adds a script to be performed before or after the view of this controller is shown.
     * 
     * @param string $js
     * @param bool $onBeforeShow
     * @return UI5ControllerInterface
     */
    public function addOnShowViewScript(string $js, bool $onBeforeShow = true) : UI5ControllerInterface;
    
    /**
     * Adds a script to be performed before or after the view of this controller is hidden.
     * 
     * @param string $js
     * @param bool $onBeforeHide
     * @return UI5ControllerInterface
     */
    public function addOnHideViewScript(string $js, bool $onBeforeHide = true) : UI5ControllerInterface;
    
    /**
     * Executes the provided script once a route to the view of this controller is matched
     * 
     * @param string $js
     * @param string $id
     * @return UI5ControllerInterface
     */
    public function addOnRouteMatchedScript(string $js, string $id) : UI5ControllerInterface;
    
    /**
     * Executes a script before the controller is initialized: before it's define()
     * 
     * @param string $js
     * @return UI5ControllerInterface
     */
    public function addOnDefineScript(string $js) : UI5ControllerInterface;
    
    /**
     * 
     * @param string $methodName
     * @param UI5AbstractElement $callerElement
     * @param string $oController
     * 
     * @throws OutOfBoundsException if method not found in the controller
     * 
     * @return string
     */
    public function buildJsMethodCallFromView(string $methodName, UI5AbstractElement $callerElement, $oController = 'oController') : string;
    
    /**
     * 
     * @param string $methodName
     * @param UI5AbstractElement $methodOwner
     * @param string $paramsJs
     * @param string $oControllerJsVar
     * 
     * @throws OutOfBoundsException if method not found in the controller
     * 
     * @return string
     */
    public function buildJsMethodCallFromController(string $methodName, UI5AbstractElement $methodOwner, string $paramsJs, string $oControllerJsVar = null) : string;
    
    /**
     * 
     * @param string $methodName
     * @param UI5AbstractElement $ownerElement
     * @return string
     */
    public function buildJsMethodName(string $methodName, UI5AbstractElement $ownerElement) : string;
    
    /**
     *
     * @param string $name
     * @param string $path
     * @param string $var
     * @return UI5ControllerInterface
     */
    public function addExternalModule(string $name, string $urlRelativeToAppRoot, string $var = null) : UI5ControllerInterface;
    
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
     * @return UI5ControllerInterface
     */
    public function addExternalCss(string $path, string $id = null) : UI5ControllerInterface;
    
    /**
     * 
     * @param string $objectName
     * @param UI5AbstractElement $ownerElement
     * @return string
     */
    public function buildJsObjectName(string $objectName, UI5AbstractElement $ownerElement) : string;
    
    /**
     * 
     * @param string $objectName
     * @param UI5AbstractElement $ownerElement
     * @param string $construtorJs
     * @return UI5ControllerInterface
     */
    public function addDependentObject(string $objectName, UI5AbstractElement $ownerElement, string $construtorJs) : UI5ControllerInterface;
    
    /**
     * 
     * @param UI5AbstractElement $fromElement
     * @return string
     */
    public function buildJsControllerGetter(UI5AbstractElement $fromElement) : string;
    
    /**
     * 
     * @return UI5ViewInterface
     */
    public function getView() : UI5ViewInterface;
    
    /**
     * 
     * @param string $name
     * @return bool
     */
    public function hasProperty(string $name) : bool;
    
    /**
     * 
     * @param string $name
     * @param UI5AbstractElement $ownerElement
     * @return bool
     */
    public function hasMethod(string $name, UI5AbstractElement $ownerElement) : bool;
    
    /**
     * 
     * @param string $name
     * @param UI5AbstractElement $ownerElement
     * @return bool
     */
    public function hasDependent(string $name, UI5AbstractElement $ownerElement) : bool;
    
    /**
     * 
     * @param string $controlName
     * @param UI5AbstractElement $ownerElement
     * @param string $oControllerJsVar
     * 
     * @throws OutOfBoundsException if dependent control not found in controller
     * 
     * @return string
     */
    public function buildJsDependentControlSelector(string $controlName, UI5AbstractElement $ownerElement, string $oControllerJsVar = null) : string;
    
    /**
     * 
     * @return string
     */
    public function buildJsComponentGetter() : string;
}