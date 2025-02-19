<?php

use App\Services\Forus\Session\Models\Session;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $sessions = Session::query()
            ->whereNull('created_at')
            ->whereHas('first_request')
            ->with('first_request')
            ->get();

        foreach ($sessions as $session) {
            $session->forceFill(['created_at' => $session->first_request?->created_at])->save();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
