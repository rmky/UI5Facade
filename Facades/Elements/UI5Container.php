<?php
namespace exface\UI5Facade\Facades\Elements;

use exface\Core\Facades\AbstractAjaxFacade\Elements\JqueryContainerTrait;
use exface\Core\Widgets\Container;

/**
 * Renders a sap.m.Panel with no margins or paddings for a simple Container widget.
 * 
 * @method Container getWidget()
 * 
 * @author Andrej Kabachnik
 *
 */
class UI5Container extends UI5AbstractElement
{
    use JqueryContainerTrait;
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5AbstractElement::buildJsConstructor()
     */
    public function buildJsConstructor($oControllerJs = 'oController') : string
    {
        $js = $this->buildJsPanelWrapper($this->buildJsChildrenConstructors());
        
        if ($this->hasPageWrapper() === true) {
            return $this->buildJsPageWrapper($js);
        }
        
        return $js;
    }
    
    /**
     * Wraps any JS content in an sap.m.Panel with no margins/padding.
     * 
     * @param string $contentJs
     * @return string
     */
    protected function buildJsPanelWrapper(string $contentJs) : string
    {
        $caption = $this->getCaption();
        if ($caption && $this->hasPageWrapper() === false) {
            $heading = "headerText: '{$caption}',";
        }
        return <<<JS

        new sap.m.Panel("{$this->getId()}", {
            {$heading}
            {$this->buildJsPropertyHeight()}
            content: [
                {$contentJs}
            ]
        }).addStyleClass("sapUiNoMargin sapUiNoContentPadding {$this->buildCssElementClass()}")

JS;
    }
    
    /**
     * Returns height: "xxx" if required by the container control
     * 
     * @return string
     */
    protected function buildJsPropertyHeight() : string
    {
        if ($this->getWidget()->hasParent() === false) {
            return 'height: "100%",';
        }
        return '';
    }
                
    /**
     * Returns TRUE if this widget requires a page wrapper.
     * 
     * @return bool
     */
    protected function hasPageWrapper() : bool
    {
        return $this->getWidget()->hasParent() === false && $this->getView()->isWebAppRoot() === false;
    }
    
    /**
     * Wraps the given content in a sap.m.Page with back-button and a title.
     *
     * @param string $contentJs
     * @param string $footerConstructor
     * @param string $headerContentJs
     *
     * @return string
     */
    protected function buildJsPageWrapper(string $contentJs, string $footerConstructor = '', string $headerContentJs = '') : string
    {
        $showNavButton = $this->getView()->isWebAppRoot() ? 'false' : 'true';
        
        $caption = $this->getCaption();
        if ($caption === '' && $this->getWidget()->hasParent() === false) {
            $caption = $this->getWidget()->getPage()->getName();
        }
        
        return <<<JS
        
        new sap.m.Page({
            title: "{$caption}",
            showNavButton: {$showNavButton},
            navButtonPress: [oController.onNavBack, oController],
            content: [
                {$contentJs}
            ],
            footer: [
                {$footerConstructor}
            ],
            headerContent: [
                {$headerContentJs}
            ]
        })
        
JS;
    }
    
    /**
     * 
     * @return string
     */
    public function buildJsChildrenConstructors() : string
    {
        $js = '';
        foreach ($this->getWidget()->getWidgets() as $widget) {
            $js .= ($js ? ",\n" : '') . $this->getFacade()->getElement($widget)->buildJsConstructor();
        }
        
        return $js;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\JqueryContainerTrait::buildJsValidationError()
     */
    public function buildJsValidationError()
    {        
        $output = $this->buildJsShowMessageError(json_encode($this->translate('WIDGET.FORM.FILL_REQUIRED_FIELDS'))) . ';';
        foreach ($this->getWidgetsToValidate() as $child) {
            $el = $this->getFacade()->getElement($child);
            $validator = $el->buildJsValidator();
            $output .= '
				if(!' . $validator . ') { ' . $el->buildJsValidationError() . '; }';
        }
        return $output;
    }
}