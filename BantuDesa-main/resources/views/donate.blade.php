@extends('layouts.app')

@section('css')
<style>
    .hidden {
        display: none;
    }

    #payment-message {
        color: rgb(105, 115, 134);
        font-size: 16px;
        line-height: 20px;
        padding-top: 12px;
        text-align: center;
    }

    #payment-element {
        margin-bottom: 24px;
    }

    /* Buttons and links */
    button[type=submit] {
        background: #5469d4;
        font-family: Arial, sans-serif;
        color: #ffffff;
        border-radius: 4px;
        border: 0;
        padding: 12px 16px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        display: block;
        transition: all 0.2s ease;
        box-shadow: 0px 4px 5.5px 0px rgba(0, 0, 0, 0.07);
        width: 100%;
    }

    button[type=submit]:hover {
        filter: contrast(115%);
    }

    button[type=submit]:disabled {
        opacity: 0.5;
        cursor: default;
    }

    /* spinner/processing state, errors */
    .spinner,
    .spinner:before,
    .spinner:after {
        border-radius: 50%;
    }

    .spinner {
        color: #ffffff;
        font-size: 22px;
        text-indent: -99999px;
        margin: 0px auto;
        position: relative;
        width: 20px;
        height: 20px;
        box-shadow: inset 0 0 0 2px;
        -webkit-transform: translateZ(0);
        -ms-transform: translateZ(0);
        transform: translateZ(0);
    }

    .spinner:before,
    .spinner:after {
        position: absolute;
        content: "";
    }

    .spinner:before {
        width: 10.4px;
        height: 20.4px;
        background: #5469d4;
        border-radius: 20.4px 0 0 20.4px;
        top: -0.2px;
        left: -0.2px;
        -webkit-transform-origin: 10.4px 10.2px;
        transform-origin: 10.4px 10.2px;
        -webkit-animation: loading 2s infinite ease 1.5s;
        animation: loading 2s infinite ease 1.5s;
    }

    .spinner:after {
        width: 10.4px;
        height: 10.2px;
        background: #5469d4;
        border-radius: 0 10.2px 10.2px 0;
        top: -0.1px;
        left: 10.2px;
        -webkit-transform-origin: 0px 10.2px;
        transform-origin: 0px 10.2px;
        -webkit-animation: loading 2s infinite ease;
        animation: loading 2s infinite ease;
    }

    @-webkit-keyframes loading {
        0% {
            -webkit-transform: rotate(0deg);
            transform: rotate(0deg);
        }

        100% {
            -webkit-transform: rotate(360deg);
            transform: rotate(360deg);
        }
    }

    @keyframes loading {
        0% {
            -webkit-transform: rotate(0deg);
            transform: rotate(0deg);
        }

        100% {
            -webkit-transform: rotate(360deg);
            transform: rotate(360deg);
        }
    }

    @media only screen and (max-width: 600px) {
        form {
            width: 80vw;
            min-width: initial;
        }
    }
</style>
{{-- Script Stripe dipertahankan jika nanti digunakan lagi --}}
<script src="https://js.stripe.com/v3/"></script>
@endsection

