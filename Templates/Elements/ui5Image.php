<?php
namespace exface\OpenUI5Template\Templates\Elements;

use exface\Core\Widgets\Html;
use exface\Core\Widgets\Image;

/**
 * Generates sap.m.Image
 * 
 * @method Image getWidget()
 *
 * @author Andrej Kabachnik
 *        
 */
class ui5Image extends ui5Display
{
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Elements\ui5AbstractElement::buildJsConstructor()
     */
    public function buildJsConstructorForMainControl($oControllerJs = 'oController')
    {        
        return <<<JS

        new sap.m.Image("{$this->getid()}", {
    		src: {$this->buildJsValue()},
            densityAware: false,
            {$this->buildJsProperties()}
    	})

JS;
    }
            
    public function buildJsValueBindingOptions()
    {
        if ($this->getWidget()->getUseProxy()) {
        
            return <<<JS

        formatter: function(value) {
            var url = encodeURI(value);
            var proxyUrl = "{$this->getWidget()->buildProxyUrl('xxurixx')}";
            return proxyUrl.replace("xxurixx", url);
        },

JS;
        }
            
        return parent::buildJsValueBindingOptions();
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Elements\ui5Value::buildCssHeightDefaultValue()
     */
    protected function buildCssHeightDefaultValue()
    {
        return '';
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Elements\ui5Value::buildCssWidthDefaultValue()
     */
    protected function buildCssWidthDefaultValue()
    {
        return '';
    }
}
?>