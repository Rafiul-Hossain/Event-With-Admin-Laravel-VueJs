<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
   
    protected $fillable = [
         'user_id',
         'event_id',
         'ticket_qty',
         'ticket_price',
         'total_price',
         'status',
    ];
   public function user()
   {
       return $this->belongsTo(User::class);
   }
    public function event()
    {
         return $this->belongsTo(Event::class);
    }
}
