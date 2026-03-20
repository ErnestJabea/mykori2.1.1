<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AchatActionPmgMail extends Mailable
{
    use Queueable, SerializesModels;
    public $valeurLiquidative;
    public $fraisGestion;
    public $montantTotal;

    public $title_product;

    public $username;
    public $useremail;

    public $vl_actuel;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($valeurLiquidative, $fraisGestion, $montantTotal, $username, $useremail, $taux_interet, $title_product)
    {
        $this->valeurLiquidative = $valeurLiquidative;
        $this->fraisGestion = $fraisGestion;
        $this->montantTotal = $montantTotal;
        $this->username = $username;
        $this->useremail = $useremail;
        $this->vl_actuel = $taux_interet;
        $this->title_product = $title_product;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('mail.achatActionPmgMail')->subject('Procédure d\'achat du produit ' . $this->username);
    }
}
