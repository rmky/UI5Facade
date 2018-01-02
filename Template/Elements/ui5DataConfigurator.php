<?php
namespace exface\OpenUI5Template\Template\Elements;

use exface\Core\Templates\AbstractAjaxTemplate\Elements\JqueryDataConfiguratorTrait;
use exface\Core\Widgets\DataConfigurator;
use exface\Core\DataTypes\BooleanDataType;

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
    
    private $include_filter_tab = true;
    
    public function generateJs(){
        return parent::generateJs() . <<<JS

    var {$this->getJsVar()} = {$this->buildJsConstructor()};

JS;
    }
        
    public function setIncludeFilterTab($true_or_false)
    {
        $this->include_filter_tab = BooleanDataType::cast($true_or_false);
        return $this;
    }
    
    public function getIncludeFilterTab()
    {
        return $this->include_filter_tab;
    }
    
    public function buildJsConstructor()
    {
        $filter_tab_element = $this->getTemplate()->getElement($this->getWidget()->getFilterTab());
        
        if ($this->getIncludeFilterTab()) {
            $filter_tab_js = <<<JS
                new exface.core.P13nLayoutPanel({
                    title: "{$this->translate('WIDGET.DATATABLE.SETTINGS_DIALOG.FILTERS')}",
                    visible: true,
                    layoutMode: "Desktop",
                    content: [
                        {$filter_tab_element->buildJsLayoutConstructor($filter_tab_element->buildJsChildrenConstructors())}
                    ]
                }),
JS;
        }
        
        return <<<JS

        new sap.m.P13nDialog("{$this->getId()}", {
            ok: function() { {$this->getJsVar()}.close(); {$this->getTemplate()->getElement($this->getWidget()->getWidgetConfigured())->buildJsRefresh()}; },
            cancel: function() { {$this->getJsVar()}.close() },
            showReset: true,
            reset: "handleReset",
            panels: [
                {$filter_tab_js}
                new sap.m.P13nFilterPanel({
                    title: "{$this->translate('WIDGET.DATATABLE.SETTINGS_DIALOG.ADVANCED_SEARCH')}",
                    visible: true,
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

                    ]
                }),
                new sap.m.P13nColumnsPanel({
                    title: "{$this->translate('WIDGET.DATATABLE.SETTINGS_DIALOG.COLUMNS')}",
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
                    title: "{$this->translate('WIDGET.DATATABLE.SETTINGS_DIALOG.SORTING')}",
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
