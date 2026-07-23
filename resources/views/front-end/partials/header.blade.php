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
                <!-- Notification
                <div class="relative">
                    <button id="notification-btn"
                        class="relative h-10 w-10 rounded-full border border-n30 bg-primary/5 dark:border-n500 dark:bg-bg3 md:h-12 md:w-12">
                        <i class="las la-bell text-2xl"></i>
                        <span
                            class="absolute -right-1 -top-1 flex h-5 w-5 items-center justify-center rounded-full bg-primary text-xs text-n0">
                            2
                        </span>
                    </button>
                    <div id="notification"
                        class="hide absolute top-full z-20 origin-[60%_0] rounded-md bg-n0 shadow-[0px_6px_30px_0px_rgba(0,0,0,0.08)] duration-300 dark:bg-bg4 ltr:-right-[110px] sm:ltr:right-0 sm:ltr:origin-top-right rtl:-left-[120px] sm:rtl:left-0 sm:rtl:origin-top-left">
                        <div class="flex items-center justify-between border-b p-3 dark:border-n500 lg:px-4">
                            <h5 class="h5">Notifications</h5>
                            <a href="#" class="text-sm text-primary"> Tout voir </a>
                        </div>
                        <ul class="flex w-[300px] flex-col p-4">
                            <div class="flex cursor-pointer gap-2 rounded-md p-2 duration-300 hover:bg-primary/10">
                                <img src="{{ asset('images/user-3.png') }}" width="44" height="40"
                                    class="shrink-0 rounded-full" alt="img" />
                                <div class="text-sm">
                                    <div class="flex gap-1">
                                        <span class="font-medium">Benjamin</span>
                                        <span>Sent a message</span>
                                    </div>
                                    <span class="text-xs text-n100 dark:text-n50">1 hour ago</span>
                                </div>
                            </div>
                            <div class="flex cursor-pointer gap-2 rounded-md p-2 duration-300 hover:bg-primary/10">
                                <img src="{{ asset('images/user-4.png') }}" width="44" height="40"
                                    class="shrink-0 rounded-full" alt="img" />
                                <div class="text-sm">
                                    <div class="flex gap-1">
                                        <span class="font-medium">Benjamin</span>
                                        <span>Left a Comment</span>
                                    </div>
                                    <span class="text-xs text-n100 dark:text-n50">1 hour ago</span>
                                </div>
                            </div>
                            <div class="flex cursor-pointer gap-2 rounded-md p-2 duration-300 hover:bg-primary/10">
                                <img src="{{ asset('images/user-5.png') }}" width="44" height="40"
                                    class="shrink-0 rounded-full" alt="img" />
                                <div class="text-sm">
                                    <div class="flex gap-1">
                                        <span class="font-medium">Benjamin</span>
                                        <span>Sent a message</span>
                                    </div>
                                    <span class="text-xs text-n100 dark:text-n50">2 hour ago</span>
                                </div>
                            </div>
                            <div class="flex cursor-pointer gap-2 rounded-md p-2 duration-300 hover:bg-primary/10">
                                <img src="{{ asset('images/user-7.png') }}" width="44" height="40"
                                    class="shrink-0 rounded-full" alt="img" />
                                <div class="text-sm">
                                    <div class="flex gap-1">
                                        <span class="font-medium">Samuel</span>
                                        <span>Uploaded a file</span>
                                    </div>
                                    <span class="text-xs text-n100 dark:text-n50">Yesterday</span>
                                </div>
                            </div>
                            <div class="flex cursor-pointer gap-2 rounded-md p-2 duration-300 hover:bg-primary/10">
                                <img src="{{ asset('images/user-7.png') }}" width="44" height="40"
                                    class="shrink-0 rounded-full" alt="img" />
                                <div class="text-sm">
                                    <div class="flex gap-1">
                                        <span class="font-medium">David</span>
                                        <span>Left a Comment</span>
                                    </div>
                                    <span class="text-xs text-n100 dark:text-n50">Yesterday</span>
                                </div>
                            </div>
                        </ul>
                    </div>
                </div>-->
                <!-- language dropdown
                <div class="relative">
                    <button id="language-btn"
                        class="flex gap-1 rounded-full border border-n30 bg-primary/5 p-2 dark:border-n500 dark:bg-bg3 md:p-3">
                        <i class="las la-language"></i>
                    </button>
                    <div id="language"
                        class="hide absolute top-full z-20 rounded-md bg-n0 shadow-[0px_6px_30px_0px_rgba(0,0,0,0.08)] duration-300 dark:bg-bg4 ltr:right-0 ltr:origin-top-right rtl:left-0 rtl:origin-top-left">
                        <ul class="flex w-32 flex-col rounded-md bg-n0 p-1 dark:bg-bg4">
                            <li
                                class="active block cursor-pointer rounded-md px-4 py-2 duration-300 hover:text-primary">
                                English
                            </li>
                            <li class="block cursor-pointer rounded-md px-4 py-2 duration-300 hover:text-primary">
                                Français
                            </li>
                            <li class="block cursor-pointer rounded-md px-4 py-2 duration-300 hover:text-primary">
                                {{ Auth::user()->id }}
                            </li>
                        </ul>
                    </div>
                </div>-->
                <!-- Profile dropdown -->
                <div class="relative shrink-0">
                    <div id="profile-btn" onclick="toggleProfileMenu(event)" class="w-10 cursor-pointer md:w-12">
                        <div class="round-fill round-full">
                            @php
                                $user_ = App\Models\User::where('id', Auth::user()->id)->first();
                            @endphp
                            {{ mb_substr($user_->name, 0, 1) }}
                        </div>

                    </div>
                    <div id="profile-dropdown"
                        style="display: none;"
                        class="hide hidden absolute top-full z-50 rounded-md bg-n0 shadow-[0px_6px_30px_0px_rgba(0,0,0,0.08)] duration-300 dark:bg-bg4 ltr:right-0 ltr:origin-top-right rtl:left-0 rtl:origin-top-left">
                        <div class="flex flex-col items-center border-b p-3 text-center dark:border-n500 lg:p-4">

                            <div class="round-fill round-full">
                                @php
                                    $user_ = App\Models\User::where('id', Auth::user()->id)->first();
                                @endphp
                                {{ mb_substr($user_->name, 0, 1) }}
                            </div>
                            <h6 class="h6 mt-2">{{ $user_->name }}</h6>
                            <span class="text-sm">{{ $user_->email }}</span>
                        </div>
                        <ul class="flex w-[250px] flex-col p-4">
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
    </nav>

    <!-- Vertical -->
    <aside id="sidebar" class="sidebar bg-n0 dark:!bg-bg4">
        <div class="sidebar-inner relative">
            <div class="logo-column">
                <div class="logo-container">
                    <div class="logo-inner">
                        <a href="{{ route('dashboard') }}" class="logo-wrapper">
                            <img src="{{ asset('images/logo-with-text.png') }}" width="174" height="38"
                                class="logo-full" alt="logo" />
                            <img src="{{ asset('images/logo-with-text.png') }}" width="37" height="36"
                                class="logo-icon hidden" alt="logo" />
                        </a>
                        <img width="141" height="38" class="logo-text hidden"
                            src="{{ asset('images/logo-text.png') }}" alt="logo text" />
                        <button class="sidebar-close-btn xl:hidden" id="sidebar-close-btn">
                            <i class="las la-times"></i>
                        </button>
                    </div>
                </div>
                <div class="menu-container pb-28">
                    <div class="menu-wrapper">
                        <p class="menu-heading">Navigation</p>
                        <ul class="menu-ul">
                            <li class="menu-li">
                                <button class="menu-btn border-n30 bg-n0 dark:!border-n500 dark:bg-bg4">
                                    <a href="{{ route('dashboard') }}"
                                        class="flex items-center justify-center gap-2">
                                        <span class="menu-icon">
                                            <i class="las la-home"></i>
                                        </span>
                                        <span class="menu-title font-medium">Tableau de bord</span>
                                    </a>
                                </button>
                                <ul class="submenu-hide submenu">
                                </ul>
                            </li>

                            <li class="menu-li">
                                <button class="menu-btn group bg-n0 dark:!border-n500 dark:!bg-bg4">
                                    <a href="{{ route('my-products') }}"
                                        class="flex items-center justify-center gap-2">
                                        <span class="menu-icon">
                                            <i class="las la-credit-card"></i>
                                        </span>
                                        <span class="menu-title font-medium">Mes souscriptions</span>
                                    </a>
                                </button>
                                <ul class="submenu-hide submenu">
                                </ul>
                            </li>
                            <li class="menu-li">
                                <button class="menu-btn group bg-n0 dark:!border-n500 dark:!bg-bg4">
                                    <a href="{{ route('my-statements') }}"
                                        class="flex items-center justify-center gap-2">
                                        <span class="menu-icon">
                                            <i class="las la-file-invoice"></i>
                                        </span>
                                        <span class="menu-title font-medium">Mes relevés</span>
                                    </a>
                                </button>
                                <ul class="submenu-hide submenu">
                                </ul>
                            </li>
                            <!--<li class="menu-li">
                                <button class="menu-btn group bg-n0 dark:!border-n500 dark:!bg-bg4">
                                    <a href="{{ route('help') }}" class="flex items-center justify-center gap-2">
                                        <span class="menu-icon">
                                            <i class="las la-handshake"></i>
                                        </span>
                                        <span class="menu-title font-medium">Aide</span>
                                    </a>
                                </button>
                                <ul class="submenu-hide submenu">
                                </ul>
                            </li>-->
                            <li class="menu-li">
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="menu-btn group bg-n0 dark:!border-n500 dark:!bg-bg4">
                                        <span class="flex items-center justify-center gap-2">
                                            <span class="menu-icon">
                                                <i class="las la-sign-out-alt "></i>
                                            </span>
                                            <span class="menu-title font-medium">Déconnexion</span>
                                        </span>
                                    </button>
                                </form>
                                <ul class="submenu-hide submenu">
                                </ul>
                            </li>
                                <ul class="submenu-hide submenu">
                                </ul>
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
        const dropdown = document.getElementById('profile-dropdown') || document.getElementById('profile');
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
        const dropdown = document.getElementById('profile-dropdown') || document.getElementById('profile');
        const profileBtn = document.getElementById('profile-btn');
        if (dropdown && profileBtn && !dropdown.contains(e.target) && !profileBtn.contains(e.target)) {
            dropdown.style.display = 'none';
            dropdown.classList.add('hidden', 'hide');
            dropdown.classList.remove('block', 'show');
        }
    });
</script>
