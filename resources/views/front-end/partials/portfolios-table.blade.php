<div class="overflow-x-auto">
    <table class="w-full text-[13px]">
        <thead>
            <tr class="bg-n10 dark:bg-bg4 text-left border-b border-n30 dark:border-n700">
                <th class="px-4 py-4 font-bold text-[11px] uppercase text-n700 dark:text-slate-200">Référence</th>
                <th class="px-4 py-4 font-bold text-[11px] uppercase text-n700 dark:text-slate-200">Client</th>
                <th class="px-4 py-4 font-bold text-[11px] uppercase text-n700 dark:text-slate-200">Email</th>
                <th class="px-4 py-4 font-bold text-[11px] uppercase text-n700 dark:text-slate-200">Localisation</th>
                <th class="px-4 py-4 font-bold text-[11px] uppercase text-n700 dark:text-slate-200 text-center">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-n30 dark:divide-n700">
            @forelse($portfolios as $portfolio)
                <tr class="hover:bg-n10/50 dark:hover:bg-bg4/50 duration-300">
                    <td class="px-4 py-4">
                        <span class="px-3 py-1 rounded-full text-[10px] font-bold {{ $portfolio->type == 'PMG' ? 'bg-secondary1/10 text-secondary1 dark:text-blue-400' : 'bg-primary/10 text-primary dark:text-yellow-400' }}">
                            {{ $portfolio->reference }}
                        </span>
                    </td>
                    <td class="px-4 py-4">
                        <div class="flex items-center gap-3">
                            <div class="min-w-0">
                                <p class="font-bold text-n900 dark:text-white truncate" title="{{ $portfolio->user->name ?? 'N/A' }}">
                                    {{ $portfolio->user->name ?? 'N/A' }}
                                </p>
                                <p class="text-[10px] md:text-[11px] text-n500 dark:text-slate-300">
                                    @if(($portfolio->user->genre ?? '') == 0) Monsieur @elseif(($portfolio->user->genre ?? '') == 1) Madame @else Entreprise @endif
                                </p>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-4">
                        <span class="italic text-n500 dark:text-slate-300 break-all">{{ $portfolio->user->email ?? 'N/A' }}</span>
                    </td>
                    <td class="px-4 py-4">
                        <span class="truncate block text-n700 dark:text-white" title="{{ $portfolio->user->localisation ?? '-' }}">{{ $portfolio->user->localisation ?? '-' }}</span>
                    </td>
                    <td class="px-4 py-4 text-center">
                        <div class="flex items-center justify-center gap-2">
                            <a href="{{ route('asset-manager.create-customer', $portfolio->id) }}" 
                                class="w-8 h-8 rounded-lg bg-primary/10 text-primary flex items-center justify-center hover:bg-primary hover:text-white duration-300"
                                title="Modifier dossier">
                                <i class="las la-edit"></i>
                            </a>
                            <a href="{{ route('customer-detail', ['customer' => $portfolio->user_id]) }}" 
                                class="w-8 h-8 rounded-lg bg-secondary1/10 text-secondary1 flex items-center justify-center hover:bg-secondary1 hover:text-white duration-300"
                                title="Voir profil">
                                <i class="las la-eye"></i>
                            </a>
                            <form action="{{ route('asset-manager.delete-portfolio', $portfolio->id) }}" 
                                method="POST" 
                                onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer définitivement le dossier {{ $portfolio->reference }} ? Cette action ne pourra pas être annulée.')"
                                class="inline-block">
                                @csrf
                                @method('DELETE')
                                <button type="submit" 
                                    class="w-8 h-8 rounded-lg bg-red-500/10 text-red-500 flex items-center justify-center hover:bg-red-500 hover:text-white duration-300"
                                    title="Supprimer dossier">
                                    <i class="las la-trash-alt"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-10 text-center opacity-60 italic">
                        Aucun dossier trouvé {{ $search ? "pour \"$search\"" : "" }}.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Pagination -->
<div class="mt-6 flex justify-center portfolios-pagination">
    @if($portfolios->hasPages())
        <div class="custom-pagination">
            {{ $portfolios->links('pagination::bootstrap-4') }}
        </div>
    @endif
</div>

<style>
    .custom-pagination .pagination {
        display: flex;
        gap: 5px;
        align-items: center;
    }
    .custom-pagination .page-item {
        list-style: none;
    }
    .custom-pagination .page-link {
        display: block;
        padding: 8px 16px;
        background: #f1f1f1;
        border-radius: 8px;
        color: #333;
        font-weight: bold;
        transition: all 0.3s;
    }
    .custom-pagination .page-item.active .page-link {
        background: var(--primary, #007bff); /* Utilise votre couleur primaire */
        color: #fff;
    }
    .custom-pagination .page-link:hover {
        background: #e1e1e1;
        text-decoration: none;
    }
    .dark .custom-pagination .page-link {
        background: #2a2a2a;
        color: #ddd;
    }
    .dark .custom-pagination .page-item.active .page-link {
        background: var(--primary, #007bff);
        color: #fff;
    }
</style>
