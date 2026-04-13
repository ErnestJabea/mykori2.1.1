    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
    </head>
    <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0;">
        <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
            
            <!-- Message pour le système (sera supprimé par Power Automate) -->
            <div style="background-color: #f0f0f0; padding: 10px; margin-bottom: 20px; border: 1px dashed #999; font-size: 11px; color: #666;">
                <strong>🤖 Instructions automatiques (sera supprimé avant envoi au client) :</strong><br>
                Client: {{ $client->name }}<br>
                Email: {{ $client->email }}<br>
                Période: {{ $periode }}
            </div>

            <!-- Contenu réel de l'email pour le client -->
            <div style="border-top: 3px solid #ebb008; padding-top: 20px;">
                <p>Bonjour {{ $client->name }},</p>

                <p>Veuillez trouver en pièce jointe votre relevé de compte pour la période <strong>{{ $periode }}</strong>.</p>

                <p style="margin-top: 30px;">
                    Cordialement,<br>
                    <strong>KORI Asset Management</strong>
                </p>

                <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #eee; font-size: 11px; color: #666; text-align: center;">
                    <p>
                        +237 681 79 35 92 | 
                        Rue 1.131 DIKOUME BELL, BP: 1245 BALI-DOUALA<br>
                        info@koriassetmanagement.com | www.koriassetmanagement.com
                    </p>
                </div>
            </div>
        </div>
    </body>
    </html>
