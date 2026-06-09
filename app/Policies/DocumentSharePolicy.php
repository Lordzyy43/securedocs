<?php

namespace App\Policies;

use App\Models\DocumentShare;
use App\Models\User;

class DocumentSharePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, DocumentShare $documentShare): bool
    {
        return $user->hasRole('admin')
            || $documentShare->sender_id === $user->id
            || $documentShare->receiver_id === $user->id;
    }

    public function update(User $user, DocumentShare $documentShare): bool
    {
        return $documentShare->sender_id === $user->id;
    }

    public function delete(User $user, DocumentShare $documentShare): bool
    {
        return $documentShare->sender_id === $user->id;
    }
}
