<?php namespace exface\OpenUI5Template\Template;

use exface\AbstractAjaxTemplate\Template\AbstractAjaxTemplate;

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
	
	public function get_request_sorting_direction(){
		if (!$this->request_sorting_direction){
			$this->get_request_sorting_sort_by();
		}
		return $this->request_sorting_direction;
	}
	
	public function get_request_sorting_sort_by(){
		if (!$this->request_sorting_sort_by){
			$sorters = !is_null($this->get_workbench()->get_request_params()['order']) ? $this->get_workbench()->get_request_params()['order'] : array();
			$this->get_workbench()->remove_request_param('order');

			foreach ($sorters as $sorter){
				if (!is_null($sorter['column'])){ //sonst wird nicht nach der 0. Spalte sortiert (0 == false)
					if ($sort_attr = $this->request_columns[$sorter['column']]['data']){
						$this->request_sorting_sort_by .= ($this->request_sorting_sort_by ? ',' : '') . $sort_attr;
						$this->request_sorting_direction .= ($this->request_sorting_direction ? ',' : '') . $sorter['dir'];
					}
				} elseif ($sorter['attribute_alias']){
					$this->request_sorting_sort_by .= ($this->request_sorting_sort_by ? ',' : '') . $sorter['attribute_alias'];
					$this->request_sorting_direction .= ($this->request_sorting_direction ? ',' : '') . $sorter['dir'];
				}
			}
		}
		return $this->request_sorting_sort_by;
	}
}
?>