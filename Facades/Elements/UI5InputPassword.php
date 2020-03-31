<?php
namespace exface\UI5Facade\Facades\Elements;

use exface\Core\Widgets\InputPassword;
use exface\Core\Factories\WidgetFactory;
use exface\Core\Interfaces\WidgetInterface;

/**
 * Renders a UI5 textbox and changes the input type to password.
 *
 * @method InputPassword getWidget()
 *
 * @author Andrej Kabachnik
 *
 */
class UI5InputPassword extends UI5Input
{
    
    private $conformationInputWidget = null;
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5Input::buildJsConstructorForMainControl()
     */
    public function buildJsConstructorForMainControl($oControllerJs = 'oController')
    {
        $widget = $this->getWidget();
        if ($widget->getShowSecondInputForConfirmation() === false) {
            return parent::buildJsConstructorForMainControl($oControllerJs);
        }
        
        $confirmInputElement = $this->getFacade()->getElement($this->getConfirmationInput());
        $confirmInputElement->setController($this->getController());
        $confirmInputElement->setValueBindingDisabled(true);
        $onChangeEnableDisableScript = <<<JS
        
                    if ({$this->buildJsValueGetter()} === '') {
                        {$confirmInputElement->buildJsDisabler()}
                        {$confirmInputElement->buildJsValueSetter('')}
                    } else {
                        {$confirmInputElement->buildJsEnabler()}
                    }
JS;
        $this->addOnChangeScript($onChangeEnableDisableScript);
        
        $onConfirmInputChangeScript = <<<JS
        
            sap.ui.getCore().byId('{$this->getId()}').setValueStateText('{$this->getValidationErrorText()}');
            if(! {$this->buildJsValidator()} ) {
                {$this->buildJsValidationError()};
            } else {
                sap.ui.getCore().byId('{$this->getId()}').setValueState('None');
            }
    
JS;
        $confirmInputElement->addOnChangeScript($onConfirmInputChangeScript);
        $output = parent::buildJsConstructorForMainControl($oControllerJs) . ',';
        $output .= $confirmInputElement->buildJsLabelWrapper($confirmInputElement->buildJsConstructorForMainControl($oControllerJs));
        return $output;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildJsValidator()
     */
    public function buildJsValidator()
    {
        if ($this->getWidget()->getShowSecondInputForConfirmation() === true) {
            $confirmInputElement = $this->getFacade()->getElement($this->getConfirmationInput());
            return "({$this->buildJsValueGetter()} === {$confirmInputElement->buildJsValueGetter()})";
        }
        return 'true';
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5Input::buildJsPropertyType()
     */
    protected function buildJsPropertyType()
    {
        return 'type: sap.m.InputType.Password,';
    }
    
    /**
     * returns the password confirmation input widget
     * 
     * @return WidgetInterface
     */
    protected function getConfirmationInput() : WidgetInterface
    {
        if ($this->conformationInputWidget === null) {
            $widget = $this->getWidget();
            $confirmWidget = WidgetFactory::create($widget->getPage(), $widget->getWidgetType());
            $confirmWidget->setMetaObject($this->getMetaObject());
            $confirmWidget->setCaption($this->translate("WIDGET.INPUTPASSWORD.CONFIRM"));
            //$confirmWidget->setWidth('100%');
            $this->conformationInputWidget = $confirmWidget;
        }
        return $this->conformationInputWidget;
    }
    
    /**
     * 
     */
    protected function getValidationErrorText() : string
    {
        return $this->translate("WIDGET.INPUTPASSWORD.DONT_MATCH");
    }
    
}
?>