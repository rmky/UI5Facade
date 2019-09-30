<?php
namespace exface\UI5Facade\Facades\Elements\ServerAdapters;

use exface\UI5Facade\Facades\Elements\UI5AbstractElement;
use exface\UI5Facade\Facades\Interfaces\UI5ServerAdapterInterface;
use exface\Core\Interfaces\Actions\ActionInterface;
use exface\Core\Actions\ReadData;
use exface\Core\Interfaces\Widgets\iHaveQuickSearch;

class PreloadServerAdapter implements UI5ServerAdapterInterface
{
    private $element = null;
    
    private $fallbackAdapter = null;
    
    public function __construct(UI5AbstractElement $element, UI5ServerAdapterInterface $fallBackAdapter)
    {
        $this->element = $element;
        $this->fallbackAdapter = $fallBackAdapter;
    }
    
    public function getElement() : UI5AbstractElement
    {
        return $this->element;
    }
    
    protected function getFallbackAdapter() : UI5ServerAdapterInterface
    {
        return $this->fallbackAdapter;
    }
    
    public function buildJsServerRequest(ActionInterface $action, string $oModelJs, string $oParamsJs, string $onModelLoadedJs, string $onOfflineJs = '') : string
    {
        $fallBackRequest = $this->getFallbackAdapter()->buildJsServerRequest($action, $oModelJs, $oParamsJs, $onModelLoadedJs, $onOfflineJs);
        switch (true) {
            case $action instanceof ReadData:
                return $this->buildJsDataLoader($oModelJs, $oParamsJs, $onModelLoadedJs, $onOfflineJs, $fallBackRequest);
        }
        
        return $fallBackRequest;
    }
    
    protected function buildJsDataLoader(string $oModelJs, string $oParamsJs, string $onModelLoadedJs, string $onOfflineJs, string $fallBackRequest) : string
    {
        $element = $this->getElement();
        $widget = $element->getWidget();
        
        return <<<JS
        
                exfPreloader
                .getPreload('{$widget->getMetaObject()->getAliasWithNamespace()}')
                .then(preload => {
                    if (preload !== undefined && preload.response !== undefined && preload.response.rows !== undefined) {
                        var aData = preload.response.rows;
                        if ({$oParamsJs}.data && {$oParamsJs}.data.filters && {$oParamsJs}.data.filters.conditions) {
                            var conditions = {$oParamsJs}.data.filters.conditions;
                            var fnFilter;
console.log({$oParamsJs});
                            for (var i in conditions) {
                                var cond = conditions[i];
                                if (cond.value === undefined || cond.value === null || cond.value === '') continue;
                                switch (cond.comparator) {
                                    case '==':
                                        aData = aData.filter(oRow => {
                                            return oRow[cond.expression] == cond.value
                                        });
                                        break;
                                    case '!==':
                                        aData = aData.filter(oRow => {
                                            return oRow[cond.expression] !== cond.value
                                        });
                                        break;
                                    case '!=':
                                        var val = cond.value.toString().toLowerCase();
                                        aData = aData.filter(oRow => {
                                            if (oRow[cond.expression] === undefined) return true;
                                            return ! oRow[cond.expression].toString().toLowerCase().includes(val);
                                        });
                                        break;
                                    case '=':
                                    default:
                                        var val = cond.value.toString().toLowerCase();
                                        aData = aData.filter(oRow => {
                                            if (oRow[cond.expression] === undefined) return false;
                                            return oRow[cond.expression].toString().toLowerCase().includes(val);
                                        });
                                }
                            }

                            if ({$oParamsJs}.q !== undefined && {$oParamsJs}.q !== '') {
                                var sQuery = {$oParamsJs}.q.toString().toLowerCase();
                                {$this->buildJsQuickSearchFilter('sQuery', 'aData')}
                            }
                            
                            var iFiltered = aData.length;
                        }
                        
                        if ({$oParamsJs}.start >= 0 && {$oParamsJs}.length > 0) {
                            aData = aData.slice({$oParamsJs}.start, {$oParamsJs}.start+{$oParamsJs}.length);
                        }
                        
                        {$oModelJs}.setData($.extend({}, preload.response, {rows: aData, recordsFiltered: iFiltered}));
                        {$onModelLoadedJs}
                    } else {
                        console.log('No preloaded data found: falling back to server request');
                        {$fallBackRequest}
                    }
                });
                
JS;
    }
    
    /**
     * Returns an inline JS-snippet to test if a given JS row object matches the quick search string.
     *  
     * @param string $sQueryJs
     * @param string $oRowJs
     * @return string
     */
    protected function buildJsQuickSearchFilter(string $sQueryJs = 'sQuery', string $aDataJs = 'aData') : string
    {
        $widget = $this->getElement()->getWidget();
        
        if (! $widget instanceof iHaveQuickSearch) {
            return '';
        }
        
        $filters = [];
        foreach ($widget->getAttributesForQuickSearch() as $attr) {
            $filters[] = "((oRow['{$attr->getAliasWithRelationPath()}'] || '').toString().toLowerCase().indexOf({$sQueryJs}) !== -1)";
        }
        
        if (! empty($filters)) {
            $filterJs = implode(' || ', $filters);
        } else {
            return ''; 
        }
        
        return <<<JS

                            
                                {$aDataJs} = {$aDataJs}.filter(oRow => {
                                    if (oRow[cond.expression] === undefined) return false;
                                    return {$filterJs};
                                });

JS;
    }
}