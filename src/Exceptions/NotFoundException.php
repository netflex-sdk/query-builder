<?php

namespace Netflex\Query\Exceptions;

use Illuminate\Database\Eloquent\ModelNotFoundException;

use Illuminate\Support\Str;
use Facade\IgnitionContracts\ProvidesSolution;
use Facade\IgnitionContracts\Solution;
use Facade\IgnitionContracts\BaseSolution;

class NotFoundException extends ModelNotFoundException implements ProvidesSolution
{
    protected $field = 'id';

    /**
     * @param string $field
     * @return void
     */
    public function setField($field)
    {
        $this->field = $field;
    }

    /**
     * @return string
     */
    public function getField()
    {
        return $this->field;
    }

    public function getSolution(): Solution
    {
        $model = $this->getModel();
        $ids = $this->getIds();
        $field = $this->getField();

        $subject = 'model';

        $description = 'Unable to find ' . $subject;

        if ($ids && count($ids)) {
            $subject = Str::plural($subject, count($ids));

            $description = 'Unable to find ' . $subject . ' by id: ' . implode(', ', $ids);

            if ($field !== 'id') {
                $description = 'Unable to resolve ' . $subject . ' by ' . $field . ': ' . implode(', ', $ids);
            }
        }

        $description . '. It might be unpublished.';

        return BaseSolution::create(($model ? ($model . ': ') : '') . 'NotFoundException')
            ->setSolutionDescription($description)
            ->setDocumentationLinks([
                'Netflex SDK documentation' => 'https://netflex-sdk.github.io/#/docs/models?id=working-with-models',
            ]);
    }
}
