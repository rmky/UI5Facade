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
        $widget = $this->getWidget();
        $calItem = $widget->getItemsConfig();
        
        $controller = $this->getController();
        $this->initConfiguratorControl($controller);
        
        $subtitleBinding = $calItem->hasSubtitle() ? $this->buildJsValueBindingForWidget($calItem->getSubtitleColumn()->getCellWidget()) : '""';
        
        return <<<JS

new sap.m.PlanningCalendar("{$this->getId()}", {
	startDate: "{/_scheduler/startDate}",
	rows: "{path: '/people'}",
	appointmentsVisualization: "Filled",
	//appointmentSelect: "handleAppointmentSelect",
	showEmptyIntervalHeaders: false,
	//showWeekNumbers: true,
	toolbarContent: [
		{$this->buildJsToolbarContent($oControllerJs)}
	],
	rows: [
		new sap.m.PlanningCalendarRow({
			//icon: "{pic}",
			//title: "{name}",
			//text: "{role}",
			appointments: {
                path: '/_scheduler/items', 
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
			/*intervalHeaders: {
                path: '_scheduler/headers', 
                templateShareable: true,
                template: new sap.ui.unified.CalendarAppointment({
					startDate: "{start}",
					endDate: "{end}",
					icon: "{pic}",
					title: "{title}",
					type: "{type}",
				})
            },*/
		})
	]
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
        
        $endTime = $calItem->hasEndTime() ? "oRow['{$calItem->getEndTimeColumn()->getDataColumnName()}']" : "''";
        $subtitle = $calItem->hasSubtitle() ? "{$calItem->getSubtitleColumn()->getDataColumnName()}: oRow['{$calItem->getSubtitleColumn()->getDataColumnName()}']," : '';
        
        if ($workdayStart = $widget->getTimelineConfig()->getWorkdayStartTime()){
            $workdayStartSplit = explode(':', $workdayStart);
            $workdayStartSplit = array_map('intval', $workdayStartSplit);
            $workdayStartJs = 'dMin.setHours(' . implode(', ', $workdayStartSplit) . ');';
        }
        
        return $this->buildJsDataLoaderOnLoadedViaTrait($oModelJs) . <<<JS
        
            var aData = {$oModelJs}.getProperty('/data');
            var aItems = [];
            var aHeaders = [];
            var dMin, dStart, dEnd, sEnd, oRow;
            for (var i in aData) {
                oRow = aData[i];
                dStart = new Date(oRow["{$calItem->getStartTimeColumn()->getDataColumnName()}"]);
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
                aItems.push({
                    _start: dStart,
                    _end: dEnd,
                    {$calItem->getTitleColumn()->getDataColumnName()}: oRow["{$calItem->getTitleColumn()->getDataColumnName()}"],
                    {$subtitle}
                });
            }
            {$oModelJs}.setProperty('/_scheduler', {
                startDate: dMin,
                items: aItems,
                headers: aHeaders
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