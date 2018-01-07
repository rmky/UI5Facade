<?php
namespace exface\OpenUI5Template\Template\Elements;

use exface\Core\Widgets\DataConfigurator;
use exface\Core\Widgets\ChartSeries;
use exface\Core\Widgets\ChartAxis;
use exface\Core\Templates\AbstractAjaxTemplate\Elements\JqueryFlotTrait;

/**
 * 
 * @method DataConfigurator getWidget()
 * 
 * @author Andrej Kabachnik
 *
 */
class ui5InputJson extends ui5Input
{
    use JqueryFlotTrait {
        generateHeaders as generateHeadersByTrait;
    }
    
    public function buildJsConstructor()
    {
        return <<<JS

        new sap.ui.core.HTML("{$this->getId()}_wrapper", {
            content: "<div id=\"{$this->getId()}\" style=\"height: {$this->getHeight()}; width: 100%;\"></div>",
            afterRendering: function() { {$this->buildJsJsonEditor()} }
        })

JS;
    }
        
    protected function buildJsJsonEditor()
    {
        $init_value = $this->getWidget()->getValueWithDefaults() ? $this->getId() . '_JSONeditor.set(' . $this->getWidget()->getValueWithDefaults() . ');' : '';
        $script = <<<JS

            if ($('#{$this->getId()} > .jsoneditor').length == 0) {
                var {$this->getId()}_JSONeditor = new JSONEditor(document.getElementById("{$this->getId()}"), {
                                					mode: 'tree',
                               						modes: ['code', 'form', 'text', 'tree', 'view']
                            					});
                {$init_value}
                {$this->getId()}_JSONeditor.expandAll();
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
        return 'function(){var text = ' . $this->getId() . '_JSONeditor.getText(); if (text === "{}" || text === "[]") { return ""; } else { return text;}}';
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement::generateHeaders()
     */
    public function generateHeaders()
    {
        $includes = parent::generateHeaders();
        $includes[] = '<link href="exface/vendor/bower-asset/jsoneditor/dist/jsoneditor.min.css" rel="stylesheet">';
        $includes[] = '<script type="text/javascript" src="exface/vendor/bower-asset/jsoneditor/dist/jsoneditor.min.js"></script>';
        return $includes;
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
