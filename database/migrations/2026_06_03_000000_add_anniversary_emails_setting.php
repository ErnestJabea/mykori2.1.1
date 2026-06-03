<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddAnniversaryEmailsSetting extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('settings')) {
            $exists = DB::table('settings')->where('key', 'site.anniversary_emails')->exists();
            if (!$exists) {
                $maxId = DB::table('settings')->max('id') ?? 0;
                DB::table('settings')->insert([
                    'id' => $maxId + 1,
                    'key' => 'site.anniversary_emails',
                    'display_name' => 'Emails pour notifications d’anniversaires PMG',
                    'value' => 'onboarding@koriassetmanagement.com, admin@koriassetmanagement.com',
                    'details' => 'Entrez les adresses email séparées par des virgules.',
                    'type' => 'text_area',
                    'order' => 10,
                    'group' => 'Site',
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('settings')) {
            DB::table('settings')->where('key', 'site.anniversary_emails')->delete();
        }
    }
}
