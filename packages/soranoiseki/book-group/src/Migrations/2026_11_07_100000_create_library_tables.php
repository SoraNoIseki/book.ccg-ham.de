<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Modern replacement for the old openbiblio database (biblio, biblio_copy, member).
 * Borrowing now has its own table, so the history is kept instead of being overwritten.
 * The legacy_* columns let `library:import-openbiblio` run repeatedly without duplicating rows.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('library_books', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();          // catalogue number, e.g. FMCD0012 or A01
            $table->string('isbn', 20)->nullable()->index();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->string('author')->nullable();
            $table->string('publisher')->nullable();
            $table->string('category')->nullable()->index();
            $table->string('cover_url', 500)->nullable();
            $table->boolean('is_visible')->default(true);
            $table->unsignedInteger('legacy_id')->nullable()->unique(); // openbiblio biblio.bibid
            $table->timestamps();
        });

        Schema::create('library_copies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained('library_books')->cascadeOnDelete();
            $table->unsignedSmallInteger('number');          // 1, 2, 3 ... within the book
            $table->string('code', 50)->unique();            // label on the physical copy, e.g. FMCD0012.1
            $table->string('status', 20)->default('active')->index(); // active | lost | retired
            $table->unsignedInteger('legacy_id')->nullable();         // openbiblio biblio_copy.copyid
            $table->timestamps();

            $table->unique(['book_id', 'number']);
        });

        Schema::create('library_members', function (Blueprint $table) {
            $table->id();
            $table->string('card_no', 40)->nullable()->unique();
            $table->string('first_name');
            $table->string('last_name')->default('');
            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();
            $table->date('membership_ends_at')->nullable();
            $table->unsignedInteger('legacy_id')->nullable()->unique(); // openbiblio member.mbrid
            $table->timestamps();

            $table->index(['last_name', 'first_name']);
        });

        Schema::create('library_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('copy_id')->constrained('library_copies')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('library_members')->cascadeOnDelete();
            $table->dateTime('borrowed_at');
            $table->dateTime('due_at');
            $table->dateTime('returned_at')->nullable();
            $table->unsignedTinyInteger('renewals')->default(0);
            $table->unsignedBigInteger('borrowed_by')->nullable();  // users.id
            $table->unsignedBigInteger('returned_by')->nullable();  // users.id
            $table->timestamps();

            $table->index(['copy_id', 'returned_at']);
            $table->index(['member_id', 'returned_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_loans');
        Schema::dropIfExists('library_members');
        Schema::dropIfExists('library_copies');
        Schema::dropIfExists('library_books');
    }
};
