<?php
namespace exface\OpenUI5Template\Templates\Elements\Traits;

use exface\Core\Widgets\Data;
use exface\Core\Widgets\DataTable;
use exface\OpenUI5Template\Templates\Interfaces\ui5ControllerInterface;
use exface\OpenUI5Template\Templates\Elements\ui5AbstractElement;

/**
 * This trait helps wrap thrid-party data widgets (like charts, image galleries, etc.) in 
 * UI5 panels with standard toolbars, a configurator dialog, etc. 
 * 
 * @author Andrej Kabachnik
 * 
 * @method Data getWidget()
 *
 */
trait ui5DataElementTrait {
    
    /**
     * Wraps the given content in a sap.m.Panel with data-specific toolbars (configurator button, etc.).
     * 
     * This is usefull for third-party widget libraries, that need this wrapper to look like UI5 controls.
     * 
     * @param string $contentConstructorsJs
     * @param string $oControllerJs
     * @param string $caption
     * 
     * @return string
     */
    protected function buildJsPanelWrapper(string $contentConstructorsJs, string $oControllerJs = 'oController', string $caption = null)  : string
    {
        $caption = $caption ?? $this->getCaption();
        
        return <<<JS
        new sap.m.Panel({
            height: "100%",
            headerToolbar: [
                {$this->buildJsToolbar($oControllerJs)}.addStyleClass("sapMTBHeader-CTX")
            ],
            content: [
                {$contentConstructorsJs}
            ]
        })
        
JS;
    }
           
    /**
     * 
     * @return bool
     */
    protected function hasPaginator() : bool
    {
        return ($this->getWidget() instanceof Data) && $this->getWidget()->isPaged();
    }
    
    /**
     * Returns the constructor for the table's main toolbar (OverflowToolbar).
     *
     * The toolbar contains the paginator, all the action buttons, the quick search
     * and the button for the personalization dialog as well as the P13nDialog itself.
     *
     * The P13nDialog is appended to the toolbar wrapped in an invisible container in
     * order not to affect the overflow behavior. The dialog must be included in the
     * toolbar to ensure it is destroyed with the toolbar and does not become an
     * orphan (e.g. when the view containing the table is destroyed).
     * 
     * @param string $oControllerJsVar
     * @param string $leftExtras
     * @param string $rightExtras
     *
     * @return string
     */
    protected function buildJsToolbar($oControllerJsVar = 'oController', string $leftExtras = null, string $rightExtras = null)
    {
        $heading = $this->getCaption() . ($this->hasPaginator() ? ': ' : '');
        $heading = $this->isWrappedInDynamicPage() ? '' : 'new sap.m.Label({text: "' . $heading . '"}),';
        
        $leftExtras = $leftExtras === null ? '' : rtrim($leftExtras, ", ") . ',';
        $rightExtras = $rightExtras === null ? '' : rtrim($leftExtras, ", ") . ',';
        
        $toolbar = <<<JS
			new sap.m.OverflowToolbar({
                design: "Transparent",
				content: [
					{$heading}
                    {$leftExtras}
			        new sap.m.ToolbarSpacer(),
                    {$this->buildJsButtonsConstructors()}
                    {$rightExtras}
                    {$this->buildJsQuickSearchConstructor()}
					{$this->buildJsConfiguratorButtonConstructor()}
				]
			})
JS;
         
	   return $toolbar;
    }
    
    /**
     * Returns the text to be shown a table title
     *
     * @return string
     */
    public function getCaption() : string
    {
        $widget = $this->getWidget();
        return $widget->getCaption() ? $widget->getCaption() : $widget->getMetaObject()->getName();
    }
    
    /**
     * Returns TRUE if the controll will be wrapped in a sap.f.DynamicPage to create a Fiori ListReport or similar floorplan
     *
     * @return boolean
     */
    protected function isWrappedInDynamicPage()
    {
        return false;
    }

