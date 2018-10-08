<?php
namespace exface\OpenUI5Template\Templates\Elements;

use exface\Core\Widgets\DialogButton;
use exface\Core\Interfaces\Actions\ActionInterface;
use exface\Core\Templates\AbstractAjaxTemplate\Elements\JqueryButtonTrait;
use exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement;
use exface\Core\Widgets\Button;
use exface\Core\Interfaces\Actions\iShowDialog;

/**
 * Generates jQuery Mobile buttons for ExFace
 * 
 * @method Button getWidget()
 *
 * @author Andrej Kabachnik
 *        
 */
class ui5Button extends ui5AbstractElement
{
    
    use JqueryButtonTrait {
        buildJsInputRefresh as buildJsInputRefreshViaTrait;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Elements\ui5AbstractElement::buildJsConstructor()
     */
    public function buildJsConstructor($oControllerJs = 'oController') : string
    {
        return <<<JS
new sap.m.Button("{$this->getId()}", { 
    {$this->buildJsProperties()}
})
JS;
    }
    
    public function buildJsProperties()
    {
        $widget = $this->getWidget();
        switch ($widget->getVisibility()) {
            case EXF_WIDGET_VISIBILITY_PROMOTED: 
                $visibility = 'type: "Emphasized", layoutData: new sap.m.OverflowToolbarLayoutData({priority: "High"}),'; break;
            case EXF_WIDGET_VISIBILITY_OPTIONAL: 
                $visibility = 'type: "Default", layoutData: new sap.m.OverflowToolbarLayoutData({priority: "AlwaysOverflow"}),'; break;
            case EXF_WIDGET_VISIBILITY_NORMAL: 
            default: $visibility = 'type: "Default",';
            
        }
        
        $handler = $this->buildJsClickViewEventHandlerCall();
        $press = $handler !== '' ? 'press: ' . $handler . ',' : '';
        $icon = $widget->getIcon() && ! $widget->getHideButtonIcon() ? 'icon: "' . $this->getIconSrc($widget->getIcon()) . '",' : '';
        
        $options = '
                    text: "' . $this->getCaption() . '",
                    ' . $icon . '
                    ' . $visibility . '
                    ' . $press . '
                    ' . $this->buildJsPropertyTooltip();
        return $options;
    }
    
    /**
     * Returns the JS to call the press event handler from the view or $default if there is no handler.
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
        $clickJs = $this->buildJsClickFunction();
        return $clickJs ? $this->getController()->buildJsViewEventHandler('press', $this, "function(oEvent){ {$clickJs}; }") : $default;        
    }
    
    /**
     * 
     * @param string $oControllerJsVar
     * @param string $default
     * @return string
     */
    public function buildJsClickEventHandlerCall(string $oControllerJsVar = null, string $default = '') : string
    {
        if ($oControllerJsVar === null) {
            return $this->getController()->buildJsMethodCallFromController('press', $this);
        } else {
            return $this->getController()->buildJsMethodCallFromController('press', $this, '', $oControllerJsVar);
        }
        
    }
    
    /**
     * 
     * @return string
     */
    public function buildJsClickFunctionName()
    {
        return $this->getController()->buildJsMethodName('press', $this);
    }

    protected function buildJsClickShowDialog(ActionInterface $action, AbstractJqueryElement $input_element)
    {
        $widget = $this->getWidget();
        
        /* @var $prefill_link \exface\Core\CommonLogic\WidgetLink */
        $prefill = '';
        if ($prefill_link = $this->getAction()->getPrefillWithDataFromWidgetLink()) {
            if ($prefill_link->getPage()->is($widget->getPage())) {
                $prefill = ", prefill: " . $this->getTemplate()->getElement($prefill_link->getWidget())->buildJsDataGetter($this->getAction());
            }
        }
        
        $output = $this->buildJsRequestDataCollector($action, $input_element);
        $targetWidget = $widget->getAction()->getWidget();
        
        // Build the AJAX request
        $output .= <<<JS
                        {$this->buildJsBusyIconShow()}
                        
						var xhrSettings = {
							type: 'POST',
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
                        this.navTo('{$targetWidget->getPage()->getAliasWithNamespace()}', '{$targetWidget->getId()}', xhrSettings);

JS;
        } else {
            // If it's a dialog, load the view and open the dialog after it has been loaded.
            
            // Note, that the promise resolves _before_ the content of the view is rendered,
            // so opening the dialog right away will make it appear blank. Instead, we use
            // setTimeout() to wait for the view to render completely.
            
            // Also make sure, the view model receives route parameters despite the fact, that
            // it was not actually handled by a router. This is importat as all kinds of on-show
            // handler will use route parameters (e.g. data, prefill, etc.) for their own needs.
            $output .= <<<JS
                        var oComponent = this.getOwnerComponent();
                        var sViewName = oComponent.getViewName('{$targetWidget->getPage()->getAliasWithNamespace()}', '{$targetWidget->getId()}'); 
                        var sViewId = oComponent.getViewId(sViewName);
                        var jqXHR = oComponent._loadView(sViewName, function(){ 
                            var oView = sap.ui.getCore().byId(sViewId);
                            if (oView === undefined) {
                                sap.ui.core.mvc.JSView.create({
                                    id: sViewId,
                                    viewName: "{$this->getTemplate()->getViewName($targetWidget, $this->getController()->getWebapp()->getRootPage())}"
                                }).then(function(oView){
                                    oView.getModel('view').setProperty("/_route", {params: xhrSettings.data});
                                    setTimeout(function() {
                                        var oDialog = oView.getContent()[0];
                                        oDialog.attachAfterClose(function() {
                                            {$this->buildJsInputRefresh($widget, $input_element)}
                                        });
                                        oDialog.open();
                                    });
                                })
                            } else {
                                oView.getContent()[0].open();
                            }
                        }, xhrSettings);
                        
JS;
        }
        
        /*
        $viewId = $this->getTemplate()->getViewName($widget->getAction()->getWidget(), $widget->getPage());
        $viewName = $viewId;
        $output .= <<<JS
						{$this->buildJsBusyIconShow()}
						$.ajax({
							type: 'POST',
							url: '{$this->getAjaxUrl()}',
							dataType: 'html',
							data: {
								action: '{$widget->getActionAlias()}',
								resource: '{$widget->getPage()->getAliasWithNamespace()}',
								element: '{$widget->getId()}',
								data: requestData
								{$prefill}
							},
							success: function(data, textStatus, jqXHR) {
								{$this->buildJsCloseDialog($widget, $input_element)}
								{$this->buildJsBusyIconHide()}
		                       	
                                $('body').append(data);
                                oDialogStack.push({
                                    content: oShell.getContent(),
                                    dialog: sap.ui.view("{$viewId}", {
                                        type:sap.ui.core.mvc.ViewType.JS, 
                                        height: "100%", 
                                        viewName:"{$viewName}"
                                    }),
                                    onClose: function(){
								        {$this->buildJsInputRefresh($widget, $input_element)}
                                    }
                                });
                                {$this->buildJsDialogLoader()}
							},
							error: function(jqXHR, textStatus, errorThrown){
								{$this->buildJsShowError('jqXHR.responseText', 'jqXHR.status + " " + jqXHR.statusText')}
								{$this->buildJsBusyIconHide()}
							}
						});
JS;*/
        
        return $output;
    }
    
    /**
     * 
     * {@inheritdoc}
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Elements\JqueryButtonTrait::buildJsNavigateToPage
     */
    protected function buildJsNavigateToPage(string $pageSelector, string $urlParams = '', AbstractJqueryElement $inputElement) : string
    {
        return <<<JS
						
                        this.navTo("{$pageSelector}", '', {
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
     * @param ui5AbstractElement $input_element
     * @return string
     */
    protected function buildJsInputRefresh(Button $widget, $input_element)
    {
        return <<<JS
    if (sap.ui.getCore().byId("{$this->getId()}") !== undefined) {
        {$this->buildJsInputRefreshViaTrait($widget, $input_element)}
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
        if ($widget->getWidgetType() == 'DialogButton' && $widget->getCloseDialogAfterActionSucceeds()) {
            $dialogElement = $this->getTemplate()->getElement($widget->getDialog());
            if ($dialogElement->isMaximized()) {
                return $this->getController()->buildJsControllerGetter($this) . '.onNavBack();';
            } else {
                return "sap.ui.getCore().byId('{$input_element->getId()}').close();";
            }
        }
        return "";
    }
    
    protected function buildJsDialogLoader()
    {        
        if ($this->opensDialogPage()) {
            return $this->buildJsOpenPage();
        } else {
            return $this->buildJsOpenDialog();
        }
    }
    
    protected function opensDialogPage()
    {
        $action = $this->getAction();
        
        if ($action instanceof iShowDialog) {
            return $this->getTemplate()->getElement($action->getDialogWidget())->isMaximized();
        } 
        
        return false;
    }
    
    protected function buildJsOpenDialog()
    {
        $dialog = $this->getAction()->getWidget();
        return <<<JS

                                oShell.addContent(
                                    oDialogStack[oDialogStack.length-1].dialog
                                );
                                sap.ui.getCore().byId("{$dialog->getId()}").open();

JS;
    }
    
    protected function buildJsOpenPage()
    {
        return <<<JS

                                oShell.removeAllContent()
                                oShell.addContent(
                                    oDialogStack[oDialogStack.length-1].dialog
                                );

JS;
    }
   
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Elements\ui5AbstractElement::buildJsBusyIconShow()
     */
    public function buildJsBusyIconShow($global = false)
    {
        return parent::buildJsBusyIconShow(true);
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Elements\ui5AbstractElement::buildJsBusyIconHide()
     */
    public function buildJsBusyIconHide($global = false)
    {
        return parent::buildJsBusyIconHide(true);
    }
}