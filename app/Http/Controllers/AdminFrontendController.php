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
        $stats = [
            'total_users' => User::count(),
            'total_actions' => UserActivityLog::count(),
            'recent_actions' => UserActivityLog::with('user')->orderBy('created_at', 'desc')->limit(12)->get(),
            'role_distribution' => DB::table('users')
                ->join('roles', 'users.role_id', '=', 'roles.id')
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
        $query = User::with('role');
        
        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
        }

        $users = $query->orderBy('name')->paginate(20);
        $roles = DB::table('roles')->get();

        return view('front-end.admin.users', compact('users', 'roles'));
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
        $logs = UserActivityLog::with('user')->orderBy('created_at', 'desc')->paginate(50);
        return view('front-end.admin.logs', compact('logs'));
    }

    public function exportLogs()
    {
        $fileName = 'activity_logs_' . date('Y-m-d_His') . '.csv';
        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() {
            $file = fopen('php://output', 'w');
            fputs($file, (chr(0xEF) . chr(0xBB) . chr(0xBF))); // BOM UTF-8
            fputcsv($file, ['Date', 'Utilisateur', 'Action', 'Description', 'Cible', 'Adresse IP'], ';');

            UserActivityLog::with('user')->chunk(100, function($logs) use ($file) {
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

        FrontMenu::create($request->all());

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
}
