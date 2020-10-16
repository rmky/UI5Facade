<?php
namespace exface\UI5Facade\Facades\Elements;

use exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement;
use exface\Core\CommonLogic\Constants\Icons;
use exface\Core\Interfaces\Widgets\iHaveValue;
use exface\Core\DataTypes\StringDataType;
use exface\UI5Facade\Facades\Interfaces\UI5ControllerInterface;
use exface\Core\Exceptions\LogicException;
use exface\UI5Facade\Facades\Interfaces\UI5ServerAdapterInterface;
use exface\Core\Interfaces\Widgets\iCanPreloadData;
use exface\UI5Facade\Facades\Elements\ServerAdapters\PreloadServerAdapter;
use exface\UI5Facade\Facades\Interfaces\UI5ViewInterface;
use exface\Core\Interfaces\Widgets\iHaveIcon;
use exface\UI5Facade\Facades\Interfaces\UI5ModelInterface;

/**
 *
 * @method \exface\UI5Facade\Facades\UI5Facade getFacade()
 *        
 * @author Andrej Kabachnik
 *        
 */
abstract class UI5AbstractElement extends AbstractJqueryElement
{
    const EVENT_NAME_CHANGE = 'change';
    
    private $jsVarName = null;
    
    private $useWidgetId = true;
    
    private $controller = null;
    
    private $layoutData = null;
    
    /**
     * 
     * @var array [ event_name => [code, code, ...] ]
     */
    private $pseudo_events = [];
    
    /**
     * Returns the JS constructor for this element (without the semicolon!): e.g. "new sap.m.Button()" etc.
     * 
     * For complex widgets (e.g. requireing a model, init-scripts, etc.) you can use the following approaches:
     * - create custom controller methods via $this->getController()->add...
     * - add code to the onInit-method of the controller via $this->getController()->addOnInitScript()  
     * - use an immediately invoked function expression like "function(){...}()" as constructor (not recommended!)
     * 
     * @see getController()
     *
     * @return string
     */
    public function buildJsConstructor($oControllerJs = 'oController') : string
    {
        return '';
    }
    
    /**
     * Returns a unique variable name for this element, that meets UI5 conventions: e.g. "oDataTableDataToolbarDataButton02".
     * 
     * @return string
     */
    public function buildJsVarName() : string
    {
        if (is_null($this->jsVarName)) {
            $this->jsVarName = 'o' . StringDataType::convertCaseUnderscoreToPascal($this->getId());
        }
        return $this->jsVarName;
    }
    
    protected function setJsVar($jsVarName)
    {
        $this->jsVarName = $jsVarName;
        return $this;
    }
    
    public function buildJsProperties()
    {
        return <<<JS

        {$this->buildJsPropertyVisibile()}

JS;
    }

    public function buildJsInlineEditorInit()
    {
        return '';
    }

    public function buildJsBusyIconShow($global = false)
    {
        if ($global) {
            return 'sap.ui.core.BusyIndicator.show(0);';
        } else {
            return 'sap.ui.getCore().byId("' . $this->getId() . '").setBusyIndicatorDelay(0).setBusy(true);';
        }
    }

