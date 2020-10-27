<?php
namespace exface\UI5Facade\Facades\Elements;

use exface\Core\Widgets\DialogButton;
use exface\Core\Interfaces\Actions\ActionInterface;
use exface\Core\Facades\AbstractAjaxFacade\Elements\JqueryButtonTrait;
use exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement;
use exface\Core\Widgets\Button;
use exface\Core\Interfaces\Actions\iShowDialog;
use exface\Core\Interfaces\Actions\iRunFacadeScript;
use exface\Core\DataTypes\StringDataType;
use exface\Core\CommonLogic\Constants\Icons;
use exface\Core\CommonLogic\Constants\Colors;
use exface\Core\Exceptions\Facades\FacadeUnsupportedWidgetPropertyWarning;
use exface\Core\Actions\SendToWidget;

/**
 * Generates sap.m.Button for Button widgets.
 * 
 * ## Custom facade options
 * 
 * - `custom_request_data_script` [string] - allows to process the javascript variable `requestData`
 * right before the action is actually performed. Returning FALSE will prevent the the action!
 * 
 * Example:
 * 
 * ```
 * {
 *  "widget_type": "Button",
 *  "facade_options": {
 *      "exface.UI5Facade.UI5Facade": {
 *          "custom_request_data_script": "console.log(requestData);"
 *      }
 *  }
 * }
 * 
 * ```
 * 
 * @method Button getWidget()
 *
 * @author Andrej Kabachnik
 *        
 */
