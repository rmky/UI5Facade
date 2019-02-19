<?php
namespace exface\OpenUI5Template\Templates\Elements;

use exface\Core\Widgets\Scheduler;
use exface\Core\DataTypes\StringDataType;
use exface\OpenUI5Template\Templates\Elements\Traits\ui5DataElementTrait;
use exface\Core\Interfaces\WidgetInterface;
use exface\OpenUI5Template\Templates\Interfaces\ui5CompoundControlInterface;

/**
 * 
 * @method Scheduler getWidget()
 * 
 * @author Andrej Kabachnik
 *
 */
class ui5Scheduler extends ui5AbstractElement
{
    use ui5DataElementTrait {
        buildJsDataLoaderOnLoaded as buildJsDataLoaderOnLoadedViaTrait;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Elements\ui5AbstractElement::buildJsConstructor()
     */
    public function buildJsConstructorForControl($oControllerJs = 'oController') : string
    {
        $controller = $this->getController();
        $this->initConfiguratorControl($controller);
        
        $showRowHeaders = $this->getWidget()->hasResources() ? 'true' : 'false';
        
        return <<<JS

new sap.m.PlanningCalendar("{$this->getId()}", {
	startDate: "{/_scheduler/startDate}",
	appointmentsVisualization: "Filled",
	//appointmentSelect: "handleAppointmentSelect",
	showRowHeaders: {$showRowHeaders},
    showEmptyIntervalHeaders: false,
	//showWeekNumbers: true,
	//appointmentSelect: "handleAppointmentSelect",
	toolbarContent: [
		{$this->buildJsToolbarContent($oControllerJs)}
	],
	rows: {
		path: '/_scheduler/rows',
        template: {$this->buildJsRowsConstructors()}
	}
})

JS;
    }
		
    protected function buildJsRowsConstructors() : string
    {
        $widget = $this->getWidget();
        
        $calItem = $widget->getItemsConfig();
        $subtitleBinding = $calItem->hasSubtitle() ? $this->buildJsValueBindingForWidget($calItem->getSubtitleColumn()->getCellWidget()) : '""';
        
        $rowProps = '';
        if ($widget->hasResources() === true) {
            $resource = $widget->getResourcesConfig();
            $rowProps .= 'title: ' . $this->buildJsValueBindingForWidget($resource->getTitleColumn()->getCellWidget()) . ',';
            if ($resource->hasSubtitle()) {
                $rowProps .= 'text: ' . $this->buildJsValueBindingForWidget($resource->getSubtitleColumn()->getCellWidget()) . ',';
            }
        } 
        
        return <<<JS

        new sap.m.PlanningCalendarRow({
			{$rowProps}
			appointments: {
                path: 'items', 
                templateShareable: true,
                template: new sap.ui.unified.CalendarAppointment({
					startDate: "{_start}",
					endDate: "{_end}",
					icon: "{pic}",
					title: {$this->buildJsValueBindingForWidget($calItem->getTitleColumn()->getCellWidget())},
					text: {$subtitleBinding},
					type: "{type}",
					tentative: "{tentative}",
				})
            },
			intervalHeaders: {
                path: '_scheduler/headers', 
                templateShareable: true,
                template: new sap.ui.unified.CalendarAppointment({
					startDate: "{start}",
					endDate: "{end}",
					icon: "{pic}",
					title: {$this->buildJsValueBindingForWidget($calItem->getTitleColumn()->getCellWidget())},
					text: {$subtitleBinding},
					type: "{type}",
				})
            },
		})

JS;
    }
        
    public function buildJsRefresh()
    {
        return $this->getController()->buildJsMethodCallFromController('onLoadData', $this, '');
    }
        
    protected function buildJsDataResetter() : string
    {
        // TODO
        return '';
    }
    
    protected function buildJsDataLoaderOnLoaded(string $oModelJs = 'oModel') : string
    {
        $widget = $this->getWidget();
        $calItem = $widget->getItemsConfig();
        
        $endTime = $calItem->hasEndTime() ? "oDataRow['{$calItem->getEndTimeColumn()->getDataColumnName()}']" : "''";
        $subtitle = $calItem->hasSubtitle() ? "{$calItem->getSubtitleColumn()->getDataColumnName()}: oDataRow['{$calItem->getSubtitleColumn()->getDataColumnName()}']," : '';
        
        if ($workdayStart = $widget->getTimelineConfig()->getWorkdayStartTime()){
            $workdayStartSplit = explode(':', $workdayStart);
            $workdayStartSplit = array_map('intval', $workdayStartSplit);
            $workdayStartJs = 'dMin.setHours(' . implode(', ', $workdayStartSplit) . ');';
        }
        
        if ($widget->hasResources()) {
            $rConf = $widget->getResourcesConfig();
            $rowKeyGetter = "oDataRow['{$rConf->getTitleColumn()->getDataColumnName()}']";
            if ($rConf->hasSubtitle()) {
                $rSubtitle = "{$rConf->getSubtitleColumn()->getDataColumnName()}: oDataRow['{$rConf->getSubtitleColumn()->getDataColumnName()}'],";
            }
            $rowProps = <<<JS

                        {$rConf->getTitleColumn()->getDataColumnName()}: oDataRow['{$rConf->getTitleColumn()->getDataColumnName()}'],
                        {$rSubtitle}

JS;
        } else {
            $rowKeyGetter = "''";
        }
        
        return $this->buildJsDataLoaderOnLoadedViaTrait($oModelJs) . <<<JS
        
            var aData = {$oModelJs}.getProperty('/data');
            var oRows = [];
            var dMin, dStart, dEnd, sEnd, oDataRow, sRowKey;
            for (var i in aData) {
                oDataRow = aData[i];

                sRowKey = {$rowKeyGetter};
                if (oRows[sRowKey] === undefined) {
                    oRows[sRowKey] = {
                        {$rowProps}
                        items: [],
                        headers: []
                    };
                }

                dStart = new Date(oDataRow["{$calItem->getStartTimeColumn()->getDataColumnName()}"]);
                if (dMin === undefined) {
                    dMin = new Date(dStart.getTime());
                    {$workdayStartJs}
                }
                sEnd = $endTime;
                if (sEnd) {
                    dEnd = new Date(sEnd);
                } else {
                    dEnd = new Date(dStart.getTime());
                    dEnd.setHours(dEnd.getHours() + {$calItem->getDefaultDurationHours(1)});
                }
                oRows[sRowKey].items.push({
                    _start: dStart,
                    _end: dEnd,
                    {$calItem->getTitleColumn()->getDataColumnName()}: oDataRow["{$calItem->getTitleColumn()->getDataColumnName()}"],
                    {$subtitle}
                });
            }
            {$oModelJs}.setProperty('/_scheduler', {
                startDate: dMin,
                rows: Object.values(oRows),
            });
			console.log({$oModelJs}.getData());
			
JS;
    }
    
    /**
     *
     * @return bool
     */
    protected function hasQuickSearch() : bool
    {
        return true;
    }
    
    protected function buildJsValueBindingForWidget(WidgetInterface $tplWidget, string $modelName = null) : string
    {
        $tpl = $this->getTemplate()->getElement($tplWidget);
        // Disable using widget id as control id because this is a template for multiple controls
        $tpl->setUseWidgetId(false);
        
        $modelPrefix = $modelName ? $modelName . '>' : '';
        if ($tpl instanceof ui5Display) {
            $tpl->setValueBindingPath($modelPrefix . $tplWidget->getDataColumnName());
        } elseif ($tpl instanceof ui5Input) {
            $tpl->setValueBindingPath($modelPrefix . $tplWidget->getDataColumnName());
        }
        
        return $tpl->buildJsValueBinding();
    }
}