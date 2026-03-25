<section class="topbar-container z-30">
    <nav class="navbar-top topbarfull z-20 gap-3 bg-n0 py-3 shadow-sm duration-300 border-b border-n0 dark:border-n700 dark:bg-bg4 xl:py-4 xxxl:py-6"
        id="topbar">
        <div class="topbar-inner flex items-center justify-between">
            <div class="flex grow items-center gap-4 xxl:gap-6">
                <a href="./" class="topbar-logo hidden shrink-0">
                    <img width="174" height="38" src="{{ asset('images/logo-with-text.png') }}"
                        alt="Kori Asset Management" class="logo-full2 hidden lg:block" />
                </a>
                <button aria-label="sidebar-toggle-btn"
                    class="flex items-center rounded-s-2xl bg-primary px-0.5 py-3 text-xl text-n0"
                    id="sidebar-toggle-btn">
                    <i class="las la-angle-left text-lg"></i>
                </button>
                <!-- Select layout -->
                <div class="topnav-layout">
                </div>
            </div>
            <div class="flex items-center gap-3 sm:gap-4 xxl:gap-6">
                <!-- dark mode toggle -->
                <button id="darkModeToggle" aria-label="dark mode switch"
                    class="h-10 w-10 shrink-0 rounded-full border border-n30 bg-primary/5 dark:border-n500 dark:bg-bg3 md:h-12 md:w-12">
                    <i class="las la-sun text-2xl dark:hidden"></i>
                    <span class="hidden text-n30 dark:block">
                        <i class="las la-moon text-2xl"></i>
                    </span>
                </button>

                <div class="flex items-center gap-3 md:gap-4">
                    <div class="user-profile relative">
                        <button id="profile-btn" class="flex items-center gap-3 rounded-full bg-n0 p-1 dark:bg-bg3">

                            <div class="round-fill round-full">
                                @php
                                    $user_ = App\Models\User::where('id', Auth::user()->id)->first();
                                @endphp
                                {{ mb_substr($user_->name, 0, 1) }}
                            </div>
                            <span class="hidden text-sm font-medium lg:block">
                                {{ Auth::user()->name }}
                            </span>
                            <i class="las la-angle-down hidden text-lg lg:block"></i>
                        </button>
                        <div id="profile-dropdown"
                            class="hide absolute top-full ltr:right-0 rtl:left-0 z-20 w-[200px] origin-top rounded-md bg-n0 p-4 shadow-lg duration-300 dark:bg-bg4">
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
                                    <a href="{{ route('logout') }}"
                                        class="flex items-center gap-2 rounded-md p-2 duration-300 hover:bg-primary hover:text-n0">
                                        <span>
                                            <i class="las la-sign-out-alt mt-1 text-xl"></i>
                                        </span>
                                        Déconnexion
                                    </a>
                                </li>
                            </ul>
                        </div>
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
                <div class=" pb-28">
                    <div class="menu-wrapper">
                        <!-- Navigation Asset Manager (Roles != 5) -->
                        @if (Auth::user()->role_id != 5)
                            <p class="menu-heading">Navigation Asset Manager</p>
                            <ul class="menu-ul">
                                <li class="menu-li">
                                    <button class="menu-btn border-n30 bg-n0 dark:!border-n500 dark:bg-bg4">
                                        <a href="{{ route('asset-manager') }}"
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
                                        <a href="{{ route('customer') }}"
                                            class="flex items-center justify-center gap-2">
                                            <span class="menu-icon">
                                                <i class="las la-piggy-bank"></i>
                                            </span>
                                            <span class="menu-title font-medium">Clients</span>
                                        </a>
                                    </button>
                                    <ul class="submenu-hide submenu">
                                    </ul>
                                </li>
                                <li class="menu-li">
                                    <button class="menu-btn group bg-n0 dark:!border-n500 dark:!bg-bg4">
                                        <a href="{{ route('asset-manager.create-customer') }}"
                                            class="flex items-center justify-center gap-2">
                                            <span class="menu-icon">
                                                <i class="las la-user-plus text-primary"></i>
                                            </span>
                                            <span class="menu-title font-medium">Créer un client</span>
                                        </a>
                                    </button>
                                    <ul class="submenu-hide submenu">
                                    </ul>
                                </li>
                            </ul>
                        @endif

                        <!-- Navigation Compliance (Roles 5 or Admin) -->
                        @if (Auth::user()->role_id == 5 || Auth::user()->role_id == 1)
                            <p class="menu-heading">Navigation Compliance</p>
                            <ul class="menu-ul">
                                <li class="menu-li">
                                    <button class="menu-btn group bg-n0 dark:!border-n500 dark:!bg-bg4">
                                        <a href="{{ route('compliance.dashboard') }}"
                                            class="flex items-center justify-center gap-2">
                                            <span class="menu-icon">
                                                <i class="las la-shield-alt"></i>
                                            </span>
                                            <span class="menu-title font-medium">Tableau de bord</span>
                                        </a>
                                    </button>
                                </li>
                                <li class="menu-li">
                                    <button class="menu-btn group bg-n0 dark:!border-n500 dark:!bg-bg4">
                                        <a href="{{ route('compliance.clients') }}"
                                            class="flex items-center justify-center gap-2">
                                            <span class="menu-icon">
                                                <i class="las la-user-check"></i>
                                            </span>
                                            <span class="menu-title font-medium">Audit Clients</span>
                                        </a>
                                    </button>
                                </li>
                                <li class="menu-li">
                                    <button class="menu-btn group bg-n0 dark:!border-n500 dark:!bg-bg4">
                                        <a href="{{ route('compliance.vl-history') }}"
                                            class="flex items-center justify-center gap-2">
                                            <span class="menu-icon">
                                                <i class="las la-history"></i>
                                            </span>
                                            <span class="menu-title font-medium">Historique VL</span>
                                        </a>
                                    </button>
                                </li>
                            </ul>
                        @endif

                        <ul class="menu-ul mt-4 border-t border-n30 pt-4">
                            <li class="menu-li">
                                <button class="menu-btn group bg-n0 dark:!border-n500 dark:!bg-bg4">
                                    <a href="{{ route('logout') }}" class="flex items-center justify-center gap-2">
                                        <span class="menu-icon">
                                            <i class="las la-sign-out-alt"></i>
                                        </span>
                                        <span class="menu-title font-medium">Déconnexion</span>
                                    </a>
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </aside>
</section>
