<?php
namespace exface\UI5Facade\Facades\Elements;

use exface\Core\Widgets\WizardStep;

/**
 * A special form to be used within `UI5Wizard` widgets.
 * 
 * method WizardStep getWidget()
 * @author tmc
 *
 */
class UI5WizardStep extends UI5Form
{
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5Form::buildJsConstructor()
     */
    public function buildJsConstructor($oControllerJs = 'oController') : string
    {
        $wizardStep = $this->buildJsWizardStep();
        return $wizardStep;
    }
    
    /**
     * This function creates the code for a `WizardStep` instanciated in the parent `Wizard`,
     * taking care of all its attributes and their settings, whilst the content of the `WizardStep`s is
     * being build with a call of the childrens LayoutConstructor.
     * 
     * @return string
     */
    protected function buildJsWizardStep()
    {
        $widget = $this->getWidget();
        $caption = $this->escapeJsTextValue($this->getCaption());
        $toolbar = $widget->getToolbarMain();
        $icon = $widget->getIcon() && $widget->getShowIcon(true) ? $this->getIconSrc($widget->getIcon()) : '';
        $optional = $widget->isOptional() === true ? "optional: true," : '';
        
        if ($widget->getAutofocusFirstInput() === false) {
            $focusFirstInputJs = 'document.activeElement.blur()';
        } else {
            $firstVisibleInput = null;
            foreach ($widget->getInputWidgets() as $input) {
                if ($input->isHidden() === false) {
                    $firstVisibleInput = $input;
                    break;
                }
            }
            if ($firstVisibleInput !== null) {
                $firstVisibleInputEl = $this->getFacade()->getElement($firstVisibleInput);
                if ($firstVisibleInputEl instanceof UI5Input) {
                    $focusFirstInputJs = $firstVisibleInputEl->buildJsSetFocus() . ';';
                }
            }
        }
        
        $introText = str_replace("\n", '', nl2br($widget->getIntro()));
        
        if ($introText !== null){
            $introText = <<<JS
new sap.m.FormattedText({
                htmlText: "{$introText}" 
            }),
JS;
        }
                
        return <<<JS
    new sap.m.WizardStep("{$this->getId()}", {
        title: "{$caption}",
        icon: "{$icon}",
        {$optional}
        activate: function(oEvent) {
            setTimeout(function(){
                $focusFirstInputJs
            }, 500);
        },
        content: [
            {$introText}
            {$this->buildJsLayoutConstructor()},
            {$this->getFacade()->getElement($toolbar)->buildJsConstructor()}.setStyle('Clear')
        ]
    })
JS;
    }
    
}

?>