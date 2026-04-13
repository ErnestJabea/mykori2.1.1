@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination Navigation" class="flex items-center justify-between">
        <div class="flex flex-1 justify-between sm:hidden">
            @if ($paginator->onFirstPage())
                <span class="relative inline-flex items-center rounded-md border border-n30 bg-bg1 px-4 py-2 text-sm font-medium text-n100 cursor-default">
                    <i class="las la-angle-left"></i>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="relative inline-flex items-center rounded-md border border-n30 bg-bg1 px-4 py-2 text-sm font-medium text-n900 hover:text-primary transition-all">
                    <i class="las la-angle-left"></i>
                </a>
            @endif

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="relative ml-3 inline-flex items-center rounded-md border border-n30 bg-bg1 px-4 py-2 text-sm font-medium text-n900 hover:text-primary transition-all">
                    <i class="las la-angle-right"></i>
                </a>
            @else
                <span class="relative ml-3 inline-flex items-center rounded-md border border-n30 bg-bg1 px-4 py-2 text-sm font-medium text-n100 cursor-default">
                    <i class="las la-angle-right"></i>
                </span>
            @endif
        </div>

        <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between px-4">
            <div>
                <p class="text-sm text-n100 dark:text-n50 italic">
                    Affichage de 
                    <span class="font-bold">{{ $paginator->firstItem() }}</span>
                    à
                    <span class="font-bold">{{ $paginator->lastItem() }}</span>
                    sur
                    <span class="font-bold">{{ $paginator->total() }}</span>
                    relevés
                </p>
            </div>

            <div>
                <span class="relative z-0 inline-flex shadow-sm rounded-xl overflow-hidden border border-n30 dark:border-n500">
                    {{-- Previous Page Link --}}
                    @if ($paginator->onFirstPage())
                        <span aria-disabled="true" aria-label="@lang('pagination.previous')">
                            <span class="relative inline-flex items-center px-4 py-2 bg-bg1 text-sm font-medium text-n100 cursor-default" aria-hidden="true">
                                <i class="las la-angle-left text-lg"></i>
                            </span>
                        </span>
                    @else
                        <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="relative inline-flex items-center px-4 py-2 bg-white dark:bg-bg4 text-sm font-medium text-n900 dark:text-n0 hover:bg-primary hover:text-white transition-colors" aria-label="@lang('pagination.previous')">
                            <i class="las la-angle-left text-lg"></i>
                        </a>
                    @endif

                    {{-- Pagination Elements --}}
                    @foreach ($elements as $element)
                        {{-- "Three Dots" Separator --}}
                        @if (is_string($element))
                            <span aria-disabled="true">
                                <span class="relative inline-flex items-center px-4 py-2 bg-white dark:bg-bg4 text-sm font-medium text-n100 cursor-default">{{ $element }}</span>
                            </span>
                        @endif

                        {{-- Array Of Links --}}
                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                @if ($page == $paginator->currentPage())
                                    <span aria-current="page">
                                        <span class="relative inline-flex items-center px-4 py-2 bg-primary text-sm font-bold text-white cursor-default">{{ $page }}</span>
                                    </span>
                                @else
                                    <a href="{{ $url }}" class="relative inline-flex items-center px-4 py-2 bg-white dark:bg-bg4 text-sm font-medium text-n900 dark:text-n0 hover:bg-primary/10 hover:text-primary transition-colors" aria-label="Page {{ $page }}">
                                        {{ $page }}
                                    </a>
                                @endif
                            @endforeach
                        @endif
                    @endforeach

                    {{-- Next Page Link --}}
                    @if ($paginator->hasMorePages())
                        <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="relative inline-flex items-center px-4 py-2 bg-white dark:bg-bg4 text-sm font-medium text-n900 dark:text-n0 hover:bg-primary hover:text-white transition-colors" aria-label="@lang('pagination.next')">
                            <i class="las la-angle-right text-lg"></i>
                        </a>
                    @else
                        <span aria-disabled="true" aria-label="@lang('pagination.next')">
                            <span class="relative inline-flex items-center px-4 py-2 bg-bg1 text-sm font-medium text-n100 cursor-default" aria-hidden="true">
                                <i class="las la-angle-right text-lg"></i>
                            </span>
                        </span>
                    @endif
                </span>
            </div>
        </div>
    </nav>
@endif
