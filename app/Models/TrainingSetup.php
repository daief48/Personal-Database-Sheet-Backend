<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainingSetup extends Model
{
    use HasFactory;
    protected $fillable = [
        'training_name',
        'create_at',
        'status',
    ];
}