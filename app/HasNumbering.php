<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

trait HasNumbering
{
    public static function bootHasNumbering(): void
    {
        static::creating(function (Model $model) {
            if (method_exists($model, 'generateNumber')) {
                $numberField = $model->getNumberField();
                if (empty($model->$numberField)) {
                    $model->$numberField = $model->generateNumber();
                }
            }
        });
    }

    abstract protected function generateNumber(): string;
    abstract protected function getNumberField(): string;
}
