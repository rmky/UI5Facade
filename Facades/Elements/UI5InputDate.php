<?php
namespace exface\UI5Facade\Facades\Elements;

use exface\Core\Widgets\InputDate;
use exface\Core\DataTypes\DateDataType;
use exface\Core\DataTypes\TimeDataType;
use exface\Core\DataTypes\TimestampDataType;
use exface\Core\Factories\DataTypeFactory;
use exface\Core\Facades\AbstractAjaxFacade\Elements\JqueryInputDateTrait;

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
    use JqueryInputDateTrait;
    
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
        $controller->addExternalModule('libs.exface.ui5Custom.dataTypes.MomentDateType', $this->getFacade()->buildUrlToSource("LIBS.UI5CUSTOM.DATETYPE.JS"));
        
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
		})
        {$this->buildJsInternalModelInit()}
        {$this->buildJsPseudoEventHandlers()}
		
JS;
    }
    
    protected function buildJsInternalModelInit() : string
    {
        if ($this->hasInternalDateModel() === true) {
            $prop = $this->getWidget()->getDataColumnName();
            $defaultValue = $this->getWidget()->getValueWithDefaults();
            if ($defaultValue) {
                $initialModel = "{ $prop : {$this->getDateFormatter()->buildJsFormatParserToJsDate("'$defaultValue'")} }";
            }
            return ".setModel(new sap.ui.model.json.JSONModel($initialModel), '{$this->getInternalDateModelName()}')";
        } else {
            return '';
        }
        
        
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
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5Value::buildJsValueBindingOptions()
     */
    public function buildJsValueBindingOptions()
    {
        return <<<JS

                type: 'exface.ui5Custom.dataTypes.MomentDateType',
                {$this->buildJsValueBindingFormatOptions()}
JS;
    }
    
    /**
     *
     * @return string
     */
    protected function getFormatToParseTo() : string
    {
        $type = $this->getWidget()->getValueDataType();
        if (! $type instanceof DateDataType && ! $type instanceof TimeDataType && ! $type instanceof TimestampDataType) {
            $type = DataTypeFactory::createFromPrototype($this->getWorkbench(), DateDataType::class);
        }
        $format = $type->getFormatToParseTo();
        return $format;
    }
    
   /**
    * 
    * @return string
    */
    protected function buildJsValueBindingFormatOptions() : string
    {
        return <<<JS
        
                    formatOptions: {
                        dateFormat: '{$this->getFormatToParseTo()}'
                    },
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
    
    /**
     *
     * @return boolean
     */
    protected function isValueBoundToModel()
    {        
        return true;
    }
    
    public function getValueBindingPath() : string
    {
        if ($this->hasInternalDateModel() === true) {
            return $this->getInternalDateModelName() . '>'  . $this->getValueBindingPrefix() . $this->getWidget()->getDataColumnName();
        } else {
            return parent::getValueBindingPath();
        }
    }
        
    protected function hasInternalDateModel() : bool
    {
        return parent::isValueBoundToModel() === false;
    }
    
    protected function getInternalDateModelName() : string
    {
        return 'internalDate';
    }
}