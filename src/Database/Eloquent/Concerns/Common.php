<?php

declare(strict_types=1);

namespace Deflinhec\LaravelClickHouse\Database\Eloquent\Concerns;

trait Common
{
    /**
     * Save the model to the database.
     */
    public function save($options = array())
    {
        $this->mergeAttributesFromClassCasts();

        if ($this->fireModelEvent('saving') === false) {
            return false;
        }

        $saved = static::insert($this->attributesToArray());

        // If the model is successfully saved, we need to do a few more things once
        // that is done. We will call the "saved" method here to run any actions
        // we need to happen after a model gets successfully saved right here.
        if ($saved) {
            $this->finishSave($options);
        }

        return $saved;
    }
}
