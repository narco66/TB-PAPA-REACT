<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('document.viewAny');
    }

    public function view(User $user, Document $document): bool
    {
        if ($document->confidentiel && ! $user->can('document.viewConfidential')) {
            return false;
        }

        return $user->can('document.view');
    }

    public function upload(User $user): bool
    {
        return $user->can('document.upload');
    }

    public function update(User $user, Document $document): bool
    {
        if ($document->valide_at) {
            return false; // Document validé non modifiable
        }

        return $user->can('document.update') || $document->uploade_par_id === $user->id;
    }

    public function delete(User $user, Document $document): bool
    {
        if ($document->valide_at) {
            return false;
        }

        return $user->can('document.delete') || $document->uploade_par_id === $user->id;
    }

    public function validate(User $user, Document $document): bool
    {
        return $user->can('document.validate') && ! $document->valide_at;
    }
}
