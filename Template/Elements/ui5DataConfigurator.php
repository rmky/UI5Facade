<?php
namespace exface\OpenUI5Template\Template\Elements;

use exface\Core\Templates\AbstractAjaxTemplate\Elements\JqueryDataConfiguratorTrait;
use exface\Core\Widgets\DataConfigurator;

/**
 * 
 * @method DataConfigurator getWidget()
 * 
 * @author Andrej Kabachnik
 *
 */
class ui5DataConfigurator extends ui5Tabs
{
    use JqueryDataConfiguratorTrait;
    
    public function generateJs(){
        return parent::generateJs() . <<<JS

    var {$this->getJsVar()} = {$this->buildJsConstructor()};

JS;
    }
    
    public function buildJsConstructor()
    {
        return <<<JS

        new sap.m.P13nDialog("{$this->getId()}", {
            ok: function() { {$this->getJsVar()}.close(); {$this->getTemplate()->getElement($this->getWidget()->getWidgetConfigured())->buildJsRefresh()}; },
            cancel: function() { {$this->getJsVar()}.close() },
            showReset: true,
            reset: "handleReset",
            initialVisiblePanelType: "filter",
            panels: [
                new sap.m.P13nFilterPanel({
                    title: "Filter",
                    visible: true,
                    type: "filter",
                    containerQuer: true, 
                    layoutMode: "Desktop",
                    /*items: "{
                        path: '/ColumnCollection',
                        template: new sap.m.P13nItem({
                            columnKey: "{path}", 
                            text: "{text}"
                        })
                    }",*/
                    filterItems: [
                        new sap.m.P13nFilterItem({
                            columnKey: "name",
                            operation: "BT",
                            value1: "a"
                        })
                    ]
                }),
                new sap.m.P13nColumnsPanel({
                    title: "Columns",
                    visible: true,
                    addColumnsItem: "onAddColumnsItem",
                    type: "columns",
                    /*items: "{
                        path: '/ColumnCollection',
                        template: new sap.m.P13nItem({
                            columnKey: "{path}",
                            text: "{text}",
                            visible: "{visible}"
                        })
                    }"*/
                }),
                new sap.m.P13nSortPanel({
                    title: "Sort",
                    visible: true,
                    type: "sort",
                    containerQuer: true,
                    layoutMode: "Desktop",
                    /*items: "{
                        path: '/ColumnCollection',
                        template: new sap.m.P13nSortItem({
                            columnKey: "{path}",
                            text: "{text}"
                        })
                    }",*/
                    sortItems: [
                        new sap.m.P13nSortItem({
                            columnKey: "name",
                            operation: "Ascending"
                        })
                    ]
                })
            ]
        })

JS;
    }
}
?>
