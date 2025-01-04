<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProviderId extends Model
{
    use HasFactory;

    protected $fillable = ['valid_provider_id'];
}
