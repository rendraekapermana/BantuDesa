@extends('admin.auth.layouts.app')

@section('title', 'Catat Penarikan Dana')

@section('content')
<div class="container-fluid p-0">

    <h1 class="h3 mb-3">Catat Penarikan Dana (Withdrawal)</h1>

    <div class="row">
        <div class="col-12 col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Informasi Saldo Campaign</h5>
                </div>
                <div class="card-body">
                    {{-- Info Saldo --}}
                    <div class="alert alert-primary">
                        <h4 class="alert-heading fw-bold">{{ $campaign->title }}</h4>
                        <hr>
                        <div class="row">
                            <div class="col-md-4">
                                <small>Total Donasi Masuk:</small><br>
                                <span class="fw-bold">Rp {{ number_format($campaign->current_amount, 0, ',', '.') }}</span>
                            </div>
                            <div class="col-md-4">
                                <small>Potongan Ops (5%):</small><br>
                                <span class="text-danger">Rp {{ number_format($campaign->current_amount * 0.05, 0, ',', '.') }}</span>
                            </div>
                            <div class="col-md-4">
                                <small>Dana Bersih Tersedia:</small><br>
                                <span class="text-success fw-bold fs-4">Rp {{ number_format($available, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('auth.campaigns.withdraw.store', $campaign->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label">Jumlah Penarikan (Rp) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="amount" max="{{ $available }}" min="10000" placeholder="Contoh: 10000000" required>
                            <small class="text-muted">Maksimal: Rp {{ number_format($available, 0, ',', '.') }}</small>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nama Penerima <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="recipient_name" placeholder="Misal: Bpk. Kepala Desa" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nama Bank & No. Rek</label>
                                <input type="text" class="form-control" name="bank_name" placeholder="Misal: BRI - 1234567890">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Tanggal Penarikan</label>
                            <input type="date" class="form-control" name="withdrawal_date" value="{{ date('Y-m-d') }}" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Keperluan / Deskripsi <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="description" rows="3" placeholder="Jelaskan dana ini digunakan untuk tahap apa..." required></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Bukti Transfer (Gambar) <span class="text-danger">*</span></label>
                            <input type="file" class="form-control" name="proof_image" accept="image/*" required>
                            <small class="text-muted">Upload foto resi bank atau bukti serah terima uang.</small>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-success btn-lg"><i class="align-middle" data-feather="check-circle"></i> Catat Penarikan</button>
                            <a href="{{ route('auth.campaigns.index') }}" class="btn btn-secondary">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection