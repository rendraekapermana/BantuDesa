@extends('admin.auth.layouts.app')

@section('title', 'Edit Campaign')

@section('content')
<div class="container-fluid p-0">

    <h1 class="h3 mb-3">Edit Data Campaign</h1>

    <div class="row">
        <div class="col-12 col-lg-8">
            <div class="card">
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- FIX: Route name corrected to match web.php definition (auth.campaigns.update) --}}
                    <form action="{{ route('auth.campaigns.update', $campaign->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label class="form-label">Nama Desa / Proyek <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="title" value="{{ old('title', $campaign->title) }}" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Target Donasi (Rp) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="target_amount" value="{{ old('target_amount', $campaign->target_amount) }}" min="1000" required>
                        </div>
                        
                        <div class="mb-3">
                             <label class="form-label">Dana Terkumpul Saat Ini (Rp)</label>
                             <input type="text" class="form-control" value="Rp {{ number_format($campaign->current_amount, 0, ',', '.') }}" disabled>
                             <small class="text-muted">Otomatis bertambah saat ada donasi masuk.</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Deskripsi Singkat</label>
                            <textarea class="form-control" name="description" rows="4">{{ old('description', $campaign->description) }}</textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Foto Desa / Proyek</label>
                            <div class="mb-2">
                                @if($campaign->image)
                                    <img src="{{ asset('images/campaigns/' . $campaign->image) }}" class="img-thumbnail" width="150" alt="Foto Lama">
                                    <div class="form-text">Gambar saat ini. Biarkan kosong jika tidak ingin mengubah.</div>
                                @else
                                    <div class="text-muted fst-italic">Belum ada gambar.</div>
                                @endif
                            </div>
                            <input type="file" class="form-control" name="image" accept="image/*">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="active" {{ $campaign->status == 'active' ? 'selected' : '' }}>Aktif</option>
                                <option value="inactive" {{ $campaign->status == 'inactive' ? 'selected' : '' }}>Nonaktif</option>
                                <option value="completed" {{ $campaign->status == 'completed' ? 'selected' : '' }}>Selesai</option>
                            </select>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">Update Data</button>
                            {{-- FIX: Route name corrected to match web.php definition (auth.campaigns.index) --}}
                            <a href="{{ route('auth.campaigns.index') }}" class="btn btn-secondary">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection