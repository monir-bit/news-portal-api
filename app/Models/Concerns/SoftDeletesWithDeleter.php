<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Laravel soft deletes plus nullable {@see $deleted_by} set from {@see auth()->id()} on trash,
 * cleared on {@see restore()}.
 */
trait SoftDeletesWithDeleter
{
    use SoftDeletes;

    protected function runSoftDelete(): void
    {
        $query = $this->setKeysForSaveQuery($this->newModelQuery());

        $time = $this->freshTimestamp();

        $deletedAtColumn = $this->getDeletedAtColumn();

        $columns = [
            $deletedAtColumn => $this->fromDateTime($time),
            'deleted_by' => auth()->id(),
        ];

        $this->{$deletedAtColumn} = $time;
        $this->deleted_by = auth()->id();

        if ($this->usesTimestamps() && ! is_null($this->getUpdatedAtColumn())) {
            $this->{$this->getUpdatedAtColumn()} = $time;

            $columns[$this->getUpdatedAtColumn()] = $this->fromDateTime($time);
        }

        $query->update($columns);

        $this->syncOriginalAttributes(array_keys($columns));

        $this->fireModelEvent('trashed', false);
    }

    /**
     * @return bool|null
     */
    public function restore()
    {
        if ($this->fireModelEvent('restoring') === false) {
            return false;
        }

        $this->{$this->getDeletedAtColumn()} = null;
        $this->deleted_by = null;

        $this->exists = true;

        $result = $this->save();

        $this->fireModelEvent('restored', false);

        return $result;
    }
}
