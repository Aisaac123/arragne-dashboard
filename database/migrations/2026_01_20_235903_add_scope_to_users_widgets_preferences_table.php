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
        Schema::table('user_widget_preferences', function (Blueprint $table) {
            // Agregar scope para diferenciar entre páginas y secciones
            $table->string('scope')->default('default')->after('widget_name');

            // Eliminar el unique anterior y crear uno nuevo con scope
            $table->dropUnique(['user_id', 'widget_name']);
            $table->unique(['user_id', 'widget_name', 'scope']);
        });
    }

    public function down(): void
    {
        Schema::table('user_widget_preferences', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'widget_name', 'scope']);
            $table->unique(['user_id', 'widget_name']);
            $table->dropColumn('scope');
        });
    }
};
