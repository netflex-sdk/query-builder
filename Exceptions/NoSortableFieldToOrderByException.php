<?php

namespace Netflex\Query\Exceptions;

use Exception;

class NoSortableFieldToOrderByException extends Exception
{
    public function __construct($message = 'No sortable field to order by')
    {
        parent::__construct($message);
    }
}
