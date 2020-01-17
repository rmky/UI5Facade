<?php
namespace exface\UI5Facade\Facades\Elements;

use exface\Core\Interfaces\WidgetInterface;
use exface\Core\Interfaces\Widgets\iFillEntireContainer;
use exface\Core\Widgets\DataLookupDialog;
use exface\Core\Widgets\DataTable;
use exface\Core\Widgets\DataTableResponsive;
use exface\Core\Interfaces\Widgets\iHaveHeader;
use exface\Core\Interfaces\Widgets\iSupportMultiSelect;
use exface\Core\Factories\ActionFactory;
use exface\Core\Actions\UpdateData;

/**
 * @method DataLookupDialog getWidget()
 * @author tmc
 *
 */
class UI5DataLookupDialog extends UI5Dialog 
{
    protected function init()
    {
        parent::init();
        $table = $this->getWidget()->getDataWidget();
        $table->setHideCaption(true);
        if ($table instanceof iHaveHeader) {
            $this->getWidget()->getDataWidget()->setHideHeader(false);
        }
        return;
    }

    protected function buildJsDialog()
    {
        $widget = $this->getWidget();
        $icon = $widget->getIcon() ? 'icon: "' . $this->getIconSrc($widget->getIcon()) . '",' : '';
                    
            // If the dialog requires a prefill, we need to load the data once the dialog is opened.
            if ($this->needsPrefill()) {
                $prefill = <<<JS
                
            beforeOpen: function(oEvent) {
                var oDialog = oEvent.getSource();
                var oView = {$this->getController()->getView()->buildJsViewGetter($this)};
                {$this->buildJsPrefillLoader('oView')}
            },
            
JS;
            } else {
                $prefill = '';
            }
            
            // Finally, instantiate the dialog
            return <<<JS
            
        new sap.m.Dialog("{$this->getId()}", {
			{$icon}
            contentHeight: "80%",
            contentWidth: "70%",
            stretch: jQuery.device.is.phone,
            title: "{$this->getCaption()}",
			buttons : [ {$this->buildJsDialogButtons()} ],
			content : [ {$this->buildJsDialogContent()} ],
            {$prefill}
		});
JS;
    }
    
    protected function buildJsDialogContent() : string
    {
        return <<<JS
new sap.ui.layout.Splitter({
                    orientation: "Vertical",
                    height: "100%",
                    contentAreas: [
                        {$this->buildJsDialogContentChildren()},
                        {$this->buildJsDialogContentSelectedItems()}
                    ]
                    })
JS;
    }
    
    protected function buildJsDialogContentChildren() : string
    {
        $widget = $this->getWidget();
        $visibleChildren = $widget->getWidgets(function(WidgetInterface $widget){
            return $widget->isHidden() === false;
        });
            if (count($visibleChildren) === 1 && $visibleChildren[0] instanceof iFillEntireContainer) {
                $children = $this->buildJsChildrenConstructors(false);
            } else {
                $children = $this->buildJsLayoutForm($this->buildJsChildrenConstructors(true));
            }
        return $children;
    }
    
    protected function buildJsDialogContentSelectedItems() : string
    {
        if ($this->getWidget()->getMultiSelect() !== true){
            return '';
        }
        
        $table = $this->getWidget()->getDataWidget();
        $splitterId = $this->getDialogContentPanelSplitterLayoutId();
        
        return <<<JS
            new sap.m.Panel( "{$this->getDialogContentPanelId()}",
                {
                    expandable: true,
                    expanded: true,
                    height: "100%",
                    headerToolbar: [
                        new sap.m.OverflowToolbar({
                            content: [
                                new sap.m.Text({
                                    text: "Selected Items"
                                })
                            ]
                        })
                    ],
                    content: [
                        new sap.m.HBox({
                            width: "100%",
                            alignItems: "Center",
                            fitContainer: true,
                            items: [
                                new sap.m.Tokenizer("{$this->getDialogContentPanelTokenizerId()}",
                                    {
                                    width: "100%",
                                    layoutData: [
                                        new sap.m.FlexItemData({
                                            styleClass: "dataLookupDialogSelectedElementsHBoxFlexItem"
                                        })
                                    ]
                                }).addStyleClass('dataLookupDialogSelectedElementsTokenizer'),
                                new sap.m.Button("{$this->getDialogContentPanelTokenizerClearButtonId()}",
                                {
                                    icon: "sap-icon://sys-cancel",
                                    type: "Transparent",
                                    enabled: false,
                                    press: function(){
                                        var oTokenizer = sap.ui.getCore().byId("{$this->getDialogContentPanelTokenizerId()}");
                                        var aTokens = oTokenizer.getTokens();
                                        if (aTokens.length != 0){
                                            // remove all tokens by clearing the selection in the table, the tokens are assigned to
                                            var oTable = sap.ui.getCore().byId(aTokens[0].data().tableId);
                                            oTable.removeSelections().fireSelectionChange();
                                        }
                                    }
                                })
                            ]
                        })
                    ],
                    layoutData: [
                        new sap.ui.layout.SplitterLayoutData("{$splitterId}",
                            {
                                size: "5rem",
                                resizable: false
                            })
                    ]
                }).attachExpand(function(){
                                    if (this.getExpanded() == true){
                                        sap.ui.getCore().byId('{$splitterId}').setSize("5rem");
                                    } else {
                                        sap.ui.getCore().byId('{$splitterId}').setSize("2.1rem");
                                    }
                                })
JS;
    }
    
