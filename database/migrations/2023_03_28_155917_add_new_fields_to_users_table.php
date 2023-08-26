<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('firstname')->after('name');
            $table->string('lastname')->after('firstname');
            $table->boolean('enabled')->default(0)->after('email_verified_at');
            $table->timestamp('last_login')->nullable()->after('remember_token');
            $table->timestamp('last_activity')->nullable()->after('last_login');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('firstname');
            $table->dropColumn('lastname');
            $table->dropColumn('enabled');
            $table->dropColumn('last_login');
            $table->dropColumn('last_activity');
        });
    }
};
