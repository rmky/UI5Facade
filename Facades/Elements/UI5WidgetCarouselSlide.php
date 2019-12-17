<?php
namespace exface\UI5Facade\Facades\Elements;

/**
 * 
 * @author tmc
 *
 */
class UI5WidgetCarouselSlide extends UI5Tab
{
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5Panel::buildJsConstructor()
     */
    public function buildJsConstructor($oControllerJs = 'oController') : string
    {
        // Since the tab is allways a child of Tabs, we don't need to check for hasPageWrapper() here
        return $this->buildJsSlidePage();
    }
    
    /**
     * 
     * @return string
     */
    protected function buildJsSlidePage()
    {
        $caption = $this->escapeJsTextValue($this->getCaption());
        return <<<JS

    new sap.m.Page("{$this->getId()}", {
        title: "{$caption}",
        content: [
            {$this->buildJsLayoutConstructor($this->buildJsChildrenConstructors())}
        ]
    })

JS;
    }
}