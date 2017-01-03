<?php

namespace MathieuTu\PDFLayer\Exceptions;

use Exception;

class PDFLayerException extends Exception
{
    public function __construct($error)
    {
        $error = new ErrorReturned($error);
        parent::__construct($error->info, $error->code);
    }
}
