<?php

namespace App\Policies;

class SousProduitPolicy extends RbmPolicy
{
    protected function ressource(): string
    {
        return 'sous_produits';
    }
}
