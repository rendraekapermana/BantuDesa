@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3>Proses Pembayaran</h3>
                </div>
                <div class="card-body text-center">
                    <div class="mb-4">
                        <h5>Detail Donasi</h5>
                        <p><strong>Nama:</strong> {{ $donation->name }}</p>
                        <p><strong>Email:</strong> {{ $donation->email }}</p>
                        <p><strong>Jumlah:</strong> Rp {{ number_format($donation->amount, 0, ',', '.') }}</p>
                    </div>

                    <button id="pay-button" class="btn btn-primary btn-lg">
                        <i class="fas fa-credit-card"></i> Bayar Sekarang
                    </button>

                    <div class="mt-3">
                        <small class="text-muted">
                            Anda akan diarahkan ke halaman pembayaran Midtrans yang aman
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('javascript')
<!-- Midtrans Snap.js -->
<script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="{{ $clientKey }}"></script>
<!-- Untuk Production gunakan: -->
<!-- <script src="https://app.midtrans.com/snap/snap.js" data-client-key="{{ $clientKey }}"></script> -->

<script>
    document.getElementById('pay-button').onclick = function() {
        // Panggil Midtrans Snap
        snap.pay('{{ $snapToken }}', {
            onSuccess: function(result) {
                console.log('Payment success:', result);
                // Redirect ke halaman finish
                window.location.href = "{{ route('midtrans.finish') }}?order_id=" + result.order_id + 
                                      "&status_code=" + result.status_code + 
                                      "&transaction_status=" + result.transaction_status;
            },
            onPending: function(result) {
                console.log('Payment pending:', result);
                window.location.href = "{{ route('midtrans.pending') }}";
            },
            onError: function(result) {
                console.log('Payment error:', result);
                window.location.href = "{{ route('midtrans.error') }}";
            },
            onClose: function() {
                alert('Anda menutup popup pembayaran tanpa menyelesaikan pembayaran');
                window.location.href = "{{ route('home.donate') }}";
            }
        });
    };
</script>
@endsection