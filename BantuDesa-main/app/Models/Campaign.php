<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'target_amount',
        'current_amount',
        'image',
        'status'
    ];

    public function donations()
    {
        return $this->hasMany(Donation::class)->whereIn('status', ['paid', 'recorded_on_chain']);
    }

    public function withdrawals()
    {
        return $this->hasMany(CampaignWithdrawal::class)->orderBy('withdrawal_date', 'desc');
    }
    
    // Hitung persentase terkumpul
    public function getProgressAttribute()
    {
        if ($this->target_amount == 0) return 0;
        return min(100, round(($this->current_amount / $this->target_amount) * 100));
    }

    // Total Dana Keluar (Dicairkan)
    public function getTotalWithdrawnAttribute()
    {
        return $this->withdrawals()->sum('amount');
    }

    // Sisa Dana yang Belum Dicairkan (Setelah potongan 5%)
    public function getRemainingBalanceAttribute()
    {
        // Total bersih = Total Donasi - 5% Biaya Operasional
        $netAmount = $this->current_amount * 0.95; 
        return $netAmount - $this->total_withdrawn;
    }
}