class UI5Button extends UI5AbstractElement
{
    use JqueryButtonTrait {
        buildJsInputRefresh as buildJsInputRefreshViaTrait;
        buildJsNavigateToPage as buildJsNavigateToPageViaTrait;
        buildJsClickSendToWidget as buildJsClickSendToWidgetViaTrait;
        buildJsRequestDataCollector as buildJsRequestDataCollectorViaTrait;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5AbstractElement::buildJsConstructor()
     */
    public function buildJsConstructor($oControllerJs = 'oController') : string
    {
        // Get the java script required for the action itself
        $action = $this->getAction();
        if ($action) {
            // Actions with facade scripts may contain some helper functions or global variables.
            // Print the here first.
            if ($action && $action instanceof iRunFacadeScript) {
                $this->getController()->addOnInitScript($action->buildScriptHelperFunctions($this->getFacade()));
                foreach ($action->getIncludes($this->getFacade()) as $includePath) {
                    if (mb_stripos($includePath, '.css') !== false) {
                        if (StringDataType::startsWith($includePath, '<link')) {
                            $matches = [];
                            preg_match('/(<link.*href=[\"\'])(.*css)([\"\'].[^>]*)/i', $includePath, $matches);
                            if ($matches[2]) {
                                $includePath = $matches[2];
                            }
                        }
                        $this->getController()->addExternalCss($includePath);                        
                    } else {
                        $moduleName = str_replace([':', '-'], '', $includePath);
                        $moduleName = str_replace('/', '.', $moduleName);
                        $varName = StringDataType::convertCaseUnderscoreToPascal(str_replace(['/', '.', '-', ':'], '_', $includePath));
                        $this->getController()->addExternalModule($moduleName, $includePath, $varName);
                    }
                }
            }
        }
        
        // Register conditional reactions
        $this->registerDisableConditionAtLinkedElement();
        $this->getController()->addOnInitScript($this->buildJsDisableConditionInitializer());
        
        return <<<JS

        new sap.m.Button("{$this->getId()}", { 
            {$this->buildJsProperties()}
        })
        .addStyleClass("{$this->buildCssElementClass()}")
        {$this->buildJsPseudoEventHandlers()}

JS;
    }
    
    public function buildJsProperties()
    {
        $widget = $this->getWidget();
        switch ($widget->getVisibility()) {
            case EXF_WIDGET_VISIBILITY_PROMOTED: 
                $type = 'type: "Emphasized",';
                $layoutData = 'layoutData: new sap.m.OverflowToolbarLayoutData({priority: "High"}),'; break;
            case EXF_WIDGET_VISIBILITY_OPTIONAL: 
                $type = 'type: "Default",';
                $layoutData = 'layoutData: new sap.m.OverflowToolbarLayoutData({priority: "AlwaysOverflow"}),'; break;
            case EXF_WIDGET_VISIBILITY_NORMAL: 
            default: 
                if ($color = $widget->getColor()) {
                    if (Colors::isSemantic($color) === true) {
                        if ($semType = $this->getColorSemanticMap()[$color]) {
                            $type = 'type: "' . $semType . '",';
                        } else {
                            $err = new FacadeUnsupportedWidgetPropertyWarning('Color "' . $color . '" not supported for button widget in UI5 - only semantic colors usable!');
                            $this->getWorkbench()->getLogger()->logException($err);
                            $type = 'type: "Default"';
                        }
                    }
                } else {
                    $type = 'type: "Default",';
                }
            
        }
        
        $handler = $this->buildJsClickViewEventHandlerCall();
        $press = $handler !== '' ? 'press: ' . $handler . ',' : '';
        $icon = $widget->getIcon() && $widget->getShowIcon(true) ? 'icon: "' . $this->getIconSrc($widget->getIcon()) . '",' : '';
        
        $options = <<<JS

    text: "{$this->getCaption()}",
    {$icon}
    {$type}
    {$layoutData}
    {$press}
    {$this->buildJsPropertyTooltip()}
    {$this->buildJsPropertyVisibile()}

JS;
        return $options;
    }
    
    /**
     * Returns the JS to call the press event handler from the view.
     * 
     * Typical output would be `[oController.onPressXXX, oController]`.
     * 
     * Use buildJsClickEventHandlerCall() to get the JS to use in a controller.
     * 
     * Use buildJsClickFunctionName() to the name of the handler within the controller (e.g.
     * just `onPressXXX`);
     * 
     * @see buildJsClickFunctionName()
     * @see buildJsClickEventHandlerCall()
     * 
     * @return string
     */
    public function buildJsClickViewEventHandlerCall(string $default = '') : string
    {
        $controller = $this->getController();
        $clickJs = $this->buildJsClickFunction();
        $controller->addOnEventScript($this, 'press', ($clickJs ? $clickJs : $default));
        return $this->getController()->buildJsEventHandler($this, 'press', true);        
    }
    
    /**
     * 
     * @param string $oControllerJsVar
     * @return string
     */
    public function buildJsClickEventHandlerCall(string $oControllerJsVar = null) : string
    {
        $methodName = $this->getController()->buildJsEventHandlerMethodName('press');
        if ($oControllerJsVar === null) {
            return $this->getController()->buildJsMethodCallFromController($methodName, $this, '');
        } else {
            return $this->getController()->buildJsMethodCallFromController($methodName, $this, '', $oControllerJsVar);
        }
        
    }
    
    /**
     * 
     * @return string
     */
    public function buildJsClickFunctionName()
    {
        $controller = $this->getController();
        return $controller->buildJsMethodName($controller->buildJsEventHandlerMethodName('press'), $this);
    }

    protected function buildJsClickShowDialog(ActionInterface $action, AbstractJqueryElement $input_element)
    {
        $widget = $this->getWidget();
        
        /* @var $prefill_link \exface\Core\CommonLogic\WidgetLink */
        $prefill = '';
        if ($prefill_link = $this->getAction()->getPrefillWithDataFromWidgetLink()) {
            if ($prefill_link->getTargetPageAlias() === null || $prefill_link->getPage()->is($widget->getPage())) {
                $prefill = ", prefill: " . $this->getFacade()->getElement($prefill_link->getTargetWidget())->buildJsDataGetter($this->getAction());
            }
        }
        
        $output = $this->buildJsRequestDataCollector($action, $input_element);
        $targetWidget = $widget->getAction()->getWidget();
        
        // Build the AJAX request
        $output .= <<<JS
                        {$this->buildJsBusyIconShow()}
                        var xhrSettings = {
							data: {
								data: requestData
								{$prefill}
							},
                            success: function(data, textStatus, jqXHR) {
                                {$this->buildJsCloseDialog($widget, $input_element)}
                            },
                            complete: function() {
                                {$this->buildJsBusyIconHide()}
                            }
						};

JS;
        
        // Load the view and open the dialog or page
        if ($this->opensDialogPage()) {
            // If the dialog is actually a UI5 page, just navigate to the respecitve view.
            $output .= <<<JS
                        this.navTo('{$targetWidget->getPage()->getAliasWithNamespace()}', '{$this->getController()->getWebapp()->getWidgetIdForViewControllerName($targetWidget)}', xhrSettings);

JS;
        } else {
            // If it's a dialog, load the view and open the dialog after it has been loaded.
            
            // Note, that the promise resolves _before_ the content of the view is rendered,
            // so opening the dialog right away will make it appear blank. Instead, we use
            // setTimeout() to wait for the view to render completely.
            
            // Also make sure, the view model receives route parameters despite the fact, that
            // it was not actually handled by a router. This is importat as all kinds of on-show
            // handler will use route parameters (e.g. data, prefill, etc.) for their own needs.
            
            // TODO add {$this->buildJsRefreshCascade($widget)} right after {$this->buildJsInputRefresh($widget)}
            // below. However, this produces controller-not-initialized errors in nested dialogs like
            // BDE(DRS) in the MES demo.
            $output .= <<<JS
                        var sViewName = this.getViewName('{$targetWidget->getPage()->getAliasWithNamespace()}', '{$targetWidget->getId()}'); 
                        var sViewId = this.getViewId(sViewName);
                        var oComponent = this.getOwnerComponent();
                        
                        var jqXHR = this._loadView(sViewName, function(){ 
                            var oView = sap.ui.getCore().byId(sViewId);
                            if (oView === undefined) {
                                oComponent.runAsOwner(function(){
                                    return sap.ui.core.mvc.JSView.create({
                                        id: sViewId,
                                        viewName: "{$this->getController()->getWebapp()->getViewName($targetWidget)}"
                                    }).then(function(oView){
                                        var oParentView = {$this->getController()->getView()->buildJsViewGetter($this)};
                                        if (oParentView !== undefined) {
                                            oParentView.addDependent(oView);
                                        }
                                        
                                        if (oView.getModel('view') === undefined) {
                                            oView.setModel(new sap.ui.model.json.JSONModel(), 'view');    
                                        }
                                        oView.getModel('view').setProperty("/_route", {params: xhrSettings.data});
                                        
                                        {$this->buildJsOpenDialogFixMissingEvents('oView', 'oParentView')};
                                        
                                        setTimeout(function() {
                                            var oDialog = oView.getContent()[0];
                                            if (oDialog instanceof sap.m.Dialog) {
                                                oDialog.attachAfterClose(function() {
                                                    {$this->buildJsInputRefresh($widget)}
                                                    {$this->buildJsCloseDialogFixMissingEvents('oView', 'oParentView')}
                                                });
                                                oDialog.open();
                                            } else {
                                                if (oDialog instanceof sap.m.Page || oDialog instanceof sap.m.MessagePage) {
                                                    oDialog.setShowNavButton(false);
                                                }
                                                {$this->buildJsOpenDialogForUnexpectedView('oDialog')};
                                            }
                                        }, 0);
                                    });
                                });
                            } else {
                                oView.getModel('view').setProperty("/_route", {params: xhrSettings.data});
                                var oDialog = oView.getContent()[0];
                                if (oDialog instanceof sap.m.Dialog) {
                                    oDialog.open();
                                } else {
                                    {$this->buildJsOpenDialogForUnexpectedView('oDialog')};
                                }
                            }
                        }, xhrSettings);
                        
JS;
        }
        
        return $output;
    }
    
    /**
     * Views that are never explicitly navigated to are also never rendered/shown by UI5 - this method generates
     * JS code to fire corresponding events manually.
     * 
     * If not used, tables and other data controls are rendered empty in sap.m.Dialog. Strangely they are
     * filled when opening the dialog the second time. Don't know why.
     * 
     * @link https://stackoverflow.com/questions/36792358/access-model-in-js-view-to-render-programmatically
     * 
     * @param string $oViewJs
     * @param string $oParentViewJs
     * @return string
     */
    protected function buildJsOpenDialogFixMissingEvents(string $oViewJs, string $oParentViewJs) : string
    {
        return <<<JS

                                        $oViewJs.fireBeforeRendering();
                                        $oViewJs.fireAfterRendering();

                                        var oNavInfo = {
                            				from: $oParentViewJs || null,
                            				fromId: ($oParentViewJs !== undefined ? $oParentViewJs.getId() : null),
                            				to: $oViewJs,
                            				toId: $oViewJs.getId(),
                            				firstTime: true,
                            				isTo: false,
                            				isBack: false,
                            				isBackToTop: false,
                            				isBackToPage: false,
                            				direction: "initial"
                            			};
                            
                            			oEvent = jQuery.Event("BeforeShow", oNavInfo);
                            			oEvent.srcControl = this;
                            			oEvent.data = {};
                            			oEvent.backData = {};
                            			$oViewJs._handleEvent(oEvent);

                                        oEvent = jQuery.Event("AfterShow", oNavInfo);
                            			oEvent.srcControl = this;
                            			oEvent.data = {};
                            			oEvent.backData = {};
                            			$oViewJs._handleEvent(oEvent);

JS;
    }
    
    protected function buildJsCloseDialogFixMissingEvents(string $oViewJs, string $oParentViewJs) : string
    {
        return <<<JS
        
                                        var oNavInfo = {
                            				from: $oViewJs,
                            				fromId: $oViewJs,
                            				to: $oParentViewJs || null,
                            				toId: ($oParentViewJs !== undefined ? $oParentViewJs.getId() : null),
                            				firstTime: true,
                            				isTo: false,
                            				isBack: false,
                            				isBackToTop: false,
                            				isBackToPage: false,
                            				direction: "initial"
                            			};
                            			
                            			oEvent = jQuery.Event("BeforeHide", oNavInfo);
                            			oEvent.srcControl = this;
                            			oEvent.data = {};
                            			oEvent.backData = {};
                            			$oViewJs._handleEvent(oEvent);
                            			
                                        oEvent = jQuery.Event("AfterHide", oNavInfo);
                            			oEvent.srcControl = this;
                            			oEvent.data = {};
                            			oEvent.backData = {};
                            			$oViewJs._handleEvent(oEvent);
                            			
JS;
    }
    
    protected function buildJsOpenDialogForUnexpectedView(string $oViewContent) : string
    {
        return <<<JS

                                                var oWrapper = new sap.m.Dialog({
                                                    stretch: true,
                                                    verticalScrolling: false,
                                                    title: "{$this->getCaption()}",
                                        			content: [ {$oViewContent} ],
                                                    buttons: [
                                                        new sap.m.Button({
                                                            icon: "{$this->getIconSrc(Icons::CLOSE)}",
                                                            text: "{$this->getWorkbench()->getCoreApp()->getTranslator()->translate('WIDGET.DIALOG.CLOSE_BUTTON_CAPTION')}",
                                                            press: function() {oWrapper.close();},
                                                        })
                                                    ]
                                        		}).open();

JS;
    }
    
    /**
     * 
     * {@inheritdoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\JqueryButtonTrait::buildJsNavigateToPage
     */
    protected function buildJsNavigateToPage(string $pageSelector, string $urlParams = '', AbstractJqueryElement $inputElement, bool $newWindow = false) : string
    {
        if ($newWindow === true) {
            return <<<JS
            
                        {$this->buildJsNavigateToPageViaTrait($pageSelector, $urlParams, $inputElement, $newWindow)}
                        {$inputElement->buildJsBusyIconHide()}
JS;
        }
        
        return <<<JS
						var sUrlParams = '{$urlParams}';
                        var oUrlParams = {};
                        var vars = sUrlParams.split('&');
                    	for (var i = 0; i < vars.length; i++) {
                    		var pair = vars[i].split('=');
                            if (pair[0]) {
                                var val = decodeURIComponent(pair[1]);
                                if (val.substring(0, 1) === '{') {
                                    try {
                                        val = JSON.parse(val);
                                    } catch (error) {
                                        // Do nothing, val will remain a string
                                    }
                                }
            		            oUrlParams[pair[0]] = val;
                            }
                    	} 
                        this.navTo("{$pageSelector}", '', {
                            data: oUrlParams,
                            success: function(){ 
                                {$inputElement->buildJsBusyIconHide()} 
                            },
                            error: function(){ 
                                {$inputElement->buildJsBusyIconHide()} 
                            }
                        });

JS;
    }
    
    /**
     * 
     * @param Button $widget
     * @param UI5AbstractElement $input_element
     * @return string
     */
    protected function buildJsInputRefresh(Button $widget)
    {
        return <<<JS
    if (sap.ui.getCore().byId("{$this->getId()}") !== undefined) {
        {$this->buildJsInputRefreshViaTrait($widget)}
    }

JS;
    }

    /**
     * Returns javascript code with global variables and functions needed for certain button types
     */
    protected function buildJsGlobals()
    {
        $output = '';
        /*
         * Commented out because moved to generate_js()
         * // If the button reacts to any hotkey, we need to declare a global variable to collect keys pressed
         * if ($this->getWidget()->getHotkey() == 'any'){
         * $output .= 'var exfHotkeys = [];';
         * }
         */
        return $output;
    }
    
    protected function buildJsCloseDialog($widget, $input_element)
    {
        if ($widget instanceof DialogButton && $widget->getCloseDialogAfterActionSucceeds()) {
            return $this->getFacade()->getElement($widget->getDialog())->buildJsCloseDialog();
        }
        return "";
    }
    
    protected function opensDialogPage()
    {
        $action = $this->getAction();
        
        if ($action instanceof iShowDialog) {
            return $this->getFacade()->getElement($action->getDialogWidget())->isMaximized();
        } 
        
        return false;
    }
   
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5AbstractElement::buildJsBusyIconShow()
     */
    public function buildJsBusyIconShow($global = false)
    {
        return parent::buildJsBusyIconShow(true);
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5AbstractElement::buildJsBusyIconHide()
     */
    public function buildJsBusyIconHide($global = false)
    {
        return parent::buildJsBusyIconHide(true);
    }
    
    protected function buildJsClickCallServerAction(ActionInterface $action, AbstractJqueryElement $input_element)
    {
        $widget = $this->getWidget();
        
        $onModelLoadedJs = <<<JS

								
								{$this->buildJsBusyIconHide()}
                                {$this->buildJsCloseDialog($widget, $input_element)}
								{$this->buildJsInputRefresh($widget)}
		                       	{$this->buildJsBusyIconHide()}
		                       	$('#{$this->getId()}').trigger('{$action->getAliasWithNamespace()}.action.performed', [requestData, '{$input_element->getId()}']);
								{$this->buildJsOnSuccessScript()}

                                if (oResultModel.getProperty('/success') !== undefined || oResultModel.getProperty('/undoURL')){
		                       		{$this->buildJsShowMessageSuccess("oResultModel.getProperty('/success') + (response.undoable ? ' <a href=\"" . $this->buildJsUndoUrl($action, $input_element) . "\" style=\"display:block; float:right;\">UNDO</a>' : '')")}
									/* TODO redirects do not work in UI5 that easily. Additionally server adapters don't return any response variable.*/
                                    var sRedirect;
                                    if((sRedirect = oResultModel.getProperty('/redirect')) !== undefined){
                                        switch (true) {
										    case sRedirect.indexOf('target=_blank') !== -1:
											    window.open(sRedirect.replace('target=_blank',''), '_newtab');
                                                break;
                                            case sRedirect === '':
                                                {$this->getFacade()->getElement($widget->getPage()->getWidgetRoot())->buildJsBusyIconShow()}
                                                window.location.reload();
                                                break;
                                            default: 
                                                {$this->getFacade()->getElement($widget->getPage()->getWidgetRoot())->buildJsBusyIconShow()}
                                                window.location.href = sRedirect;
										}
                   					}
								}
JS;
		                       		
   		return <<<JS

                var fnRequest = function() {
                    if ({$input_element->buildJsValidator()}) {
                        {$this->buildJsBusyIconShow()}
                        var oResultModel = new sap.ui.model.json.JSONModel();
                        var params = {
    							{$this->buildJsRequestCommonParams($widget, $action)}
    							data: requestData
    					}
                        {$this->getServerAdapter()->buildJsServerRequest($action, 'oResultModel', 'params', $onModelLoadedJs, $this->buildJsBusyIconHide())}	    
    				} else {
    					{$input_element->buildJsValidationError()}
    				}
                };

                {$this->buildJsRequestDataCollector($action, $input_element)}

                fnRequest();
				
JS;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildJsEnabler()
     */
    public function buildJsEnabler()
    {
        return "sap.ui.getCore().byId('{$this->getId()}').setEnabled(true)";
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildJsDisabler()
     */
    public function buildJsDisabler()
    {
        return "sap.ui.getCore().byId('{$this->getId()}').setEnabled(false)";
    }
    
    protected function getColorSemanticMap() : array
    {
        $semCols = [];
        foreach (Colors::getSemanticColors() as $semCol) {
            switch ($semCol) {
                case Colors::SEMANTIC_ERROR: $btnType = 'Reject'; break;
                case Colors::SEMANTIC_WARNING: $btnType = 'Reject'; break;
                case Colors::SEMANTIC_OK: $btnType = 'Accept'; break;
            }
            $semCols[$semCol] = $btnType;
        }
        return $semCols;
    }
    
    /**
     * 
     * @param SendToWidget $action
     * @param AbstractJqueryElement $input_element
     * @return string
     */
    protected function buildJsClickSendToWidget(SendToWidget $action, AbstractJqueryElement $input_element) : string
    {
        $this->getFacade()->createController($this->getFacade()->getElement($this->getWidget()->getPage()->getWidgetRoot()));
        return $this->buildJsClickSendToWidgetViaTrait($action, $input_element);
    }
    
    /**
     * 
     * @see JqueryButtonTrait::buildJsRequestDataCollector()
     */
    protected function buildJsRequestDataCollector(ActionInterface $action, AbstractJqueryElement $input_element)
    {
        $js = $this->buildJsRequestDataCollectorViaTrait($action, $input_element);
        
        if ($facadeOptUxon = $this->getWidget()->getFacadeOptions($this->getFacade())) {
            if ($facadeOptUxon->hasProperty('custom_request_data_script')) {
                $js .= $facadeOptUxon->getProperty('custom_request_data_script');
            }
        }
        
        return $js;
    }
}