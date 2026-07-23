@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination Navigation" class="flex items-center justify-between pt-4">
        <div class="flex justify-between flex-1 sm:hidden">
            @if ($paginator->onFirstPage())
                <span class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-n400 bg-n30 dark:bg-bg4 dark:text-slate-500 rounded-xl cursor-default">
                    Précédent
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="ajax-tab relative inline-flex items-center px-4 py-2 text-sm font-bold text-n700 dark:text-white bg-white dark:bg-bg3 border border-n30 dark:border-n700 rounded-xl hover:bg-primary hover:text-white transition-all">
                    Précédent
                </a>
            @endif

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="ajax-tab relative inline-flex items-center px-4 py-2 text-sm font-bold text-n700 dark:text-white bg-white dark:bg-bg3 border border-n30 dark:border-n700 rounded-xl hover:bg-primary hover:text-white transition-all ml-3">
                    Suivant
                </a>
            @else
                <span class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-n400 bg-n30 dark:bg-bg4 dark:text-slate-500 rounded-xl cursor-default ml-3">
                    Suivant
                </span>
            @endif
        </div>

        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
            <div>
                <p class="text-xs text-n500 dark:text-slate-300 font-semibold">
                    Affichage de <span class="font-bold text-n700 dark:text-white">{{ $paginator->firstItem() }}</span> à <span class="font-bold text-n700 dark:text-white">{{ $paginator->lastItem() }}</span> sur <span class="font-bold text-n700 dark:text-white">{{ $paginator->total() }}</span> résultats
                </p>
            </div>

            <div>
                <span class="relative z-0 inline-flex rounded-xl shadow-sm gap-1">
                    {{-- Previous Page Link --}}
                    @if ($paginator->onFirstPage())
                        <span aria-disabled="true" aria-label="Précédent">
                            <span class="relative inline-flex items-center px-3 py-2 text-xs font-bold text-n400 bg-n30/50 dark:bg-bg4/50 dark:text-slate-600 rounded-lg cursor-default" aria-hidden="true">
                                <i class="las la-angle-left text-sm"></i>
                            </span>
                        </span>
                    @else
                        <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="ajax-tab relative inline-flex items-center px-3 py-2 text-xs font-bold text-n700 dark:text-white bg-white dark:bg-bg3 border border-n30 dark:border-n700 rounded-lg hover:bg-primary hover:text-white transition-all" aria-label="Précédent">
                            <i class="las la-angle-left text-sm"></i>
                        </a>
                    @endif

                    {{-- Pagination Elements --}}
                    @foreach ($elements as $element)
                        {{-- "Three Dots" Separator --}}
                        @if (is_string($element))
                            <span aria-disabled="true">
                                <span class="relative inline-flex items-center px-3 py-2 text-xs font-bold text-n500 dark:text-slate-400 bg-white dark:bg-bg3 border border-n30 dark:border-n700 rounded-lg cursor-default">{{ $element }}</span>
                            </span>
                        @endif

                        {{-- Array Of Links --}}
                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                @if ($page == $paginator->currentPage())
                                    <span aria-current="page">
                                        <span class="relative inline-flex items-center px-3.5 py-2 text-xs font-bold text-white bg-primary rounded-lg shadow-sm cursor-default">{{ $page }}</span>
                                    </span>
                                @else
                                    <a href="{{ $url }}" class="ajax-tab relative inline-flex items-center px-3.5 py-2 text-xs font-bold text-n700 dark:text-white bg-white dark:bg-bg3 border border-n30 dark:border-n700 rounded-lg hover:bg-primary/20 hover:text-primary transition-all">{{ $page }}</a>
                                @endif
                            @endforeach
                        @endif
                    @endforeach

                    {{-- Next Page Link --}}
                    @if ($paginator->hasMorePages())
                        <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="ajax-tab relative inline-flex items-center px-3 py-2 text-xs font-bold text-n700 dark:text-white bg-white dark:bg-bg3 border border-n30 dark:border-n700 rounded-lg hover:bg-primary hover:text-white transition-all" aria-label="Suivant">
                            <i class="las la-angle-right text-sm"></i>
                        </a>
                    @else
                        <span aria-disabled="true" aria-label="Suivant">
                            <span class="relative inline-flex items-center px-3 py-2 text-xs font-bold text-n400 bg-n30/50 dark:bg-bg4/50 dark:text-slate-600 rounded-lg cursor-default" aria-hidden="true">
                                <i class="las la-angle-right text-sm"></i>
                            </span>
                        </span>
                    @endif
                </span>
            </div>
        </div>
    </nav>
@endif
