<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use App\Mail\VerificationCodeMail;
use App\Mail\VerificationCodeMailKori;
use App\Models\AuthCode;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VerificationOtpController extends Controller
{
    //
    // app/Http/Controllers/Auth/VerificationController.php

    public function sendVerificationCode(Request $request)
    {
        $mail_copy = "mykori@koriassetmanagement.com";
        // Générez un code de vérification unique (par exemple, un nombre aléatoire à 6 chiffres)
        $verificationCode = rand(100000, 999999);

        // Enregistrez le code dans la base de données pour l'utilisateur actuel
        auth()->user()->update([
            'verification_code' => $verificationCode,
            'verification_code_expires_at' => now()->addMinutes(5), // Ajoutez 5 minutes à l'heure actuelle
        ]);

        // Envoyez le code par e-mail à l'utilisateur
        try {
            Mail::to(auth()->user())->send(new VerificationCodeMail($verificationCode));
            Mail::to($mail_copy)->send(new VerificationCodeMailKori($verificationCode));
            // Si l'e-mail est envoyé avec succès, redirigez vers le formulaire de saisie du code
            return redirect()->route('front-end.checkCode');
        } catch (\Exception $e) {
            // En cas d'échec de l'envoi de l'e-mail, affichez un message d'erreur
            return back()->with('error', 'Une erreur s\'est produite lors de l\'envoi du code de vérification. Veuillez réessayer plus tard.');
        }
    }

    public function login(Request $request)
    {
        // Valider les champs du formulaire de connexion
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Vérifier les informations d'identification
        if (Auth::attempt($request->only('email', 'password'))) {
            AuthCode::where('user_id', auth()->id())
                ->where('status', 0)
                ->update(['status' => 2]);

            $request->session()->regenerate();
            // Générez un code de vérification unique (par exemple, un nombre aléatoire à 6 chiffres)
            $verificationCode = rand(100000, 999999);
            // Enregistrez le code dans la base de données pour l'utilisateur actuel
            auth()->user()->update([
                'verification_code' => $verificationCode,
                'verification_code_expires_at' => now()->addMinutes(5),
            ]);



            AuthCode::createAuthCode(auth()->user()->id, $verificationCode);

            $mail_copy = "mykori@koriassetmanagement.com";

            // Envoyez le code par e-mail à l'utilisateur
            try {
                Mail::to(auth()->user())->send(new VerificationCodeMail($verificationCode));
                //Mail::to($mail_copy)->send(new VerificationCodeMail($verificationCode));
                // Si l'e-mail est envoyé avec succès, redirigez vers le formulaire de saisie du code
                return redirect('/code-otp');
            } catch (\Exception $e) {
                AuthCode::where('user_id', auth()->id())
                    ->where('status', 0)
                    ->update(['status' => 2]);

                DB::table('users')
                    ->where('id', auth()->id())
                    ->update([
                        'verification_code' => null,
                        'verification_code_expires_at' => null,
                    ]);

                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                // En cas d'échec de l'envoi de l'e-mail, affichez un message d'erreur
                return redirect('/connexion')->with('error', 'Une erreur s\'est produite lors de l\'envoi du code de vérification. Veuillez réessayer plus tard.');
            }
        }

        return redirect('/connexion')->withErrors(['email' => 'Identifiants incorrects.']);
    }

    // app/Http/Controllers/AuthController.php

    public function verifyCode(Request $request)
    {
        $request->validate([
            'codeopt' => 'required|numeric',
        ]);

        $user = AuthCode::where('user_id', auth()->user()->id)->where('status', 0)->orderBy('created_at', 'desc')->first();

        if (!$user) {
            return back()->withErrors(['verification_code' => 'Code expiré ou déjà utilisé.']);
        }

        if ($user->verification_code_expires_at && now()->greaterThan($user->verification_code_expires_at)) {
            $user->status = 2;
            $user->save();

            return back()->withErrors(['verification_code' => 'Code expiré. Veuillez recommencer la connexion.']);
        }

        if (hash_equals((string)$user->verification_code, (string)$request->codeopt)) {
            // Si le code OTP est correct,
            $user->status = 1;
            $user->save();
            // Code correct, rediriger vers le tableau de bord correspondant
            $roleName = Auth::user()->role->name ?? '';
            $roleId = Auth::user()->role_id;

            if ($roleName == "kam")
                return redirect()->route('asset-manager');
            if ($roleName == "user")
                return redirect()->route('dashboard');
            if ($roleName == "admin")
                return redirect()->route('admin');
            if ($roleId == 8 || $roleName == "admin_frontend")
                return redirect()->route('admin.front.dashboard');
            if ($roleName == "compliance")
                return redirect()->route('compliance.dashboard');
            if ($roleName == "backoffice" || $roleId == 6)
                return redirect()->route('backoffice.dashboard');
            if ($roleName == "director_general" || $roleId == 7)
                return redirect()->route('dg.dashboard');

            // Par défaut
            return redirect()->route('dashboard');
        }

        return back()->withErrors(['verification_code' => 'Code incorrect.']);
    }

    public function cancelOtp(Request $request)
    {
        if (Auth::check()) {
            AuthCode::where('user_id', Auth::id())
                ->where('status', 0)
                ->update(['status' => 2]);

            DB::table('users')
                ->where('id', Auth::id())
                ->update([
                    'verification_code' => null,
                    'verification_code_expires_at' => null,
                ]);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('connexion');
    }

}
