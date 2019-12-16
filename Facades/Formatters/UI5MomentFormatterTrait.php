<?php
namespace exface\UI5Facade\Facades\Formatters;

use exface\UI5Facade\Facades\Interfaces\UI5ControllerInterface;
use exface\UI5Facade\Facades\Interfaces\UI5BindingFormatterInterface;
use exface\UI5Facade\Facades\UI5Facade;

/**
 *  
 * @author Andrej Kabachnik
 *
 */
trait UI5MomentFormatterTrait
{    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Formatters\AbstractUI5BindingFormatter::registerExternalModules()
     */
    public function registerExternalModules(UI5ControllerInterface $controller) : UI5BindingFormatterInterface
    {
        $facade = $controller->getWebapp()->getFacade();
        $controller->addExternalModule('libs.moment.moment', $facade->buildUrlToSource("LIBS.MOMENT.JS"), null, 'moment');
        $controller->addExternalModule('libs.exface.exfTools', $facade->buildUrlToSource("LIBS.EXFTOOLS.JS"), null, 'exfTools');
        $this->registerUi5CustomType($controller);
        $locale = $this->getMomentLocale($facade);
        if ($locale !== '') {
            $controller->addExternalModule('libs.moment.locale', $facade->buildUrlToSource("LIBS.MOMENT.LOCALES") . '/' . $locale . '.js', null);
        }
        return $this;
    }
    
    /**
     * 
     * @param UI5Facade $facade
     * @return string
     */
    protected function getMomentLocale(UI5Facade $facade) : string
    {
        $localesPath = $facade->getWorkbench()->filemanager()->getPathToVendorFolder() . DIRECTORY_SEPARATOR . $facade->getConfig()->getOption('LIBS.MOMENT.LOCALES');
        $fullLocale = $this->getJsFormatter()->getDataType()->getLocale();
        $locale = str_replace("_", "-", $fullLocale);
        if (file_exists($localesPath . DIRECTORY_SEPARATOR . $locale . '.js')) {
            return $locale;
        }
        $locale = substr($fullLocale, 0, strpos($fullLocale, '_'));
        if (file_exists($localesPath . DIRECTORY_SEPARATOR . $locale . '.js')) {
            return $locale;
        }
        return '';
    }
    
    /**
     * 
     * @param UI5ControllerInterface $controller
     * @return UI5BindingFormatterInterface
     */
    protected function registerUi5CustomType(UI5ControllerInterface $controller) : UI5BindingFormatterInterface
    {
        $facade = $controller->getWebapp()->getFacade();
        $controller->addExternalModule('libs.exface.ui5Custom.dataTypes.MomentDateType', $facade->buildUrlToSource("LIBS.UI5CUSTOM.DATETYPE.JS"));
        return $this;
    }
}