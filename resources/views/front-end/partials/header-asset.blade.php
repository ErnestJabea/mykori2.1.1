<section class="topbar-container z-30">
    <!-- Backdrop Overlay for Mobile Sidebar -->
    <div id="sidebar-overlay" class="fixed inset-0 bg-n900/60 backdrop-blur-sm z-40 hidden transition-opacity duration-300"></div>

    <nav class="navbar-top topbarfull z-20 gap-3 bg-n0 py-3 shadow-sm duration-300 border-b border-n0 dark:border-n700 dark:bg-bg4 xl:py-4 xxxl:py-6"
        id="topbar">
        <div class="topbar-inner flex items-center justify-between">
            <div class="flex grow items-center gap-4 xxl:gap-6">
                <a href="./" class="topbar-logo hidden shrink-0">
                    <img width="174" height="38" src="{{ asset('images/logo-with-text.png') }}"
                        alt="Kori Asset Management" class="logo-full2 hidden lg:block" />
                </a>
                <button aria-label="sidebar-toggle-btn"
                    class="flex items-center justify-center h-10 w-10 rounded-xl bg-primary text-white shadow-sm hover:bg-primary/90 transition-all"
                    id="sidebar-toggle-btn">
                    <i class="las la-bars text-2xl"></i>
                </button>
                <!-- Select layout -->
                <div class="topnav-layout">
                </div>
            </div>
            <div class="flex items-center gap-3 sm:gap-4 xxl:gap-6">
                <!-- dark mode toggle -->
                <button id="darkModeToggle" onclick="toggleDarkMode(event)" aria-label="dark mode switch"
                    class="h-10 w-10 shrink-0 rounded-full border border-n30 bg-primary/5 dark:border-n500 dark:bg-bg3 md:h-12 md:w-12 flex items-center justify-center cursor-pointer">
                    <i class="las la-sun text-2xl dark:hidden"></i>
                    <span class="hidden text-n30 dark:block">
                        <i class="las la-moon text-2xl"></i>
                    </span>
                </button>

                <div class="flex items-center gap-3 md:gap-4">
                    <div class="user-profile relative">
                        <button id="profile-btn" onclick="toggleProfileMenu(event)" class="flex items-center gap-3 rounded-full bg-n0 p-1 dark:bg-bg3 cursor-pointer">

                            <div class="round-fill round-full flex items-center justify-center bg-primary/10 text-primary font-bold">
                                @php
                                    $user_ = Auth::user();
                                @endphp
                                @if($user_)
                                    {{ mb_substr($user_->name, 0, 1) }}
                                @endif
                            </div>
                            <span class="hidden text-sm font-medium lg:block">
                                {{ $user_ ? $user_->name : 'Utilisateur' }}
                            </span>
                            <i class="las la-angle-down hidden text-lg lg:block"></i>
                        </button>
                        <div id="profile-dropdown"
                            style="display: none;"
                            class="hide hidden absolute top-full ltr:right-0 rtl:left-0 z-50 w-[200px] origin-top rounded-md bg-n0 p-4 shadow-lg duration-300 dark:bg-bg4">
                            <ul class="flex flex-col gap-2">
                                <li>
                                    <a href="{{ route('profile') }}"
                                        class="flex items-center gap-2 rounded-md p-2 duration-300 hover:bg-primary hover:text-n0">
                                        <span>
                                            <i class="las la-user mt-1 text-xl"></i>
                                        </span>
                                        Mon profile
                                    </a>
                                </li>
                                <li>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit"
                                            class="flex w-full items-center gap-2 rounded-md p-2 text-left duration-300 hover:bg-primary hover:text-n0">
                                            <span>
                                                <i class="las la-sign-out-alt mt-1 text-xl"></i>
                                            </span>
                                            Déconnexion
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Vertical Sidebar Drawer -->
    <aside id="sidebar" class="sidebar bg-n0 dark:!bg-bg4">
        <div class="sidebar-inner relative">
            <div class="logo-column">
                <div class="logo-container">
                    <div class="logo-inner flex items-center justify-between px-4 py-3">
                        <a href="{{ route('dashboard') }}" class="logo-wrapper">
                            <img src="{{ asset('images/logo-with-text.png') }}" width="150" height="32"
                                class="logo-full" alt="logo" />
                        </a>
                        <button class="sidebar-close-btn xl:hidden flex items-center justify-center h-8 w-8 rounded-full bg-n30 text-n700 dark:bg-n700 dark:text-n100 hover:bg-primary hover:text-white transition-all" id="sidebar-close-btn">
                            <i class="las la-times text-xl"></i>
                        </button>
                    </div>
                </div>
                <div class="pb-28">
                    <div class="menu-wrapper px-4">
                        @php
                            $sidebarGroups = \App\Services\AccessControlService::getSidebarMenus();
                        @endphp

                        @foreach($sidebarGroups as $group)
                            <p class="menu-heading uppercase text-[10px] font-bold tracking-widest text-n500 dark:text-n30 mt-6 mb-2">{{ $group['heading'] }}</p>
                            <ul class="menu-ul flex flex-col gap-1">
                                @foreach($group['items'] as $item)
                                    @php
                                        $isActive = (\Route::has($item['route']) && request()->routeIs($item['route'])) || request()->is($item['route'] . '*');
                                    @endphp
                                    <li class="menu-li">
                                        <a href="{{ \Route::has($item['route']) ? route($item['route']) : url($item['route']) }}"
                                            class="flex items-center justify-start gap-3 w-full px-4 py-2.5 rounded-xl transition-all duration-200 {{ $isActive ? 'bg-primary text-white font-bold shadow-sm' : 'text-n700 dark:text-white hover:bg-primary/10 hover:text-primary dark:hover:text-primary' }}">
                                            <span class="menu-icon text-xl {{ $isActive ? 'text-white' : 'text-primary' }}">
                                                <i class="{{ $item['icon'] }}"></i>
                                            </span>
                                            <span class="menu-title font-semibold text-sm">{{ $item['title'] }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @endforeach

                        <ul class="menu-ul mt-8 border-t border-n30 pt-6 dark:border-n700">
                            <li class="menu-li text-center">
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="btn-logout flex w-full items-center justify-center gap-2 bg-danger/10 text-danger dark:bg-danger/20 dark:text-red-400 p-3 rounded-2xl hover:bg-danger hover:text-white transition-all font-bold uppercase text-xs tracking-wider">
                                        <i class="las la-sign-out-alt text-lg"></i>
                                        Déconnexion
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </aside>
</section>

<style>
    @media (max-width: 1199.98px) {
        #sidebar {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            bottom: 0 !important;
            z-index: 50 !important;
            width: 290px !important;
            max-width: 85vw !important;
            transition: transform 0.3s ease-in-out !important;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35) !important;
        }
        #sidebar.sidebarhide {
            transform: translateX(-100%) !important;
            visibility: hidden !important;
        }
        #sidebar.sidebarshow {
            transform: translateX(0) !important;
            visibility: visible !important;
        }
        #topbar.topbarmargin, #topbar.topbarfull {
            margin-left: 0 !important;
            width: 100% !important;
            left: 0 !important;
        }
        .main-content, .main-content.has-sidebar {
            margin-left: 0 !important;
            width: 100% !important;
        }
    }

    /* Force pure white text on dark mode sidebar menus */
    body.dark #sidebar .menu-title,
    body.dark #sidebar a:not(.btn-logout) {
        color: #ffffff !important;
    }
    body.dark #sidebar .menu-heading {
        color: #cbd5e1 !important;
    }
