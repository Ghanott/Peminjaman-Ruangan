<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApprovalFlow extends Model
{
    protected $fillable = [
        'organization_type',
        'step_order',
        'role_key',
        'is_active',
    ];
}
