<?php
namespace exface\UI5Facade\Facades\Templates;

use exface\Core\DataTypes\StringDataType;
use exface\Core\Facades\AbstractAjaxFacade\Templates\FacadePageTemplateRenderer;

/**
 * UI5Facade specific HTML template renderer working with [#placeholders#].
 *
 * ## additional Supported Placeholders
 *
 * - `[#ui5:<option>#]` - replaced by option specific value
 *
 * @author Ralf Mulansky
 *
 */
class UI5FacadePageTemplateRenderer extends FacadePageTemplateRenderer
{
    protected function renderPlaceholderValue(string $placeholder) : string
    {
        if (StringDataType::startsWith($placeholder, 'ui5:') === true) {
            $option = StringDataType::substringAfter($placeholder, 'ui5:');       
            $val = $this->renderPlaceholderUI5Option($option);
            return $val;
        }
        
        return parent::renderPlaceholderValue($placeholder);
    }
    
    protected function renderPlaceholderUI5Option(string $option) : string
    {
        switch ($option) {
            case 'density':
                if ($this->getFacade()->getContentDensity() === 'cozy') {
                    $val = 'sapUiBody';
                } else {
                    $val = 'sapUiBody sapUiSizeCompact';
                }
                break;
            default:
                $val = '';
        }
        return $val;
    }
}