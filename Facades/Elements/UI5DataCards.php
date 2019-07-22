<?php
namespace exface\UI5Facade\Facades\Elements;

class UI5DataCards extends UI5DataTable
{
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5DataTable::buildJsConstructorForTable()
     */
    protected function buildJsConstructorForControl(string $oControllerJs = 'oController') : string
    {
        $mode = $this->getWidget()->getMultiSelect() ? 'sap.m.ListMode.MultiSelect' : 'sap.m.ListMode.SingleSelectMaster';
        return <<<JS

        new sap.m.VBox({
            items: [
                new sap.f.GridList("{$this->getId()}", {
                    mode: {$mode},
                    noDataText: "{$this->translate('WIDGET.DATATABLE.NO_DATA_HINT')}",
            		itemPress: {$this->getController()->buildJsEventHandler($this, 'change')},
                    headerToolbar: [
                        {$this->buildJsToolbar()}
            		],
            		items: {
            			path: '/rows',
                        {$this->buildJsBindingOptionsForGrouping()}
                        template: new sap.m.CustomListItem({
                            type: "Active",
                            content: [
                                {$this->buildJsConstructorForCard()}
                            ]
                        }),
            		}
                })
                .setModel(new sap.ui.model.json.JSONModel())
                {$this->buildJsClickListeners('oController')}
                {$this->buildJsPseudoEventHandlers()}
                ,
                {$this->buildJsConstructorForMTableFooter()}
            ]
        }) 

JS;
    }
                
    protected function buildJsConstructorForCard() : string
    {
        return <<<JS

                                new sap.m.VBox({
                                    layoutData: new sap.m.FlexItemData({
                                        growFactor: 1,
                                        shrinkFactor: 0
                                    }),
                                    items: [
                                        {$this->buildJsCellsForMTable()}
                                    ]
                                }).addStyleClass("sapUiSmallMargin")

JS;
    }
           
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5DataTable::buildJsCellsForMTable()
     */
    protected function buildJsCellsForMTable()
    {
        $cells = '';
        foreach ($this->getWidget()->getColumns() as $column) {
            $class = '';
            if ($column->getVisibility() === EXF_WIDGET_VISIBILITY_PROMOTED) {
                $class .= ' exf-promoted';
            }
            $cells .= ($cells ? ", " : '') . $this->getFacade()->getElement($column)->buildJsConstructorForCell(null, false) . ($class !== '' ? '.addStyleClass("' . $class . '")' : '');
        }
        
        return $cells;
    }
    
    protected function isMList() : bool
    {
        return true;
    }
        
    protected function isMTable() : bool
    {
        return false;
    }
    
    protected function isUiTable() : bool
    {
        return false;
    }
}