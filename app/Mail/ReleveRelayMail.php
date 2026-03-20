<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use TCG\Voyager\Models\User;

class ReleveRelayMail extends Mailable
{
    public User $client;
    public array $pdfFiles;
    public string $periode;

    public function __construct(User $client, array $pdfFiles, ?string $periode = null)
    {
        $this->client = $client;
        $this->pdfFiles = $pdfFiles;
        $this->periode = $periode ?? now()->subMonth()->locale('fr')->isoFormat('MMMM YYYY');
    }

    public function build()
    {
        // ✅ Format du sujet pour extraction automatique
        // [CLIENT:email@client.com] Relevé mensuel - Période
        $mail = $this
            ->subject('[CLIENT:' . $this->client->email . '] Relevé mensuel - ' . $this->periode)
            ->view('front-end.releves.releve-relay');

        foreach ($this->pdfFiles as $file) {
            if (file_exists($file)) {
                $mail->attach($file, [
                    'as' => 'releve-' . now()->format('Y-m') . '.pdf',
                    'mime' => 'application/pdf',
                ]);
            }
        }

        return $mail;
    }
}