<?php
namespace exface\UI5Facade\Facades\Formatters;

use exface\UI5Facade\Facades\Interfaces\UI5ControllerInterface;
use exface\UI5Facade\Facades\Interfaces\UI5BindingFormatterInterface;

/**
 * 
 * @method JsTimeFormatter getJsFormatter()
 * 
 * @author Andrej Kabachnik
 *
 */
class UI5TimeFormatter extends UI5DateFormatter
{    
    use UI5MomentFormatterTrait;
    
    protected function buildPatternSource()
    {
        return 'HH:mm:ss';
    }
    
    protected function getSapDataType()
    {
        return 'exface.ui5Custom.dataTypes.MomentTimeType';
    }
    
    /**
     * 
     * @see UI5MomentFormatterTrait::registerUi5CustomType()
     */
    protected function registerUi5CustomType(UI5ControllerInterface $controller) : UI5BindingFormatterInterface
    {
        $facade = $controller->getWebapp()->getFacade();
        $controller->addExternalModule('libs.exface.ui5Custom.dataTypes.MomentTimeType', $facade->buildUrlToSource("LIBS.UI5CUSTOM.TIMETYPE.JS"));
        return $this;
    }
}