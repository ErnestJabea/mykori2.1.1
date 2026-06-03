<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class AnniversaryNotificationMail extends Mailable
{
    public array $transactions;
    public string $targetDateFormatted;

    public function __construct(array $transactions, string $targetDateFormatted)
    {
        $this->transactions = $transactions;
        $this->targetDateFormatted = $targetDateFormatted;
    }

    public function build()
    {
        return $this
            ->subject('⚠️ ALERTES ANNIVERSAIRES PMG - ÉCHÉANCE DANS 7 JOURS (' . $this->targetDateFormatted . ')')
            ->view('emails.anniversary-notification');
    }
}
