<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddLiquiditeInteretsToFinancialMovementsType extends Migration
{
    private const TYPES = [
        'souscription',
        'souscription_initiale',
        'versement_libre',
        'rachat_partiel',
        'rachat_total',
        'capitalisation_interets',
        'frais_gestion',
        'precompte_interets',
        'paiement_interets',
        'liquidite_interets',
        'remboursement',
        'dividende_interets',
    ];

    public function up()
    {
        if (DB::getDriverName() !== 'mysql' || !Schema::hasTable('financial_movements')) {
            return;
        }

        DB::statement("ALTER TABLE financial_movements MODIFY COLUMN type ENUM('" . implode("','", self::TYPES) . "') NOT NULL");
    }

    public function down()
    {
        if (DB::getDriverName() !== 'mysql' || !Schema::hasTable('financial_movements')) {
            return;
        }

        $types = array_values(array_filter(self::TYPES, fn ($type) => $type !== 'liquidite_interets'));
        DB::statement("ALTER TABLE financial_movements MODIFY COLUMN type ENUM('" . implode("','", $types) . "') NOT NULL");
    }
}
