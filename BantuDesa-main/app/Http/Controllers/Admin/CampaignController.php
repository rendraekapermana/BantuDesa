<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use DataTables;

class CampaignController extends Controller
{
    /**
     * Menampilkan halaman daftar Campaign (Desa)
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            // Ambil data terbaru
            $data = Campaign::latest()->get();
            
            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('image', function($row){
                    // Tampilkan gambar jika ada, jika tidak tampilkan placeholder
                    $imgUrl = $row->image ? asset('images/campaigns/' . $row->image) : asset('images/no-image.jpg');
                    return '<img src="'.$imgUrl.'" width="100" class="img-thumbnail">';
                })
                ->addColumn('progress', function($row){
                    // Hitung persentase (menggunakan accessor di Model)
                    $percent = $row->progress; 
                    return '
                        <div class="progress" style="height: 20px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: '.$percent.'%;" aria-valuenow="'.$percent.'" aria-valuemin="0" aria-valuemax="100">'.$percent.'%</div>
                        </div>
                        <small>Rp '.number_format($row->current_amount).' / Rp '.number_format($row->target_amount).'</small>
                    ';
                })
                ->addColumn('status', function($row){
                    if($row->status == 'active') 
                        return '<span class="badge bg-success">Aktif</span>';
                    elseif($row->status == 'completed') 
                        return '<span class="badge bg-primary">Selesai</span>';
                    else 
                        return '<span class="badge bg-secondary">Nonaktif</span>';
                })
                ->addColumn('action', function($row){
                    // Menggunakan route name 'auth.' sesuai web.php
                    // Pastikan route resource 'campaigns' ada di dalam group name('auth.')
                    $btn = '<a href="'.route('auth.campaigns.edit', $row->id).'" class="edit btn btn-primary btn-sm">Edit</a>';
                    
                    // Tombol Withdraw (Pastikan route ini ada di web.php)
                    // Route: Route::get('campaigns/{id}/withdraw', ...)->name('campaigns.withdraw');
                    // Karena di dalam group 'auth.', namanya jadi 'auth.campaigns.withdraw'
                    $btn .= ' <a href="'.route('auth.campaigns.withdraw', $row->id).'" class="btn btn-warning btn-sm ms-1">Withdraw</a>';
                    
                    $btn .= ' <button onclick="deleteCampaign('.$row->id.')" class="btn btn-danger btn-sm ms-1">Hapus</button>';
                    return $btn;
                })
                ->rawColumns(['image', 'progress', 'status', 'action'])
                ->make(true);
        }

        // Menggunakan view path yang benar: resources/views/admin/campaigns/index.blade.php
        return view('admin.campaigns.index');
    }

    /**
     * Menampilkan form untuk membuat Campaign baru
     */
    public function create()
    {
        // View path: resources/views/admin/campaigns/create.blade.php
        return view('admin.campaigns.create');
    }

    /**
     * Menyimpan data Campaign baru ke database
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'target_amount' => 'required|numeric|min:1000',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'status' => 'required|in:active,inactive,completed',
        ]);

        $input = $request->all();

        // Proses Upload Gambar
        if ($image = $request->file('image')) {
            $destinationPath = 'images/campaigns/';
            // Pastikan folder ada
            if (!File::exists(public_path($destinationPath))) {
                File::makeDirectory(public_path($destinationPath), 0755, true);
            }
            
            $profileImage = date('YmdHis') . "." . $image->getClientOriginalExtension();
            $image->move(public_path($destinationPath), $profileImage);
            $input['image'] = $profileImage;
        }

        Campaign::create($input);

        return redirect()->route('auth.campaigns.index')
                        ->with('success', 'Data Desa/Campaign berhasil ditambahkan.');
    }

    /**
     * Menampilkan form edit Campaign
     */
    public function edit($id)
    {
        $campaign = Campaign::findOrFail($id);
        // View path: resources/views/admin/campaigns/edit.blade.php
        return view('admin.campaigns.edit', compact('campaign'));
    }

