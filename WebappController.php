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
    
    private $externalModules = [];
    
    private $externalCss = [];
    
    public function __construct(Webapp $webapp, string $controllerName, WidgetInterface $rootWidget)
    {
        $this->webapp = $webapp;
        $this->controllerName = $controllerName;
        $this->rootWidget = $rootWidget;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Interfaces\ui5ControllerInterface::buildJsMethodName()
     */
    public function buildJsMethodName(string $methodName, ui5AbstractElement $ownerElement) : string
    {
        return $methodName . StringDataType::convertCaseUnderscoreToPascal($ownerElement->getId());
    }
    
    
    public function buildJsObjectName(string $objectName, ui5AbstractElement $ownerElement) : string
    {
        return $objectName . StringDataType::convertCaseUnderscoreToPascal($ownerElement->getId());
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
            $oControllerJsVar = "{$this->buildJsComponentGetter()}.findViewOfControl(sap.ui.getCore().byId('{$methodOwner->getId()}')).getController()";
        }
        if ($methodOwner->getController() === $this) {
            return "{$oControllerJsVar}.{$this->buildJsMethodName($methodName, $methodOwner)}({$paramsJs})";
        }
        
        throw new TemplateLogicError('Calling a controller method from another controller not implemented yet!');
    }
    
    public function buildJsAccessFromElement(ui5AbstractElement $fromElement) : string
    {
        return "sap.ui.getCore().byId('{$this->getViewId($fromElement)}').getController()";
    }
    
    protected function buildJsComponentGetter()
    {
        return "sap.ui.getCore().getComponent('{$this->getWebapp()->getComponentId()}')";
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
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Interfaces\ui5ControllerInterface::buildJsViewEventHandler()
     */
    public function buildJsViewEventHandler(string $methodName, ui5AbstractElement $callerElement, string $jsFunction) : string
    {
        $this->addProperty($this->buildJsMethodName($methodName, $callerElement), $jsFunction);
        return $this->buildJsMethodCallFromView($methodName, $callerElement);
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Interfaces\ui5ControllerInterface::addProperty()
     */
    public final function addProperty(string $name, string $js) : ui5ControllerInterface
    {
        if ($this->wasExported === true) {
            throw new TemplateLogicError('Cannot add controller property "' . $name . '" after the controller "' . $this->getName() . '" had been built!');
        }
        $this->properties[$name] = $js;
        return $this;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Interfaces\ui5ControllerInterface::addDependentObject()
     */
    public function addDependentObject(string $objectName, ui5AbstractElement $ownerElement, string $initJs) : ui5ControllerInterface
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
        $this->addOnInitScript('console.log(JSONEditor);' . $initFunctionCall);
        
        return $this;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Interfaces\ui5ControllerInterface::addMethod()
     */
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
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Interfaces\ui5ControllerInterface::addDependentControl()
     */
    public function addDependentControl(ui5AbstractElement $element, $name = null) : ui5ControllerInterface
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
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Interfaces\ui5ControllerInterface::buildJsController()
     */
    public function buildJsController() : string
    {
        foreach ($this->externalModules as $name => $properties) {
            $modules .= ",\n\t\"" . str_replace('.', '/', $name) . '"';
            $controllerVars .= ', ' . ($properties['var'] ? $properties['var'] : $this->getDefaultVarForModule($name));
            $moduleRegistration .= "\n" . $this->buildJsModulePathRegistration($name, $properties['path']);
        }
        $cssIncludes = $this->buildJsCssIncludes();
        return <<<JS

{$cssIncludes}

{$moduleRegistration}
        
sap.ui.define([
	"{$this->getWebapp()->getComponentPath()}/controller/BaseController"{$modules}
], function (BaseController{$controllerVars}) {
	"use strict";
	
	return BaseController.extend("{$this->getName()}", {

        onInit: function () {
            var oController = this;
			{$this->buildJsOnInitScript()}
		},

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
    
    /**
     * 
     * @return string
     */
    protected function buildJsProperties() : string
    {
        $this->wasExported = true;
        $js = '';
        
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
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Interfaces\ui5ControllerInterface::addExternalModule()
     */
    public function addExternalModule(string $name, string $path, string $var = null) : ui5ControllerInterface
    {
        $this->externalModules[$name] = ['path' => $path, 'var' => $var];
        return $this;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Interfaces\ui5ControllerInterface::addExternalCss()
     */
    public function addExternalCss(string $path, string $id = null) : ui5ControllerInterface
    {
        $this->externalCss[($id === null ? $path : $id)] = $path;
        return $this;
    }
    
    /**
     * 
     * @param string $moduleName
     * @return string
     */
    protected function getDefaultVarForModule(string $moduleName) : string
    {
        $split = explode('.', $moduleName);
        $cnt = count($split);
        for ($i=1; $i<$cnt; $i++) {
            $var .= StringDataType::convertCaseUnderscoreToPascal($split[$i]);
        }
        $var = lcfirst($var);
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
    
    
}