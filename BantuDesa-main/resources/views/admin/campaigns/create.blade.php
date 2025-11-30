@extends('admin.auth.layouts.app')

@section('title', 'Tambah Campaign Baru')

@section('content')
<div class="container-fluid p-0">

    <h1 class="h3 mb-3">Tambah Campaign (Desa) Baru</h1>

    <div class="row">
        <div class="col-12 col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Formulir Data Desa</h5>
                </div>
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

                    <form action="{{ route('auth.campaigns.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label">Nama Desa / Proyek <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="title" value="{{ old('title') }}" placeholder="Contoh: Pembangunan Jembatan Desa Sukamaju" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Target Donasi (Rp) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="target_amount" value="{{ old('target_amount') }}" min="1000" placeholder="Contoh: 50000000" required>
                            <small class="text-muted">Masukkan angka saja tanpa titik/koma.</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Deskripsi Singkat</label>
                            <textarea class="form-control" name="description" rows="4" placeholder="Jelaskan tujuan penggalangan dana ini...">{{ old('description') }}</textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Foto Desa / Proyek</label>
                            <input type="file" class="form-control" name="image" accept="image/*">
                            <small class="text-muted">Format: JPG, PNG, JPEG. Maks: 2MB.</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="active" selected>Aktif (Bisa menerima donasi)</option>
                                <option value="inactive">Nonaktif (Sembunyikan)</option>
                                <option value="completed">Selesai (Target tercapai)</option>
                            </select>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">Simpan Data</button>
                            <a href="{{ route('auth.campaigns.index') }}" class="btn btn-secondary">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection