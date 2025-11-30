<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\Request;
use App\Models\Donation;
use App\Models\Campaign;
use App\Models\Members;
use App\Models\About;
use App\Models\Album;
use App\Models\Albums;

class HomeController extends Controller
{
    public function index()
    {
        $campaigns = Campaign::where('status', 'active')->latest()->limit(3)->get();
        return view('index', compact('campaigns'));
    }

    public function donate()
    {
        $campaigns = Campaign::where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->paginate(9); 

        $donors = Donation::select('donation.*')
            ->whereIn('status', ['paid', 'recorded_on_chain'])
            ->where('add_to_leaderboard', 'yes')
            ->orderBy('amount', 'desc')
            ->limit(5)
            ->get();

        return view('donate', compact('campaigns', 'donors'));
    }

    // [REVISI] Halaman Detail Campaign dengan Perhitungan Eksplisit
    public function showCampaignDetail($id)
    {
        $campaign = Campaign::with(['donations' => function($q) {
            $q->where('add_to_leaderboard', 'yes')->orderBy('created_at', 'desc');
        }, 'withdrawals'])->findOrFail($id);

        // 1. Total Donasi Masuk (Gross)
        $totalDonations = $campaign->current_amount;

        // 2. Total Dana yang Sudah Dicairkan
        // Kita hitung manual dari relasi withdrawals agar akurat
        $totalWithdrawn = $campaign->withdrawals->sum('amount');

        // 3. Hitung Dana Belum Dicairkan (Sisa Saldo Bersih)
        // Rumus: (Total Masuk - 5% Biaya Ops) - Total Keluar
        $operationalFee = $totalDonations * 0.05;
        $netFunds = $totalDonations - $operationalFee;
        
        $remainingFunds = $netFunds - $totalWithdrawn;

        // Cegah nilai negatif (jika ada pembulatan)
        if($remainingFunds < 0) $remainingFunds = 0;

        return view('campaign_detail', compact(
            'campaign', 
            'totalDonations', // Variabel ini WAJIB ada untuk card Total
            'totalWithdrawn', 
            'remainingFunds'
        ));
    }

    public function showDonateForm($id)
    {
        $campaign = Campaign::where('status', 'active')->findOrFail($id);
        return view('donate_payment', compact('campaign'));
    }

    // ... (Sisa method contact, about, albums, dll biarkan SAMA seperti sebelumnya) ...
    
    public function contact() { return view('contact'); }
    public function about() { $member=Members::all(); return view('about',compact('member')); }
    public function albums() { $albums = Albums::select('*')->with('media')->where('albums.status', 1)->orderBy('id', 'DESC')->paginate(12); return view('albums',compact('albums')); }
    public function album($id) { $album = Albums::where('id', $id)->with('media')->where('albums.status', 1)->first(); if (!$album) return redirect()->route('home.albums')->with('error', 'Album does not exists'); return view('album', compact('album')); }
    public function contactSubmit(Request $request) {
        $request->validate([ 'name' => 'required|string|max:50', 'email' => 'required|email', 'mobile' => 'required|numeric', 'message' => 'required|string', ]);
        try { $contact = new Contact(); $contact->name = $request->name; $contact->email = $request->email; $contact->mobile = $request->mobile; $contact->message = $request->message; $contact->save(); return redirect()->back()->with(['success' => 'Contact Form submited successfully']); } catch (\Exception $e) { return redirect()->back()->with(['error' => 'Something went wrong.[' . $e->getMessage() . ']'])->withInput(); }
    }
    public function privacy() { return view('privacy'); }

    public function leaderboard()
    {
        $donors = Donation::select('donation.*')
            ->with('campaign')
            ->whereIn('status', ['paid', 'recorded_on_chain'])
            ->where('add_to_leaderboard', 'yes') 
            ->orderBy('amount', 'desc')
            ->paginate(10);
        return view('leaderboard', compact('donors'));
    }
}