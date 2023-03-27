<?php

namespace App\Models\Traits;

use App\Models\Faq;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @mixin \Eloquent
 */
trait HasFaq
{
    /**
     * @return MorphMany
     */
    public function faq(): MorphMany
    {
        return $this->morphMany(Faq::class, 'faq')->orderBy('order');
    }

    /**
     * @param array|null $faq
     * @return static
     */
    public function syncFaqOptional(?array $faq = null): static
    {
        return is_array($faq) ? $this->syncFaq($faq) : $this;
    }

    /**
     * @param array $faq
     * @return static
     */
    public function syncFaq(array $faq): static
    {
        // remove faq not listed in the array
        $this->faq()->whereNotIn('id', array_filter(array_pluck($faq, 'id')))->delete();

        foreach ($faq as $index => $question) {
            $this->syncQuestion(array_merge($question, [
                'order' => $index
            ]));
        }

        return $this;
    }

    /**
     * Update faq question or create new fund question
     *
     * @param array $question
     * @return HasFaq|Model
     */
    protected function syncQuestion(array $question): Faq|Model
    {
        $faq = $this->faq()->find($question['id'] ?? null) ?: $this->faq()->create();
        $faq->updateModel(array_only($question, ['title', 'description', 'order']));
        $faq->syncDescriptionMarkdownMedia('cms_media');

        return $faq;
    }
}