@section('content')
<div class="container">
    <main>
        <div class="py-5 text-center">
            <i class="fa fa-hand-holding-usd fa-5x"></i>
            <p class="lead mb-0 text-italic fs-4"><i class="fa fa-quote-left fa-sm text-muted"></i> No one has ever become poor by giving. <i class="fa fa-quote-right fa-sm text-muted"></i></p>
            <p class="lead text-italic" class="mb-0">Donate a tiny part of your income in charity!</p>
        </div>
    </main>

    {{-- donation form --}}
    <div class="row g-5">
        <h2 class="mb-3 text-center">Donor Information</h2>
        
        {{-- FORM ACTION MENGARAH KE ALUR BLOCKCHAIN BARU --}}
        <form id="payment-form" method="POST" action="{{ route('donation.process') }}" class="mt-0">
            @csrf
            <div class="card">
                <div class="card-body">
                    {{-- ... (Pesan Error/Success Anda) ... --}}
                    
                    <div class="row">
                        {{-- Nama Depan & Belakang --}}
                        <div class="col-md-6">
                            <label class="mb-0">First name</label>
                            <input type="text" class="form-control mb-3 required" name="first_name"
                                value="{{ old('first_name') }}" placeholder="First Name">
                        </div>
                        <div class="col-md-6">
                            <label class="mb-0">Last name</label>
                            <input type="text" class="form-control mb-3 required" name="last_name"
                                value="{{ old('last_name') }}" placeholder="Last Name">
                        </div>
                        {{-- Email & Mobile --}}
                        <div class="col-md-6">
                            <label class="mb-0">Email address</label>
                            <input type="text" class="form-control mb-3 required" name="email"
                                value="{{ old('email') }}" placeholder="Email Address">
                        </div>
                        <div class="col-md-6">
                            <label class="mb-0">Mobile number</label>
                            <input type="text" class="form-control mb-3 optional mobile" name="mobile"
                                value="{{ old('mobile') }}" placeholder="Mobile number">
                        </div>
                        {{-- Street address --}}
                        <div class="col-md-6">
                            <label class="mb-0">Street address</label>
                            <input type="text" class="form-control mb-3 optional" name="street_address"
                                value="{{ old('street_address') }}" placeholder="Street address">
                        </div>
                        
                        {{-- V V V GANTI DARI SELECT2 KE INPUT TEXT SEDERHANA V V V --}}
                        <div class="col-md-6 mb-3">
                            <label class="mb-0">Country Name</label>
                            <input type="text" class="form-control mb-3 optional" name="country_name"
                                value="{{ old('country_name') }}" placeholder="Country">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="mb-0">State Name</label>
                            <input type="text" class="form-control mb-3 optional" name="state_name"
                                value="{{ old('state_name') }}" placeholder="State">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="mb-0">City Name</label>
                            <input type="text" class="form-control mb-3 optional" name="city_name"
                                value="{{ old('city_name') }}" placeholder="City">
                        </div>
                        {{-- ^ ^ ^ END INPUT TEXT ^ ^ ^ --}}
                        
                        {{-- Amount --}}
                        <div class="col-md-12">
                            <label class="mb-0">Amount you want to donate <small class="text-muted">(Minimum
                                    @if(env("DONATION_CURRENCY") == "USD") $@elseif(env("DONATION_CURRENCY") == "INR")&#8377;@else{{ env("DONATION_CURRENCY") }}@endif{{number_format(env('MIN_DONATION_AMOUNT'),2,".",",")}})</small></label>
                            <input type="number" class="form-control form-control-lg mb-1 required"
                                name="amount" value="{{ old('amount') }}"
                                placeholder="Donation Amount in @if(env(" DONATION_CURRENCY")=="USD" ) $ (USD) @elseif(env("DONATION_CURRENCY")=="INR" ) &#8377; (INR) @else {{ env("DONATION_CURRENCY") }} @endif" min="{{env('MIN_DONATION_AMOUNT')}}">
                        </div>
                        
                        {{-- Leaderboard Checkbox --}}
                        <div class="col-md-12">
                            <div class="form-check form-switch fw-bold">
                                <input type="checkbox" class="form-check-input border-secondary" name="add_to_leaderboard"
                                    @if (old('add_to_leaderboard')=='yes' ) checked="checked" @endif value="yes"
                                    id="flexSwitchCheckDefault" role="button">
                                <label class="form-check-label" for="flexSwitchCheckDefault" role="button">
                                    Show your name on Donor's <a href="{{ route('home.leaderboard') }}" target="_blank">Leaderboard</a>?</label>
                            </div>
                        </div>
                    </div>
                    
                    {{-- FIELD KRITIS: HIDDEN FIELD UNTUK NAMA DONATUR --}}
                    <input type="hidden" name="donor_name" id="donor_name_field">
                    
                </div>
                <div class="card-footer">
                    <button type="submit">
                        <span id="button-text">Donasi dan Catat Blockchain</span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- leaderboard section --}}
    @include('components.leaderboard')

</div>
@endsection

@section('javascript')
<script>
    $(function() {
        // HANYA PERTAHANKAN LOGIC SUBMIT DAN PENGGABUNGAN NAMA
        $(document).on('submit', '#payment-form', function(e) {
            let form = $(this);
            
            form.find('[type=submit]').prop('disabled', true);

            // LOGIC PENGGABUNGAN NAMA KRITIS:
            let firstName = form.find('[name=first_name]').val() || '';
            let lastName = form.find('[name=last_name]').val() || '';
            let donorName = firstName.trim() + ' ' + lastName.trim();

            // Isi field tersembunyi 'donor_name'
            form.find('#donor_name_field').val(donorName.trim());
        });

        // KODE SELECT2 TELAH DIHAPUS SEPENUHNYA UNTUK MENGHINDARI ROUTENOTFOUNDEXCEPTION
    });
</script>
@endsection