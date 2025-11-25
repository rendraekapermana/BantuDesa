@extends('layouts.app')


@section('content')
    <main role="main">

        <div class="container mt-md-5">
            <!-- Three columns of text below the carousel -->
            <div class="row">
              <h2 class="text-center">Tentang BantuDesa</h2>
              <p>BantuDesa adalah platform donasi sosial yang menghubungkan masyarakat dengan desa-desa di Indonesia melalui teknologi. Kami percaya kemajuan Indonesia dimulai dari desa. Melalui transparansi blockchain dan sistem pembayaran digital yang mudah, setiap donasi dicatat secara aman, transparan, dan dapat dipantau perkembangannya secara real-time.</p>
              @foreach ($member as $person)
                  <div class="col-md-4 text-center">
                    <div class="card">
                        <div class="card-title mt-2">
                            <div class="rounded-circle mx-auto" style="width:150px; height:150px; background:url({{ asset('images/members/'.$person->image) }}) center no-repeat ; background-size :cover;" ></div>
                        </div>
                        <div class="card-body">
                            <h4 class="fw-bold mb-0">{{ $person->name }}</h4>
                            <small class="text-muted">&horbar; {{ $person->designation }}</small>
                            <p class="text-italic fw-light my-2">
                                <i class="fa fa-quote-left text-muted fa-sm"></i>
                                {{ $person->quote }}
                                <i class="fa fa-quote-right text-muted fa-sm"></i>
                            </p>
                        </div>
                    </div>
                </div>
              @endforeach
            </div><!-- /.row -->

            <!-- START THE FEATURETTES -->
              <div class="row featurette my-md-5 mt-5 mt-md-5">
                <div class="col-md-7">
                    <h2 class="featurette-heading">Teknologi untuk Desa yang Lebih Maju</h2>
                    <p class="lead">BantuDesa hadir sebagai jembatan digital antara desa, pelaku UMKM, dan masyarakat. Dengan layanan yang terintegrasi, kami membantu desa mengelola data, mempromosikan potensi lokal, dan memberikan akses teknologi yang mudah digunakan oleh semua.</p>
                </div>
                <div class="col-md-5">
                    <img class="featurette-image rounded mx-auto img-fluid mx-auto" src="{{ asset('images/about1.jpg') }}"
                        alt="Generic placeholder image">
                </div>
              </div>

              <div class="row featurette my-md-5 mt-5 mt-md-5">
                <div class="col-md-7 order-md-2">
                  <h2 class="featurette-heading">Mendukung Pertumbuhan UMKM Desa</h2>
                  <p class="lead">UMKM adalah tulang punggung ekonomi desa. Melalui platform BantuDesa, pelaku usaha lokal dapat memasarkan produk mereka secara digital, menjangkau lebih banyak pelanggan, dan meningkatkan pendapatan yang berkelanjutan.</p>
                </div>
                <div class="col-md-5 order-md-1">
                  <img class="featurette-image rounded mx-auto img-fluid mx-auto" src="{{ asset('images/about2.jpg') }}"
                      alt="Generic placeholder image">
                </div>
              </div>

              <div class="row featurette my-md-5 mt-5 mt-md-5">
                <div class="col-md-7">
                    <h2 class="featurette-heading">Administrasi Desa yang Lebih Mudah & Efisien</h2>
                    <p class="lead">Mulai dari pengelolaan dokumen, pencatatan penduduk, hingga laporan keuangan desa — semuanya bisa diakses dalam satu sistem. Dengan digitalisasi, pelayanan menjadi lebih cepat, transparan, dan akurat.</p>
                </div>
                <div class="col-md-5">
                    <img class="featurette-image rounded mx-auto img-fluid mx-auto" src="{{ asset('images/about3.avif') }}"
                        alt="Generic placeholder image">
                </div>
              </div>



            <!-- /END THE FEATURETTES -->

        </div><!-- /.container -->

        <p class="text-center"><a href="#">Back to top</a></p>
    </main>
@endsection
@section('javascript')
    <script></script>
@endsection