    /**
     * 
     * @return bool
     */
    protected function hasActionButtons() : bool
    {
        return $this->getWidget()->hasButtons();
    }
    
    /**
     * Returns a comma separated list of javascript constructors for all buttons of the table.
     *
     * Must end with a comma unless it is an empty string!
     * 
     * @return string
     */
    protected function buildJsButtonsConstructors()
    {
        if ($this->hasActionButtons() === false) {
            return '';
        }
        
        $widget = $this->getWidget();
        $buttons = '';
        foreach ($widget->getToolbars() as $toolbar) {
            if ($toolbar->getIncludeSearchActions()){
                $search_button_group = $toolbar->getButtonGroupForSearchActions();
            } else {
                $search_button_group = null;
            }
            foreach ($widget->getToolbarMain()->getButtonGroups() as $btn_group) {
                if ($btn_group === $search_button_group){
                    continue;
                }
                $buttons .= ($buttons && $btn_group->getVisibility() > EXF_WIDGET_VISIBILITY_OPTIONAL ? ",\n new sap.m.ToolbarSeparator()" : '');
                foreach ($btn_group->getButtons() as $btn) {
                    $buttons .= $this->getTemplate()->getElement($btn)->buildJsConstructor() . ",\n";
                }
            }
        }
        return $buttons;
    }
    
    /**
     * Returns the JS constructor for the configurator button.
     * 
     * Must end with a comma unless it is an empty string!
     * 
     * @param string $oControllerJs
     * @return string
     */
    protected function buildJsConfiguratorButtonConstructor(string $oControllerJs = 'oController', string $buttonType = 'Default') : string
    {
        return <<<JS
        
                    new sap.m.OverflowToolbarButton({
                        type: sap.m.ButtonType.{$buttonType},
                        icon: "sap-icon://drop-down-list",
                        text: "{$this->translate('WIDGET.DATATABLE.SETTINGS_DIALOG.TITLE')}",
                        tooltip: "{$this->translate('WIDGET.DATATABLE.SETTINGS_DIALOG.TITLE')}",
                        layoutData: new sap.m.OverflowToolbarLayoutData({priority: "High"}),
                        press: function() {
                			{$this->getController()->buildJsDependentControlSelector('oConfigurator', $this, $oControllerJs)}.open();
                		}
                    }),
                    
JS;
    }
    
    /**
     * Initializes the configurator control (sap.m.P13nDialog or similar) and makes it available in the given controller.
     * 
     * Use buildJsConfiguratorOpen() to show the configurator dialog. 
     * 
     * @param ui5ControllerInterface $controller
     * 
     * @return ui5AbstractElement
     */
    protected function initConfiguratorControl(ui5ControllerInterface $controller) : ui5AbstractElement
    {
        $controller->addDependentControl('oConfigurator', $this, $this->getTemplate()->getElement($this->getWidget()->getConfiguratorWidget()));
        return $this;
    }
    
    /**
     *
     * @return bool
     */
    protected function hasQuickSearch() : bool
    {
        return $this->getWidget() instanceof DataTable;
    }
    
    /**
     * Returns the constructor for the sap.m.SearchField for toolbar quick search.
     * 
     * Must end with a comma unless it is an empty string!
     * 
     * @return string
     */
    protected function buildJsQuickSearchConstructor() : string
    {
        return '';
    }
    
    /**
     * Returns a JS snippet, that performs the given $onFailJs if required filters are missing.
     * 
     * @param string $onFailJs
     * @return string
     */
    protected function buildJsCheckRequiredFilters(string $onFailJs) : string
    {
        $configurator_element = $this->getTemplate()->getElement($this->getWidget()->getConfiguratorWidget());
        return <<<JS

                try {
                    if (! {$configurator_element->buildJsValidator()}) {
                        {$onFailJs};
                    }
                } catch (e) {
                    console.warn('Could not check filter validity - ', e);
                }      
                
JS;
    }
                        
    abstract protected function buildJsDataResetter() : string;
}