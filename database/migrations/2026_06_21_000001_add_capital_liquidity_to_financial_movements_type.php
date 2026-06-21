<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddCapitalLiquidityToFinancialMovementsType extends Migration
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
        'liquidite_capital',
        'paiement_capital',
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

        $types = array_values(array_filter(self::TYPES, fn ($type) => !in_array($type, ['liquidite_capital', 'paiement_capital'])));
        DB::statement("ALTER TABLE financial_movements MODIFY COLUMN type ENUM('" . implode("','", $types) . "') NOT NULL");
    }
}