    public function buildJsBusyIconHide($global = false)
    {
        if ($global) {
            return 'sap.ui.core.BusyIndicator.hide();';
        } else {
            return 'sap.ui.getCore().byId("' . $this->getId() . '").setBusy(false);';
        }
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildJsShowMessageError()
     */
    public function buildJsShowMessageError($message_body_js, $title = null)
    {
        $title = ($title ? $title : '"' . $this->translate('MESSAGE.ERROR_TITLE') . '"');
        return <<<JS
                var dialog = new sap.m.Dialog({
    				title: {$title},
    				type: 'Message',
    				state: 'Error',
    				content: new sap.m.Text({
    					text: {$message_body_js}
    				}),
    				beginButton: new sap.m.Button({
    					text: 'OK',
    					press: function () {
    						dialog.close();
    					}
    				}),
    				afterClose: function() {
    					dialog.destroy();
    				}
    			});
    
    			dialog.open();
JS;
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildJsShowError()
     */
    public function buildJsShowError($message_body_js, $title_js = null)
    {
        $title_js = $title_js ? $title_js : '"' . $this->translate('MESSAGE.ERROR_TITLE') . '"';
        return $this->getController()->buildJsComponentGetter() . ".showErrorDialog({$message_body_js}, {$title_js});";
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildJsShowMessageSuccess()
     */
    public function buildJsShowMessageSuccess($message_body_js, $title = null)
    {
        return <<<JS

        sap.m.MessageToast.show(function(){
            var tmp = document.createElement("DIV");
            tmp.innerHTML = {$message_body_js};
            return tmp.textContent || tmp.innerText || "";
        }());
JS;
    }

    public function escapeString($string)
    {
        return htmlentities($string, ENT_QUOTES);
    }
    
    /**
     * Returns the SAP icon URI (e.g. "sap-icon://edit") for the given icon name
     * 
     * @param string $icon_name
     * @return string
     */
    protected function getIconSrc($icon_name)
    {
        $widget = $this->getWidget();
        if ($widget instanceof iHaveIcon) {
            $iconSet = $widget->getIconSet();
        }
        
        switch (true) {
            case Icons::isDefined($icon_name) === true:
            case $iconSet === 'fa':
                $path = 'sap-icon://font-awesome/';
                break;
            case StringDataType::startsWith($icon_name, 'sap-icon://', false):
                $path = '';
                break;
            case StringDataType::startsWith($iconSet, 'sap-icon://', false):
                $path = $iconSet;
                break;
        }
        return $path . $icon_name;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildCssIconClass($icon)
     */
    public function buildCssIconClass($icon)
    {
        return $icon ? $this->getIconSrc($icon) : '';
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildJsValueGetter()
     */
    public function buildJsValueGetter()
    {
        $widget = $this->getWidget();
        if ($widget instanceof iHaveValue) {
            return "sap.ui.getCore().byId('{$this->getId()}').{$this->buildJsValueGetterMethod()}";
        } else {
            return '""';
        }
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildJsValueGetterMethod()
     */
    public function buildJsValueGetterMethod()
    {
        return "getValue()";
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildJsValueSetter()
     */
    public function buildJsValueSetter($valueJs)
    {
        $widget = $this->getWidget();
        if ($widget instanceof iHaveValue) {
            return "sap.ui.getCore().byId('{$this->getId()}').{$this->buildJsValueSetterMethod($valueJs)}";
        } else {
            return '""';
        }
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildJsValueSetterMethod()
     */
    public function buildJsValueSetterMethod($valueJs)
    {
        return "setValue(" . $valueJs . ")";
    }
        
    /**
     * 
     * @param mixed $text
     * @return string
     */
    protected function escapeJsTextValue($text)
    {
        if ($text === null || $text === '') {
            return $text;
        }
        
        // json_encode() escapes " and ' really well
        $escaped = json_encode(str_replace(['\u'], ['&#92;u'], $text));
        // however, the result is enclosed in double quotes if it's a string. If so, we
        // need to remove the first an last character (the quotes). Note: trim() won't
        // work here because if the $text was already beginning or ending with " it will
        // get trimmed off too!
        if (substr($escaped, 0, 1) === '"') {
            $escaped = substr($escaped, 1, -1);   
        }
        
        return $escaped;
    }
    
    /**
     * Returns "visible: false," if the element is not visible (e.g. widget has visibility=hidden).
     * 
     * NOTE: The returned string is either empty or ends with a comma
     * 
     * @return string
     */
    protected function buildJsPropertyVisibile()
    {
        if (! $this->isVisible()) {
            return 'visible: false, ';
        }
        return '';
    }
    
    /**
     * Returns TRUE if the element is visible and FALSE otherwise
     * @return boolean
     */
    protected function isVisible()
    {
        return ! $this->getWidget()->isHidden();
    }
    
    /**
     * Returns the JS code adding pseudo event handlers to a control: e.g. .addEventDelegate(...).
     * 
     * NOTE: the string is either empty or starts with a leading dot and ends with a closing
     * brace (no semicolon!)
     * 
     * @see addPseudoEventHandler()
     * 
     * @return string
     */
    protected function buildJsPseudoEventHandlers()
    {
        $js = '';
        foreach ($this->pseudo_events as $event => $code_array) {
            $code = implode("\n", $code_array);
            $js .= <<<JS
            
            {$event}: function(oEvent) {
                {$code}
            },
            
JS;
        }
        
        if ($js) {
            $js = <<<JS
            
        .addEventDelegate({
            {$js}
        })
        
JS;
        }
        
        return $js;
    }
    
    /**
     * Registers the given JS code to be executed on a specified pseudo event for this control.
     * 
     * Note: the event fired will be available via the oEvent javascript variable.
     * 
     * Example: UI5Input::addPseudoEventHandler('onsapenter', 'console.log("Enter pressed:", oEvent)')
     * 
     * @link https://openui5.hana.ondemand.com/#/api/jQuery.sap.PseudoEvents
     * 
     * @param string $event_name
     * @param string $js
     * @return \exface\UI5Facade\Facades\Elements\UI5AbstractElement
     */
    public function addPseudoEventHandler($event_name, $js)
    {
        $this->pseudo_events[$event_name][] = $js;
        return $this;
    }
    
    protected function buildJsPropertyTooltip()
    {
        $widget = $this->getWidget();
        return 'tooltip: "' . $this->escapeJsTextValue($widget->getHint() ? $widget->getHint() : $widget->getCaption()) . '",';
    }
    
    public function setUseWidgetId($true_or_false)
    {
        $this->useWidgetId = $true_or_false;
        return $this;
    }
    
    public function getUseWidgetId()
    {
        return $this->useWidgetId;
    }
    
    /**
     * {@inheritDoc}
     * 
     * Since pages are loaded asynchronously in UI5, we need to make sure, the element ids include
     * page ids to avoid conflicts.
     * 
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::getId()
     */
    public function getId()
    {
        if ($this->getUseWidgetId() === false) {
            return '';
        }
        
        return substr($this->getWidget()->getPage()->getUid(), 1) . '__' . parent::getId();
    }
    
    /**
     * UI5-Elements do not have a general buildJs() method, because there is no place in the controller
     * where it's global variables and methods can be defined in "regular" JS syntax. E.g. instead of
     * "function ... () {}" a controller method must be defined as "...: function(){}", etc.
     * 
     * Making this method final makes sure, no element makes use of it unintentionally (e.g.
     * via trait).
     * 
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildJs()
     */
    public final function buildJs()
    {
        return '';
    }
    
    /**
     * UI5-Elements do not produce HTML, but rather views in JS/XML.
     * 
     * Making this method final makes sure, no element makes use of it unintentionally (e.g.
     * via trait). 
     * 
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildHtml()
     */
    public final function buildHtml()
    {
        return '';
    }
    
    public function getController() : UI5ControllerInterface
    {
        if ($this->controller === null) {
            if ($this->getWidget()->hasParent()) {
                return $this->getFacade()->getElement($this->getWidget()->getParent())->getController();
            } else {
                throw new LogicException('No controller was initialized for page "' . $this->getWidget()->getPage()->getAliasWithNamespace() . '"!');
            }
        }
        return $this->controller;
    }
    
    public function getView() : UI5ViewInterface
    {
        return $this->getController()->getView();
    }
    
    public function setController(UI5ControllerInterface $controller) : UI5AbstractElement
    {
        if (! $this->controller === null) {
            throw new LogicException('Cannot change the controller of a UI5 element after it had been set initially!');
        }
        $this->controller = $controller;
        return $this;
    }
    
    public final function buildHtmlHeadTags()
    {
        return [];
    }
    
    public function addOnBindingChangeScript(string $bindingName, string $script, string $oEventJs = 'oEvent') : UI5AbstractElement
    {
        $handler = <<<JS

                sap.ui.getCore().byId("{$this->getId()}")
                    .getBinding("{$bindingName}")
                    .attachChange(function({$oEventJs}){
                    {$script}
                });
JS;
        $this->getController()->addOnInitScript($handler);
        return $this;
    }

    /**
     *
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::addOnChangeScript()
     */
    public function addOnChangeScript($string)
    {
        parent::addOnChangeScript($string);
        $this->getController()->addOnEventScript($this, self::EVENT_NAME_CHANGE, $string);
        return $this;
    }
    
    public function getServerAdapter() : UI5ServerAdapterInterface
    {
        $widget = $this->getWidget();
        
        $adapterclass = $this->getFacade()->getConfig()->getOption("DEFAULT_SERVER_ADAPTER_CLASS");
        $adapter = new $adapterclass($this);
        
        if ($widget instanceof iCanPreloadData && $widget->isPreloadDataEnabled()) {
            $adapter = new PreloadServerAdapter($this, $adapter);
        }
        return $adapter;
    }
    
    /**
     * Allows to add pre-/post-processing to event handler scripts.
     * 
     * The controller collects all event handlers via $controller->addOnEventScript() while the view
     * is rendered. After all elements are were generated, the controller generates event handler
     * methods for every registered event by calling buildJsOnEventScript() on the trigger element
     * of this event. 
     * 
     * By overriding this method, you can add additional code to certain events - e.g. a filter, that
     * only actually executes the handlers on certain conditions or stop event propagation once all
     * handlers are executed to prevent the UI5 logic from further handling the event. 
     * 
     * This method is called after all handler script were collected by the controller. These handler 
     * scripts are passed to this method as $scriptJs. By default the method simply returns $scriptJs 
     * without changes. Refer to the UI5DataTables class or the included UI5DataElementTrait for usage
     * examples.
     * 
     * @param string $eventName
     * @param string $scriptJs
     * @param string $oEventJs
     * @return string
     */
    public function buildJsOnEventScript(string $eventName, string $scriptJs, string $oEventJs) : string 
    {
        return $scriptJs;
    }
    
    /**
     * 
     * @return string
     */
    public function buildJsPropertyLayoutData() : string
    {
        if ($this->layoutData === null) {
            return '';
        } else {
            return "layoutData: [{$this->layoutData}],";
        }
    }
    
    /**
     * Sets the layout data for the control: e.g. "new sap.m.FlexItemData({growFactor: 1})".
     * 
     * @param string $layoutDataConstructorJs
     * @return UI5AbstractElement
     */
    public function setLayoutData(string $layoutDataConstructorJs) : UI5AbstractElement
    {
        $this->layoutData = $layoutDataConstructorJs;
        return $this;
    }
    
    /**
     * 
     * @param UI5ControllerInterface $controller
     * @return UI5AbstractElement
     */
    public function registerExternalModules(UI5ControllerInterface $controller) : UI5AbstractElement
    {
        return $this;
    }
}
?>