<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class MicrosoftMailService
{
    private $accessToken;
    private $fromEmail;

    public function __construct()
    {
        $this->fromEmail = config('services.microsoft.from_email');
        $this->authenticate();
    }

    private function authenticate()
    {
        try {
            $client = new Client();
            $url = 'https://login.microsoftonline.com/' . config('services.microsoft.tenant_id') . '/oauth2/v2.0/token';
            
            $response = $client->post($url, [
                'form_params' => [
                    'client_id' => config('services.microsoft.client_id'),
                    'client_secret' => config('services.microsoft.client_secret'),
                    'scope' => 'https://graph.microsoft.com/.default',
                    'grant_type' => 'client_credentials',
                ],
            ]);

            $data = json_decode($response->getBody()->getContents());
            $this->accessToken = $data->access_token;
            
        } catch (\Exception $e) {
            Log::error('Erreur authentification Microsoft: ' . $e->getMessage());
            throw $e;
        }
    }

    public function envoyerOTP($email, $otp)
    {
        try {
            $client = new Client();
            
            $message = [
                'message' => [
                    'subject' => 'Votre code OTP - Kori Asset Management',
                    'body' => [
                        'contentType' => 'HTML',
                        'content' => $this->templateOTP($otp)
                    ],
                    'toRecipients' => [
                        [
                            'emailAddress' => [
                                'address' => $email
                            ]
                        ]
                    ]
                ],
                'saveToSentItems' => true
            ];

            $response = $client->post(
                "https://graph.microsoft.com/v1.0/users/{$this->fromEmail}/sendMail",
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->accessToken,
                        'Content-Type' => 'application/json',
                    ],
                    'json' => $message
                ]
            );

            Log::info('OTP envoyé avec succès', ['email' => $email]);
            return true;

        } catch (\Exception $e) {
            Log::error('Erreur envoi OTP: ' . $e->getMessage());
            return false;
        }
    }

    private function templateOTP($otp)
    {
        return "
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset='UTF-8'>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background-color: #0066cc; color: white; padding: 20px; text-align: center; }
                    .content { background-color: #f9f9f9; padding: 30px; border-radius: 5px; margin-top: 20px; }
                    .otp-code { font-size: 32px; font-weight: bold; color: #0066cc; text-align: center; padding: 20px; background-color: white; border-radius: 5px; letter-spacing: 5px; }
                    .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #666; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>Kori Asset Management</h1>
                    </div>
                    <div class='content'>
                        <h2>Code de vérification OTP</h2>
                        <p>Bonjour,</p>
                        <p>Votre code de vérification OTP est :</p>
                        <div class='otp-code'>{$otp}</div>
                        <p style='text-align: center; margin-top: 20px;'>
                            <strong>Ce code expire dans 10 minutes.</strong>
                        </p>
                        <p>Si vous n'avez pas demandé ce code, veuillez ignorer cet email.</p>
                    </div>
                    <div class='footer'>
                        <p>© " . date('Y') . " Kori Asset Management. Tous droits réservés.</p>
                    </div>
                </div>
            </body>
            </html>
        ";
    }

    public function envoyerEmail($destinataire, $sujet, $corps)
    {
        try {
            $client = new Client();
            
            $message = [
                'message' => [
                    'subject' => $sujet,
                    'body' => [
                        'contentType' => 'HTML',
                        'content' => $corps
                    ],
                    'toRecipients' => [
                        [
                            'emailAddress' => [
                                'address' => $destinataire
                            ]
                        ]
                    ]
                ],
                'saveToSentItems' => true
            ];

            $response = $client->post(
                "https://graph.microsoft.com/v1.0/users/{$this->fromEmail}/sendMail",
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->accessToken,
                        'Content-Type' => 'application/json',
                    ],
                    'json' => $message
                ]
            );

            return true;

        } catch (\Exception $e) {
            Log::error('Erreur envoi email: ' . $e->getMessage());
            return false;
        }
    }
}