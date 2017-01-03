<?php
namespace MathieuTu\PDFLayer\Facades;

use Illuminate\Support\Facades\Facade as IlluminateFacade;

class PDF extends IlluminateFacade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'pdflayer';
    }
}