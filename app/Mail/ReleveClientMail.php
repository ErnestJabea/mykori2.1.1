<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use TCG\Voyager\Models\User;

class ReleveClientMail extends Mailable
{
    public User $client;
    public array $pdfFiles;
    public string $periode;
    public ?string $statementType;

    public function __construct(User $client, array $pdfFiles, ?string $periode = null, ?string $statementType = null)
    {
        $this->client = $client;
        $this->pdfFiles = $pdfFiles;
        $this->statementType = $statementType ? strtolower($statementType) : null;

        if ($this->statementType !== null && !in_array($this->statementType, ['pmg', 'fcp'], true)) {
            throw new \InvalidArgumentException('Type de releve inconnu.');
        }

        // Si période non fournie, utiliser le mois précédent
        $this->periode = $periode ?? now()->subMonth()->locale('fr')->isoFormat('MMMM YYYY');
    }

    public function build()
    {
        $mail = $this
            ->subject('[CLIENT:' . $this->client->email . '] Relevé mensuel de votre portefeuille - ' . $this->periode)
            ->view('front-end.releves.releve-relay');

        if ($this->statementType !== null) {
            $sender = config("mail.statement_senders.{$this->statementType}");
            $mail->from($sender['address'], $sender['name']);
        }

        foreach ($this->pdfFiles as $file) {
            if (file_exists($file)) {
                $mail->attach($file, [
                    'as' => basename($file),
                    'mime' => 'application/pdf',
                ]);
            }
        }

        return $mail;
    }
}
