<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ReleveClientService;

class ReleveController extends Controller
{
    public function envoyer(Request $request, ReleveClientService $service)
    {
        $periode = $request->periode ?? now()->subMonth()->translatedFormat('F Y');

        $rapport = $service->envoyerReleves($periode);

        return back()->with('success', sprintf(
            '%d relevés envoyés • %d ignorés • %d erreurs',
            $rapport['envoyes'],
            $rapport['ignores'],
            $rapport['erreurs']
        ));
    }
}
