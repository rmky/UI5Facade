<?php
namespace exface\OpenUI5Template;

use exface\OpenUI5Template\Templates\Interfaces\ui5ControllerInterface;
use exface\Core\DataTypes\StringDataType;
use exface\OpenUI5Template\Templates\Elements\ui5AbstractElement;
use exface\Core\Exceptions\Templates\TemplateLogicError;
use exface\Core\Interfaces\WidgetInterface;

class WebappController implements ui5ControllerInterface
{
    private $wasExported = false;
    
    private $webapp = null;
    
    private $rootWidget = null;
    
    private $controllerName = '';
    
    private $properties = [];
    
    private $onInitScript = '';
    
    public function __construct(Webapp $webapp, string $controllerName, WidgetInterface $rootWidget)
    {
        $this->webapp = $webapp;
        $this->controllerName = $controllerName;
        $this->rootWidget = $rootWidget;
    }
    
    /**
     *
     * @param string $purpose
     * @return string
     */
    public function buildJsMethodName(string $methodName, ui5AbstractElement $ownerElement) : string
    {
        return $methodName . StringDataType::convertCaseUnderscoreToPascal($ownerElement->getId());
    }
    
    /**
     *
     * @param string $methodName
     * @return string
     */
    public function buildJsMethodCallFromView(string $methodName, ui5AbstractElement $callerElement, $oController = 'oController') : string
    {
        return "[{$oController}.{$this->buildJsMethodName($methodName, $callerElement)}, {$oController}]";
    }
    
    public function buildJsMethodCallFromController(string $methodName, ui5AbstractElement $methodOwner, string $paramsJs, string $oControllerJsVar = null) : string
    {
        if ($oControllerJsVar === null) {
            $oControllerJsVar = "sap.ui.getCore().byId('{$this->getViewId($methodOwner)}').getController()";
        }
        if ($methodOwner->getController() === $this) {
            return "{$oControllerJsVar}.{$this->buildJsMethodName($methodName, $methodOwner)}({$paramsJs})";
        }
        
        throw new TemplateLogicError('Calling a controller method from another controller not implemented yet!');
    }
    
    /**
     * TODO replace by a dedicated view object and $this->getView()
     * 
     * @param ui5AbstractElement $element
     * @return string
     */
    protected function getViewId(ui5AbstractElement $element) : string
    {
        return str_replace('.controller.', '.view.', $this->getId());
    }
    
    /**
     *
     * @param string $methodName
     * @param string $jsFunction
     * @return string
     */
    public function buildJsViewEventHandler(string $methodName, ui5AbstractElement $callerElement, string $jsFunction) : string
    {
        $this->addProperty($this->buildJsMethodName($methodName, $callerElement), $jsFunction);
        return $this->buildJsMethodCallFromView($methodName, $callerElement);
    }
    
    /**
     *
     * @param string $js
     * @throws TemplateLogicError
     * @return ui5AbstractElement
     */
    public final function addProperty(string $name, string $js) : ui5ControllerInterface
    {
        if ($this->wasExported === true) {
            throw new TemplateLogicError('Cannot add controller property "' . $name . '" after the controller "' . $this->getName() . '" had been built!');
        }
        $this->properties[$name] = $js;
        return $this;
    }
    
    public final function addMethod(string $methodName, ui5AbstractElement $methodOwner, string $params, string $body, $comment = '') : ui5ControllerInterface
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
    
    public function addControl(ui5AbstractElement $element, $name = null) : ui5ControllerInterface
    {
        if ($name === null) {
            $name = $element->buildJsVarName();
        }
        
        $initFunctionCall = <<<JS
        
                this._{$name}Init();
JS;
        $initFunction = <<<JS
function() {
                    var oController = this;
                    this.{$name} = {$element->buildJsConstructor('oController')};
                    oController.getView().addDependent(this.{$name});
                },
JS;
        $this->addProperty($name, 'null');
        $this->addProperty('_'.$name.'Init', $initFunction);
        $this->addOnInitScript($initFunctionCall);
        return $this;
    }
    
    public function buildJsController() : string
    {
        return <<<JS

sap.ui.define([
	"{$this->getWebapp()->getComponentPath()}/controller/BaseController"
], function (BaseController) {
	"use strict";
	
	return BaseController.extend("{$this->getName()}", {

		{$this->buildJsProperties()}

	});

});

JS;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Interfaces\ui5ControllerInterface::getName()
     */
    public function getName() : string
    {
        return $this->controllerName;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Interfaces\ui5ControllerInterface::getId()
     */
    public function getId() : string
    {
        return $this->controllerName;
    }
    
    protected function buildJsProperties() : string
    {
        $this->wasExported = true;
        $js = <<<JS

        onInit: function () {
            var oController = this;
			{$this->buildJsOnInitScript()}
		},

JS;
        
        foreach ($this->properties as $name => $script) {
            $js .= $name . ': ' . rtrim($script, ", \r\n\t\0\0xB") . ",\n";
        }
        return $js;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Interfaces\ui5ControllerInterface::getWebapp()
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
        return $this->onInitScript;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Interfaces\ui5ControllerInterface::addOnInitScript()
     */
    public function addOnInitScript(string $js) : ui5ControllerInterface
    {
        $this->onInitScript .= "\n\n" . $js;
        return $this;
    }
}