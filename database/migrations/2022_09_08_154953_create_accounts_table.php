<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
<<<<<<< HEAD:database/migrations/2022_09_08_154953_create_accounts_table.php
            $table->string('username')->nullbale();
            $table->string('nama')->nullbale();
            $table->string('password')->nullbale();
            // $table->string('username')->nullbale();
=======
            $table->string('nama')->nullable();
            $table->string('url')->nullable();
>>>>>>> 5a5865f34e9a0866bee773204e2d5343edd57e01:database/migrations/2022_09_07_220742_create_categories_table.php
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('accounts');
    }
}
