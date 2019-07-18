<?php
namespace exface\UI5Facade\Exceptions;

use exface\Core\Exceptions\UnexpectedValueException;

/**
 * Exception thrown if the webapp router cannot match any route to the request.
 * 
 * @author Andrej Kabachnik
 *
 */
class UI5RouteInvalidException extends UnexpectedValueException
{
    
}