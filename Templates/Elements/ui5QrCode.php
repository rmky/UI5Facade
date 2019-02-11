<?php
namespace exface\OpenUI5Template\Templates\Elements;

use exface\Core\Widgets\Html;
use exface\Core\Widgets\Image;

/**
 * Generates com.penninkhof.controls.QRCode for a QrCode widget
 * 
 * @method QrCode getWidget()
 *
 * @author Andrej Kabachnik
 *        
 */
class ui5QrCode extends ui5Display
{
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Elements\ui5AbstractElement::buildJsConstructor()
     */
    public function buildJsConstructorForMainControl($oControllerJs = 'oController')
    {        
        $this->getController()->addExternalModule('com.penninkhof.controls.QRCode', $this->getTemplate()->buildUrlToSource('LIBS.QRCODE.JS') . 'QRCode');
        $this->getController()->addExternalModule('com.penninkhof.controls.3rdparty.qrcode', $this->getTemplate()->buildUrlToSource('LIBS.QRCODE.JS') . '3rdparty/qrcode');
        
        return <<<JS

        new com.penninkhof.controls.QRCode("{$this->getid()}", {
            code: {$this->buildJsValue()},
            {$this->buildJsPropertyHeight()}
            {$this->buildJsPropertyWidth()}
    	})

JS;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Elements\ui5Display::buildJsConstructor()
     */
    public function buildJsConstructor($oControllerJs = 'oController') : string
    {
        return $this->buildJsConstructorForMainControl($oControllerJs);
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
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Elements\ui5Value::buildJsValueBindingPropertyName()
     */
    public function buildJsValueBindingPropertyName() : string
    {
        return 'code';
    }
    
    public function getCaption() : string
    {
        return '';
    }
}