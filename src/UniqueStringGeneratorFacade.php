<?php

namespace Aaqib\UniqueStringGenerator;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Aaqib\UniqueStringGenerator\Skeleton\SkeletonClass
 */
class UniqueStringGeneratorFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'unique-string-generator';
    }
}
