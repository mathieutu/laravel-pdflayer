<?php

namespace MathieuTu\PDFLayer\Exceptions;

class ErrorReturned
{
    public $code;
    public $type;
    public $info;

    public function __construct($error)
    {
        foreach ($error->error as $key => $value) {
            $this->{$key} = $value;
        }
    }
}
