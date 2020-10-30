<?php
namespace exface\UI5Facade\Facades\Elements;

use exface\Core\Interfaces\Widgets\iShowSingleAttribute;
use exface\UI5Facade\Facades\Interfaces\UI5ValueBindingInterface;
use exface\Core\Facades\AbstractAjaxFacade\Elements\JqueryDataCarouselTrait;
use exface\Core\Factories\ActionFactory;
use exface\Core\Actions\ShowDialog;

/**
 * Generates a sap.ui.layout.Splitter with logic similar to the FlexibleColumnLayout.
 * 
 * Theoretically, a DataCarousel widget would match the `sap.f.FlexibleColumnLayout` in 
 * Fiori best, but the FlexibleColumnLayout only works properly if used as the view's
 * top-level control - not in ObjectPages, etc. 
 * 
 * Instead we use simple Splitter with on pane being the data widget and the other - the
 * details packed inside a `sap.m.Panel` with the required expand/collapse buttons.
 * 
 * The current expand/collapse state is stored in a special model attached to the Splitter,
 * so it can easily be accessed from any code.
 *
 * @author Andrej Kabachnik
 * 
 * @method \exface\Core\Widgets\DataCarousel getWidget()
 *        
 */
class UI5DataCarousel extends UI5Split
{
    use JqueryDataCarouselTrait;
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5Split::buildJsConstructor()
     */
    public function buildJsConstructor($oControllerJs = 'oController') : string
    {
        $this->registerSyncOnMaster();
        
        $initSplitter = $this->buildJsSetSizesInitial("sap.ui.getCore().byId('{$this->getId()}')") . "setTimeout(function(){ {$this->buildJsEmptyHintShow()} }, 100);";
        $this->getController()->addOnInitScript($initSplitter);
        
        $splitter = <<<JS
        
    new sap.ui.layout.Splitter("{$this->getId()}", {
        height: "100%",
        width: "100%",
        orientation: "{$this->getOrientation()}",
        contentAreas: [
            {$this->buildJsChildrenConstructors()}
        ]
    })
    .setModel(new sap.ui.model.json.JSONModel({
        detailsExpanded: false,
        dataExpanded: sap.ui.Device.system.phone
    }), "_innerState")
    {$this->buildJsPseudoEventHandlers()}

JS;
        if ($this->hasPageWrapper() === true) {
            return $this->buildJsPageWrapper($splitter);
        }
        
        return $splitter;
    }
    
    public function buildJsChildrenConstructors() : string
    {
        $widget = $this->getWidget();
        $dataElem = $this->getFacade()->getElement($widget->getDataWidget());
        $detailElem = $this->getFacade()->getElement($widget->getDetailsWidget());
        
        if ($detailTitle = $detailElem->getCaption()) {
            $headerText = $this->escapeJsTextValue($detailTitle);
            $detailElem->getWidget()->setCaption("");
        } else {
            $headerText = '""';
        }
        
        return <<<JS

            {$dataElem->buildJsConstructor()},
            new sap.m.Panel('{$this->getId()}-DetailPanel', {
                headerText: {$headerText},
                headerToolbar: [
                    new sap.m.OverflowToolbar({
                        content: [
                            new sap.m.ToolbarSpacer(),
                            new sap.m.Button({
                                icon: "{= \${_innerState>/detailsExpanded} === true ? 'sap-icon://exit-full-screen' : 'sap-icon://full-screen'}",
                                press: function(oEvent) {
                                    var oButton = oEvent.getSource();
                                    var oSplitter = sap.ui.getCore().byId('{$this->getId()}');
                                    if (oSplitter.getModel('_innerState').getProperty('/detailsExpanded') === false) {
                                        {$this->buildJsSetSizesExpandDetails('oSplitter')}
                                    } else {
                                        {$this->buildJsSetSizesInitial('oSplitter')}
                                    }
                                }
                            }),
                            new sap.m.Button({
                                icon: "sap-icon://decline",
                                tooltip: "{i18n>WIDGET.DATACAROUSEL.DETAILS_HIDE}",
                                press: function(oEvent) {
                                    var oSplitter = sap.ui.getCore().byId('{$this->getId()}');
                                    {$this->buildJsSetSizesExpandData('oSplitter')}
                                }
                            })
                        ]
                    })
                ],
                content: [
                    {$detailElem->buildJsConstructor()}
                ]
            })
            .addStyleClass("{$this->buildCssElementClass()} exf-panel-no-border")

JS;
    }
    
