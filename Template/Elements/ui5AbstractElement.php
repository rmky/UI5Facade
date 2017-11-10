<?php
namespace exface\OpenUI5Template\Template\Elements;

use exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement;
use exface\OpenUI5Template\Template\OpenUI5Template;
use exface\Core\Exceptions\Templates\TemplateLogicError;
use exface\Core\CommonLogic\Constants\Icons;

/**
 *
 * @method OpenUI5Template getTemplate()
 *        
 * @author Andrej Kabachnik
 *        
 */
abstract class ui5AbstractElement extends AbstractJqueryElement
{
    private $jsVarName = null;
    
    /**
     * Returns the JS constructor for this element (without the semicolon!): e.g. "new sap.m.Button()" etc.
     * 
     * For complex widgets (e.g. requireing a model) either use an immediately invoked function expression
     * like "function(){...}()" or place your code in generateJs() and the constructor-iife will be built
     * automatically. The name of your resulting JS object MUST be $this->getJsVar() in this case! 
     *
     * @return string
     */
    public function generateJsConstructor()
    {
        return <<<JS
    function(){
        {$this->generateJs()}
        return {$this->getJsVar()};
    }()
JS;
    }
    
    public function getJsVar()
    {
        if (is_null($this->jsVarName)) {
            //throw new TemplateLogicError('No JavaScript instance name specified for OpenUI5 element "' . get_class($this) . '"!');
            $this->jsVarName = 'o' . $this->getId();
        }
        return $this->jsVarName;
    }
    
    protected function setJsVar($jsVarName)
    {
        $this->jsVarName = $jsVarName;
        return $this;
    }
    
    public function buildJsInitOptions()
    {
        return '';
    }

    public function buildJsInlineEditorInit()
    {
        return '';
    }

    public function buildJsBusyIconShow()
    {
        return 'sap.ui.core.BusyIndicator.show(0);';
    }

    public function buildJsBusyIconHide()
    {
        return 'sap.ui.core.BusyIndicator.hide();';
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement::buildJsShowMessageError()
     */
    public function buildJsShowMessageError($message_body_js, $title = null)
    {
        return '
			swal(' . ($title ? $title : '"' . $this->translate('MESSAGE.ERROR_TITLE') . '"') . ', ' . $message_body_js . ', "error");';
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement::buildJsShowError()
     */
    public function buildJsShowError($message_body_js, $title = null)
    {
        return '
			adminLteCreateDialog($("#ajax-dialogs").append(\'<div class="ajax-wrapper"></div>\').children(".ajax-wrapper").last(), "error", ' . ($title ? $title : '"' . $this->translate('MESSAGE.ERROR_TITLE') . '"') . ', ' . $message_body_js . ');
			';
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement::buildJsShowMessageSuccess()
     */
    public function buildJsShowMessageSuccess($message_body_js, $title = null)
    {
        $title = ! is_null($title) ? $title : '"' . $this->translate('MESSAGE.SUCCESS_TITLE') . '"';
        return '$.notify({
					title: ' . $title . ',
					message: ' . $message_body_js . ',
				}, {
					type: "success",
					placement: {
						from: "bottom",
						align: "right"
					},
					animate: {
						enter: "animated fadeInUp",
						exit: "animated fadeOutDown"
					},
					mouse_over: "pause",
					template: "<div data-notify=\"container\" class=\"col-xs-11 col-sm-3 alert alert-{0}\" role=\"alert\">" +
						"<button type=\"button\" aria-hidden=\"true\" class=\"close\" data-notify=\"dismiss\">×</button>" +
						"<div data-notify=\"icon\"></div> " +
						"<div data-notify=\"title\">{1}</div> " +
						"<div data-notify=\"message\">{2}</div>" +
						"<div class=\"progress\" data-notify=\"progressbar\">" +
							"<div class=\"progress-bar progress-bar-{0}\" role=\"progressbar\" aria-valuenow=\"0\" aria-valuemin=\"0\" aria-valuemax=\"100\" style=\"width: 0%;\"></div>" +
						"</div>" +
						"<a href=\"{3}\" target=\"{4}\" data-notify=\"url\"></a>" +
					"</div>"
				});';
    }

    public function escapeString($string)
    {
        return htmlentities($string, ENT_QUOTES);
    }

    /**
     * Returns the css classes, that define the grid width for the element (e.g.
     * col-xs-12, etc.)
     *
     * @return string
     */
    public function getWidthClasses()
    {
        if ($this->getWidget()->getWidth()->isRelative()) {
            switch ($this->getWidget()->getWidth()->getValue()) {
                case 1:
                    $width = 'col-xs-12 col-md-4';
                    break;
                case 2:
                    $width = 'col-xs-12 col-md-8';
                    break;
                case 3:
                case 'max':
                    $width = 'col-xs-12';
            }
        }
        return $width;
    }

    public function prepareData(\exface\Core\Interfaces\DataSheets\DataSheetInterface $data_sheet)
    {
        // apply the formatters
        foreach ($data_sheet->getColumns() as $name => $col) {
            if ($formatter = $col->getFormatter()) {
                $expr = $formatter->toString();
                $function = substr($expr, 1, strpos($expr, '(') - 1);
                // FIXME the next three lines seem obsolete... Not sure though, since everything works fine right now
                $formatter_class_name = 'formatters\'' . $function;
                if (class_exists($class_name)) {
                    $formatter = new $class_name($y);
                }
                // See if the formatter returned more results, than there were rows. If so, it was also performed on
                // the total rows. In this case, we need to slice them off and pass to set_column_values() separately.
                // This only works, because evaluating an expression cannot change the number of data rows! This justifies
                // the assumption, that any values after count_rows() must be total values.
                $vals = $formatter->evaluate($data_sheet, $name);
                if ($data_sheet->countRows() < count($vals)) {
                    $totals = array_slice($vals, $data_sheet->countRows());
                    $vals = array_slice($vals, 0, $data_sheet->countRows());
                }
                $data_sheet->setColumnValues($name, $vals, $totals);
            }
        }
        
        $data = array();
        $data['data'] = array_merge($data_sheet->getRows(), $data_sheet->getTotalsRows());
        $data['recordsFiltered'] = $data_sheet->countRowsAll();
        $data['recordsTotal'] = $data_sheet->countRowsAll();
        $data['footerRows'] = count($data_sheet->getTotalsRows());
        return $data;
    }
    
    public function generateHtml()
    {
        return '';
    }
    
    /**
     * Returns the SAP icon URI (e.g. "sap-icon://edit") for the given icon name
     * 
     * @param string $icon_name
     * @return string
     */
    protected function getIconSrc($icon_name)
    {
        $path = Icons::isDefined($icon_name) ? 'font-awesome/' : '';
        return 'sap-icon://' . $path . $icon_name;
    }
    
    public function buildCssIconClass($icon)
    {
        return $this->getIconSrc($icon);
    }
}
?>