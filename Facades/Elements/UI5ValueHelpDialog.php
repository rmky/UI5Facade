<?php
namespace exface\UI5Facade\Facades\Elements;

use exface\Core\Widgets\DataTable;

/**
 * Generates a sap.m.Dialog for a Value-Help-Dialog.
 * 
 * ## How to use
 * 
 * Instantiate the dialog in the handler script of the button-press-event
 * for the button, that should open the dialog.
 * 
 * ```php
 * 
 * $vhpd = new UI5ValueHelpDialog($tableWidgetToRenderInTheDialog, $facade);
 * $vhpd->addOnCloseScript('alert("You have selected: "' . $vhpd->buildJsValueGetter() . ')');
 * $vhpdConstrJs = $vhpd->buildJsConstructor($oControllerJs);
 * $resultingJS = <<<JS
 *      
 *      var oDialog = {$vhpdConstrJs};
 *      oDialog.open();
 *  
 * JS;
 * 
 * ```
 * 
 * @method DataTable getWidget()
 *
 * @author Andrej Kabachnik
 *        
 */
class UI5ValueHelpDialog extends UI5DataTable
{
    public function buildJsConstructor($oControllerJs = 'oController') : string
    {
        $this->getPaginatorElement()->registerControllerMethods();
        $this->initConfiguratorControl($this->getController());
        
        $body = <<<JS
        sap.ui.getCore().byId("{$this->getId()}__ValueHelpDialog").destroy();
JS;
        $this->getController()->addMethod("handleCancelVHelp", $this, "oEvent", $body);
        
        return $this->buildJsValueHelpDialogConstructor($oControllerJs);
    }   
                    
    protected function buildJsValueHelpDialogConstructor(string $oControllerJs) : string
    {
        return <<<JS
            new sap.m.Dialog( "{$this->getId()}__ValueHelpDialog" ,{
                content: [
                    //todo: "Title, etc.",
                    {$this->buildJsValueHelpTableWrapper($oControllerJs, $this->buildJsConstructorForControl($oControllerJs))},
                ],
                //todo: "toolbar",
                buttons: [
                    new sap.m.Button({
                        text: "OK",
                        press: "handleOkayVHelp{$this->getId()}"
                    }),
                    new sap.m.Button({
                        text: "Cancel",
                        press: "handleCancelVHelp{$this->getId()}"
                    }),
                ],
                afterClose: {$this->getController()->buildJsEventHandler($this, 'close', true)}
            })

JS;
    }
    
    protected function buildJsValueHelpTableWrapper(string $oControllerJs, string $tableConstructorJs) : string
    {
        // TODO add a special table wrapper instead of UI5DataElementTrait::buildJsPage().
        return $this->changeDataTableIds($tableConstructorJs);
        
//         return <<<JS
//         new sap.m.Text({text: "insert datatable here!"}),
// JS;
    }
    
    protected function changeDataTableIds(string $tableConstructorJs)
    {
        return str_replace("__DataTable", "__ValueHelpDialog__DataTable", $tableConstructorJs);
    }
    
    /**
     * 
     * @param string $js
     * @return UI5ValueHelpDialog
     */
    public function addOnCloseScript(string $js) : UI5ValueHelpDialog
    {
        $this->getController()->addOnEventScript($this, 'close', $js);
        return $this;
    }
    
    public function addOnLoadDataScript(string $js) : UI5ValueHelpDialog
    {
        $this->getController()->addOnEventScript($this, 'LoadData', $js);
        return $this;
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5AbstractElement::buildJsValueGetter()
     */
    public function buildJsValueGetter($dataColumnName = null, $rowNr = null)
    {
        $row = $this->buildJsGetSelectedRows('oTable');
        $col = $dataColumnName !== null ? '["' . $dataColumnName . '"]' : '';
        $id = $this->changeDataTableIds($this->getId());
        
        return <<<JS
        
function(){
    var oTable = sap.ui.getCore().byId('{$id}');
    return {$row}{$col};
}()

JS;
    }
}