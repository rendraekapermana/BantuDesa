@extends('layouts.app')

@section('css')
<style>
    /* Copy CSS dari file donate.blade.php yang lama di sini */
    .hidden { display: none; }
    /* ... (CSS spinner dll) ... */
    button[type=submit] {
        background: #5469d4;
        color: #ffffff;
        /* ... style tombol ... */
        width: 100%;
        padding: 12px;
        border: none;
        border-radius: 4px;
        font-weight: 600;
    }
</style>
@endsection

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            
            {{-- Detail Campaign yang dipilih --}}
            <div class="card shadow-sm mb-4 border-0">
                <div class="card-body d-flex align-items-center">
                    <img src="{{ $campaign->image ? asset('images/campaigns/'.$campaign->image) : asset('images/no-image.jpg') }}" 
                         class="rounded me-3" width="100" height="100" style="object-fit: cover;">
                    <div>
                        <h5 class="mb-1">Anda akan berdonasi untuk:</h5>
                        <h3 class="fw-bold text-primary mb-0">{{ $campaign->title }}</h3>
                    </div>
                </div>
            </div>

            {{-- Form Donasi --}}
            <form id="payment-form" method="POST" action="{{ route('process.checkout.midtrans') }}">
                @csrf
                
                {{-- PENTING: Kirim ID Campaign --}}
                <input type="hidden" name="campaign_id" value="{{ $campaign->id }}">
                
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white">
                        <h4 class="mb-0">Isi Data Diri</h4>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nama Depan</label>
                                <input type="text" class="form-control required" name="first_name" placeholder="Contoh: Budi" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nama Belakang</label>
                                <input type="text" class="form-control required" name="last_name" placeholder="Contoh: Santoso" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control required" name="email" placeholder="email@contoh.com" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">No. HP (Opsional)</label>
                                <input type="text" class="form-control" name="mobile" placeholder="08123456789">
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-bold">Jumlah Donasi (Rp)</label>
                                <input type="number" class="form-control form-control-lg required" name="amount" min="1000" placeholder="Minimal Rp 1.000" required>
                            </div>

                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="add_to_leaderboard" value="yes" id="leaderboardCheck" checked>
                                    <label class="form-check-label" for="leaderboardCheck">
                                        Tampilkan nama saya di <a href="{{ route('home.leaderboard') }}" target="_blank">Leaderboard</a>
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        {{-- Hidden field gabungan nama (untuk kompatibilitas script lama) --}}
                        <input type="hidden" name="donor_name" id="donor_name_field">

                    </div>
                    <div class="card-footer bg-white p-3">
                        <button type="submit" class="btn btn-primary btn-lg w-100">
                            <i class="fas fa-lock me-2"></i> Lanjut Pembayaran
                        </button>
                    </div>
                </div>
            </form>

        </div>
    </div>
</div>
@endsection

@section('javascript')
<script>
    $(document).on('submit', '#payment-form', function(e) {
        let form = $(this);
        let firstName = form.find('[name=first_name]').val() || '';
        let lastName = form.find('[name=last_name]').val() || '';
        let donorName = firstName.trim() + ' ' + lastName.trim();
        
        form.find('#donor_name_field').val(donorName);
        form.find('button[type=submit]').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Memproses...');
    });
</script>
@endsection