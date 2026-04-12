<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;

class AccessControlService
{
    /**
     * Configuration des permissions par rôle ID.
     * ID: 1=Admin, 2=User, 3=KAM, 4=Manager, 5=Compliance, 6=Backoffice, 7=DG, 8=AdminFrontend
     */
    protected static $rolePermissions = [
        1 => ['*'], // Super Admin : tout accès
        3 => ['view_asset_manager', 'manage_customers'], // KAM
        4 => ['view_asset_manager', 'manage_customers'], // Manager
        5 => ['view_compliance', 'validate_compliance'], // Compliance
        6 => ['view_backoffice', 'validate_backoffice'], // Backoffice
        7 => ['view_dg', 'validate_dg'], // Directeur Général
        8 => [
            'view_admin_frontend', 
            'manage_users', 
            'manage_menus', // Nouveau
            'view_audit_logs', 
            'export_logs', 
            'view_asset_manager',
            'view_compliance',
            'view_backoffice',
            'view_dg',
            'view_crm',
            'manage_prospects'
        ], // Admin Frontend : Accès complet pour supervision
        9 => ['view_crm', 'manage_prospects'], // Commercial / CRM
    ];

    public static function can($permission)
    {
        $user = Auth::user();
        if (!$user) return false;

        $permissions = self::$rolePermissions[$user->role_id] ?? [];

        if (in_array('*', $permissions)) return true;

        if (!$permission) return true; // Permission vide = accès par défaut si rôle autorisé

        return in_array($permission, $permissions);
    }

    public static function getSidebarMenus()
    {
        $roleId = Auth::user()->role_id ?? 0;
        
        // On récupère tous les menus (on retire le filtre is_active le temps du test pour forcer l'affichage des existants)
        $menus = \App\Models\FrontMenu::orderBy('section')
                  ->orderBy('order')
                  ->get();

        $sections = [
            'asset_manager' => ['heading' => 'Navigation Asset Manager', 'permission' => 'view_asset_manager'],
            'compliance'    => ['heading' => 'Navigation Compliance',    'permission' => 'view_compliance'],
            'backoffice'    => ['heading' => 'Navigation Backoffice',    'permission' => 'view_backoffice'],
            'dg'            => ['heading' => 'Navigation D.G.',         'permission' => 'view_dg'],
            'crm'           => ['heading' => 'Gestion Commerciale',     'permission' => 'view_crm'],
            'admin'         => ['heading' => 'Administration',           'permission' => 'view_admin_frontend'],
        ];

        $filteredMenus = [];

        $isAdminRoute = request()->is('admin-front*');

        foreach ($sections as $key => $section) {
            // Si on est sur une route admin, on ne veut voir QUE la section admin
            if ($isAdminRoute && $key !== 'admin') {
                continue;
            }

            $items = [];
            $currentUserRoleId = intval($roleId);

            // Force l'accès pour l'admin ou si l'utilisateur a le droit sur la section
            $hasSectionAccess = in_array($currentUserRoleId, [1, 8]) || self::can($section['permission']);
            
            // Cas particulier : l'Asset Manager (3) doit TOUJOURS voir sa propre section
            if ($key === 'asset_manager' && $currentUserRoleId === 3) {
                $hasSectionAccess = true;
            }

            if ($hasSectionAccess) {
                // On cherche les menus de cette section pour lesquels l'utilisateur a le rôle
                foreach ($menus->where('section', $key) as $menu) {
                    $roles = is_array($menu->roles_json) ? $menu->roles_json : [];
                    $roles = array_map('intval', $roles);
                    $currentUserRoleId = intval($roleId);

                    // Admin (1) et Admin Frontend (8) voient tout pour piloter
                    if (in_array($currentUserRoleId, [1, 8])) {
                        $items[] = ['title' => $menu->title, 'route' => $menu->route, 'icon' => $menu->icon];
                    }
                    // Autres : vérification par rôle ou par permission (si pas de rôle défini)
                    elseif (in_array($currentUserRoleId, $roles) || (empty($roles) && self::can($menu->permission))) {
                         $items[] = ['title' => $menu->title, 'route' => $menu->route, 'icon' => $menu->icon];
                    }
                }

                if (!empty($items)) {
                    $filteredMenus[$key] = [
                        'heading' => $section['heading'],
                        'permission' => $section['permission'],
                        'items' => $items
                    ];
                }
            }
        }

        return $filteredMenus;
    }
}
