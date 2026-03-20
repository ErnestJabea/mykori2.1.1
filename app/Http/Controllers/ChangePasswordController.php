<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ChangePasswordController extends Controller
{

    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'new_password' => [
                'required',
                'string',
                'min:8',
                'regex:/[a-z]/', // doit contenir au moins une lettre minuscule
                'regex:/[A-Z]/', // doit contenir au moins une lettre majuscule
                'regex:/[0-9]/', // doit contenir au moins un chiffre
                'regex:/[@$!%*?&]/', // doit contenir au moins un caractère spécial
                'confirmed'
            ],
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return redirect()->back()->withErrors(['current_password' => 'Le mot de passe actuel est incorrect'])->withInput();
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json(['success' => 'Mot de passe changé avec succès']);
    }
}