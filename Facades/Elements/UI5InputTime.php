<?php
namespace exface\UI5Facade\Facades\Elements;

use exface\Core\Widgets\InputTime;

/**
 * Renders a sap.m.TimePicker for InputTime widgets.
 * 
 * @method InputTime getWidget()
 *
 * @author Andrej Kabachnik
 *
 */
class UI5InputTime extends UI5InputDate
{
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5AbstractElement::buildJsConstructor()
     */
    public function buildJsConstructor($oControllerJs = 'oController') : string
    {
        $controller = $this->getController();
        $this->registerConditionalBehaviors();
        $this->registerOnChangeValidation();
        
        $controller->addExternalModule('libs.moment.moment', $this->getFacade()->buildUrlToSource("LIBS.MOMENT.JS"), null, 'moment');
        $controller->addExternalModule('libs.exface.exfTools', $this->getFacade()->buildUrlToSource("LIBS.EXFTOOLS.JS"), null, 'exfTools');
        $controller->addExternalModule('libs.exface.ui5Custom.dataTypes.MomentTimeType', $this->getFacade()->buildUrlToSource("CUSTOM.TIMETYPE.JS"));
        
        return $this->buildJsLabelWrapper($this->buildJsConstructorForMainControl($oControllerJs));
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5InputDate::buildJsConstructorForMainControl()
     */
    public function buildJsConstructorForMainControl($oControllerJs = 'oController')
    {
        return <<<JS
        
        new sap.m.TimePicker("{$this->getId()}", {
            {$this->buildJsProperties()}
		})
        {$this->buildJsInternalModelInit()}
        {$this->buildJsPseudoEventHandlers()}
		
JS;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5InputDate::buildJsValueBindingOptions()
     */
    public function buildJsValueBindingOptions()
    {
        return <<<JS
        
                type: 'exface.ui5Custom.dataTypes.MomentTimeType',
                {$this->buildJsValueBindingFormatOptions()}
JS;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5InputDate::buildJsValueFormat()
     */
    protected function buildJsValueFormat() : string
    {
        return '"HH:mm:ss"';
    }
            
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5InputDate::buildJsDisplayFormat()
     */
    protected function buildJsDisplayFormat() : string
    {
        $widget = $this->getWidget();
        
        $format = 'HH:mm';
        if ($widget->getShowSeconds() === true) {
            $format .= ':ss';
        }
        if ($widget->getAmPm() === true) {
            $format .= ' a';
        }
        
        return '"' . $format . '"';
    }
    
}