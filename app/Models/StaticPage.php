<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaticPage extends Model
{
    protected $fillable = ['name', 'content'];

    public const ALLOWED_NAMES = ['terms', 'about', 'contact', 'privacy'];
}
