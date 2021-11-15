<?php

namespace App\Rules;

class SystemNotificationTemplateTitle extends BaseRule
{
    protected $error;

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

        if ($type == 'push' && strlen($value) > 40) {
            return $this->reject(trans('validation.max.string', [
                'attribute' => 'titel',
                'max' => 40,
            ]));
        }

        if ($type != 'push' && strlen($value) > 140) {
            return $this->reject(trans('validation.max.string', [
                'attribute' => 'titel',
                'max' => 140,
            ]));
        }

        return true;
    }
}
