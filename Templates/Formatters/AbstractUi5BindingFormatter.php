<?php
namespace exface\OpenUI5Template\Templates\Formatters;

use exface\OpenUI5Template\Templates\Interfaces\ui5BindingFormatterInterface;
use exface\Core\Templates\AbstractAjaxTemplate\Interfaces\JsDataTypeFormatterInterface;

abstract class AbstractUi5BindingFormatter implements ui5BindingFormatterInterface
{
    private $jsFormatter = null;
    
    public function __construct(JsDataTypeFormatterInterface $jsFormatter)
    {
        $this->setJsFormmater($jsFormatter);
    }
    
    /**
     * 
     * @param JsDataTypeFormatterInterface $jsFormatter
     * @return \exface\OpenUI5Template\Templates\Formatters\ui5DateFormatter
     */
    protected function setJsFormmater(JsDataTypeFormatterInterface $jsFormatter)
    {
        $this->jsFormatter = $jsFormatter;
        return $this;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Interfaces\ui5BindingFormatterInterface::getJsFormatter()
     */
    public function getJsFormatter()
    {
        return $this->jsFormatter;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Formatters\JsDateFormatter::getDataType()
     */
    public function getDataType()
    {
        return $this->getJsFormatter()->getDataType();
    }
}