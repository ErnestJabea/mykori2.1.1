<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CrmProspect;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class CrmController extends Controller
{
    /**
     * Dashboard CRM du commercial.
     */
    public function index()
    {
        $user = Auth::user();
        
        // Statistiques
        $stats = [
            'total_prospects' => $user->leads()->count(),
            'negotiating' => $user->leads()->where('status', 'negotiation')->count(),
            'won' => $user->leads()->where('status', 'won')->count(),
            'total_clients' => $user->clients()->count(),
        ];

        $prospects = $user->leads()->latest()->take(10)->get();
        
        return view('crm.dashboard', compact('stats', 'prospects'));
    }

    /**
     * Liste complète des prospects.
     */
    public function prospects()
    {
        $prospects = Auth::user()->leads()->latest()->paginate(15);
        return view('crm.prospects.index', compact('prospects'));
    }

    /**
     * Enregistrer un nouveau prospect.
     */
    public function storeProspect(Request $request)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'amount_expected' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $prospect = new CrmProspect($validated);
        $prospect->commercial_id = Auth::id();
        $prospect->status = 'new';
        $prospect->save();

        return redirect()->back()->with('success', 'Prospect créé avec succès.');
    }

    /**
     * Mettre à jour l'état d'avancement de la négociation.
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate(['status' => 'required|in:new,negotiation,won,lost']);
        
        $prospect = Auth::user()->leads()->findOrFail($id);
        $prospect->status = $request->status;
        $prospect->save();

        return redirect()->back()->with('success', 'État du prospect mis à jour.');
    }

    /**
     * Liste des clients rattachés au commercial.
     */
    public function clients()
    {
        $clients = Auth::user()->clients()->latest()->paginate(15);
        return view('crm.clients.index', compact('clients'));
    }
}
