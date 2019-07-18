<?php
namespace exface\UI5Facade\Facades\Elements\ServerAdapters;

use exface\UI5Facade\Facades\Elements\UI5AbstractElement;
use exface\UI5Facade\Facades\Interfaces\UI5ServerAdapterInterface;

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
    
    public function buildJsDataLoader(string $oModelJs, string $oParamsJs, string $onModelLoadedJs, string $onOfflineJs = '') : string
    {
        return <<<JS
        
                var fnCompleted = function(oEvent){
                    {$this->getElement()->buildJsBusyIconHide()}
        			if (oEvent.getParameters().success) {
                        {$onModelLoadedJs}
                    } else {
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
}