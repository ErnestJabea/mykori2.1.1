<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\FrontMenu;
use App\Models\UserActivityLog;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminFrontendController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!\App\Services\AccessControlService::can('view_admin_frontend')) {
                abort(403, 'Accès réservé à l\'administrateur système.');
            }
            return $next($request);
        });
    }

    public function dashboard()
    {
        $this->syncAdminMenus();

        $stats = [
            'total_users' => User::whereNotIn('role_id', [1, 2])->count(),
            'total_actions' => UserActivityLog::count(),
            'recent_actions' => UserActivityLog::with('user')->orderBy('created_at', 'desc')->paginate(10),
            'role_distribution' => DB::table('users')
                ->join('roles', 'users.role_id', '=', 'roles.id')
                ->whereNotIn('users.role_id', [1, 2])
                ->select('roles.display_name', DB::raw('count(*) as total'))
                ->groupBy('roles.display_name')
                ->get()
        ];

        $menus = FrontMenu::orderBy('section')->orderBy('order')->get();
        $roles = DB::table('roles')->get();
        $sections = [
            'supervision' => 'Supervision & Audit',
            'asset_manager' => 'Asset Manager',
            'compliance' => 'Compliance',
            'backoffice' => 'Backoffice',
            'dg' => 'Direction Générale',
            'admin' => 'Administration'
        ];

        return view('front-end.admin.dashboard', compact('stats', 'menus', 'roles', 'sections'));
    }

    public function users(Request $request)
    {
        $query = User::with('role')->whereNotIn('role_id', [1, 2]);
        
        if ($request->has('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        $users = $query->orderBy('name')->paginate(20);
        $roles = DB::table('roles')->whereNotIn('id', [1, 2])->get();

        return view('front-end.admin.users', compact('users', 'roles'));
    }

    public function storeUser(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'role_id' => 'required|exists:roles,id'
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => \Illuminate\Support\Facades\Hash::make('Kori@2026'), // Mot de passe par défaut
            'role_id' => $request->role_id,
        ]);

        UserActivityLog::log(
            "CREATION_COLLABORATEUR", 
            $user, 
            "L'Administrateur " . auth()->user()->name . " a créé le compte de " . $user->name . " avec le rôle ID: " . $request->role_id
        );

        return back()->with('success', 'Le collaborateur ' . $user->name . ' a été créé avec succès. Mot de passe par défaut : Kori@2026');
    }

    public function updateUserRole(Request $request, User $user)
    {
        $oldRoleId = $user->role_id;
        $user->role_id = $request->role_id;
        $user->save();

        UserActivityLog::log(
            "Mise à jour Rôle", 
            $user, 
            "Changement du rôle de l'utilisateur " . $user->name . " (ID: " . $user->id . ")",
            ['old_role' => $oldRoleId, 'new_role' => $request->role_id]
        );

        return back()->with('success', 'Rôle mis à jour avec succès.');
    }

    public function activityLogs(Request $request)
    {
        $query = UserActivityLog::with('user');

        // Filtre par Utilisateur (ID ou Nom)
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filtre par Date de début
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        // Filtre par Date de fin
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        // Tri dynamique
        $sort = $request->get('sort', 'created_at');
        $direction = $request->get('direction', 'desc');
        $query->orderBy($sort, $direction);

        $logs = $query->paginate(10)->withQueryString();
        
        $allUsers = User::whereNotIn('role_id', [1, 2])->orderBy('name')->paginate(10);

        return view('front-end.admin.logs', compact('logs', 'allUsers'));
    }

    public function exportLogs(Request $request)
    {
        $query = UserActivityLog::with('user');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $fileName = 'activity_logs_' . date('Y-m-d_His') . '.csv';
        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() use ($query) {
            $file = fopen('php://output', 'w');
            fputs($file, (chr(0xEF) . chr(0xBB) . chr(0xBF))); // BOM UTF-8
            fputcsv($file, ['Date', 'Utilisateur', 'Action', 'Description', 'Cible', 'Adresse IP'], ';');

            $query->chunk(100, function($logs) use ($file) {
                foreach ($logs as $log) {
                    fputcsv($file, [
                        $log->created_at->format('d/m/Y H:i:s'),
                        $log->user->name ?? 'Système',
                        $log->action,
                        $log->description,
                        $log->target_type . ' #' . $log->target_id,
                        $log->ip_address
                    ], ';');
                }
            });

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function menus()
    {
        $menus = FrontMenu::orderBy('section')->orderBy('order')->get();
        $roles = DB::table('roles')->get();
        $sections = [
            'asset_manager' => 'Asset Manager',
            'compliance' => 'Compliance',
            'backoffice' => 'Backoffice',
            'dg' => 'Direction Générale',
            'admin' => 'Administration'
        ];
        
        return view('front-end.admin.menus', compact('menus', 'roles', 'sections'));
    }

    public function storeMenu(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'route' => 'required|string|max:255',
            'section' => 'required|string',
            'roles_json' => 'required|array'
        ]);

        $data = $request->all();
        $data['is_active'] = true;
        FrontMenu::create($data);

        return back()->with('success', 'Menu créé avec succès.');
    }

    public function updateMenu(Request $request, FrontMenu $menu)
    {
        $menu->update($request->all());
        // Handle roles_json specifically if not in $request->all() as array
        if (!$request->has('roles_json')) {
            $menu->roles_json = [];
            $menu->save();
        }

        return back()->with('success', 'Menu mis à jour.');
    }

    public function deleteMenu(FrontMenu $menu)
    {
        $menu->delete();
        return back()->with('success', 'Menu supprimé.');
    }

    private function syncAdminMenus()
    {
        $adminRoles = [1, 8];
        $menus = [
            [
                'title' => 'Console Admin',
                'route' => 'admin.front.dashboard',
                'icon' => 'las la-server',
                'section' => 'admin',
                'order' => 1,
                'roles_json' => $adminRoles,
                'is_active' => true
            ],
            [
                'title' => 'Habilitations',
                'route' => 'admin.front.users',
                'icon' => 'las la-users-cog',
                'section' => 'admin',
                'order' => 2,
                'roles_json' => $adminRoles,
                'is_active' => true
            ],
            [
                'title' => 'Piste d\'Audit',
                'route' => 'admin.front.logs',
                'icon' => 'las la-history',
                'section' => 'admin',
                'order' => 3,
                'roles_json' => $adminRoles,
                'is_active' => true
            ],
            [
                'title' => 'Valeurs Liquidatives',
                'route' => 'asset-manager.vls',
                'icon' => 'las la-chart-area',
                'section' => 'asset_manager',
                'order' => 4,
                'roles_json' => [1, 3, 8],
                'is_active' => true
            ],
            [
                'title' => 'Valeurs Liquidatives',
                'route' => 'compliance.vl-history',
                'icon' => 'las la-chart-area',
                'section' => 'compliance',
                'order' => 4,
                'roles_json' => [1, 5, 8],
                'is_active' => true
            ]
        ];

        foreach ($menus as $m) {
            FrontMenu::updateOrCreate(
                ['route' => $m['route']],
                $m
            );
        }
    }
}