    /**
     * Memperbarui data Campaign di database
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'target_amount' => 'required|numeric|min:1000',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'status' => 'required|in:active,inactive,completed',
        ]);

        $campaign = Campaign::findOrFail($id);
        $input = $request->all();

        // Proses Update Gambar (Hapus lama jika ada baru)
        if ($image = $request->file('image')) {
            $destinationPath = 'images/campaigns/';
            
            // Buat folder jika belum ada
             if (!File::exists(public_path($destinationPath))) {
                File::makeDirectory(public_path($destinationPath), 0755, true);
            }

            // Hapus gambar lama
            if($campaign->image && File::exists(public_path($destinationPath.$campaign->image))){
                File::delete(public_path($destinationPath.$campaign->image));
            }

            $profileImage = date('YmdHis') . "." . $image->getClientOriginalExtension();
            $image->move(public_path($destinationPath), $profileImage);
            $input['image'] = $profileImage;
        } else {
            // Jika tidak upload gambar baru, pakai yang lama (hapus dari input agar tidak overwrite dengan null)
            unset($input['image']);
        }

        $campaign->update($input);

        return redirect()->route('auth.campaigns.index')
                        ->with('success', 'Data Desa/Campaign berhasil diperbarui.');
    }

    /**
     * Menghapus Campaign
     */
    public function destroy($id)
    {
        $campaign = Campaign::findOrFail($id);
        
        // Hapus gambar terkait
        if($campaign->image){
            $imagePath = 'images/campaigns/'.$campaign->image;
            if(File::exists(public_path($imagePath))){
                File::delete(public_path($imagePath));
            }
        }
        
        $campaign->delete();

        return response()->json(['success' => 'Data berhasil dihapus.']);
    }

    /**
     * Halaman Form Penarikan Dana
     */
    public function withdraw($id)
    {
        $campaign = Campaign::findOrFail($id);
        
        // Hitung sisa dana yang bisa ditarik
        // Total Masuk - (5% Fee) - (Yang sudah ditarik)
        // Pastikan model Campaign punya attribute 'total_withdrawn' (atau relasi withdrawals)
        $netFunds = $campaign->current_amount * 0.95;
        
        // Hitung total withdrawn manual jika accessor belum ada di model
        // $withdrawn = \App\Models\CampaignWithdrawal::where('campaign_id', $id)->sum('amount');
        // Atau gunakan accessor model jika sudah dibuat:
        $withdrawn = $campaign->total_withdrawn ?? 0; 
        
        $available = $netFunds - $withdrawn;

        // View path: resources/views/admin/campaigns/withdraw.blade.php
        return view('admin.campaigns.withdraw', compact('campaign', 'available'));
    }

    /**
     * Proses Simpan Penarikan Dana
     */
    public function withdrawStore(Request $request, $id)
    {
        $campaign = Campaign::findOrFail($id);
        
        // Validasi
        $request->validate([
            'amount' => 'required|numeric|min:1000',
            'recipient_name' => 'required|string',
            'description' => 'required|string',
            'withdrawal_date' => 'required|date',
            'proof_image' => 'required|image|max:2048', // Wajib ada bukti transfer
        ]);
        
        // Cek apakah saldo cukup (validasi backend tambahan)
        $netFunds = $campaign->current_amount * 0.95;
        $withdrawn = $campaign->total_withdrawn ?? 0;
        $available = $netFunds - $withdrawn;
        
        if ($request->amount > $available) {
             return redirect()->back()->withErrors(['amount' => 'Jumlah penarikan melebihi dana tersedia!'])->withInput();
        }

        // Upload Bukti
        $input = $request->all();
        $input['campaign_id'] = $id;
        
        if ($image = $request->file('proof_image')) {
            $destinationPath = 'images/withdrawals/';
            // Buat folder jika belum ada
            if (!File::exists(public_path($destinationPath))) {
                File::makeDirectory(public_path($destinationPath), 0755, true);
            }

            $profileImage = date('YmdHis') . "." . $image->getClientOriginalExtension();
            $image->move(public_path($destinationPath), $profileImage);
            $input['proof_image'] = $profileImage;
        }

        // Simpan ke tabel campaign_withdrawals (Pakai Model CampaignWithdrawal)
        // Pastikan model ini sudah di-import atau gunakan FQCN
        \App\Models\CampaignWithdrawal::create($input);

        return redirect()->route('auth.campaigns.index')->with('success', 'Penarikan dana berhasil dicatat.');
    }
}