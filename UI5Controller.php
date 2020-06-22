<?php
namespace exface\UI5Facade;

use exface\UI5Facade\Facades\Interfaces\UI5ControllerInterface;
use exface\Core\DataTypes\StringDataType;
use exface\UI5Facade\Facades\Elements\UI5AbstractElement;
use exface\Core\Exceptions\Facades\FacadeLogicError;
use exface\UI5Facade\Facades\Interfaces\UI5ViewInterface;
use exface\Core\Exceptions\OutOfBoundsException;
use exface\Core\Interfaces\Widgets\iTriggerAction;
use exface\Core\Interfaces\Widgets\iCanPreloadData;
use exface\Core\Interfaces\Actions\iShowWidget;
use exface\UI5Facade\Facades\Elements\UI5Dialog;
use exface\Core\Exceptions\Facades\FacadeRuntimeError;
use exface\Core\Factories\ActionFactory;
use exface\Core\Widgets\Dialog;

class UI5Controller implements UI5ControllerInterface
{
    const EVENT_NAME_PREFILL = 'prefill';
    
    private $isBuilt = false;
    
    private $webapp = null;
    
    private $view = null;
    
    private $controllerName = '';
    
    private $properties = [];
    
    private $onInitScripts = [];
    
    private $onRouteMatchedScripts = [];
    
    private $onViewPrefilledScripts = [];
    
    private $onDefineScripts = [];
    
    /**
     * Array of the following structure:
     * 
     * [
     *  controller_method_name_of_event_handler => [
     *      __element   => (UI5AbstractElement) facade_element_instance,
     *      __eventName => (String) event_name - e.g. UI5AbstractElement::EVENT_NAME_CHANGE,
     *      0           => (String) handler script 1,
     *      1           => (String) handler script 2,
     *      ...
     *  ]
     * ]
     * 
     * @var array
     */
    private $onEventScripts = [];
    
    private $externalModules = [];
    
    private $externalCss = [];
    
    private $pseudo_events = [];
    
