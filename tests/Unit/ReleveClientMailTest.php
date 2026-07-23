<?php

namespace Tests\Unit;

use App\Mail\ReleveClientMail;
use App\Models\User;
use Tests\TestCase;

class ReleveClientMailTest extends TestCase
{
    public function test_pmg_statement_uses_the_configured_pmg_sender(): void
    {
        config([
            'mail.statement_senders.pmg.address' => 'onboarding@koriassetmanagement.com',
            'mail.statement_senders.pmg.name' => 'KORI ASSET MANAGEMENT - PMG',
        ]);

        $mail = (new ReleveClientMail($this->client(), [], 'juin 2026', 'pmg'))->build();

        $this->assertSame('onboarding@koriassetmanagement.com', $mail->from[0]['address']);
        $this->assertSame('KORI ASSET MANAGEMENT - PMG', $mail->from[0]['name']);
    }

    public function test_fcp_statement_uses_the_configured_fcp_sender(): void
    {
        config([
            'mail.statement_senders.fcp.address' => 'fcp.koriserenite@koriassetmanagement.com',
            'mail.statement_senders.fcp.name' => 'FCP KORI SERENITE',
        ]);

        $mail = (new ReleveClientMail($this->client(), [], 'juin 2026', 'fcp'))->build();

        $this->assertSame('fcp.koriserenite@koriassetmanagement.com', $mail->from[0]['address']);
        $this->assertSame('FCP KORI SERENITE', $mail->from[0]['name']);
    }

    public function test_unknown_statement_type_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new ReleveClientMail($this->client(), [], 'juin 2026', 'inconnu');
    }

    private function client(): User
    {
        $client = new User();
        $client->name = 'Client Test';
        $client->email = 'client@example.com';

        return $client;
    }
}
