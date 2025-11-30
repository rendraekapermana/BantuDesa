<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CampaignWithdrawal extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_id',
        'amount',
        'recipient_name',
        'bank_name',
        'account_number',
        'description',
        'proof_image',
        'withdrawal_date',
    ];

    protected $casts = [
        'withdrawal_date' => 'date',
    ];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }
}