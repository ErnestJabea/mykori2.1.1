<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Transaction;
use App\Models\TransactionSupplementaire;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Mail\AnniversaryNotificationMail;
use App\Http\Controllers\ProductController;

class NotifyAnniversaries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pmg:notify-anniversaries';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envoie un email d’alerte une semaine avant chaque date anniversaire PMG';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info("Recherche des anniversaires PMG arrivant à échéance dans 7 jours...");

        // Date cible : exactement dans 7 jours
        $targetDate = Carbon::today()->addDays(7);

        // 1. Récupérer toutes les transactions principales PMG actives
        $mainTransactions = Transaction::where('status', 'Succès')
            ->whereHas('product', function ($q) {
                $q->where('products_category_id', 2);
            })
            ->where(function($q) {
                $q->whereNull('date_echeance')
                  ->orWhere('date_echeance', '>=', Carbon::today()->toDateString());
            })
            ->whereRaw('MONTH(date_validation) = ?', [$targetDate->month])
            ->whereRaw('DAY(date_validation) = ?', [$targetDate->day])
            ->get();

        // 2. Récupérer toutes les transactions supplémentaires PMG actives
        $suppTransactions = TransactionSupplementaire::where('status', 'Succès')
            ->whereHas('product', function ($q) {
                $q->where('products_category_id', 2);
            })
            ->where(function($q) {
                $q->whereNull('date_echeance')
                  ->orWhere('date_echeance', '>=', Carbon::today()->toDateString());
            })
            ->whereRaw('MONTH(date_validation) = ?', [$targetDate->month])
            ->whereRaw('DAY(date_validation) = ?', [$targetDate->day])
            ->get();

        $allTrans = collect();
        foreach ($mainTransactions as $t) {
            $t->is_supplementaire = false;
            $allTrans->push($t);
        }
        foreach ($suppTransactions as $t) {
            $t->is_supplementaire = true;
            $allTrans->push($t);
        }

        if ($allTrans->isEmpty()) {
            $this->info("Aucun anniversaire PMG à notifier pour le " . $targetDate->format('d/m/Y') . ".");
            return 0;
        }

        $this->info($allTrans->count() . " transaction(s) d’anniversaire trouvée(s). Calcul des valorisations...");

        $productController = app(ProductController::class);
        $transactionsData = [];

        foreach ($allTrans as $trans) {
            try {
                // Calcul de la valorisation actuelle à aujourd'hui
                $valuation = $productController->calculatePMGValorization($trans, Carbon::today());

                $transactionsData[] = [
                    'client_name' => $trans->user->name ?? 'Client inconnu',
                    'product_title' => $trans->product->title ?? 'Produit inconnu',
                    'reference' => $trans->ref ?? ($trans->is_supplementaire ? 'Suppl. ID: ' . $trans->id : 'ID: ' . $trans->id),
                    'is_supplementaire' => $trans->is_supplementaire,
                    'capital_nominal' => (float)$trans->amount,
                    'valorisation_actuelle' => $valuation,
                    'taux' => $trans->vl_buy,
                    'date_valeur' => Carbon::parse($trans->date_validation)->format('d/m/Y'),
                    'date_echeance' => $trans->date_echeance ? Carbon::parse($trans->date_echeance)->format('d/m/Y') : '-',
                ];
            } catch (\Exception $e) {
                Log::error("Erreur calcul valorisation alerte anniversaire transaction ID {$trans->id}: " . $e->getMessage());
            }
        }

        if (empty($transactionsData)) {
            $this->warn("Aucune donnée de transaction calculable. Envoi annulé.");
            return 0;
        }

        // Récupérer la liste des emails destinataires depuis Voyager, sinon .env, sinon valeur par défaut
        $emailsRaw = null;
        if (function_exists('setting')) {
            try {
                $emailsRaw = setting('site.anniversary_emails');
            } catch (\Exception $e) {
                // Fallback
            }
        }

        if (!$emailsRaw) {
            $emailsRaw = env('ANNIVERSARY_EMAILS', 'onboarding@koriassetmanagement.com, admin@koriassetmanagement.com');
        }

        $emails = array_filter(array_map('trim', explode(',', $emailsRaw)));

        if (empty($emails)) {
            Log::error("Anniversary Alerts : Aucun destinataire valide configuré.");
            $this->error("Aucun destinataire email valide configuré.");
            return 1;
        }

        try {
            Mail::to($emails)->send(new AnniversaryNotificationMail($transactionsData, $targetDate->format('d/m/Y')));
            $this->info("Email envoyé avec succès à : " . implode(', ', $emails));
            
            // Loguer l'événement dans le fichier de log principal
            Log::info("Anniversary Alerts : Email envoyé pour " . count($transactionsData) . " alertes à : " . implode(', ', $emails));
        } catch (\Exception $e) {
            Log::error("Anniversary Alerts Exception : " . $e->getMessage());
            $this->error("Erreur lors de l'envoi de l'email : " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
