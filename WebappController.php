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
    
    public function buildJsMethodCallFromController(string $methodName, ui5AbstractElement $methodOwner, string $paramsJs, string $oControllerJsVar = 'this') : string
    {
        if ($methodOwner->getController() === $this) {
            return "{$oControllerJsVar}.{$this->buildJsMethodName($methodName, $methodOwner)}({$paramsJs})";
        }
        
        throw new TemplateLogicError('Calling a controller method from another controller not implemented yet!');
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
    "sap/ui/core/mvc/Controller"
], function (Controller) {
    "use strict";
    
    return Controller.extend("{$this->getName()}", {
        {$this->buildJsProperties()}
    });
});

JS;
        return <<<JS

sap.ui.define([
	"{$this->getWebapp()->getComponentPath()}/controller/BaseController"
], function (BaseController) {
	"use strict";
	
	return BaseController.extend("{$this->getWebapp()->getComponentName()}.controller.$this->getName()", {

		{$this->buildJsProperties()}

	});

});

JS;
    }
    
    public function getName() : string
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
    
    public function getWebapp() : Webapp
    {
        return $this->webapp;
    }
    
    protected function buildJsOnInitScript() : string
    {
        return $this->onInitScript;
    }
    
    public function addOnInitScript(string $js) : ui5ControllerInterface
    {
        $this->onInitScript .= "\n\n" . $js;
        return $this;
    }
}