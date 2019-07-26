<?php
namespace exface\UI5Facade\Facades\Elements\ServerAdapters;

use exface\UI5Facade\Facades\Elements\UI5AbstractElement;
use exface\UI5Facade\Facades\Interfaces\UI5ServerAdapterInterface;
use exface\Core\Interfaces\Actions\ActionInterface;
use exface\Core\Actions\ReadData;
use exface\Core\Actions\ReadPrefill;

class UI5FacadeServerAdapter implements UI5ServerAdapterInterface
{
    private $element = null;
    
    public function __construct(UI5AbstractElement $element)
    {
        $this->element = $element;
    }
    
    public function getElement() : UI5AbstractElement
    {
        return $this->element;
    }
    
    public function buildJsServerRequest(ActionInterface $action, string $oModelJs, string $oParamsJs, string $onModelLoadedJs, string $onErrorJs = '', string $onOfflineJs = '') : string
    {
        switch (true) {
            case $action instanceof ReadPrefill:
                return $this->buildJsPrefillLoader($oModelJs, $oParamsJs, $onModelLoadedJs, $onErrorJs, $onOfflineJs);
            case $action instanceof ReadData:
                return $this->buildJsDataLoader($oModelJs, $oParamsJs, $onModelLoadedJs, $onErrorJs, $onOfflineJs);
        }
        
        return '';
    }
    
    protected function buildJsDataLoader(string $oModelJs, string $oParamsJs, string $onModelLoadedJs, string $onErrorJs = '', string $onOfflineJs = '') : string
    {
        return <<<JS
        
                var fnCompleted = function(oEvent){
                    {$this->getElement()->buildJsBusyIconHide()}
        			if (oEvent.getParameters().success) {
                        {$onModelLoadedJs}
                    } else {
                        {$onErrorJs}
                        var error = oEvent.getParameters().errorobject;
                        if (navigator.onLine === false) {
                            if (oData.length = 0) {
                                {$onOfflineJs}
                            } else {
                                {$this->getElement()->getController()->buildJsComponentGetter()}.showDialog('{$this->getElement()->translate('WIDGET.DATATABLE.OFFLINE_ERROR')}', '{$this->getElement()->translate('WIDGET.DATATABLE.OFFLINE_ERROR_TITLE')}', 'Error');
                            }
                        } else {
                            {$this->getElement()->buildJsShowError('error.responseText', "(error.statusCode+' '+error.statusText)")}
                        }
                    }
                    
                    this.detachRequestCompleted(fnCompleted);
        		};
        		
        		$oModelJs.attachRequestCompleted(fnCompleted);
        		
                $oModelJs.loadData("{$this->getElement()->getAjaxUrl()}", {$oParamsJs});
                
JS;
    }
    
    protected function buildJsPrefillLoader(string $oModelJs, string $oParamsJs, string $onModelLoadedJs, string $onErrorJs = '', string $onOfflineJs = '') : string
    {
        return <<<JS
        
            $.ajax({
                url: "{$this->getElement()->getAjaxUrl()}",
                type: "POST",
				data: {$oParamsJs},
                success: function(response, textStatus, jqXHR) {
                    if (Object.keys({$oModelJs}.getData()).length !== 0) {
                        {$oModelJs}.setData({});
                    }
                    if (Array.isArray(response.rows) && response.rows.length === 1) {
                        {$oModelJs}.setData(response.rows[0]);
                    }
                    {$this->buildJsBusyIconHide()}
                    {$onModelLoadedJs}
                },
                error: function(jqXHR, textStatus, errorThrown){
                    {$onErrorJs}
                    {$this->getElement()->buildJsBusyIconHide()}
                    if (navigator.onLine === false) {
                        {$onOfflineJs}
                    } else {
                        {$this->getElement()->getController()->buildJsComponentGetter()}.showAjaxErrorDialog(jqXHR)
                    }
                }
			})
JS;
    }
}