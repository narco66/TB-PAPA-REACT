<?php

namespace App\Policies;

class ActivitePolicy extends RbmPolicy
{
    protected function ressource(): string
    {
        return 'activites';
    }
}
