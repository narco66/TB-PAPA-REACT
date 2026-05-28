<?php

namespace App\Policies;

class ProduitPolicy extends RbmPolicy
{
    protected function ressource(): string
    {
        return 'produits';
    }
}
