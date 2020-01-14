<?php
namespace exface\UI5Facade\Facades\Elements;

use exface\Core\Facades\AbstractAjaxFacade\Elements\JqueryDisableConditionTrait;
use exface\Core\Widgets\InputButton;
use exface\Core\CommonLogic\DataSheets\DataColumn;

/**
 * Generates OpenUI5 inputs
 *
 * @author Andrej Kabachnik
 * 
 * @method InputButton getWidget()
 *        
 */
class UI5InputButton extends UI5Input
{    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5Value::buildJsConstructorForMainControl()
     */
    public function buildJsConstructorForMainControl($oControllerJs = 'oController')
    {
        $btnElement = $this->getFacade()->getElement($this->getWidget()->getButton());
        $saveDataToModelJs = <<<JS

var oInput = sap.ui.getCore().byId("{$this->getId()}");
oInput.getModel('action_result').setData(response);
oInput.fireChange();

JS;
        $btnElement->addOnSuccessScript($saveDataToModelJs);
        // Press the button on enter in the input
        $this->getController()->addOnInitScript("sap.ui.getCore().byId('{$this->getId()}').onsapenter = (function(oEvent){{$btnElement->buildJsClickEventHandlerCall()}});");
        // Press the button initially
        if ($this->getWidget()->getButtonPressOnStart() === true) {
            $this->getController()->addOnInitScript("console.log('autopress!'); sap.ui.getCore().byId('{$btnElement->getId()}').firePress();");
        }
        return <<<JS

        new sap.m.HBox({
            items: [
                new sap.m.Input("{$this->getId()}", {
                    {$this->buildJsProperties()}
                    {$this->buildJsPropertyType()}
                    {$this->buildJsPropertyChange()}
                    {$this->buildJsPropertyRequired()}
                    {$this->buildJsPropertyValue()}
                    {$this->buildJsPropertyDisabled()}
                    {$this->buildJsPropertyHeight()}
                    layoutData: new sap.m.FlexItemData({
                        growFactor: 1
                    }),
                }).setModel(new sap.ui.model.json.JSONModel({}), 'action_result'){$this->buildJsPseudoEventHandlers()},
                {$btnElement->buildJsConstructor()}
                
            ],
            {$this->buildJsPropertyWidth()}
            {$this->buildJsPropertyHeight()}
        })

JS;
    }
            
    public function buildJsValueGetter(string $dataColumnName = null, string $iRowJs = null)
    {
        $widget = $this->getWidget();
        
        if ($dataColumnName === null || $dataColumnName === $widget->getDataColumnName()) {
            return parent::buildJsValueGetter();
        }
        
        if ($iRowJs === null) {
            $iRowJs = '0';
        }
        
        return <<<JS

function(){
    var aData = sap.ui.getCore().byId('{$this->getId()}').getModel('action_result').getData().data;
    if (aData !== undefined && aData.length > 0) {
        return aData[{$iRowJs}]['{$dataColumnName}'];
    } else {
        return '';
    }
}()

JS;
    }
}