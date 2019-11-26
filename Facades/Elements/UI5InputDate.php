<?php
namespace exface\UI5Facade\Facades\Elements;

use exface\Core\Widgets\InputDate;
use exface\Core\Factories\DataPointerFactory;

/**
 * Generates sap.m.DatePicker for InputDate widgets
 *
 * @method InputDate getWidget()
 *
 * @author Andrej Kabachnik
 *
 */
class UI5InputDate extends UI5Input
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
        $default = $this->getWidget()->getValueWithDefaults();
        $controller->addExternalModule('libs.momentJs', $this->getFacade()->buildUrlToSource("LIBS.MOMENT.JS"));
        $controller->addExternalModule('libs.exfToolsJs', $this->getFacade()->buildUrlToSource("LIBS.EXFTOOLS.JS"));
        $controller->addExternalModule('libs.DateTypeJs', $this->getFacade()->buildUrlToSource("CUSTOM.DATETYPE.JS"));
        $js = <<<JS
            /* global exfTools: true */
            var oViewModel = oView.getModel('view');
            var defaultValue = '{$default}';
            var parsedValue = exfTools.date.parse(defaultValue);
            oViewModel.setProperty("/{$this->getId()}", {dateValue: parsedValue});
            
            
JS;
        $controller->addOnInitScript($js);
        return $this->buildJsLabelWrapper($this->buildJsConstructorForMainControl($oControllerJs));
    }
    /**
     *
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5Input::buildJsConstructorForMainControl()
     */
    public function buildJsConstructorForMainControl($oControllerJs = 'oController')
    {
        return <<<JS
        
        new sap.m.DatePicker("{$this->getId()}", {
            {$this->buildJsProperties()}
		}){$this->buildJsPseudoEventHandlers()}
		
JS;
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5Input::buildJsProperties()
     */
    public function buildJsProperties()
    {
        $options = parent::buildJsProperties() . <<<JS
        
			valueFormat: {$this->buildJsValueFormat()},
            displayFormat: {$this->buildJsDisplayFormat()},
            
JS;
        return $options;
    }
    
    protected function buildJsPropertyValue() {
        return <<<JS
        
            value: {path: 'view>/{$this->getId()}/dateValue', type: 'DateType'},
            
JS;
    }
    
    /**
     *
     * @return string
     */
    protected function buildJsValueFormat() : string
    {
        return '"yyyy-MM-dd HH:mm:ss"';
    }
    
    /**
     *
     * @return string
     */
    protected function buildJsDisplayFormat() : string
    {
        return '""';
    }
    
}