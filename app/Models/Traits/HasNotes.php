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
     * @return HasNotes|\App\Models\Reimbursement
     */
    public function addNote(string $description, ?Employee $employee = null): self
    {
        $this->notes()->create([
            'description' => $description,
            'employee_id' => $employee?->id,
        ]);

        return $this;
    }
}