<?php
namespace exface\UI5Facade\Facades\Elements;

use exface\Core\Widgets\InputJson;
use exface\UI5Facade\Facades\Interfaces\UI5ControllerInterface;

/**
 * 
 * @method InputJson getWidget()
 * 
 * @author Andrej Kabachnik
 *
 */
class UI5InputJson extends UI5Input
{    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5Input::buildJsConstructorForMainControl()
     */
    public function buildJsConstructorForMainControl($oControllerJs = 'oController')
    {
        // TODO create own control instead of using the HTML control in order to be able to destroy the JSONeditor
        // properly. The way the whole thing works now, the JS variable {$this->getId()}_JSONeditor lives even
        // after the control or it's view had been destroyed.
        return <<<JS

        new sap.ui.core.HTML("{$this->getId()}_wrapper", {
            content: "<div id=\"{$this->getId()}\" style=\"height: {$this->getHeight()}; width: 100%;\"></div>",
            afterRendering: function() { {$this->buildJsJsonEditor('oController')} }
        })

JS;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5AbstractElement::registerExternalModules()
     */
    public function registerExternalModules(UI5ControllerInterface $controller) : UI5AbstractElement
    {
        $controller->addExternalCss($this->getFacade()->buildUrlToSource('LIBS.JSONEDITOR.CSS'));
        $controller->addExternalModule('exface.openui5.jsoneditor', $this->getFacade()->buildUrlToSource('LIBS.JSONEDITOR.JS'), 'JSONEditor');
        
        return $this;
    }
        
    protected function buildJsJsonEditor($oControllerJs = 'oController')
    {
        $controllerVar = $this->buildJsControllerVar();
        $init_value = $this->getWidget()->getValueWithDefaults() ? $oControllerJs . '.' . $controllerVar . '.set(' . $this->getWidget()->getValueWithDefaults() . ');' : '';
        $script = <<<JS

            if ($('#{$this->getId()} > .jsoneditor').length == 0) {
                {$oControllerJs}.{$controllerVar} = new JSONEditor(document.getElementById("{$this->getId()}"), {
    				mode: {$this->buildJsEditorModeDefault()},
    				modes: {$this->buildJsEditorModes()},
                    sortObjectKeys: false
    			});
                {$init_value}
                {$oControllerJs}.{$controllerVar}.expandAll();
                $('#{$this->getId()}').parents('.exf-input').children('label').css('vertical-align', 'top');
            }

JS;
            return $script;
    }
    
    /**
     *
     * @return string
     */
    protected function buildJsEditorModes() : string
    {
        if ($this->getWidget()->isDisabled()) {
            return "['view']";
        }
        return "['code', 'tree']";
    }
    
    /**
     *
     * @return string
     */
    protected function buildJsEditorModeDefault() : string
    {
        if ($this->getWidget()->isDisabled()) {
            return "'view'";
        }
        return "'tree'";
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildJsValueGetter()
     */
    public function buildJsValueGetter()
    {
        return 'function(){var text = ' . $this->getController()->buildJsControllerGetter($this) . '.' . $this->buildJsControllerVar() . '.getText(); if (text === "{}" || text === "[]") { return ""; } else { return text;}}';
    }
    
    protected function buildJsControllerVar() : string
    {
        return $this->buildJsVarName() . 'JsonEditor';
    }
    
    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildJsValidator()
     */
    function buildJsValidator()
    {
        return 'true';
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildCssHeightDefaultValue()
     */
    protected function buildCssHeightDefaultValue()
    {
        return ($this->getHeightRelativeUnit() * 5) . 'px';
    }
    
}
?>
