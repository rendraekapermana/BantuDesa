@extends('layouts.app')

@section('css')
<style>
    .campaign-header-img {
        width: 100%;
        height: 400px;
        object-fit: cover;
        border-radius: 15px;
    }

    .nav-tabs .nav-link {
        color: #495057;
        font-weight: 600;
        border: none;
        border-bottom: 3px solid transparent;
    }

    .nav-tabs .nav-link.active {
        color: #0d6efd;
        border-bottom: 3px solid #0d6efd;
        background: transparent;
    }

    .stat-card {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 20px;
        text-align: center;
    }

    .withdrawal-timeline {
        border-left: 2px solid #0d6efd;
        padding-left: 20px;
        margin-left: 10px;
    }

    .withdrawal-item {
        position: relative;
        margin-bottom: 30px;
    }

    .withdrawal-item::before {
        content: '';
        width: 12px;
        height: 12px;
        background: #0d6efd;
        border-radius: 50%;
        position: absolute;
        left: -26px;
        top: 5px;
    }

    .balance-card {
        border-radius: 12px;
        border: 1px solid #e9ecef;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    }

    /* Style untuk Card Total Dana */
    .total-card {
        background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
        color: white;
        border: none;
    }

    .total-card h3,
    .total-card h6,
    .total-card small {
        color: white;
    }
</style>
@endsection

@section('content')
<div class="container py-5">
    <div class="row">
        {{-- Kolom Kiri --}}
        <div class="col-lg-8">
            <img src="{{ $campaign->image ? asset('images/campaigns/'.$campaign->image) : asset('images/no-image.jpg') }}" class="campaign-header-img mb-4 shadow-sm" alt="{{ $campaign->title }}">

            <ul class="nav nav-tabs mb-4" id="campaignTabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#desc" type="button">Deskripsi</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#funds" type="button">Rincian Dana & Pencairan</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#donors" type="button">Donatur ({{ $campaign->donations->count() }})</button>
                </li>
            </ul>

            <div class="tab-content">
                {{-- Tab Deskripsi --}}
                <div class="tab-pane fade show active" id="desc">
                    <h3 class="fw-bold mb-3">{{ $campaign->title }}</h3>
                    <div class="text-muted" style="line-height: 1.8;">
                        {!! nl2br(e($campaign->description)) !!}
                    </div>
                </div>

                {{-- Tab Rincian Dana (REVISI FINAL) --}}
                <div class="tab-pane fade" id="funds">

                    <div class="row g-3 mb-4">
                        {{-- 1. TOTAL DANA TERKUMPUL (Gross) --}}
                        <div class="col-12">
                            <div class="card balance-card total-card text-center py-4">
                                <div class="card-body">
                                    <h6 class="mb-2 opacity-75">Total Dana Terkumpul</h6>
                                    <h2 class="fw-bold">Rp {{ number_format($totalDonations, 0, ',', '.') }}</h2>
                                    <div class="mt-2">
                                        <small class="opacity-75 border border-white rounded px-2 py-1 d-inline-block" style="font-size: 0.85rem;">
                                            <i class="fas fa-info-circle me-1"></i> 5% Biaya Operasional: Rp {{ number_format($totalDonations * 0.05, 0, ',', '.') }}
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- 2. DANA SUDAH DICAIRKAN --}}
                        <div class="col-md-6">
                            <div class="card balance-card bg-light text-center h-100 py-4">
                                <div class="card-body">
                                    <h6 class="text-muted mb-2">Dana Sudah Dicairkan</h6>
                                    <h3 class="fw-bold text-success">Rp {{ number_format($totalWithdrawn, 0, ',', '.') }}</h3>
                                    <small class="text-muted">Telah disalurkan ke desa</small>
                                </div>
                            </div>
                        </div>

                        {{-- 3. DANA BELUM DICAIRKAN (Sisa Bersih) --}}
                        <div class="col-md-6">
                            <div class="card balance-card bg-white text-center h-100 py-4 border-primary">
                                <div class="card-body">
                                    <h6 class="text-muted mb-2">Dana Belum Dicairkan</h6>
                                    <h3 class="fw-bold text-primary">Rp {{ number_format($remainingFunds, 0, ',', '.') }}</h3>
                                    <small class="text-muted">Tersedia untuk penarikan (Bersih)</small>

                                    <div class="mt-2">
                                        <span class="badge bg-info text-dark" data-bs-toggle="tooltip" title="Dana tersedia sudah dikurangi biaya operasional platform 5%">
                                            <i class="fas fa-info-circle"></i> Sudah pot. 5%
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <h5 class="fw-bold mb-3 mt-4"><i class="fas fa-history text-primary me-2"></i> Riwayat Penarikan Dana</h5>
                    @if($campaign->withdrawals->count() > 0)
                    <div class="withdrawal-timeline">
                        @foreach($campaign->withdrawals as $withdrawal)
                        <div class="withdrawal-item">
                            <span class="badge bg-secondary mb-1">{{ $withdrawal->withdrawal_date->format('d M Y') }}</span>
                            <h5 class="fw-bold mt-1">Pencairan Dana: Rp {{ number_format($withdrawal->amount, 0, ',', '.') }}</h5>
                            <p class="text-muted mb-1">Disalurkan ke: <strong>{{ $withdrawal->recipient_name }}</strong> ({{ $withdrawal->bank_name }})</p>
                            <p class="mb-2">{{ $withdrawal->description }}</p>
                            @if($withdrawal->proof_image)
                            <a href="{{ asset('images/withdrawals/'.$withdrawal->proof_image) }}" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fas fa-file-image"></i> Lihat Bukti Transfer</a>
                            @endif
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="text-center py-4 text-muted border rounded bg-light">
                        <i class="fas fa-piggy-bank fa-2x mb-2"></i>
                        <p class="mb-0">Belum ada dana yang dicairkan oleh Admin.</p>
                    </div>
                    @endif
                </div>

                {{-- Tab Donatur --}}
                <div class="tab-pane fade" id="donors">
                    <div class="list-group list-group-flush">
                        @forelse($campaign->donations as $donor)
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <div>
                                <h6 class="mb-0 fw-bold">{{ $donor->name }}</h6>
                                <small class="text-muted">{{ $donor->created_at->diffForHumans() }}</small>
                            </div>
                            <span class="text-success fw-bold">Rp {{ number_format($donor->amount, 0, ',', '.') }}</span>
                        </div>
                        @empty
                        <div class="text-center py-3">Belum ada donatur. Jadilah yang pertama!</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        {{-- Sidebar Kanan --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm sticky-top" style="top: 90px; z-index: 10;">
                <div class="card-body">
                    <h3 class="fw-bold mb-1">Rp {{ number_format($campaign->current_amount, 0, ',', '.') }}</h3>
                    <p class="text-muted mb-3">terkumpul dari target Rp {{ number_format($campaign->target_amount, 0, ',', '.') }}</p>

                    <div class="progress mb-3" style="height: 10px;">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $campaign->progress }}%"></div>
                    </div>

                    <div class="d-grid gap-2">
                        <a href="{{ route('home.donate.form', $campaign->id) }}" class="btn btn-primary btn-lg fw-bold">
                            <i class="fas fa-heart me-2"></i> Donasi Sekarang
                        </a>
                        <button class="btn btn-outline-secondary btn-lg">
                            <i class="fas fa-share-alt me-2"></i> Bagikan
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    })
</script>
@endsection