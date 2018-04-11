<?php
namespace exface\OpenUI5Template\Exceptions;

use exface\Core\Exceptions\UnexpectedValueException;

/**
 * Exception thrown if the webapp router cannot match any route to the request.
 * 
 * @author Andrej Kabachnik
 *
 */
class Ui5RouteInvalidException extends UnexpectedValueException
{
    
}