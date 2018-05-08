<?php
namespace exface\OpenUI5Template\Templates\Elements;

use exface\Core\Widgets\InputJson;

/**
 * 
 * @method InputJson getWidget()
 * 
 * @author Andrej Kabachnik
 *
 */
class ui5InputJson extends ui5Input
{    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Elements\ui5Input::buildJsConstructorForMainControl()
     */
    public function buildJsConstructorForMainControl($oControllerJs = 'oController')
    {
        $this->getController()->addExternalCss($this->getTemplate()->buildUrlToSource('LIBS.JSONEDITOR.CSS'));
        $this->getController()->addExternalModule('exface.openui5.jsoneditor', $this->getTemplate()->buildUrlToSource('LIBS.JSONEDITOR.JS'), 'JSONEditor');
        
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
        
    protected function buildJsJsonEditor($oControllerJs = 'oController')
    {
        $controllerVar = $this->buildJsControllerVar();
        $init_value = $this->getWidget()->getValueWithDefaults() ? $oControllerJs . '.' . $controllerVar . '.set(' . $this->getWidget()->getValueWithDefaults() . ');' : '';
        $script = <<<JS

            if ($('#{$this->getId()} > .jsoneditor').length == 0) {
                {$oControllerJs}.{$controllerVar} = new JSONEditor(document.getElementById("{$this->getId()}"), {
    				mode: 'tree',
    				modes: ['code', 'form', 'text', 'tree', 'view'],
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
     * {@inheritDoc}
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement::buildJsValueGetter()
     */
    public function buildJsValueGetter()
    {
        return 'function(){var text = ' . $this->getController()->buildJsAccessFromElement($this) . '.' . $this->buildJsControllerVar() . '.getText(); if (text === "{}" || text === "[]") { return ""; } else { return text;}}';
    }
    
    protected function buildJsControllerVar() : string
    {
        return $this->buildJsVarName() . 'JsonEditor';
    }
    
    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement::buildJsValidator()
     */
    function buildJsValidator()
    {
        return 'true';
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement::buildCssHeightDefaultValue()
     */
    protected function buildCssHeightDefaultValue()
    {
        return ($this->getHeightRelativeUnit() * 5) . 'px';
    }
    
}
?>