    public function __construct(Webapp $webapp, string $controllerName, UI5ViewInterface $view)
    {
        $this->webapp = $webapp;
        $this->controllerName = $controllerName;
        $this->view = $view->setController($this);
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Interfaces\UI5ControllerInterface::buildJsMethodName()
     */
    public function buildJsMethodName(string $methodName, UI5AbstractElement $ownerElement) : string
    {
        if ($ownerElement->getUseWidgetId() === true) {
            $elementSuffix = $ownerElement->getId();
        } else {
            $elementSuffix = $ownerElement->getWidget()->getId();
        }
        if ($elementSuffix === null || $elementSuffix === '') {
            throw new FacadeLogicError('Cannot create controller method "' . $methodName . '" for widget "' . $ownerElement->getWidget()->getId() . '": the facade element does not have a unique id!');
        }
        return $methodName . StringDataType::convertCaseUnderscoreToPascal($elementSuffix);
    }
    
    
    public function buildJsObjectName(string $objectName, UI5AbstractElement $ownerElement) : string
    {
        return $objectName . StringDataType::convertCaseUnderscoreToPascal($ownerElement->getId());
    }
    
    /**
     *
     * @param string $methodName
     * @return string
     */
    public function buildJsMethodCallFromView(string $methodName, UI5AbstractElement $callerElement, $oController = 'oController') : string
    {
        $propertyName = $this->buildJsMethodName($methodName, $callerElement);
        if (! $this->hasProperty($propertyName)) {
            throw new OutOfBoundsException('Method "' . $propertyName . '" not found in controller "' . $this->getName() . '"!');
        }
        
        return "[{$oController}.{$propertyName}, {$oController}]";
    }
    
    public function buildJsMethodCallFromController(string $methodName, UI5AbstractElement $methodOwner, string $paramsJs = '', string $oControllerJsVar = null) : string
    {
        if ($oControllerJsVar === null) {
            $oControllerJsVar = "{$this->buildJsComponentGetter()}.findViewOfControl(sap.ui.getCore().byId('{$methodOwner->getId()}')).getController()";
        }
        
        $propertyName = $this->buildJsMethodName($methodName, $methodOwner);
        
        if ($methodOwner->getController() === $this) {
            return "{$oControllerJsVar}.{$propertyName}({$paramsJs})";
        }
        
        throw new FacadeLogicError('Calling a controller method from another controller not implemented yet!');
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Interfaces\UI5ControllerInterface::buildJsControllerGetter()
     */
    public function buildJsControllerGetter(UI5AbstractElement $fromElement) : string
    {
        return $this->buildJsComponentGetter() . ".findViewOfControl(sap.ui.getCore().byId('{$fromElement->getId()}')).getController()";
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Interfaces\UI5ControllerInterface::buildJsComponentGetter()
     */
    public function buildJsComponentGetter() : string
    {
        return "sap.ui.getCore().getComponent('{$this->getWebapp()->getComponentId()}')";
    }
    
    /**
     * Adds controller methods to handle all registered events.
     * 
     * All event scripts (registered via addOnEventScript()) for this event are concatennated
     * and put into a controller method.
     * 
     * @return UI5ControllerInterface
     */
    protected function createEventHandlerMethods() : UI5ControllerInterface
    {
        foreach ($this->onEventScripts as $methodName => $scripts) {
            if ($scripts['__element'] !== null) {
                $element = $scripts['__element'];
                $eventName = $scripts['__eventName'];
                unset($scripts['__element']);
                unset($scripts['__eventName']);
            }
            if (empty($scripts) === false) {
                $js = implode("\n", array_unique($scripts));
                if ($element !== null) {
                    $js = $element->buildJsOnEventScript($eventName, $js, 'oEvent');
                }
            } else {
                $js = '';
            }
            
            $js = <<<JS
    
function(oEvent) {
                    {$js}
                }
JS;
            
            $this->addProperty($methodName, $js);
        }
        return $this;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Interfaces\UI5ControllerInterface::buildJsEventHandlerMethodName()
     */
    public function buildJsEventHandlerMethodName(string $eventName) : string
    {
        return 'on' . ucfirst($eventName);
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Interfaces\UI5ControllerInterface::buildJsEventHandler()
     */
    public function buildJsEventHandler(UI5AbstractElement $triggerElement, string $eventName, bool $buildForView) : string
    {
        $methodName = $this->buildJsEventHandlerMethodName($eventName);
        
        // Make sure, there is allways an event-handler method
        // If we don't do that, there will be errors when generating event-handler calls in views
        // if no real handlers were registered for the event.
        $propertyName = $this->buildJsMethodName($methodName, $triggerElement);
        if ($this->onEventScripts[$propertyName] === null) {
            $this->addOnEventScript($triggerElement, $eventName, '');
        }
        
        if ($buildForView === true) {
            return $this->buildJsMethodCallFromView($methodName, $triggerElement);
        } else {
            return $this->buildJsMethodCallFromController($methodName, $triggerElement);
        }
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Interfaces\UI5ControllerInterface::addProperty()
     */
    public final function addProperty(string $name, string $js) : UI5ControllerInterface
    {
        if ($this->isBuilt === true) {
            throw new FacadeLogicError('Cannot add controller property "' . $name . '" after the controller "' . $this->getName() . '" had been built!');
        }
        $this->properties[$name] = $js;
        return $this;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Interfaces\UI5ControllerInterface::addDependentObject()
     */
    public function addDependentObject(string $objectName, UI5AbstractElement $ownerElement, string $initJs) : UI5ControllerInterface
    {
        $name = $this->buildJsObjectName($objectName, $ownerElement);
        
        $initFunctionCall = <<<JS
        
                this._{$name}Init();
JS;
        $initFunction = <<<JS
function() {
                    var oController = this;
                    this.{$name} = {$initJs};
                },
JS;
        $this->addProperty($name, 'null');
        $this->addProperty('_'.$name.'Init', $initFunction);
        $this->addOnInitScript($initFunctionCall);
        
        return $this;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Interfaces\UI5ControllerInterface::addMethod()
     */
    public final function addMethod(string $methodName, UI5AbstractElement $methodOwner, string $params, string $body, $comment = '') : UI5ControllerInterface
    {
        if ($comment !== '') {
            $commeptOpen = '// BOF ' . $comment;
            $commentClose = '// EOF ' . $comment;
        }
        $js = <<<JS
    
function({$params}){
                    {$commeptOpen}
                    {$body}
                    {$commentClose}
				}
                
JS;
        $this->addProperty($this->buildJsMethodName($methodName, $methodOwner), $js);
        return $this;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Interfaces\UI5ControllerInterface::addDependentControl()
     */
    public function addDependentControl(string $name, UI5AbstractElement $ownerElement, UI5AbstractElement $dependentElement) : UI5ControllerInterface
    {
        $propertyName = $this->buildJsObjectName($name, $ownerElement);
        $initMethodName = '_'.$propertyName.'Init';
        
        $initFunctionCall = <<<JS
        
                this.{$initMethodName}();
JS;
        $initFunction = <<<JS
function() {
                    var oController = this;
                    this.{$propertyName} = {$dependentElement->buildJsConstructor('oController')};
                    oController.getView().addDependent(this.{$propertyName});
                },
JS;
        $this->addProperty($propertyName, 'null');
        $this->addProperty($initMethodName, $initFunction);
        $this->addOnInitScript($initFunctionCall);
        return $this;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Interfaces\UI5ControllerInterface::buildJsController()
     */
    public function buildJsController() : string
    {
        // See if the view requires a prefill request
        // FIXME UI5Dialog has it's own prefill logic - need to unify both approaches!
        if ($this->needsPrefill() && ! ($this->getView()->getRootElement() instanceof UI5Dialog)) {
            $script = <<<JS
var oViewModel = oView.getModel('view');
var oResultModel = oView.getModel();

JS;
            $this->addOnRouteMatchedScript($script . $this->buildJsPrefillLoader('oViewModel', 'oResultModel', $this->getView()->getRootElement()), 'loadPrefill');
        }
        
        // Build the view first to ensure, all view elements have contributed to the controller!
        $this->getView()->buildJsView();
        
        foreach ($this->externalModules as $name => $properties) {
            $modules .= ",\n\t\"" . str_replace('.', '/', $name) . '"';            
            $controllerArgs .= ', ' . ($properties['var'] ? $properties['var'] : $this->getDefaultVarForModule($name, $properties['globalVarName']));
            $moduleRegistration .= "\n" . $this->buildJsModulePathRegistration($name, $properties['path']);
            if ($properties['globalVarName'] !== null) {
                $controllerGlobals .= "\n/* global {$properties['globalVarName']} */";
            }
        }
        $cssIncludes = $this->buildJsCssIncludes();
        return <<<JS

{$cssIncludes}

{$moduleRegistration}

{$this->buildJsOnDefineScript()}

{$controllerGlobals}
      
sap.ui.define([
	"{$this->getWebapp()->getComponentPath()}/controller/BaseController"{$modules}
], function (BaseController{$controllerArgs}) {
	"use strict";
	
	return BaseController.extend("{$this->getName()}", {

        onInit: function () {
            var oController = this;
            var oView = this.getView();

            // Init model for view settings
            var oViewModel = new sap.ui.model.json.JSONModel({
                _prefill: {
                    pending: false, 
                    data: {}
                } 
            });
            oView.setModel(oViewModel, "view");
            var oPrefillPendingBinding = new sap.ui.model.Binding(oViewModel, '/_prefill/pending', oViewModel.getContext('/_prefill/pending'));
            oPrefillPendingBinding.attachChange(function(oEvent){
                if (oViewModel.getProperty('/_prefill/pending') === false) {
                        oController._onViewPrefilled();
                }
            });
            
            // Init base view model (used for prefills, control values, etc.)
            oView.setModel(new sap.ui.model.json.JSONModel());
            // Add pseudo event handlers if any defined
            oView{$this->buildJsPseudoEventHandlers()};
            
            var oRouter = this.getRouter();
            if (oRouter !== undefined) {
                var oRoute = oRouter.getRoute("{$this->getView()->getRouteName()}");
                if (oRoute) {
                    oRoute.attachMatched(this._onRouteMatched, this);
                }
            }
            
			{$this->buildJsOnInitScript()}
		},

        /**
		 * This method is executed every time a route leading to the view of this controller is matched.
		 * 
		 * @private
		 * 
		 * @param sap.ui.base.Event oEvent
		 * 
		 * @return void
		 */
		_onRouteMatched : function (oEvent) {
			var oView = this.getView();
			var oArgs = oEvent.getParameter("arguments");
			var oParams = (oArgs.params === undefined ? {} : this._decodeRouteParams(oArgs.params));
            var oViewModel = oView.getModel('view');
			oViewModel.setProperty("/_route", {params: oParams});
            
            {$this->buildJsOnRouteMatched()}
		},

        _onViewPrefilled : function () {
            {$this->buildJsOnViewPrefilledScript()}
        },

        {$this->buildJsProperties()}

	});

});

JS;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Interfaces\UI5ControllerInterface::getName()
     */
    public function getName() : string
    {
        return $this->controllerName;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Interfaces\UI5ControllerInterface::getPath()
     */
    public function getPath(bool $relativeToAppRoot = false) : string
    {
        if ($relativeToAppRoot === true) {
            $name = StringDataType::substringAfter($this->getName(), $this->getWebapp()->getComponentName() . '.');
        } else {
            $name = $this->getName();
        }
        return $this->getWebapp()->convertNameToPath($name, $this->getWebapp()->getControllerFileSuffix());
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Interfaces\UI5ControllerInterface::getId()
     */
    public function getId() : string
    {
        return $this->controllerName;
    }
    
    /**
     * 
     * @return string
     */
    protected function buildJsProperties() : string
    {
        $this->createEventHandlerMethods();
        
        $this->isBuilt = true;
        $js = '';
        
        foreach ($this->properties as $name => $script) {
            $js .= $name . ': ' . $this->sanitzeProperty($script) . ",\n";
        }
        return $js;
    }
    
    protected function sanitzeProperty($js) : string
    {
        return rtrim($js, ", \r\n\t\0\0xB");
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Interfaces\UI5ControllerInterface::getWebapp()
     */
    public function getWebapp() : Webapp
    {
        return $this->webapp;
    }
    
    /**
     * 
     * @return string
     */
    protected function buildJsOnInitScript() : string
    {
        $js = '';
        $scripts = array_unique($this->onInitScripts);
        foreach ($scripts as $script) {
            $js .= "\n\n" . $this->sanitizeScript($script);
        }
        return $js;
    }
    
    protected function buildJsPseudoEventHandlers() : string
    {
        $js = '';
        foreach ($this->pseudo_events as $event => $code_array) {
            $code = implode("\n", array_unique($code_array));
            $js .= <<<JS
            
            {$event}: function(oEvent) {
                {$code}
            },
            
JS;
        }
        
        if ($js) {
            $js = <<<JS
            
        .addEventDelegate({
            {$js}
        })
        
JS;
        }
        
        return $js;
    }
    
    protected function sanitizeScript($js) : string
    {
        return rtrim($js, "; \r\n\t\0\0xB") . ';';
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Interfaces\UI5ControllerInterface::addOnInitScript()
     */
    public function addOnInitScript(string $js) : UI5ControllerInterface
    {
        $this->onInitScripts[] = $js;
        return $this;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Interfaces\UI5ControllerInterface::addOnShowViewScript()
     */
    public function addOnShowViewScript(string $js, bool $onBeforeShow = true) : UI5ControllerInterface
    {
        $this->pseudo_events[($onBeforeShow ? 'onBeforeShow' : 'onAfterShow')][] = $js;
        return $this;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Interfaces\UI5ControllerInterface::addOnHideViewScript()
     */
    public function addOnHideViewScript(string $js, bool $onBeforeHide = true) : UI5ControllerInterface
    {
        $this->pseudo_events[($onBeforeHide ? 'onBeforeHide' : 'onAfterHide')][] = $js;
        return $this;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Interfaces\UI5ControllerInterface::addOnRouteMatchedScript()
     */
    public function addOnRouteMatchedScript(string $js, string $id) : UI5ControllerInterface
    {
        $this->onRouteMatchedScripts[$id] = $js;
        return $this;
    }
    
    /**
     *
     * @return string
     */
    protected function buildJsOnRouteMatched() : string
    {
        return implode($this->onRouteMatchedScripts);
    }
    
    public function addOnViewPrefilledScript(string $js) : UI5ControllerInterface
    {
        $this->onViewPrefilledScripts[] = $js;
        return $this;
    }
    
    /**
     *
     * @return string
     */
    protected function buildJsOnViewPrefilledScript() : string
    {
        return implode(array_unique($this->onViewPrefilledScripts));
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Interfaces\UI5ControllerInterface::addExternalModule()
     */
    public function addExternalModule(string $name, string $urlRelativeToAppRoot, string $controllerArgumentName = null, string $globalVarName = null) : UI5ControllerInterface
    {
        $this->externalModules[$name] = ['path' => $urlRelativeToAppRoot, 'var' => $controllerArgumentName, 'globalVarName' => $globalVarName];
        return $this;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Interfaces\UI5ControllerInterface::getExternalModules()
     */
    public function getExternalModulePaths() : array
    {
        $arr = [];
        foreach ($this->externalModules as $name => $properties) {
            $arr[$name] = $properties['path'];
        }
        return $arr;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Interfaces\UI5ControllerInterface::addExternalCss()
     */
    public function addExternalCss(string $path, string $id = null) : UI5ControllerInterface
    {
        $this->externalCss[($id === null ? $path : $id)] = $path;
        return $this;
    }
    
    /**
     * 
     * @param string $moduleName
     * @return string
     */
    protected function getDefaultVarForModule(string $moduleName, string $globalVarName = null) : string
    {
        $split = explode('.', $moduleName);
        $cnt = count($split);
        for ($i=1; $i<$cnt; $i++) {
            $var .= StringDataType::convertCaseUnderscoreToPascal($split[$i]);
        }
        $var = lcfirst($var);
        
        if ($globalVarName !== null && $var === $globalVarName) {
            $var .= 'JS';
        }
        
        return $var;
    }
    
    /**
     * Returns the JS to register a module path: jQuery.sap.registerModulePath('{$moduleName}', '{$url}');
     * 
     * @param string $moduleName
     * @param string $url
     * @return string
     */
    protected function buildJsModulePathRegistration(string $moduleName, string $url) : string
    {
        if (StringDataType::endsWith($url, '.js')) {
            $url = substr($url, 0, -3);
        }
        
        return "jQuery.sap.registerModulePath('{$moduleName}', '{$url}');";
    } 
    
    /**
     * Returns the JS to include an external CSS.
     * 
     * CSS files are automatically included only once.
     * 
     * @return string
     */
    protected function buildJsCssIncludes() : string
    {
        $js = '';
        foreach ($this->externalCss as $id => $path) {
            $js .= <<<JS

if (sap.ui.getCore().byId("{$id}") === undefined) {
    jQuery.sap.includeStyleSheet('{$path}', '{$id}');
}

JS;
        }
        return $js;
    }  
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Interfaces\UI5ControllerInterface::getView()
     */
    public function getView() : UI5ViewInterface
    {
        return $this->view;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Interfaces\UI5ControllerInterface::hasProperty()
     */
    public function hasProperty(string $name) : bool
    {
        return ! empty($this->properties[$name]) || ! empty($this->onEventScripts[$name]);
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Interfaces\UI5ControllerInterface::hasMethod()
     */
    public function hasMethod(string $name, UI5AbstractElement $ownerElement) : bool
    {
        $propertyName = $this->buildJsMethodName($name, $ownerElement);
        return $this->hasProperty($propertyName);
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Interfaces\UI5ControllerInterface::hasDependent()
     */
    public function hasDependent(string $name, UI5AbstractElement $ownerElement) : bool
    {
        return $this->hasProperty($this->buildJsObjectName($name, $ownerElement));
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Interfaces\UI5ControllerInterface::buildJsDependentControlSelector()
     */
    public function buildJsDependentControlSelector(string $controlName, UI5AbstractElement $ownerElement, string $oControllerJsVar = null) : string
    {
        $propertyName = $this->buildJsObjectName($controlName, $ownerElement);
        if (! $this->hasProperty($propertyName)) {
            throw new OutOfBoundsException('Dependent control "' . $propertyName . ' not found in controller "' . $this->getName() . '"');
        }
        
        if ($oControllerJsVar === null) {
            $oControllerJsVar = $ownerElement->getController()->buildJsControllerGetter($ownerElement);
        }
        
        return $oControllerJsVar . '.' . $propertyName;
    }
    
    /**
     * 
     * @param string $pageSelector
     * @param string $widgetId
     * @param string $xhrSettingsJs
     * @return string
     */
    public function buildJsNavTo(string $pageSelector, string $widgetId = null, string $xhrSettingsJs = null) : string
    {
        $widgetId = $widgetId ?? '';
        $xhrSettingsJs = $xhrSettingsJs !== null ? ', ' . $xhrSettingsJs : '';
        return "this.navTo('{$pageSelector}', '{$widgetId}'{$xhrSettingsJs});";
    }
    
    public function addOnDefineScript(string $js) : UI5ControllerInterface
    {
        $this->onDefineScripts[] = $js;
        return $this;
    }
    
    protected function buildJsOnDefineScript() : string
    {
        return implode("\n", array_unique($this->onDefineScripts));
    }
    
    /**
     * 
     * @param UI5AbstractElement $triggerWidget
     * @param string $eventName
     * @param string $js
     * @return UI5ControllerInterface
     */
    public function addOnEventScript(UI5AbstractElement $triggerElement, string $eventName, string $js) : UI5ControllerInterface
    {
        $controllerMethodName = $this->buildJsMethodName($this->buildJsEventHandlerMethodName($eventName), $triggerElement);
        if ($this->onEventScripts[$controllerMethodName]['__element'] === null) {
            $this->onEventScripts[$controllerMethodName]['__element'] = $triggerElement;
            $this->onEventScripts[$controllerMethodName]['__eventName'] = $eventName;
        } elseif($this->onEventScripts[$controllerMethodName]['__element'] !== $triggerElement) {
            throw new FacadeRuntimeError('Cannot add event handler for ' . $triggerElement->getWidget()->getWidgetType() . ' "' . $triggerElement->getWidget()->getId() . '": element class changed in the mean time!');
        }
        $this->onEventScripts[$controllerMethodName][] = $js;
        return $this;
    }
    
    /**
     * Returns the JS code to load prefill data for the dialog.
     *
     * TODO will this work with with explicit prefill data too?
     *
     * @param string $oViewJs
     * @return string
     */
    public function buildJsPrefillLoader(string $oViewModelJs = 'oViewModel', $oResultModelJs = 'oResultModel',  UI5AbstractElement $callerElement = null, string $onModelLoadedJs = '', string $onErrorJs = '', string $onOfflineJs = '', string $urlParamsJs = null) : string
    {
        if ($urlParamsJs === null) {
            $urlParamsJs = 'undefined';
        }
        $callerElement = $callerElement ?? $this->getView()->getRootElement();
        $callerWidget = $callerElement->getWidget();
        $triggerWidget = $callerWidget->getParent() instanceof iTriggerAction ? $callerWidget->getParent() : $callerWidget;
        
        // FIXME #DataPreloader this will force the form to use any preload - regardless of the columns.
        if ($callerWidget instanceof iCanPreloadData && $callerWidget->isPreloadDataEnabled() === true) {
            $this->addOnDefineScript("exfPreloader.addPreload('{$callerWidget->getMetaObject()->getAliasWithNamespace()}');");
        }
        
        $action = ActionFactory::createFromString($callerWidget->getWorkbench(), 'exface.Core.ReadPrefill', $callerWidget);
        
        $stopJs = <<<JS
        
                        {$oViewModelJs}.setProperty('/_prefill/pending', false);
                        {$callerElement->buildJsBusyIconHide()};
JS;
        $onModelLoadedJs .= $stopJs;               
        $onErrorJs .= $stopJs;           
        $onOfflineJs .= $stopJs;
        
        // Dialogs allways do prefill. Other widgets only if they have route parameters data
        if (! ($callerWidget instanceof Dialog)) {
            $oRouteParamsCheckJs = <<<JS

            if (oRouteParams.constructor !== Object || Object.keys(oRouteParams).length === 0) {
                $stopJs
                return;
            }
JS;
        }
                        
                        return <<<JS

        // Load prefill data
        (function(){
            {$callerElement->buildJsBusyIconShow()}
            {$oViewModelJs}.setProperty('/_prefill/pending', true);
            
            if ({$urlParamsJs} === undefined) {
                var oRouteParams = {$oViewModelJs}.getProperty('/_route/params');
            } else {
                var oRouteParams = {$urlParamsJs};
            }
            
            $oRouteParamsCheckJs;

            var data = $.extend({}, {
                action: "{$action->getAliasWithNamespace()}",
				resource: "{$callerWidget->getPage()->getAliasWithNamespace()}",
				element: "{$triggerWidget->getId()}",
            }, oRouteParams);
            
            var oLastRouteString = {$oViewModelJs}.getProperty('/_prefill/current_data_hash');
            var oCurrentRouteString = JSON.stringify(data);
            if (oLastRouteString === oCurrentRouteString) {
                $stopJs
                return;
            } else {
                {$oResultModelJs}.setData({});
                {$oViewModelJs}.setProperty('/_prefill/current_data_hash', oCurrentRouteString);
            }
            
            //var oResultModel = {$oResultModelJs};
            var oResultModel = sap.ui.getCore().byId("{$callerElement->getId()}").getModel();
            
            {$callerElement->getServerAdapter()->buildJsServerRequest(
                $action,
                'oResultModel',
                'data',
                $onModelLoadedJs,
                "console.error('Error loading prefill data!');" . $onErrorJs,
                $onOfflineJs
            )}
        })();
        
JS;
    }
    
    /**
     * Returns TRUE if the dialog needs to be prefilled and FALSE otherwise.
     *
     * @return bool
     */
    protected function needsPrefill() : bool
    {
        $rootElement = $this->getView()->getRootElement();
        $widget = $rootElement->getWidget();
        if ($widget->getParent() instanceof iTriggerAction) {
            $action = $widget->getParent()->getAction();
            if (($action instanceof iShowWidget) && ($action->getPrefillWithInputData() || $action->getPrefillWithPrefillData())) {
                return true;
            } else {
                return false;
            }
        }
        
        return true;
    }
}