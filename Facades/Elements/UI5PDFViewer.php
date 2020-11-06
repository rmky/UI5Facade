<?php
namespace exface\UI5Facade\Facades\Elements;

use exface\UI5Facade\Facades\Interfaces\UI5ControllerInterface;

/**
 * Generates custom PDFViewer using the popular PDF.js library
 * 
 * @method \exface\Core\Widgets\PDFViewer getWidget()
 *
 * @author Andrej Kabachnik
 *        
 */
class UI5PDFViewer extends UI5Display
{
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5AbstractElement::buildJsConstructor()
     */
    public function buildJsConstructorForMainControl($oControllerJs = 'oController')
    {        
        $this->registerExternalModules($this->getController());
        return <<<JS

        new exface.ui5Custom.PdfViewer("{$this->getid()}", {
            pdfSource: {$this->buildJsValue()},
            {$this->buildJsPropertyHeight()}
            {$this->buildJsPropertyWidth()}
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
        $controller->addExternalModule('libs.exface.ui5Custom.PdfViewer', 'vendor/exface/UI5Facade/Facades/js/ui5Custom/PDFViewer');
        $controller->addExternalModule('libs.exface.ui5Custom.libs.pdf', 'vendor/exface/UI5Facade/Facades/js/ui5Custom/libs/pdf');
        return $this;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5Display::buildJsConstructor()
     */
    public function buildJsConstructor($oControllerJs = 'oController') : string
    {
        return $this->buildJsConstructorForMainControl($oControllerJs);
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5Value::buildCssHeightDefaultValue()
     */
    protected function buildCssHeightDefaultValue()
    {
        return '';
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5Value::buildCssWidthDefaultValue()
     */
    protected function buildCssWidthDefaultValue() : string
    {
        return '';
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5Value::buildJsValueBindingPropertyName()
     */
    public function buildJsValueBindingPropertyName() : string
    {
        return 'pdfSource';
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::getCaption()
     */
    public function getCaption() : string
    {
        return '';
    }
}