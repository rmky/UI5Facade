<?php
namespace exface\UI5Facade\Facades\Formatters;

use exface\UI5Facade\Facades\Interfaces\UI5BindingFormatterInterface;
use exface\Core\Facades\AbstractAjaxFacade\Interfaces\JsDataTypeFormatterInterface;

abstract class AbstractUI5BindingFormatter implements UI5BindingFormatterInterface
{
    private $jsFormatter = null;
    
    public function __construct(JsDataTypeFormatterInterface $jsFormatter)
    {
        $this->setJsFormmater($jsFormatter);
    }
    
    /**
     * 
     * @param JsDataTypeFormatterInterface $jsFormatter
     * @return \exface\UI5Facade\Facades\Formatters\UI5DateFormatter
     */
    protected function setJsFormmater(JsDataTypeFormatterInterface $jsFormatter)
    {
        $this->jsFormatter = $jsFormatter;
        return $this;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Interfaces\UI5BindingFormatterInterface::getJsFormatter()
     */
    public function getJsFormatter()
    {
        return $this->jsFormatter;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Formatters\JsDateFormatter::getDataType()
     */
    public function getDataType()
    {
        return $this->getJsFormatter()->getDataType();
    }
}