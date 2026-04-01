<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use App\Models\UserActivityLog;

class LogUserLoginLogoutListener
{
    /**
     * Handle the event.
     */
    public function handle($event)
    {
        if ($event instanceof Login) {
            UserActivityLog::log(
                "CONNEXION",
                $event->user,
                "Utilisateur connecté"
            );
        } elseif ($event instanceof Logout) {
            UserActivityLog::log(
                "DECONNEXION",
                $event->user,
                "Utilisateur déconnecté"
            );
        }
    }
}
