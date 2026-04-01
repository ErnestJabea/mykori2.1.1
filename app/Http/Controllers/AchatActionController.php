<?php

namespace App\Http\Controllers;

use App\Models\AssetValue;
use App\Models\User;
use App\Models\Transaction;
use App\Models\Product;
use App\Mail\AchatActionMail;
use App\Mail\AchatActionPmgMail;
use App\Models\TransactionSupplementaire;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class AchatActionController extends Controller
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
        $periodicite = $request->input('periodicite', 'Ponctuelle'); // Récupération de la périodicité
        $date_valeur = $request->input('date_valeur');
        $customer_id = $request->input('customer', Auth::user()->id);

        $name_product = Product::where('id', $product)->first();
        $vl = AssetValue::where('product_id', $product)->orderBy('created_at', 'desc')->first();
        $current_user = User::where('id', Auth::user()->id)->first();

        $username = $current_user->name;
        $useremail = $current_user->email;

        $unique_id = uniqid();

        // Vérifier si une transaction existe pour le produit et l'utilisateur actuels
        $existing_transaction = Transaction::where('product_id', $name_product->id)
            ->where('user_id', $customer_id)
            ->first();

        if ($existing_transaction) {
            // Créer une nouvelle transaction avec une référence unique
            $new_transaction = new TransactionSupplementaire();
            $new_transaction->title = "Investissement " . $periodicite . " de " . $name_product->title;
            $new_transaction->ref = "Kori-" . $existing_transaction->ref . "-" . $unique_id;
            $new_transaction->payment_mode = "A définir";
            $new_transaction->amount = $montant_normal;
            $new_transaction->fees = $fraisGestion;
            $new_transaction->status = "En attente";
            $new_transaction->user_id = $customer_id;
            $new_transaction->vl_buy = $vl ? $vl->vl : $name_product->vl;
            $new_transaction->nb_part = $valeurLiquidative;
            $new_transaction->product_id = $name_product->id;
            $new_transaction->duree = $name_product->duree;
            $new_transaction->transaction_id = $existing_transaction->id;
            $new_transaction->date_validation = $date_valeur;

            $vl_actuel = $vl ? $vl->vl : $name_product->vl;
            Mail::to($name_product->email_contact)->send(new AchatActionMail($valeurLiquidative, $vl_actuel, $fraisGestion, $montant_normal, $name_product, $username, $useremail));

            $new_transaction->save();

            $current_user->solde_stby = $current_user->solde_stby + ($valeurLiquidative * $vl_actuel);
            $current_user->save();
        } else {
            // Créer une nouvelle transaction avec une référence unique
            $transaction = new Transaction();
            $transaction->title = "Souscription " . $periodicite . " de " . $name_product->title;
            $transaction->ref = "Kori-" . $unique_id;
            $transaction->payment_mode = "A définir";
            $transaction->amount = $montant_normal;
            $transaction->fees = $fraisGestion;
            $transaction->status = "En attente";
            $transaction->user_id = $customer_id;
            $transaction->vl_buy = $vl ? $vl->vl : $name_product->vl;
            $transaction->nb_part = $valeurLiquidative;
            $transaction->duree = $name_product->duree;
            $transaction->product_id = $name_product->id;
            $transaction->date_validation = $date_valeur;
            $transaction->type = 1; // FCP

            $vl_actuel = $vl ? $vl->vl : $name_product->vl;

            Mail::to($name_product->email_contact)->send(new AchatActionMail($valeurLiquidative, $vl_actuel, $fraisGestion, $montantTotal, $name_product, $username, $useremail));

            $transaction->save();

            $current_user->solde_stby = $current_user->solde_stby + ($valeurLiquidative * $vl_actuel);
            $current_user->save();
        }



        //  $name_product->save();

        /* $transaction = new Transaction();

        $transaction->title = "Achat ";
        $transaction->ref = "Kori-" . uniqid();
        $transaction->payment_mode = "A définir";
        $transaction->amount = $montantTotal;
        $transaction->status = "En attente";
        $transaction->user_id = Auth::user()->id;
        $transaction->vl_buy = $vl->vl;
        $transaction->nb_part = $valeurLiquidative;
        $transaction->product_id = $name_product->id;

        $transaction->save(); */


        // Faites ce que vous devez faire avec ces données (par exemple, enregistrez-les en base de données)

        // Répondez avec un message JSON (vous pouvez personnaliser la réponse selon vos besoins)
        return response()->json(['message' => 'Données enregistrées avec succès']);
    }









    public function acheterActionPmg(Request $request)
    {
        // Récupérez les données envoyées via Ajax
        $valeurLiquidative = $request->input('montantAchat');
        $fraisGestion = $request->input('fraisGestion');
        $montantTotal = $request->input('montantTotal');
        $product = $request->input('product');
        $date_valeur = $request->input('date_valeur');
        $date_echeance = $request->input('date_echeance');
        $customer_id = $request->input('customer', Auth::user()->id);

        $name_product = Product::where('id', $product)->first();
        $vl = AssetValue::where('product_id', $product)->orderBy('created_at', 'desc')->first();
        $current_user = User::where('id', Auth::user()->id)->first();

        $username = $current_user->name;
        $useremail = $current_user->email;

        $unique_id = uniqid();



        // Vérifier si une transaction existe pour le produit et l'utilisateur actuels
        $existing_transaction = Transaction::where('product_id', $name_product->id)
            ->where('user_id', $customer_id)
            ->first();

        if ($existing_transaction) {
            // Créer une nouvelle transaction avec une référence unique
            $new_transaction = new TransactionSupplementaire();
            $new_transaction->title = "Souscription Supplémentaire de " . $name_product->title;
            $new_transaction->ref = $existing_transaction->ref . "-" . $unique_id;
            $new_transaction->payment_mode = "A définir";
            
            if ($name_product->products_category_id == 2) {
                $new_transaction->amount = $montantTotal;
                $new_transaction->fees = 0;
            } else {
                $new_transaction->amount = $request->input('montant_normal', $montantTotal); 
                $new_transaction->fees = $fraisGestion;
            }
            $new_transaction->status = "En attente";
            $new_transaction->user_id = $customer_id;
            $new_transaction->vl_buy = $name_product->vl;
            $new_transaction->nb_part = $valeurLiquidative;
            $new_transaction->product_id = $name_product->id;
            $new_transaction->duree = $name_product->duree;
            $new_transaction->transaction_id = $existing_transaction->id;
            $new_transaction->date_validation = $date_valeur;
            $new_transaction->date_echeance = $date_echeance;
            $new_transaction->montant_initiale = $montantTotal;
            $taux_interet = $name_product->vl;
            $title_product = $name_product->title;
            Mail::to($name_product->email_contact)->send(new AchatActionPmgMail($valeurLiquidative, $fraisGestion, $montantTotal, $title_product, $username, $taux_interet, $title_product));

            $new_transaction->save();
            //$current_user->solde_stby = $current_user->solde_stby + $montantTotal;
            //$current_user->save();


        } else {
            // Créer une nouvelle transaction avec une référence unique
            $transaction = new Transaction();
            $transaction->title = "Souscription de " . $name_product->title;
            $transaction->ref = "Kori-" . $unique_id;
            $transaction->payment_mode = "A définir";
            
            if ($name_product->products_category_id == 2) {
                $transaction->amount = $montantTotal;
                $transaction->fees = 0;
            } else {
                $transaction->amount = $request->input('montant_normal', $montantTotal); 
                $transaction->fees = $fraisGestion;
            }
            
            $transaction->status = "En attente";
            $transaction->user_id = $customer_id;
            $transaction->vl_buy = $name_product->vl;
            $transaction->duree = $name_product->duree;
            $transaction->nb_part = $valeurLiquidative;
            $transaction->product_id = $name_product->id;
            $transaction->date_validation = $date_valeur;
            $transaction->date_echeance = $date_echeance;
            $transaction->montant_initiale = $montantTotal;
            $taux_interet = $name_product->vl;
            $title_product = $name_product->title;
            Mail::to($name_product->email_contact)->send(new AchatActionPmgMail($valeurLiquidative, $fraisGestion, $montantTotal, $username, $useremail, $taux_interet, $title_product));

            $transaction->save();
            //$current_user->solde_stby = $current_user->solde_stby + $montantTotal;
            // $current_user->save();
        }


        // $name_product->nb_action = $name_product->nb_action - round($valeurLiquidative, 2);

        $name_product->save();

        /* $transaction = new Transaction();

        $transaction->title = "Achat ";
        $transaction->ref = "Kori-" . uniqid();
        $transaction->payment_mode = "A définir";
        $transaction->amount = $montantTotal;
        $transaction->status = "En attente";
        $transaction->user_id = Auth::user()->id;
        $transaction->vl_buy = $vl->vl;
        $transaction->nb_part = $valeurLiquidative;
        $transaction->product_id = $name_product->id;

        $transaction->save(); */


        // Faites ce que vous devez faire avec ces données (par exemple, enregistrez-les en base de données)

        // Répondez avec un message JSON (vous pouvez personnaliser la réponse selon vos besoins)
        return response()->json(['message' => 'Données enregistrées avec succès']);
    }
}
