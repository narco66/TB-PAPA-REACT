<?php

namespace App\Policies;

class AxePolicy extends RbmPolicy
{
    protected function ressource(): string
    {
        return 'axes';
    }
}
