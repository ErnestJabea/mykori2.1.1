<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MailValidationTransactionPmg extends Mailable
{
    use Queueable, SerializesModels;

    public $nom_client;
    public $date_transaction;
    public $status_;

    public $nom_produit;

    public $vl;

    public $ref_transaction;

    public $montantTransaction;

    public $nbpart;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($nom_client, $date_transaction, $status_, $nom_produit, $vl, $ref_transaction, $montantTransaction, $nbpart)
    {
        //

        $this->nom_client = $nom_client;
        $this->date_transaction = $date_transaction;
        $this->status_ = $status_;
        $this->nom_produit = $nom_produit;
        $this->vl = $vl;
        $this->ref_transaction = $ref_transaction;
        $this->montantTransaction = $montantTransaction;
        $this->nbpart = $nbpart;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('mail.mailValidationTransactionPmg')->subject('Status de transaction ' . $this->ref_transaction);
    }
}
