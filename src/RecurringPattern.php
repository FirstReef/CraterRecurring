<?php

namespace FirstReef\CraterRecurring;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecurringPattern extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
}
