<?php

namespace App\Mail;

use App\Models\StatementCorrection;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CorrectionPendingMail extends Mailable
{
    use Queueable, SerializesModels;

    public $correction;

    public function __construct(StatementCorrection $correction)
    {
        $this->correction = $correction;
    }

    public function build()
    {
        return $this->subject('⚠️ MyKori - Demande de correction en attente de contrôle (#'.$this->correction->id.')')
                    ->html("
                        <h2>MyKori - Contrôle Interne</h2>
                        <p>Une nouvelle demande de correction a été saisie et nécessite un contrôle à 4 yeux :</p>
                        <ul>
                            <li><strong>ID Demande :</strong> #{$this->correction->id}</li>
                            <li><strong>Client :</strong> ".($this->correction->user->name ?? 'Client #'.$this->correction->user_id)."</li>
                            <li><strong>Entité / Champ :</strong> {$this->correction->target_entity} ({$this->correction->field_name})</li>
                            <li><strong>Ancienne Valeur :</strong> {$this->correction->old_value}</li>
                            <li><strong>Nouvelle Valeur :</strong> {$this->correction->new_value}</li>
                            <li><strong>Motif :</strong> {$this->correction->reason}</li>
                            <li><strong>Opérateur :</strong> ".($this->correction->operator->name ?? 'Opérateur #'.$this->correction->operator_id)."</li>
                        </ul>
                        <p><a href='".route('control-adjustments.show', $this->correction->user_id)."' style='background-color:#0066cc;color:#fff;padding:8px 15px;text-decoration:none;border-radius:4px;'>Consulter et Contrôler la Fiche</a></p>
                    ");
    }
}
