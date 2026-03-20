<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AchatActionMail extends Mailable
{
    use Queueable, SerializesModels;
    public $valeurLiquidative;
    public $fraisGestion;
    public $montantTotal;

    public $vl_actuel;
    public $product_name;

    public $username;
    public $useremail;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($valeurLiquidative, $vl_actuel, $fraisGestion, $montantTotal, $product_name, $username, $useremail)
    {
        $this->valeurLiquidative = $valeurLiquidative;
        $this->fraisGestion = $fraisGestion;
        $this->montantTotal = $montantTotal;
        $this->product_name = $product_name;
        $this->username = $username;
        $this->useremail = $useremail;
        $this->vl_actuel = $vl_actuel;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('mail.achatActionMail')->subject('Procédure d\'achat du produit ' . $this->product_name->title);
    }
}
