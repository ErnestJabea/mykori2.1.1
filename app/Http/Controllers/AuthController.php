<?php

namespace App\Http\Controllers;

use App\Services\MicrosoftMailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    protected $mailService;

    public function __construct(MicrosoftMailService $mailService)
    {
        $this->mailService = $mailService;
    }

    public function envoyerOTP(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        // Générer l'OTP
        $otp = rand(100000, 999999);
        
        // Sauvegarder l'OTP en session ou DB
        session(['otp_' . $request->email => $otp, 'otp_expiry' => now()->addMinutes(10)]);

        // Envoyer l'email
        $resultat = $this->mailService->envoyerOTP($request->email, $otp);

        if ($resultat) {
            return response()->json([
                'success' => true,
                'message' => 'Code OTP envoyé avec succès'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de l\'envoi du code OTP'
        ], 500);
    }
}