<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('profile_relations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('profile_id')->constrained('profiles')->restrictOnDelete();
            $table->foreignId('related_profile_id')->constrained('profiles')->restrictOnDelete();

            $table->enum('type', [
                'partner', 'parent_child', 'cohabitant',
            ]);

            $table->enum('subtype', [
                // Partner
                'partner_married',
                'partner_registered',
                'partner_unmarried',
                'partner_other_family_relation',

                // Parent-child
                'parent_child',
                'foster_parent_child',

                // Cohabitant
                'parent',
                'parent_in_law',
                'grandparent_sibling',
                'rents_room_from_me',
                'i_rent_room_from_them',
                'boarder_or_landlord',
                'other',
            ]);

            $table->boolean('living_together');
            $table->timestamps();

            $table->unique(['profile_id', 'related_profile_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('identity_relations');
    }
};
