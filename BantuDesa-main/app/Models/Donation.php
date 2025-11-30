<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log; 

class Donation extends Model
{
    use HasFactory;
    protected $table = 'donation';

    protected $fillable = [
        'name',
        'email',
        'mobile',
        'street_address',
        'country_name', 
        'state_name',   
        'city_name',    
        'amount',
        'user_id',
        'add_to_leaderboard',
        'session_id',
        'status',
        'blockchain_tx_hash',
        'donor_wallet_address',
        'campaign_id',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted()
    {
        static::updating(function ($donation) {
            if (!empty($donation->getOriginal('blockchain_tx_hash'))) {
                if ($donation->isDirty('amount')) return false;
                // Izinkan perubahan status KE 'recorded_on_chain' meskipun sudah ada hash (kasus retry script)
                if ($donation->isDirty('status') && $donation->status !== 'recorded_on_chain' && $donation->getOriginal('status') == 'recorded_on_chain') {
                     return false;
                }
            }
        });

        static::deleting(function ($donation) {
            if (!empty($donation->blockchain_tx_hash)) return false;
        });
        
        // [FIX BUG PROGRESS DANA]
        // Gunakan event 'saved' atau 'updated' dengan pengecekan yang lebih teliti
        static::updated(function ($donation) {
            // Cek apakah status berubah MENJADI 'paid' atau 'recorded_on_chain'
            // DAN status sebelumnya ADALAH 'unpaid' atau 'pending'
            // Ini untuk mencegah penambahan ganda jika status berubah dari 'paid' ke 'recorded_on_chain'
            
            $oldStatus = $donation->getOriginal('status');
            $newStatus = $donation->status;

            // Daftar status yang dianggap SUKSES BAYAR
            $successStatuses = ['paid', 'recorded_on_chain'];
            // Daftar status yang dianggap BELUM BAYAR
            $pendingStatuses = ['unpaid', 'pending', 'pending_onchain'];

            // Logika: Jika status berubah dari BELUM BAYAR ke SUKSES BAYAR
            if (in_array($oldStatus, $pendingStatuses) && in_array($newStatus, $successStatuses)) {
                
                // Pastikan ada campaign_id
                if ($donation->campaign_id) {
                    $campaign = Campaign::find($donation->campaign_id);
                    if ($campaign) {
                        // Tambahkan amount donasi ke current_amount campaign
                        $campaign->current_amount += $donation->amount;
                        $campaign->save();
                        
                        Log::info("Campaign #{$campaign->id} updated. Added Rp {$donation->amount}. New Total: {$campaign->current_amount}");
                    }
                }
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relasi ke Campaign
    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }
    
    // Relasi lokasi dimatikan sementara sesuai request sebelumnya
    /*
    public function city() { return $this->belongsTo(City::class, 'city_id'); }
    public function state() { return $this->belongsTo(State::class, 'state_id'); }
    public function country() { return $this->belongsTo(Country::class, 'country_id'); }
    */
}