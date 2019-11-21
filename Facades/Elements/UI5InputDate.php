<?php
namespace exface\UI5Facade\Facades\Elements;

use exface\Core\Widgets\InputDate;

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
        $this->registerConditionalBehaviors();
        $this->registerOnChangeValidation();
        $this->getController()->addExternalModule('libs.moment', 'exface/vendor/npm-asset/moment/min/moment.min.js');
        $this->getController()->addExternalModule('libs.exfTools', 'exface/vendor/exface/Core/Facades/AbstractAjaxFacade/js/exfTools.js');
        $this->getController()->addExternalModule('libs.DateType', 'exface/vendor/exface/UI5Facade/Facades/js/ui5Custom/DateType.js');
        $js = <<<JS

            var oModel = new sap.ui.model.json.JSONModel();
			oModel.setData({
				dateValue: '2020-11-14',
			});

JS;
        $this->getController()->addOnInitScript($js);
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

            value: {path: '/dateValue', type: 'DateType'},            
			valueFormat: {$this->buildJsValueFormat()},
            displayFormat: {$this->buildJsDisplayFormat()},

JS;
            return $options;
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