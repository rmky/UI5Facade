<?php namespace exface\OpenUI5Template\Template;

use exface\AbstractAjaxTemplate\Template\AbstractAjaxTemplate;
use exface\Core\Interfaces\UiPageInterface;
use exface\Core\Interfaces\Exceptions\ErrorExceptionInterface;

class OpenUI5Template extends AbstractAjaxTemplate {
	protected $request_columns = array();
	
	public function init(){
		$this->set_class_prefix('ui5');
		$this->set_class_namespace(__NAMESPACE__);
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \exface\AbstractAjaxTemplate\Template\AbstractAjaxTemplate::process_request($page_id=NULL, $widget_id=NULL, $action_alias=NULL, $disable_error_handling=false)
	 */
	public function process_request($page_id=NULL, $widget_id=NULL, $action_alias=NULL, $disable_error_handling=false){
		$this->request_columns = $this->get_workbench()->get_request_params()['columns'];
		$this->get_workbench()->remove_request_param('columns');
		$this->get_workbench()->remove_request_param('search');
		$this->get_workbench()->remove_request_param('draw');
		$this->get_workbench()->remove_request_param('_');
		return parent::process_request($page_id, $widget_id, $action_alias, $disable_error_handling);
	}
	
	public function get_request_paging_offset(){
		if (!$this->request_paging_offset){
			$this->request_paging_offset = $this->get_workbench()->get_request_params()['start'];
			$this->get_workbench()->remove_request_param('start');
		}
		return $this->request_paging_offset;
	}
	
	public function get_request_paging_rows(){
		if (!$this->request_paging_rows){
			$this->request_paging_rows = $this->get_workbench()->get_request_params()['length'];
			$this->get_workbench()->remove_request_param('length');
		}
		return $this->request_paging_rows;
	}
	
	protected function set_response_from_error(ErrorExceptionInterface $exception, UiPageInterface $page){
		$http_status_code = is_numeric($exception->get_status_code()) ? $exception->get_status_code() : 500;
		if (is_numeric($http_status_code)){
			http_response_code($http_status_code);
		} else {
			http_response_code(500);
		}
		$this->set_response($page->get_workbench()->get_debugger()->print_exception($exception));
		return $this;
	}
}
?>