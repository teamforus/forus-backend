<?php

namespace App\Models\Traits;

use App\Models\Employee;
use App\Models\Note;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @mixin \Eloquent
 */
trait HasNotes
{
    /**
     * @return MorphMany
     */
    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'notable');
    }

    /**
     * @param string $description
     * @param Employee|null $employee
     * @return Note
     */
    public function addNote(string $description, ?Employee $employee = null): Note
    {
        return $this->notes()->create([
            'description' => $description,
            'employee_id' => $employee?->id,
        ]);
    }
}