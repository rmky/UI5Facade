<?php namespace exface\OpenUI5Template\Template\Elements;

use exface\AbstractAjaxTemplate\Template\Elements\AbstractJqueryElement;
use exface\OpenUI5Template\Template\OpenUI5Template;

/**
 * 
 * @method OpenUI5Template get_template()
 * 
 * @author Andrej Kabachnik
 *
 */
abstract class ui5AbstractElement extends AbstractJqueryElement {
	
	public function build_js_init_options(){
		return '';
	}
	
	public function build_js_inline_editor_init(){
		return '';
	}
	
	public function build_js_busy_icon_show(){
		return '$("#' . $this->get_id() . '").parents(".box").append($(\'<div class="overlay"><i class="fa fa-refresh fa-spin"></i></div>\'));';
	}
	
	public function build_js_busy_icon_hide(){
		return '$("#' . $this->get_id() . '").parents(".box").find(".overlay").remove();';
	}
	
	/**
	 *
	 * {@inheritDoc}
	 * @see \exface\AbstractAjaxTemplate\Template\Elements\AbstractJqueryElement::build_js_show_message_error()
	 */
	public function build_js_show_message_error($message_body_js, $title = null){
		return '
			swal(' . ($title ? $title : '"' . $this->translate('MESSAGE.ERROR_TITLE') . '"') . ', ' . $message_body_js . ', "error");';
	}
	
	/**
	 *
	 * {@inheritDoc}
	 * @see \exface\AbstractAjaxTemplate\Template\Elements\AbstractJqueryElement::build_js_show_error()
	 */
	public function build_js_show_error($message_body_js, $title = null){
		return '
			adminLteCreateDialog($("#ajax-dialogs").append(\'<div class="ajax-wrapper"></div>\').children(".ajax-wrapper").last(), "error", ' . ($title ? $title : '"' . $this->translate('MESSAGE.ERROR_TITLE') . '"') . ', ' . $message_body_js . ');
			';
	}
	
	/**
	 *
	 * {@inheritDoc}
	 * @see \exface\AbstractAjaxTemplate\Template\Elements\AbstractJqueryElement::build_js_show_message_success()
	 */
	public function build_js_show_message_success($message_body_js, $title = null){
		$title = !is_null($title) ? $title : '"' . $this->translate('MESSAGE.SUCCESS_TITLE') . '"';
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
	
	public function escape_string($string){
		return htmlentities($string, ENT_QUOTES);
	}
	
	/**
	 * Returns the css classes, that define the grid width for the element (e.g. col-xs-12, etc.)
	 * @return string
	 */
	public function get_width_classes(){
		if ($this->get_widget()->get_width()->is_relative()){
			switch ($this->get_widget()->get_width()->get_value()){
				case 1: $width = 'col-xs-12 col-md-4'; break;
				case 2: $width = 'col-xs-12 col-md-8'; break;
				case 3: case 'max': $width = 'col-xs-12';
			}
		} 
		return $width;
	}
	
	public function prepare_data(\exface\Core\Interfaces\DataSheets\DataSheetInterface $data_sheet){
		// apply the formatters
		foreach ($data_sheet->get_columns() as $name => $col){
			if ($formatter = $col->get_formatter()) {
				$expr = $formatter->to_string();
				$function = substr($expr, 1, strpos($expr, '(')-1);
				// FIXME the next three lines seem obsolete... Not sure though, since everything works fine right now
				$formatter_class_name = 'formatters\'' . $function;
				if (class_exists($class_name)){
					$formatter = new $class_name($y);
				}
				// See if the formatter returned more results, than there were rows. If so, it was also performed on
				// the total rows. In this case, we need to slice them off and pass to set_column_values() separately.
				// This only works, because evaluating an expression cannot change the number of data rows! This justifies
				// the assumption, that any values after count_rows() must be total values.
				$vals = $formatter->evaluate($data_sheet, $name);
				if ($data_sheet->count_rows() < count($vals)) {
					$totals = array_slice($vals, $data_sheet->count_rows());
					$vals = array_slice($vals, 0, $data_sheet->count_rows());
				}
				$data_sheet->set_column_values($name, $vals, $totals);
			}
		}
		
		$data = array();
		$data['data'] = $data_sheet->get_rows();
		$data['recordsFiltered'] = $data_sheet->count_rows_all();
		$data['recordsTotal'] = $data_sheet->count_rows_all();
		$data['footer'] = $data_sheet->get_totals_rows();
		return $data;
	} 
}
?>