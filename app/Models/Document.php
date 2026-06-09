<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'owner_id',
    'file_name',
    'original_name',
    'file_path',
    'file_size',
    'mime_type',
    'file_hash',
    'encrypted',
])]
class Document extends Model
{
    protected function casts(): array
    {
        return [
            'encrypted' => 'boolean',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function shares(): HasMany
    {
        return $this->hasMany(DocumentShare::class);
    }
}
