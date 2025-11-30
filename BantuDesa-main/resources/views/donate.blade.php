@extends('layouts.app')

@section('css')
<style>
    .campaign-card {
        transition: transform 0.2s;
        height: 100%;
    }
    .campaign-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1);
    }
    .progress {
        height: 10px;
        border-radius: 5px;
    }
    .campaign-img {
        height: 200px;
        object-fit: cover;
    }
</style>
@endsection

@section('content')
<div class="container py-5">
    <div class="text-center mb-5">
        <h1 class="fw-bold">Mari Bantu Desa Membangun</h1>
        <p class="lead text-muted">Pilih desa atau proyek yang ingin Anda bantu hari ini.</p>
    </div>

    <div class="row">
        {{-- Daftar Campaign (Kiri) --}}
        <div class="col-lg-8">
            <div class="row g-4">
                @forelse($campaigns as $campaign)
                <div class="col-md-6">
                    <div class="card campaign-card border-0 shadow-sm">
                        {{-- Link Gambar juga ke Detail --}}
                        <a href="{{ route('home.campaign.detail', $campaign->id) }}">
                            <img src="{{ $campaign->image ? asset('images/campaigns/'.$campaign->image) : asset('images/no-image.jpg') }}" class="card-img-top campaign-img" alt="{{ $campaign->title }}">
                        </a>
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title fw-bold">
                                <a href="{{ route('home.campaign.detail', $campaign->id) }}" class="text-decoration-none text-dark">
                                    {{ $campaign->title }}
                                </a>
                            </h5>
                            <p class="card-text text-muted small flex-grow-1">
                                {{ Str::limit($campaign->description, 80) }}
                            </p>
                            
                            <div class="mb-3">
                                <div class="d-flex justify-content-between small mb-1">
                                    <span class="fw-bold text-primary">Rp {{ number_format($campaign->current_amount) }}</span>
                                    <span class="text-muted">dari Rp {{ number_format($campaign->target_amount) }}</span>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $campaign->progress }}%" aria-valuenow="{{ $campaign->progress }}" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>

                            {{-- FIX: Tombol ini sekarang mengarah ke Halaman Detail (campaign_detail), bukan langsung Bayar --}}
                            <a href="{{ route('home.campaign.detail', $campaign->id) }}" class="btn btn-primary w-100 fw-bold">
                                <i class="fas fa-info-circle me-2"></i> Lihat Detail & Donasi
                            </a>
                        </div>
                    </div>
                </div>
                @empty
                <div class="col-12 text-center py-5">
                    <img src="https://via.placeholder.com/150" class="mb-3" alt="No Data">
                    <h4>Belum ada program desa yang aktif saat ini.</h4>
                </div>
                @endforelse
            </div>

            {{-- Pagination --}}
            <div class="mt-5 d-flex justify-content-center">
                {{ $campaigns->links() }}
            </div>
        </div>

        {{-- Sidebar Leaderboard (Kanan) --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-trophy text-warning me-2"></i> Top Donatur</h5>
                </div>
                <div class="card-body p-0">
                    @include('components.leaderboard', ['donors' => $donors])
                </div>
            </div>
        </div>
    </div>
</div>
@endsection