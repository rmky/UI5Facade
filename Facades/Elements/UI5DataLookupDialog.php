<?php
namespace exface\UI5Facade\Facades\Elements;

use exface\Core\Interfaces\WidgetInterface;
use exface\Core\Interfaces\Widgets\iFillEntireContainer;
use exface\Core\Widgets\DataLookupDialog;
use exface\Core\Widgets\DataTable;

/**
 * @method DataLookupDialog getWidget()
 * @author tmc
 *
 */
class UI5DataLookupDialog extends UI5Dialog 
{

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
        
        $splitterId = $this->getDialogContentPanelSplitterLayoutId();
        
        return <<<JS
            new sap.m.Panel( "{$this->getDialogContentPanelId()}",
                {
                    expandable: true,
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
                                new sap.m.Button({
                                    icon: "sap-icon://sys-cancel",
                                    press: function(){
                                        var oTokenizer = sap.ui.getCore().byId("{$this->getDialogContentPanelTokenizerId()}");
                                        var aTokens = oTokenizer.getTokens();
                                        if (aTokens.length != 0){
                                            // remove all tokens by clearing the selection in the table, the tokens are assigned to
                                            var oTable = sap.ui.getCore().byId(aTokens[0].data().tableId);
                                            var iRowCount = oTable.getRows().length;
                                            oTable.removeSelectionInterval(0, (iRowCount - 1));
                                        }
                                    }
                                })
                            ]
                        })
                    ],
                    layoutData: [
                        new sap.ui.layout.SplitterLayoutData("{$splitterId}",
                            {
                                size: "2.1rem",
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
            
            if ($widget instanceof DataTable && $this->getWidget()->getMultiSelect() === true){
                
                $this->getFacade()->getElement($widget)->addOnChangeScript($this->buildJsSelectionChangeHandler());
            }
            
            if ($widget->isHidden() === false) {
                // Larger widgets need a Title before them to make SimpleForm generate a new FormContainer
                if ($firstVisibleWidget !== null && $useFormLayout === true && (($widget instanceof iFillEntireContainer) || $widget->getWidth()->isMax())) {
                    $js .= ($js ? ",\n" : '') . $this->buildJsFormRowDelimiter();
                }
                $firstVisibleWidget = $widget;
            }
            $js .= ($js ? ",\n" : '') . $this->getFacade()->getElement($widget)->buildJsConstructor();
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
        return <<<JS
            var oTokenizer =  sap.ui.getCore().byId("{$this->getDialogContentPanelTokenizerId()}");
            oTokenizer.destroyTokens();
			
            //get selected items from table
            var idx;
            var aRows = oEvent.getSource().getRows();
            var oSelectedItem;
            oEvent.getSource().getSelectedIndices().forEach(function(idx){
                 if (idx < aRows.length){
                     oSelectedItem = aRows[idx];
                     oTokenizer.addToken(
        				 new sap.m.Token({
                             customData: [
                                {
                                    Type: "sap.ui.core.CustomData",
                                    key: "tableId",
                                    value: oEvent.getSource().getId()
                                }
                             ],
        				 	 key: idx,
        				 	 text: oSelectedItem.getCells()[0].getText(),        				 	 
                             delete: function(oEvent){
        	                      oEvent.getSource().setSelected(false);
                                  var idx = parseInt(this.getKey());
                                  sap.ui.getCore().byId(this.data().tableId).removeSelectionInterval(idx, idx);
                             }
        				 })
        			);
                 }
            });
JS;
    }
}