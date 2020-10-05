<?php
namespace exface\UI5Facade\Facades\Elements;

/**
 * Generates OpenUI5 inputs
 *
 * @author Andrej Kabachnik
 * 
 * @method \exface\Core\Widgets\Form getWidget()
 *        
 */
class UI5Form extends UI5Panel
{
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5Panel::buildJsConstructor()
     */
    public function buildJsConstructor($oControllerJs = 'oController') : string
    {
        $widget = $this->getWidget();
        
        if ($widget->hasButtons() === true) {
            $this->registerSubmitOnEnter($oControllerJs);
            $toolbar = $this->buildJsFloatingToolbar();
        } else {
            $toolbar = '';
        }
        
        if ($widget->hasParent() === true) {
            return $this->buildJsLayoutForm($this->buildJsChildrenConstructors(true), $toolbar, $this->getId());
        } else {
            $headerContent = $widget->getHideHelpButton() === false ? $this->buildJsHelpButtonConstructor($oControllerJs) : '';
            return $this->buildJsPageWrapper($this->buildJsLayoutForm($this->buildJsChildrenConstructors(true), '', $this->getId()), $toolbar, $headerContent);
        }
    }
    
    /**
     * Adds handlers for the pseudo event `onsapenter` to all input widget of the form if the form
     * has a primary action and the input widget does not have the custom facade option `advance_focus_on_enter`.
     * 
     * @see \exface\Core\Widgets\Form::getButtonWithPrimaryAction()
     * 
     * @param string $oControllerJs
     * @return UI5Form
     */
    protected function registerSubmitOnEnter(string $oControllerJs) : UI5Form
    {
        $widget = $this->getWidget();
        if ($primaryBtn = $widget->getButtonWithPrimaryAction()) {
            $primaryBtnEl = $this->getFacade()->getElement($primaryBtn);
            if (! ($primaryBtnEl instanceof UI5Button)) {
                return $this;
            }
            $primaryActionCall = $primaryBtnEl->buildJsClickEventHandlerCall($oControllerJs);
            if ($primaryActionCall === '') {
                return $this;
            }
            foreach ($widget->getInputWidgets() as $input) {
                $inputEl = $this->getFacade()->getElement($input);
                if (method_exists($inputEl, 'getAdvanceFocusOnEnter') && $inputEl->getAdvanceFocusOnEnter() === true) {
                    continue;
                }
                
                $inputEl->addPseudoEventHandler('onsapenter', $primaryActionCall);
            }
        }
        return $this;
    }
    
    /**
     * Returns the constructor for an OverflowToolbar representing the main toolbar of the dialog.
     *
     * @return string
     */
    protected function buildJsFloatingToolbar()
    {
        return $this->getFacade()->getElement($this->getWidget()->getToolbarMain())->buildJsConstructor();
    }
}