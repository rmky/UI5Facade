<?php
namespace exface\UI5Facade\Facades\Elements;

use exface\UI5Facade\Facades\Interfaces\UI5ControllerInterface;

/**
 * Generates a modified ui5lab.wl.pdf.PDFViewer for a PDFViewer widget
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

        new ui5lab.wl.pdf.PdfViewer("{$this->getid()}", {
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
        $controller->addExternalModule('ui5lab.wl.pdf.PDFViewer', $this->getFacade()->buildUrlToSource('LIBS.PDFVIEWER.DIST') . 'PDFViewer');
        $controller->addExternalModule('ui5lab.wl.pdf.utils.ControlUtils', $this->getFacade()->buildUrlToSource('LIBS.PDFVIEWER.DIST') . 'utils/ControlUtils');
        $controller->addExternalModule('ui5lab.wl.pdf.libs.pdf', $this->getFacade()->buildUrlToSource('LIBS.PDFVIEWER.DIST') . 'libs/pdf');
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