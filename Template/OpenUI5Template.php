<?php
namespace exface\OpenUI5Template\Template;

use exface\Core\Templates\AbstractAjaxTemplate\AbstractAjaxTemplate;
use exface\Core\Interfaces\Model\UiPageInterface;
use exface\Core\Interfaces\Exceptions\ErrorExceptionInterface;
use exface\OpenUI5Template\Template\Elements\ui5AbstractElement;

/**
 * 
 * @method ui5AbstractElement getElement()
 * 
 * @author Andrej Kabachnik
 *
 */
class OpenUI5Template extends AbstractAjaxTemplate
{

    protected $request_columns = array();

    public function init()
    {
        $this->setClassPrefix('ui5');
        $this->setClassNamespace(__NAMESPACE__);
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\Core\Templates\AbstractAjaxTemplate\AbstractAjaxTemplate::processRequest($page_id=NULL, $widget_id=NULL, $action_alias=NULL, $disable_error_handling=false)
     */
    public function processRequest($page_id = NULL, $widget_id = NULL, $action_alias = NULL, $disable_error_handling = false)
    {
        $this->request_columns = $this->getWorkbench()->getRequestParams()['columns'];
        $this->getWorkbench()->removeRequestParam('columns');
        $this->getWorkbench()->removeRequestParam('search');
        $this->getWorkbench()->removeRequestParam('draw');
        $this->getWorkbench()->removeRequestParam('_');
        return parent::processRequest($page_id, $widget_id, $action_alias, $disable_error_handling);
    }

    public function getRequestPagingOffset()
    {
        if (! $this->request_paging_offset) {
            $this->request_paging_offset = $this->getWorkbench()->getRequestParams()['start'];
            $this->getWorkbench()->removeRequestParam('start');
        }
        return $this->request_paging_offset;
    }

    public function getRequestPagingRows()
    {
        if (! $this->request_paging_rows) {
            $this->request_paging_rows = $this->getWorkbench()->getRequestParams()['length'];
            $this->getWorkbench()->removeRequestParam('length');
        }
        return $this->request_paging_rows;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Templates\AbstractAjaxTemplate\AbstractAjaxTemplate::generateJs()
     */
    public function generateJs(\exface\Core\Widgets\AbstractWidget $widget)
    {
        $instance = $this->getElement($widget);
        $js = $instance->generateJs();
        return $js . "\n" . $instance->generateJsView();
    }
}
?>