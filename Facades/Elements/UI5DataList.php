<?php
namespace exface\UI5Facade\Facades\Elements;

use exface\Core\Widgets\DataList;

/**
 *
 * @method DataList getWidget()
 *        
 * @author Andrej Kabachnik
 *        
 */
class UI5DataList extends UI5DataTable
{
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5DataTable::isUiTable()
     */
    protected function isUiTable()
    {
        return false;    
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5DataTable::isMTable()
     */
    protected function isMTable()
    {
        return true;
    }
}
?>