<?php

namespace Workbench\App\Models;

use Abather\ModelNotification\Contracts\Notifier;
use Abather\ModelNotification\Notifier as NotifierTrait;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model implements Notifier
{
    use NotifierTrait;

    protected $fillable = [
        'id',
        'amount',
        'file',
    ];

    protected $table = 'invoices';

    public function formattedAmount(): string
    {
        return '$' . number_format($this->amount, 2);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