    protected function getDialogContentPanelId() : string
    {
        return $this->getWidget()->getID() . '_' . 'SelectedItemsPanel';
    }
    
    protected function getDialogContentPanelSplitterLayoutId() : string
    {
        return $this->getDialogContentPanelId() . '_' . 'SplitterLayoutData';
    }
    
    protected function getDialogContentPanelTokenizerId() : string
    {
        return $this->getDialogContentPanelId() . '_' . 'Tokenizer';
    }
    
    protected function getDialogContentPanelTokenizerClearButtonId() : string
    {
        return $this->getDialogContentPanelId() . '_' . 'TokenizerClearButton';
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5Container::buildJsChildrenConstructors()
     */
    public function buildJsChildrenConstructors(bool $useFormLayout = true) : string
    {
        $js = '';
        $firstVisibleWidget = null;
        foreach ($this->getWidget()->getWidgets() as $widget) {
            
            if ($widget instanceof iSupportMultiSelect && $this->getWidget()->getMultiSelect() === true){
                $this->getFacade()->getElement($widget)->addOnChangeScript($this->buildJsSelectionChangeHandler());
            }
            /*if ($widget instanceof DataTable){
                $element = $this->getFacade()->getElement($widget);
                $dynamicPageFixes = <<<JS
                
                        sap.ui.getCore().byId('{$element->getIdOfDynamicPage()}').setHeaderExpanded(false);
                        
                        // Redraw the table to make it fit the page height agian. Otherwise it would be
                        // of default height after dialogs close, etc.
                        sap.ui.getCore().byId('{$element->getId()}').invalidate();
JS;
                $element->addOnLoadScript($dynamicPageFixes);
            }*/
            
            if ($widget->isHidden() === false) {
                // Larger widgets need a Title before them to make SimpleForm generate a new FormContainer
                if ($firstVisibleWidget !== null && $useFormLayout === true && (($widget instanceof iFillEntireContainer) || $widget->getWidth()->isMax())) {
                    $js .= ($js ? ",\n" : '') . $this->buildJsFormRowDelimiter();
                }
                $firstVisibleWidget = $widget;
            }
            $tableElement = $this->getFacade()->getElement($widget);
            $tableElement->setDynamicPageHeaderCollapsed(true);
            $tableElement->setDynamicPageShowToolbar(true);
            $js .= ($js ? ",\n" : '') . $tableElement->buildJsConstructor();
        }
        
        return $js;
    }
    
    /**
     * This function generates the JS-code for the handler of the event onChange on the LookupDialog's `DataTable`.
     * It is responsible for creating and removing the dialogs tokenizer, and 
     *  
     * @return string
     */
    protected function buildJsSelectionChangeHandler() : string
    {
        $table = $this->getWidget()->getDataWidget();
        $tableElement = $this->getFacade()->getElement($table);
        
        $attributeAlias = $table->getMetaObject()->getUidAttributeAlias();
        
        if ($table->getMetaObject()->hasLabelAttribute() === true){
            if ($labelCol = $table->getColumnByAttributeAlias($table->getMetaObject()->getLabelAttributeAlias())) {
                $labelColName = $labelCol->getDataColumnName();
            } else {
                $labelColName = $table->getMetaObject()->getLabelAttributeAlias();
            }
        } else {
            $labelColName = $attributeAlias;
        }
        
        $dataGetterJs = $tableElement->buildJsDataGetter(ActionFactory::createFromString($this->getWorkbench(), UpdateData::class));
        
        return <<<JS
            var oTokenizer =  sap.ui.getCore().byId("{$this->getDialogContentPanelTokenizerId()}");
            if (! oTokenizer) {
                return;
            }


			oTokenizer.destroyTokens();
            

            var aSelection = {$dataGetterJs};
            var aRows =  aSelection.rows;

            var oTokenizerClearButton = sap.ui.getCore().byId("{$this->getDialogContentPanelTokenizerClearButtonId()}");
            if (aRows.length == 0){
                oTokenizerClearButton.setEnabled(false);                
                return;
            }
            
            oTokenizerClearButton.setEnabled(true);

            //get selected items from table
            var aSelectedIds = {$tableElement->buildJsValueGetter($attributeAlias)};
            var aSelectedLables = {$tableElement->buildJsValueGetter("{$labelColName}")};
            aRows.forEach(function(oRow){
      
                 oTokenizer.addToken(
    				 new sap.m.Token({
                         customData: [
                            {
                                Type: "sap.ui.core.CustomData",
                                key: "tableId",
                                value: oEvent.getSource().getId()
                            }
                         ],
    				 	 key: oRow.{$attributeAlias},
    				 	 text: oRow.{$labelColName},        				 	 
                         delete: function(oEvent){
                              var sKey = this.getKey();
                              {$tableElement->buildJsSelectRowByValue($table->getUidColumn(), 'sKey', '', 'rowIdx', true)}
                              sap.ui.getCore().byId(this.data().tableId).fireSelectionChange();
                        }
    				 })
    			);

            });
JS;
    }
    
    protected function buildJsDataLoaderOnLoaded(string $oModelJs = 'oModel') : string
    {
        if ($this->isWrappedInDynamicPage()) {

        }
        
        return <<<JS
        
            oTable.getModel("{$this->getModelNameForConfigurator()}").setProperty('/filterDescription', {$this->getController()->buildJsMethodCallFromController('onUpdateFilterSummary', $this, '', 'oController')});
            {$dynamicPageFixes}
            
JS;
    }
}