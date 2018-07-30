<?php
namespace exface\OpenUI5Template;

use exface\OpenUI5Template\Templates\Interfaces\ui5ControllerInterface;
use exface\Core\DataTypes\StringDataType;
use exface\OpenUI5Template\Templates\Elements\ui5AbstractElement;
use exface\Core\Exceptions\Templates\TemplateLogicError;
use exface\OpenUI5Template\Templates\Interfaces\ui5ViewInterface;
use exface\Core\Exceptions\OutOfBoundsException;

class WebappController implements ui5ControllerInterface
{
    private $isBuilt = false;
    
    private $webapp = null;
    
    private $view = null;
    
    private $controllerName = '';
    
    private $properties = [];
    
    private $onInitScripts = [];
    
    private $externalModules = [];
    
    private $externalCss = [];
    
    public function __construct(Webapp $webapp, string $controllerName, ui5ViewInterface $view)
    {
        $this->webapp = $webapp;
        $this->controllerName = $controllerName;
        $this->view = $view->setController($this);
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
        $propertyName = $this->buildJsMethodName($methodName, $callerElement);
        if (! $this->hasProperty($propertyName)) {
            throw new OutOfBoundsException('Method "' . $propertyName . '" not found in controller "' . $this->getName() . '"!');
        }
        
        return "[{$oController}.{$propertyName}, {$oController}]";
    }
    
    public function buildJsMethodCallFromController(string $methodName, ui5AbstractElement $methodOwner, string $paramsJs, string $oControllerJsVar = null) : string
    {
        if ($oControllerJsVar === null) {
            $oControllerJsVar = "{$this->buildJsComponentGetter()}.findViewOfControl(sap.ui.getCore().byId('{$methodOwner->getId()}')).getController()";
        }
        
        $propertyName = $this->buildJsMethodName($methodName, $methodOwner);
        if (! $this->hasProperty($propertyName)) {
            throw new OutOfBoundsException('Method "' . $propertyName . '" not found in controller "' . $this->getName() . '"!');    
        }
        
        if ($methodOwner->getController() === $this) {
            return "{$oControllerJsVar}.{$propertyName}({$paramsJs})";
        }
        
        throw new TemplateLogicError('Calling a controller method from another controller not implemented yet!');
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Interfaces\ui5ControllerInterface::buildJsControllerGetter()
     */
    public function buildJsControllerGetter(ui5AbstractElement $fromElement) : string
    {
        return "sap.ui.getCore().byId('{$this->getViewId($fromElement)}').getController()";
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Interfaces\ui5ControllerInterface::buildJsComponentGetter()
     */
    public function buildJsComponentGetter() : string
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
        if ($this->isBuilt === true) {
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
        $this->addOnInitScript($initFunctionCall, $name);
        
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
    public function addDependentControl(string $name, ui5AbstractElement $ownerElement, ui5AbstractElement $dependentElement) : ui5ControllerInterface
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
        $this->addOnInitScript($initFunctionCall, $initMethodName);
        return $this;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Interfaces\ui5ControllerInterface::buildJsController()
     */
    public function buildJsController() : string
    {
        // Build the view first to ensure, all view elements have contributed to the controller!
        $this->getView()->buildJsView();
        
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
     * @see \exface\OpenUI5Template\Templates\Interfaces\ui5ControllerInterface::getPath()
     */
    public function getPath() : string
    {
        return $this->webapp::convertNameToPath($this->getName(), '.controller.js');
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
        $js = '';
        foreach ($this->onInitScripts as $script) {
            $js .= "\n\n" . $this->sanitizeScript($script);
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
     * @see \exface\OpenUI5Template\Templates\Interfaces\ui5ControllerInterface::addOnInitScript()
     */
    public function addOnInitScript(string $js, string $id) : ui5ControllerInterface
    {
        $this->onInitScripts[$id] = $js;
        return $this;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Interfaces\ui5ControllerInterface::addExternalModule()
     */
    public function addExternalModule(string $name, string $urlRelativeToAppRoot, string $var = null) : ui5ControllerInterface
    {
        $this->externalModules[$name] = ['path' => $urlRelativeToAppRoot, 'var' => $var];
        return $this;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Interfaces\ui5ControllerInterface::getExternalModules()
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
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Interfaces\ui5ControllerInterface::getView()
     */
    public function getView() : ui5ViewInterface
    {
        return $this->view;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Interfaces\ui5ControllerInterface::hasProperty()
     */
    public function hasProperty(string $name) : bool
    {
        return ! empty($this->properties[$name]);
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Interfaces\ui5ControllerInterface::hasMethod()
     */
    public function hasMethod(string $name, ui5AbstractElement $ownerElement) : bool
    {
        return $this->hasProperty($this->buildJsMethodName($name, $ownerElement));
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Interfaces\ui5ControllerInterface::hasDependent()
     */
    public function hasDependent(string $name, ui5AbstractElement $ownerElement) : bool
    {
        return $this->hasProperty($this->buildJsObjectName($name, $ownerElement));
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Interfaces\ui5ControllerInterface::buildJsDependentControlSelector()
     */
    public function buildJsDependentControlSelector(string $controlName, ui5AbstractElement $ownerElement, string $oControllerJsVar = null) : string
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
}