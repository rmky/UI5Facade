<?php
namespace exface\UI5Facade\Facades\Elements;

use exface\Core\Widgets\WizardButton;

/**
 *
 * @author Andrej Kabachnik
 *        
 * @method WizardButton getWidget()
 *        
 */
class UI5WizardButton extends UI5Button
{
    /**
     * A WizardButton validates it's step, performs it's action and navigates to another step:
     * 
     * 1) validate the button's wizard step first if we are going to leave it
     * 2) perform the regular button's action
     * 3) navigate to the target wizard step
     * 
     * Note, that the action JS will perform step validation in any case - even if the
     * button does not navigate to another step.
     * 
     * {@inheritdoc}
     * @see UI5Button::buildJsClickFunction()
     */
    public function buildJsClickFunction()
    {
        $widget = $this->getWidget();
        $tabsElement = $this->getFacade()->getElement($widget->getWizardStep()->getParent());
        
        $actionJs = parent::buildJsClickFunction();
        
        $goToStepJs = '';
        $validateJs = '';
        if (($nextStep = $widget->getGoToStepIndex()) !== null) {
            $stepElement = $this->getFacade()->getElement($widget->getWizardStep());
            
            if ($widget->getValidateCurrentStep() === true) {
                $validateJs = <<<JS
            
                    if({$stepElement->buildJsValidator()} === false) {
                        {$stepElement->buildJsValidationError()}
                        return;
                    }
                    
JS;
            }
                        $goToStepJs = <<<JS
                    var wizard = sap.ui.getCore().byId('{$tabsElement->getId()}');

                    if ($nextStep < wizard.getProgress()){
                        var destStep = wizard.getSteps()[{$nextStep}];
                        wizard.goToStep(destStep);
                    } else {
                        while (wizard.getProgress() <= $nextStep){
                            wizard.nextStep();
                        }
                    }
                    
JS;
                        
        }
        
        return <<<JS
        
					var jqTabs = $('#{$tabsElement->getId()}');
                    {$validateJs}
                    {$actionJs}
                    {$goToStepJs}
                    
JS;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildCssElementClass()
     */
    public function buildCssElementClass(){
        return 'sapMWizardNextButtonVisible';
    }
   
}