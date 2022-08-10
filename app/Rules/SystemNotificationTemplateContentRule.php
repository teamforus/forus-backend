<?php

namespace App\Rules;

class SystemNotificationTemplateContentRule extends BaseRule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        $type = request()->input(substr($attribute, 0, strrpos($attribute, '.')) . '.type');

        if ($type == 'push' && strlen($value) > 170) {
            return $this->reject(trans('validation.max.string', [
                'attribute' => 'content',
                'max' => 170,
            ]));
        }

        if ($type == 'database' && strlen($value) > 400) {
            return $this->reject(trans('validation.max.string', [
                'attribute' => 'content',
                'max' => 400,
            ]));
        }

        if ($type == 'mail' && strlen($value) > 16384) {
            return $this->reject(trans('validation.max.string', [
                'attribute' => 'content',
                'max' => 16384,
            ]));
        }

        return true;
    }
}
