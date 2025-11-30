<nav id="sidebar" class="sidebar js-sidebar">
    <div class="sidebar-content js-simplebar">
        <a class="sidebar-brand" href="{{ route('auth.dashboard') }}">
            <span class="align-middle">BantuDesa</span>
        </a>

        <ul class="sidebar-nav">
            <li class="sidebar-header">
                Pages
            </li>

            <li class="sidebar-item @if(Route::is('auth.dashboard')) active @endif">
                <a class="sidebar-link" href="{{ route('auth.dashboard') }}">
                    <i class="align-middle" data-feather="sliders"></i> <span class="align-middle">Dashboard</span>
                </a>
            </li>

            <!-- MENU CAMPAIGNS BARU -->
            <li class="sidebar-item @if(Route::is('auth.campaigns.*')) active @endif">
                <a class="sidebar-link" href="{{ route('auth.campaigns.index') }}">
                    <i class="align-middle" data-feather="flag"></i> <span class="align-middle">Campaigns</span>
                </a>
            </li>

            <li class="sidebar-item @if(Route::is('auth.queries')) active @endif">
                <a class="sidebar-link" href="{{ route('auth.queries') }}">
                    <i class="align-middle" data-feather="mail"></i> <span class="align-middle">Queries</span>
                </a>
            </li>

            <li class="sidebar-item @if(Route::is('auth.donation')) active @endif">
                <a class="sidebar-link" href="{{ route('auth.donation') }}">
                    <i class="align-middle" data-feather="credit-card"></i> <span class="align-middle">Donation List</span>
                </a>
            </li>

            <li class="sidebar-item @if(Route::is('auth.albums')) active @endif">
                <a class="sidebar-link" href="{{ route('auth.albums') }}">
                    <i class="align-middle" data-feather="image"></i> <span class="align-middle">Albums</span>
                </a>
            </li>

            <li class="sidebar-item @if(Route::is('auth.members')) active @endif">
                <a class="sidebar-link" href="{{ route('auth.members') }}">
                    <i class="align-middle" data-feather="users"></i> <span class="align-middle">Members</span>
                </a>
            </li>
        </ul>

        <!-- Optional: Footer Sidebar -->
        <!-- <div class="sidebar-cta">
            <div class="sidebar-cta-content">
                <strong class="d-inline-block mb-2">Upgrade to Pro</strong>
                <div class="mb-3 text-sm">
                    Are you looking for more components? Check out our premium version.
                </div>
                <div class="d-grid">
                    <a href="upgrade-to-pro.html" class="btn btn-primary">Upgrade to Pro</a>
                </div>
            </div>
        </div> -->
    </div>
</nav>