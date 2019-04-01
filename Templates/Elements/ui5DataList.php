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
class ui5DataList extends ui5DataTable
{
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\ui5DataTable::isUiTable()
     */
    protected function isUiTable()
    {
        return false;    
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\ui5DataTable::isMTable()
     */
    protected function isMTable()
    {
        return true;
    }
}
?>