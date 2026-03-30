@extends('front-end/app/app-home-asset', ['title' => 'Gestion des Utilisateurs Admin'])

@section('content')
    <main class="main-content has-sidebar">
        <div class="main-inner p-4 md:p-8">
            <div class="flex flex-col gap-6 md:gap-8 min-h-screen">

                <div class="flex items-center gap-4">
                    <a href="{{ route('admin.front.dashboard') }}" class="p-2 bg-white rounded-xl shadow-sm hover:text-primary transition-all">
                        <i class="las la-arrow-left text-xl"></i>
                    </a>
                    <h2 class="text-2xl font-bold text-n900">Gestion des Droits d'Accès</h2>
                </div>

                <div class="bg-white rounded-3xl border border-n30 shadow-sm overflow-hidden p-6 mb-4">
                    <form action="{{ route('admin.front.users') }}" method="GET" class="flex gap-4">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Rechercher par nom ou email..." class="flex-1 px-4 py-2 border border-n30 rounded-xl text-sm focus:border-primary transition-all outline-none">
                        <button type="submit" class="p-2 bg-primary text-white rounded-xl hover:scale-105 transition-all">
                             <i class="las la-search text-xl"></i>
                        </button>
                    </form>
                </div>

                <div class="bg-white rounded-3xl border border-n30 shadow-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="bg-n20/30 text-n500 text-[10px] uppercase font-bold italic">
                                <tr>
                                    <th class="px-6 py-4">Utilisateur</th>
                                    <th class="px-6 py-4">Rôle Actuel</th>
                                    <th class="px-6 py-4">Dernière Connexion</th>
                                    <th class="px-6 py-4 text-center">Assigner Nouveau Rôle</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-n30 italic">
                                @foreach($users as $user)
                                    <tr class="hover:bg-n10/50 transition-all transition-duration-300">
                                        <td class="px-6 py-4">
                                            <div class="flex flex-col">
                                                <span class="font-bold text-n900 text-sm italic">{{ $user->name }}</span>
                                                <span class="text-xs text-n500 font-medium">{{ $user->email }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="px-3 py-1 bg-primary/10 text-primary text-[10px] rounded-full font-extrabold uppercase tracking-wide italic">
                                                {{ $user->role->display_name ?? 'N/A' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-xs italic text-n400 font-medium">
                                            {{ $user->updated_at->format('d/m/Y H:i') }}
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <form action="{{ route('admin.front.update-role', $user->id) }}" method="POST" class="flex items-center justify-center gap-2">
                                                @csrf
                                                <select name="role_id" class="px-2 py-1 text-[10px] border border-n30 rounded bg-n10 font-bold uppercase tracking-tighter outline-none focus:border-primary">
                                                    @foreach($roles as $role)
                                                        <option value="{{ $role->id }}" {{ $user->role_id == $role->id ? 'selected' : '' }}>
                                                            {{ $role->display_name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <button type="submit" class="p-1.5 bg-success/10 text-success rounded-lg hover:bg-success hover:text-white transition-all transform hover:rotate-12" title="Mettre à jour">
                                                    <i class="las la-sync-alt"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="px-6 py-4">
                    {{ $users->links() }}
                </div>

            </div>
        </div>
    </main>
@endsection
