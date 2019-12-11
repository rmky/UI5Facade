<?php
namespace exface\UI5Facade\Facades\Elements;

use exface\Core\Widgets\InputTime;
use exface\UI5Facade\Facades\Interfaces\UI5ControllerInterface;
use exface\UI5Facade\Facades\Interfaces\UI5BindingFormatterInterface;

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
        $this->registerConditionalBehaviors();
        $this->registerOnChangeValidation();
        
        $this->registerExternalModules($this->getController());
        
        $onChangeScript = <<<JS

            var oTimePicker = oEvent.getSource();
            
			var sValue = oEvent.getParameter('value');
			var sValParsed = exfTools.time.parse(sValue);
			if (sValue !== sValParsed) {
				oTimePicker.setValue(sValParsed);
			}

JS;
        
        $this->addOnChangeScript($onChangeScript);       
        return $this->buildJsLabelWrapper($this->buildJsConstructorForMainControl($oControllerJs));
    }
    
    /**
     *
     * @return UI5BindingFormatterInterface
     */
    protected function getDateBindingFormatter() : UI5BindingFormatterInterface
    {
        return $this->getFacade()->getDataTypeFormatterForUI5Bindings($this->getWidget()->getValueDataType());
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5AbstractElement::registerExternalModules()
     */
    public function registerExternalModules(UI5ControllerInterface $controller) : UI5AbstractElement
    {
        $this->getDateBindingFormatter()->registerExternalModules($controller);
        return $this;
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
		}).setMaskMode('Off')
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
}