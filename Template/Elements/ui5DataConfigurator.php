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
        if ($this->getIncludeFilterTab()) {
            $filter_tab_js = <<<JS
                new exface.core.P13nLayoutPanel({
                    title: "{$this->translate('WIDGET.DATATABLE.SETTINGS_DIALOG.FILTERS')}",
                    visible: true,
                    layoutMode: "Desktop",
                    content: [
                        new sap.ui.layout.Grid({
                            defaultSpan: "L6 S12",
                            content: [
                                {$this->buildJsFilters()}
        					]
                        })
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
                    items: {
                        path: '/columns',
                        template: new sap.m.P13nItem({
                            columnKey: "{attribute_alias}", 
                            text: "{caption}"
                        })
                    },
                    filterItems: [

                    ]
                }),
                new sap.m.P13nColumnsPanel({
                    title: "{$this->translate('WIDGET.DATATABLE.SETTINGS_DIALOG.COLUMNS')}",
                    visible: true,
                    addColumnsItem: "onAddColumnsItem",
                    type: "columns",
                    items: {
                        path: '/columns',
                        template: new sap.m.P13nItem({
                            columnKey: "{column_name}",
                            text: "{caption}",
                            visible: "{visible}"
                        })
                    }
                }),
                new sap.m.P13nSortPanel({
                    title: "{$this->translate('WIDGET.DATATABLE.SETTINGS_DIALOG.SORTING')}",
                    visible: true,
                    type: "sort",
                    containerQuer: true,
                    layoutMode: "Desktop",
                    items: {
                        path: '/sorters',
                        template: new sap.m.P13nItem({
                            columnKey: "{alias}",
                            text: "{caption}"
                        })
                    },
                    /*sortItems: [
                        new sap.m.P13nSortItem({
                            columnKey: "name",
                            operation: "Ascending"
                        })
                    ]*/
                })
            ]
        }).setModel(function(){
            var oModel = new sap.ui.model.json.JSONModel();
            var columns = {$this->buildJsonColumnData()};
            var sorters = {$this->buildJsonSorterData()};
            var data = {
                "columns": columns,
                "sorters": sorters
            }
            oModel.setData(data);
            return oModel;        
        }())

JS;
    }
                
    protected function buildJsonColumnData()
    {
        $data = [];
        foreach ($this->getWidget()->getWidgetConfigured()->getColumns() as $col) {
            if (! $col->getAttribute()) {
                continue;
            }
            $data[] = [
                "attribute_alias" => $col->getAttributeAlias(),
                "column_name" => $col->getDataColumnName(),
                "caption" => $col->getCaption(),
                "visible" => $col->isHidden() ? false : true
            ];
        }
        return json_encode($data);
    }
    
    protected function buildJsonSorterData()
    {
        $data = [];
        $sorters = [];
        $table = $this->getWidget()->getWidgetConfigured();
        foreach ($table->getSorters() as $sorter) {
            $sorters[] = $sorter->getProperty('attribute_alias');
            $data[] = [
                "attribute_alias" => $sorter->getProperty('attribute_alias'),
                "caption" => $this->getMetaObject()->getAttribute($sorter->getProperty('attribute_alias'))->getName()
            ];
        }
        foreach ($table->getColumns() as $col) {
            if (! $col->getAttribute()) {
                continue;
            }
            if (in_array($col->getAttributeAlias(), $sorters)) {
                continue;
            }
            $data[] = [
                "attribute_alias" => $col->getAttributeAlias(),
                "caption" => $col->getCaption()
            ];
        }
        return json_encode($data);
    }
    
    /**
     * Returns an comma separated list of control constructors for filters
     * 
     * @return string
     */
    public function buildJsFilters()
    {
        $filters = '';
        $filters_hidden = '';
        foreach ($this->getWidget()->getFilters() as $filter) {
            $filter_element = $this->getTemplate()->getElement($filter);
            if (! $filter_element->isVisible()) {
                $filters_hidden .= $this->buildJsFilter($filter_element);
            } else {
                $filters .= $this->buildJsFilter($filter_element);
            }
        }
        return $filters . $filters_hidden;
    }
    
    /**
     * Returns a constructor for the give filter element followed by a comma.
     * 
     * The constructor for a filter element within a data configurator is different from a
     * filter's general constructor!
     * 
     * @param ui5Filter $element
     * @return string
     */
    protected function buildJsFilter(ui5Filter $element) {
        $element->addPseudoEventHandler('onsapenter', $this->getTemplate()->getElement($this->getWidget()->getWidgetConfigured())->buildJsRefresh());
        return <<<JS
        
                        new sap.ui.layout.VerticalLayout({
                            width: "100%",
                            {$element->buildJsPropertyVisibile()}
                            content: [
                        	    {$element->buildJsConstructor()}
                            ]
                        }),
                        
JS;
    }
}
?>
