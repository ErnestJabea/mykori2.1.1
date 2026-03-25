<?php

namespace App\Http\Controllers;

use App\Models\AssetValue;
use App\Models\User;
use App\Models\Transaction;
use App\Models\Product;
use App\Models\TransactionSupplementaire;
use App\Mail\AchatActionMail;
use App\Mail\AchatActionPmgMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class AchatActionCustomerController extends Controller
{
    //

    public function acheterAction(Request $request)
    {
        // Récupérez les données envoyées via Ajax
        $valeurLiquidative = $request->input('montantAchat');
        $fraisGestion = $request->input('fraisGestion');
        $montantTotal = $request->input('montantTotal');
        $montant_normal = $request->input('montant_normal');
        $product = $request->input('product');
        $customer = $request->input('customer');
        $date_valeur = $request->input('date_valeur');
        $taux_insere = $request->input('taux_insere');
        $type_souscription = $request->input('type_souscription', 'ponctuelle');

        $name_product = Product::where('id', $product)->first();
        if (!$name_product) {
            return response()->json(['message' => 'Produit non trouvé'], 404);
        }

        // Pour FCP, on peut utiliser le taux inséré ou la dernière VL
        $last_vl_record = AssetValue::where('product_id', $product)->orderBy('created_at', 'desc')->first();
        $vl_val = $taux_insere ?: ($last_vl_record ? $last_vl_record->vl : $name_product->vl);
        $current_user = User::where('id', $customer)->first();

        $username = $current_user->name;
        $useremail = $current_user->email;

        $unique_id = uniqid();

        // Vérifier si une transaction existe pour le produit et l'utilisateur actuels
        $existing_transaction = Transaction::where('product_id', $name_product->id)
            ->where('user_id', $customer)
            ->first();

        if ($existing_transaction) {
            // Créer une nouvelle transaction avec une référence unique
            $new_transaction = new TransactionSupplementaire();
            $new_transaction->title = "Souscription suppl. " . $type_souscription . " de " . $name_product->title;
            $new_transaction->ref = "Kori-" . $existing_transaction->ref . "-" . $unique_id;
            $new_transaction->payment_mode = "A définir";
            $new_transaction->amount = $montantTotal;
            $new_transaction->status = "En attente";
            $new_transaction->user_id = $customer;
            $new_transaction->vl_buy = $vl_val;
            $new_transaction->nb_part = $valeurLiquidative;
            $new_transaction->product_id = $name_product->id;
            $new_transaction->duree = $name_product->duree;
            $new_transaction->transaction_id = $existing_transaction->id;
            $new_transaction->date_validation = $date_valeur;
            $new_transaction->type = $name_product->products_category_id;

            $new_transaction->save();
        } else {
            // Créer une nouvelle transaction avec une référence unique
            $transaction = new Transaction();
            $transaction->title = "Souscription " . $type_souscription . " de " . $name_product->title;
            $transaction->ref = "Kori-" . $unique_id;
            $transaction->payment_mode = "A définir";
            $transaction->amount = $montant_normal;
            $transaction->status = "En attente";
            $transaction->user_id = $customer;
            $transaction->vl_buy = $vl_val;
            $transaction->nb_part = $valeurLiquidative;
            $transaction->duree = $name_product->duree;
            $transaction->product_id = $name_product->id;
            $transaction->date_validation = $date_valeur;
            $transaction->type = $name_product->products_category_id;

            $transaction->save();
        }

        $current_user->save();

        return response()->json(['message' => 'Données enregistrées avec succès']);
    }

    public function acheterActionPmg(Request $request)
    {
        // Récupérez les données envoyées via Ajax
        $valeurLiquidative = $request->input('montantAchat');
        $fraisGestion = $request->input('fraisGestion');
        $montantTotal = $request->input('montantTotal');
        $product = $request->input('product');
        $customer = $request->input('customer');
        $date_valeur = $request->input('date_valeur');
        $date_echeance = $request->input('date_echeance');
        $taux_insere = $request->input('taux_insere');
        $type_souscription = $request->input('type_souscription', 'ponctuelle');

        $name_product = Product::where('id', $product)->first();
        if (!$name_product) {
            return response()->json(['message' => 'Produit non trouvé'], 404);
        }
        $current_user = User::where('id', $customer)->first();

        $username = $current_user->name;
        $useremail = $current_user->email;

        $unique_id = uniqid();

        // Pour PMG, on crée souvent des transactions distinctes, 
        // mais nous gardons la logique de TransactionSupplementaire si configuré ainsi
        $existing_transaction = Transaction::where('product_id', $name_product->id)
            ->where('user_id', $customer)
            ->first();

        if ($existing_transaction) {
            $new_transaction = new TransactionSupplementaire();
            $new_transaction->title = "Souscription suppl. " . $type_souscription . " de " . $name_product->title;
            $new_transaction->ref = $existing_transaction->ref . "-" . $unique_id;
            $new_transaction->payment_mode = "A définir";
            $new_transaction->amount = $montantTotal;
            $new_transaction->status = "En attente";
            $new_transaction->user_id = $customer;
            $new_transaction->vl_buy = $taux_insere ?: $name_product->vl;
            $new_transaction->nb_part = $valeurLiquidative;
            $new_transaction->product_id = $name_product->id;
            $new_transaction->duree = $name_product->duree;
            $new_transaction->transaction_id = $existing_transaction->id;
            $new_transaction->date_validation = $date_valeur;
            $new_transaction->date_echeance = $date_echeance;
            $new_transaction->type = $name_product->products_category_id;
            $new_transaction->montant_initiale = $montantTotal;

            $new_transaction->save();
        } else {
            $transaction = new Transaction();
            $transaction->title = "Souscription " . $type_souscription . " de " . $name_product->title;
            $transaction->ref = "Kori-" . $unique_id;
            $transaction->payment_mode = "A définir";
            $transaction->amount = $montantTotal;
            $transaction->status = "En attente";
            $transaction->user_id = $customer;
            $transaction->vl_buy = $taux_insere ?: $name_product->vl;
            $transaction->duree = $name_product->duree;
            $transaction->nb_part = $valeurLiquidative;
            $transaction->product_id = $name_product->id;
            $transaction->date_validation = $date_valeur;
            $transaction->date_echeance = $date_echeance;
            $transaction->type = $name_product->products_category_id;
            $transaction->montant_initiale = $montantTotal;

            $transaction->save();
        }


        // $name_product->nb_action = $name_product->nb_action - round($valeurLiquidative, 2);

        $name_product->save();

        return response()->json(['message' => 'Données enregistrées avec succès']);
    }
}
