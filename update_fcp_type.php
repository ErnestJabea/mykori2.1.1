<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    DB::statement("ALTER TABLE fcp_movements MODIFY type ENUM('souscription','rachat','frais','ajustement','versement_libre') NOT NULL");
    echo "Table fcp_movements mise à jour avec succès. Type 'versement_libre' ajouté.\n";
} catch (\Exception $e) {
    echo "Erreur lors de la mise à jour : " . $e->getMessage() . "\n";
}
