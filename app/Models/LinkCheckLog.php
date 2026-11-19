<?php

namespace App\Models;

use Database\Factories\LinkCheckLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'source_type',
    'source_label',
    'url',
    'http_status',
    'is_broken',
    'error_message',
    'checked_at',
])]
class LinkCheckLog extends Model
{
    /** @use HasFactory<LinkCheckLogFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'http_status' => 'integer',
            'is_broken' => 'boolean',
            'checked_at' => 'datetime',
        ];
    }
}
