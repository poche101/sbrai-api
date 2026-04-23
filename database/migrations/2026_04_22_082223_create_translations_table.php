<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('translations', function (Blueprint $table) {
            $table->id();

            // ── Locale ─────────────────────────────────────────────────────────
            // BCP 47 language codes: en | fr | ha | ig | yo
            $table->string('locale', 10)->index();

            // ── Grouping ───────────────────────────────────────────────────────
            // Maps to Flutter's ARB file groups / screen names:
            //   common | home | auth | ads | categories | chat |
            //   dashboard | profile | settings | voucher
            $table->string('group', 50)->index();

            // ── Key ────────────────────────────────────────────────────────────
            // Snake-case identifier, e.g. "search_placeholder"
            // May contain placeholders: "results_for_{category}"
            $table->string('key', 100);

            // ── Value ──────────────────────────────────────────────────────────
            $table->text('value');

            $table->timestamps();

            // One row per locale + group + key
            $table->unique(['locale', 'group', 'key'], 'translations_locale_group_key');

            // Fast lookup: GET /api/v1/translations/{locale}
            $table->index(['locale', 'group']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('translations');
    }
};
