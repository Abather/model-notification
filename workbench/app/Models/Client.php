<?php

namespace Workbench\App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $fillable = [
        'name',
        'email',
    ];

    protected $table = 'clients';

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }
}
