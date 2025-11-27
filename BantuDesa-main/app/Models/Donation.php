<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Donation extends Model
{
    use HasFactory;
    protected $table = 'donation';

    protected $fillable = [
        'name',
        'email',
        'mobile',
        'street_address',
        'country_id',
        'state_id',
        'city_id',
        'country_name', // Nama negara disimpan sebagai string (jika ada)
        'state_name',   // Nama provinsi disimpan sebagai string (jika ada)
        'city_name',    // Nama kota disimpan sebagai string (jika ada)
        'amount',
        'user_id',
        'add_to_leaderboard',
        'session_id',
        'status',
        'blockchain_tx_hash',
        'donor_wallet_address',
    ];

    // Relationship to User model
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relationship to City model
    // [RESTORED] Uncommented because tables should now exist
    public function city()
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    // Relationship to State model
    // [RESTORED] Uncommented because tables should now exist
    public function state()
    {
        return $this->belongsTo(State::class, 'state_id');
    }

    // Relationship to Country model
    // [RESTORED] Uncommented because tables should now exist
    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }
}