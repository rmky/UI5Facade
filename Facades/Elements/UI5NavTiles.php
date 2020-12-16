<?php
namespace exface\UI5Facade\Facades\Elements;

/**
 * Renders a default container for NavTiles.
 * 
 * @method \exface\Core\Widgets\NavTiles getWidget()
 * 
 * @author Andrej Kabachnik
 *
 */
class UI5NavTiles extends UI5Container
{
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5Container::buildJsConstructor()
     */
    public function buildJsConstructor($oControllerJs = 'oController') : string
    {
        // If the NavTiles is the root widget of a view, it will have a header with the caption
        // of the first tile group - so just hide the caption of that group to avoid duplicates.
        $widget = $this->getWidget();
        if ($widget->hasParent() === false && $widget->hasWidgets()) {
            $widget->getWidgetFirst()->setHideCaption(true);
        }
        if ($widget->isEmpty() && $widget->isHiddenIfEmpty()) {
            return '';
        }
        return parent::buildJsConstructor($oControllerJs);
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5Container::buildJsChildrenConstructors()
     */
    public function buildJsChildrenConstructors() : string
    {
        if ($this->getWidget()->isEmpty()) {
            return <<<JS

            new sap.m.FlexBox({
                height: "100%",
                width: "100%",
                justifyContent: "Center",
                alignItems: "Center",
                items: [
                    new sap.m.Text({
                        text: "{$this->getWidget()->getEmptyText()}"
                    })
                ]
            })

JS;
        }
        
        return parent::buildJsChildrenConstructors();
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildCssElementClass()
     */
    public function buildCssElementClass()
    {
        return 'exf-navtiles';
    }
}