<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Document $document): bool
    {
        return $user->hasRole('admin')
            || $document->owner_id === $user->id
            || $document->shares()->where('receiver_id', $user->id)->exists();
    }

    public function update(User $user, Document $document): bool
    {
        return $document->owner_id === $user->id;
    }

    public function delete(User $user, Document $document): bool
    {
        return $document->owner_id === $user->id;
    }

    public function download(User $user, Document $document): bool
    {
        return $document->owner_id === $user->id
            || $document->shares()
                ->where('receiver_id', $user->id)
                ->where('permission', 'download')
                ->exists();
    }
}
