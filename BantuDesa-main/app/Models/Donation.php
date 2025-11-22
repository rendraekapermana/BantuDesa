<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Donation extends Model
{
    use HasFactory;
    
    protected $table = 'donation';
    
    protected $fillable = [
        'user_id', 
        'amount',
        
        // --- PERBAIKAN: Ganti 'donor_name' jadi 'name' sesuai Database ---
        'name', 
        // ----------------------------------------------------------------
        
        'email', 
        'mobile', 
        'street_address', 
        'country_id', 
        'state_id', 
        'city_id', 
        'status', 
        'add_to_leaderboard', 
        'session_id', 
        'blockchain_tx_hash', 
        'donor_wallet_address', 
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}