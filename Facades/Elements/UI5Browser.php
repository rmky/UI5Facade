<?php
namespace exface\UI5Facade\Facades\Elements;

use exface\Core\Facades\AbstractAjaxFacade\Elements\HtmlBrowserTrait;

class UI5Browser extends UI5AbstractElement
{
    use HtmlBrowserTrait;
    
    public function buildJsConstructor($oControllerJs = 'oController') : string
    {
        $escapedHtml = json_encode($this->buildHtmlIFrame());
        $control = <<<JS
        
        new sap.ui.core.HTML("{$this->getId()}_wrapper", {
            content: {$escapedHtml}
        })
        
JS;
        if ($this->getWidget()->hasParent() === false) {
            return $this->buildJsPageWrapper($control, '', '', true);
        }
        
        return $control;
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\HtmlBrowserTrait::buildCssElementStyle()
     */
    public function buildCssElementStyle()
    {
        return 'width: 100%; height: calc(100% - 5px); border: 0;';
    }
}