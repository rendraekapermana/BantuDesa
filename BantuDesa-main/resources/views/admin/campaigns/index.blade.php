@extends('admin.auth.layouts.app')

@section('title', 'Daftar Campaign / Desa')

@section('content')
<div class="container-fluid p-0">

    <div class="mb-3">
        <h1 class="h3 d-inline align-middle">Daftar Campaign (Desa)</h1>
        <a href="{{ route('auth.campaigns.create') }}" class="btn btn-primary float-end">
            <i class="align-middle" data-feather="plus"></i> Tambah Desa Baru
        </a>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-hover table-striped w-100" id="campaignTable">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Gambar</th>
                                    <th>Nama Desa/Proyek</th>
                                    <th>Progress Dana</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        var table = $('#campaignTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('auth.campaigns.index') }}",
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                {data: 'image', name: 'image', orderable: false, searchable: false},
                {data: 'title', name: 'title'},
                {data: 'progress', name: 'progress', orderable: false, searchable: false},
                {data: 'status', name: 'status'},
                {data: 'action', name: 'action', orderable: false, searchable: false},
            ]
        });
    });

    function deleteCampaign(id) {
        if(confirm("Apakah Anda yakin ingin menghapus data desa ini?")) {
            $.ajax({
                url: '/admin/campaigns/' + id,
                type: 'DELETE',
                data: {
                    "_token": "{{ csrf_token() }}",
                },
                success: function(response) {
                    $('#campaignTable').DataTable().ajax.reload();
                    toastr.success(response.success);
                },
                error: function(xhr) {
                    toastr.error('Gagal menghapus data.');
                }
            });
        }
    }
</script>
@endsection