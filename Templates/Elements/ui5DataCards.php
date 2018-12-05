<?php
namespace exface\OpenUI5Template\Templates\Elements;

class ui5DataCards extends ui5DataTable
{
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Elements\ui5DataTable::buildJsConstructorForTable()
     */
    protected function buildJsConstructorForTable(string $oControllerJs = 'oController') : string
    {
        return <<<JS

        new sap.m.VBox({
            items: [
                new sap.f.GridList("{$this->getId()}", {
                    headerToolbar: [
                        {$this->buildJsToolbar()}
            		],
            		items: {
            			path: '/data',
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
                .attachItemPress(function(event){
                    {$this->getOnChangeScript()}
                })
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
     * @see \exface\OpenUI5Template\Templates\Elements\ui5DataTable::buildJsCellsForMTable()
     */
    protected function buildJsCellsForMTable()
    {
        $cells = '';
        foreach ($this->getWidget()->getColumns() as $column) {
            $class = '';
            if ($column->getVisibility() === EXF_WIDGET_VISIBILITY_PROMOTED) {
                $class .= ' exf-promoted';
            }
            $cells .= ($cells ? ", " : '') . $this->getTemplate()->getElement($column)->buildJsConstructorForCell(null, false) . ($class !== '' ? '.addStyleClass("' . $class . '")' : '');
        }
        
        return $cells;
    }
        
    protected function isMTable() : bool
    {
        return true;
    }
    
    protected function isUiTable() : bool
    {
        return false;
    }
}