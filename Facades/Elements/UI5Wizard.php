<?php
namespace exface\UI5Facade\Facades\Elements;

use exface\Core\Widgets\Wizard;
use exface\Core\Factories\WidgetFactory;
use exface\Core\Widgets\Toolbar;

/**
 * Creates an UI5Wizard for step-by-step input of data. 
 * 
 * @method Wizard getWidget()
 * @author tmc
 *
 */
class UI5Wizard extends UI5Container
{
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5Container::buildJsConstructor()
     */
    public function buildJsConstructor($oControllerJs = 'oController') : string
    {   
        $wizard = $this->buildJsConstructorForWizard($oControllerJs);
        
        if ($this->hasPageWrapper() === true) {
            return $this->buildJsPageWrapper($wizard);
        }
        
        return $wizard;
    }
    
    /**
     * This function creates and returns the JS-code for the UI5 `sap.m.Wizard`.
     * This includes an instance of `sap.m.Page` the wizard is wrapped in and an
     * `sap.m.OverflowToolbar` for some buttons which may be used in the wizard.
     * The function then calls the constructor for the `WizardPages`, which are packed
     * into the wizards content.
     * 
     * @param string $oControllerJs
     * @return string
     */
    protected function buildJsConstructorForWizard(string $oControllerJs) : string
    {           
        $this->checkWizardStepIconAttributes();
        
        $leftButtons = '';
        $rightButtons = '';
        $wizardTbEl = $this->getFacade()->getElement($this->getWidget()->getToolbarMain());
        $leftButtons .= $wizardTbEl->buildJsConstructorsForLeftButtons();
        $rightButtons .= $wizardTbEl->buildJsConstructorsForRightButtons();
        

                 
        $toolbar = <<<JS
			new sap.m.OverflowToolbar({
				content: [
                    {$leftButtons}
                    new sap.m.ToolbarSpacer(),
                    {$rightButtons}
				]
			})
JS;
        $toolbar = $wizardTbEl->buildJsConstructor();
        
        $title = $this->getCaption() ? "title: '{$this->getCaption()}'," : 'showHeader: false,';
        
        return <<<JS
    new sap.m.Page({
            {$title}
            content: [
                new sap.m.Wizard("{$this->getId()}", {
                    
                    showNextButton: false,
                    //stepActivate: ".wizardStepChangeHandler"
                    
                    steps: [
                        {$this->buildJsChildrenConstructors()}
                    ]
                })
            ],
            footer: [
                {$toolbar}
            ]
        })
JS;
    }
    
    /**
     *
     * @param Toolbar $tb
     * @return string
     */
    protected function buildCssStepToolbarClass(Toolbar $tb) : string
    {
        return 'exf-step-toolbar-' . $tb->getId();
    }
 
    /**
     * This function checks the UXON icon parameters of all the `WizardPages` and throws an exeption
     * if some, but not all of the `WizardPages` have an icon parameter assigned. 
     * This is checked to prevent the user not recognizing UI5's behaviour with icons on `sap.m.WizardPage`'s,
     * which are not being rendered if not all instances of the `WizardPages` got an icon parameter.
     * 
     * @return UI5Wizard
     */
    protected function checkWizardStepIconAttributes() : UI5Wizard
    {
        //count WizardSteps with icons 
        $wizardSteps = $this->getWidget()->getSteps();
        $iconCounter = 0;
        foreach ($wizardSteps as $step) {
            if ($step->getIcon() != null){
                $iconCounter++;
            }
        }
        //throw exception if there are not enough icons
        if ($iconCounter != sizeof($wizardSteps) && $iconCounter != 0){
            $this->getWorkbench()->getLogger()->logException($e = new \InvalidArgumentException('Exception: Some, but not every instance of `WizardStep` has an `icon` parameter!'));
            throw ($e);
        }
        
        return $this;
    }
    
}

?>