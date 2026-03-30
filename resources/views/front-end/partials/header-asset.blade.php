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
                        @php
                            $sidebarGroups = \App\Services\AccessControlService::getSidebarMenus();
                        @endphp

                        @foreach($sidebarGroups as $group)
                            <p class="menu-heading uppercase text-[10px] font-bold tracking-widest text-n500 mt-6">{{ $group['heading'] }}</p>
                            <ul class="menu-ul">
                                @foreach($group['items'] as $item)
                                    <li class="menu-li">
                                        <button class="menu-btn transition-all duration-300 {{ request()->routeIs($item['route']) ? 'bg-primary/10 text-primary border-primary' : 'bg-n0 border-n30 hover:border-primary/50' }} dark:bg-bg4 dark:border-n500">
                                            <a href="{{ route($item['route']) }}"
                                                class="flex items-center justify-start gap-3 w-full px-4 py-2">
                                                <span class="menu-icon text-xl">
                                                    <i class="{{ $item['icon'] }}"></i>
                                                </span>
                                                <span class="menu-title font-bold text-sm italic">{{ $item['title'] }}</span>
                                            </a>
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        @endforeach

                        <ul class="menu-ul mt-8 border-t border-n30 pt-6">
                            <li class="menu-li text-center">
                                <a href="{{ route('logout') }}" class="btn-logout flex items-center justify-center gap-2 bg-danger/10 text-danger p-3 rounded-2xl hover:bg-danger hover:text-white transition-all font-black uppercase text-xs tracking-tighter italic">
                                    <i class="las la-sign-out-alt text-lg"></i>
                                    Déconnexion
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </aside>
</section>