</style>

<script>
    function toggleDarkMode(e) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }
        const isDark = document.body.classList.contains('dark');
        if (isDark) {
            document.body.classList.remove('dark');
            localStorage.setItem('darkMode', 'false');
            document.documentElement.style.colorScheme = 'light';
        } else {
            document.body.classList.add('dark');
            localStorage.setItem('darkMode', 'true');
            document.documentElement.style.colorScheme = 'dark';
        }
        if (typeof loadImg === 'function') loadImg();
    }

    function toggleProfileMenu(e) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }
        const dropdown = document.getElementById('profile-dropdown');
        if (!dropdown) return;

        const isHidden = dropdown.style.display === 'none' || dropdown.classList.contains('hidden') || dropdown.classList.contains('hide');

        if (isHidden) {
            dropdown.style.display = 'block';
            dropdown.classList.remove('hidden', 'hide');
            dropdown.classList.add('block', 'show');
        } else {
            dropdown.style.display = 'none';
            dropdown.classList.add('hidden', 'hide');
            dropdown.classList.remove('block', 'show');
        }
    }

    document.addEventListener('click', function(e) {
        const dropdown = document.getElementById('profile-dropdown');
        const profileBtn = document.getElementById('profile-btn');
        if (dropdown && profileBtn && !dropdown.contains(e.target) && !profileBtn.contains(e.target)) {
            dropdown.style.display = 'none';
            dropdown.classList.add('hidden', 'hide');
            dropdown.classList.remove('block', 'show');
        }
    });
</script>