    /**
     * 
     * @return \exface\UI5Facade\Facades\Elements\UI5DataCarousel
     */
    protected function registerSyncOnMaster()
    {
        $dataIsEditable = $this->getDataElement()->isEditable();
        foreach ($this->getWidget()->getDetailsWidget()->getChildrenRecursive() as $child) {
            if (! ($child instanceof iShowSingleAttribute) || ! $child->isBoundToAttribute()) {
                continue;
            }
            if (! $dataIsEditable) {
                $this->getDataElement()->setEditable(true);
            }
            $childElement = $this->getFacade()->getElement($child);
            if ($childElement instanceof UI5ValueBindingInterface) {
                $childElement->setValueBoundToModel(true);
            }
            if ($childElement instanceof UI5ValueBindingInterface) {
                $bindings .= <<<JS

            oControl = sap.ui.getCore().byId("{$childElement->getId()}");
            oBindingInfo = oControl.getBindingInfo("{$childElement->buildJsValueBindingPropertyName()}");
            oBindingInfo.parts[0].path = sPath + "{$childElement->getValueBindingPath()}";
            oControl.setModel(oModel).bindProperty("{$childElement->buildJsValueBindingPropertyName()}", oBindingInfo);
            oControl.setBindingContext(new sap.ui.model.Context(oModel, sPath + "{$childElement->getValueBindingPath()}"));
JS;
            }
        }
        
        // Determine the currently selected row and replace the binding path of each
        // details widget with the path to the selected row in the model of the data
        // widget. This way, they will be bound to each-other.
        // Use a fake show-dialog-action to make the data getter behave as required
        // Also make sure the details area is shown when an item is select - but only
        // if the area was previously hidden because otherwise each click would restore
        // area sizes to default values!
        $action = ActionFactory::createFromString($this->getWorkbench(), ShowDialog::class);
        $bindingScript = <<<JS

        (function() {
            var oSplit = sap.ui.getCore().byId('{$this->getId()}');
            var oDetailArea = oSplit.getContentAreas()[1];
            var oTable = sap.ui.getCore().byId('{$this->getDataElement()->getId()}');
            var oRowSelected = {$this->getDataElement()->buildJsDataGetter($action)}.rows[0];
            var oModel = oTable.getModel();
            var iRowIdx = oModel.getData().rows.indexOf(oRowSelected);
            var sPath = '/rows/' + iRowIdx;
            var oControl, oBindingInfo;

            if (iRowIdx >= 0) {
                {$this->buildJsEmptyHintHide()}
            } else {
                {$this->buildJsEmptyHintShow()}
            }

            {$bindings}
            
            if (oSplit.getModel('_innerState').getProperty('/dataExpanded') === true) {
                if (sap.ui.Device.system.phone) {
                    {$this->buildJsSetSizesExpandDetails('oSplit')}
                } else {
                    {$this->buildJsSetSizesInitial('oSplit')}
                }
            }
        })();
        
JS;
        
        $this->getDataElement()->addOnChangeScript($bindingScript);
        return $this;
    }
    
    /**
     * 
     * @return string
     */
    protected function buildJsSetSizesExpandData(string $oSplitJs) : string
    {
        return <<<JS

        sap.ui.getCore().byId('{$this->getId()}').getContentAreas().forEach(function(oControl, i){
            oControl.setLayoutData(
                new sap.ui.layout.SplitterLayoutData({size: (i === 0 ? '100%' : '0px')})
            );
        });

        $oSplitJs.getModel('_innerState').setProperty('/dataExpanded', true);
        $oSplitJs.getModel('_innerState').setProperty('/detailsExpanded', false);
JS;
    }
    
    /**
     * 
     * @return string
     */
    protected function buildJsSetSizesExpandDetails(string $oSplitJs) : string
    {
        return <<<JS
        
            $oSplitJs.getContentAreas().forEach(function(oControl, i){
                oControl.setLayoutData(
                    new sap.ui.layout.SplitterLayoutData({size: (i === 1 ? '100%' : '0px')})
                );
            });
            
        $oSplitJs.getModel('_innerState').setProperty('/dataExpanded', false);
        $oSplitJs.getModel('_innerState').setProperty('/detailsExpanded', true);
JS;
    }
    
    protected function buildJsSetSizesInitial(string $oSplitJs) : string
    {
        $expandedSizesJs = parent::buildJsSetSizesInitial($oSplitJs);
        
        return <<<JS

            if (sap.ui.Device.system.phone) {
                {$this->buildJsSetSizesExpandData($oSplitJs)}
            } else {
                $expandedSizesJs;
                $oSplitJs.getModel('_innerState').setProperty('/dataExpanded', false);
                $oSplitJs.getModel('_innerState').setProperty('/detailsExpanded', false);
            }

JS;
    }
    
    /**
     * 
     * @return string
     */
    protected function getModelNameForState() : string
    {
        return 'expandState';
    }
    
    /**
     * 
     * @return string
     */
    protected function buildJsEmptyHintShow() : string
    {
        return <<<JS

        sap.ui.getCore().byId('{$this->getId()}-DetailPanel')
        .setBusyIndicatorDelay(0)
        .setBusy(true)
        .addStyleClass('exf-busy-text');
        setTimeout(function(){
            $('#{$this->getId()}-DetailPanel .sapUiLocalBusyIndicator').prepend($('<div class="exf-busy-text-content">{$this->translate('WIDGET.DATACAROUSEL.DETAILS_EMPTY_HINT')}</div>'));
        },0);
JS;
    }
    
    /**
     * 
     * @return string
     */
    protected function buildJsEmptyHintHide() : string
    {
        return <<<JS
        
        sap.ui.getCore().byId('{$this->getId()}-DetailPanel')
        .setBusy(false)
        .removeStyleClass('exf-busy-text');
JS;
    }
}
?>