<?php

namespace App\Policies;

class TachePolicy extends RbmPolicy
{
    protected function ressource(): string
    {
        return 'taches';
    }
}
