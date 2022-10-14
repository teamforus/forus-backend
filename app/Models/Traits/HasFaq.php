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
        return $this->morphMany(Faq::class, 'faq');
    }

    /**
     * @param array|null $faq
     * @return HasFaq|Model
     */
    public function syncFaqOptional(?array $faq = null): self
    {
        return is_array($faq) ? $this->syncFaq($faq) : $this;
    }

    /**
     * @param array $faq
     * @return Faq|Model
     */
    public function syncFaq(array $faq): Faq|Model
    {
        // remove faq not listed in the array
        $this->faq()->whereNotIn('id', array_filter(array_pluck($faq, 'id')))->delete();

        foreach ($faq as $question) {
            $this->syncQuestion($question);
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
        /** @var Faq $faq */
        $faq = $this->faq()->find($question['id'] ?? null) ?: $this->faq()->create();
        $faq->updateModel(array_only($question, ['title', 'description']));
        $faq->syncDescriptionMarkdownMedia('cms_media');

        return $faq;
    }
}