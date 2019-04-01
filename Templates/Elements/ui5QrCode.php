<?php
namespace exface\UI5Facade\Facades\Elements;

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
     * @see \exface\UI5Facade\Facades\Elements\ui5AbstractElement::buildJsConstructor()
     */
    public function buildJsConstructorForMainControl($oControllerJs = 'oController')
    {        
        $this->getController()->addExternalModule('com.penninkhof.controls.QRCode', $this->getFacade()->buildUrlToSource('LIBS.QRCODE.JS') . 'QRCode');
        $this->getController()->addExternalModule('com.penninkhof.controls.3rdparty.qrcode', $this->getFacade()->buildUrlToSource('LIBS.QRCODE.JS') . '3rdparty/qrcode');
        
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
     * @see \exface\UI5Facade\Facades\Elements\ui5Display::buildJsConstructor()
     */
    public function buildJsConstructor($oControllerJs = 'oController') : string
    {
        return $this->buildJsConstructorForMainControl($oControllerJs);
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\ui5Value::buildCssHeightDefaultValue()
     */
    protected function buildCssHeightDefaultValue()
    {
        return '';
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\ui5Value::buildCssWidthDefaultValue()
     */
    protected function buildCssWidthDefaultValue()
    {
        return '';
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\ui5Value::buildJsValueBindingPropertyName()
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