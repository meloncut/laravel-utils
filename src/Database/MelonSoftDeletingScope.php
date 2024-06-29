<?php
namespace Meloncut\LaravelUtils\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Database\Eloquent\Builder;

class MelonSoftDeletingScope implements Scope {
    /**
     * All the extensions to be added to the builder.
     *
     * @var string[]
     */
    protected $extensions = ['Restore', 'WithTrashed', 'WithoutTrashed', 'OnlyTrashed'];

    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param Builder $builder
     * @param Model|MelonSoftDeletes $model
     *
     * @return void
     */
    public function apply (Builder $builder, Model $model) {
        $builder->where($model->getQualifiedDeletedColumn(), false);
    }

    /**
     * Extend the query builder with the needed functions.
     *
     * @param Builder|MelonSoftDeletes $builder
     * @return void
     */
    public function extend (Builder $builder) {
        foreach ($this->extensions as $extension) {
            $this->{"add{$extension}"}($builder);
        }

        $builder->onDelete(function (Builder $builder) {
            $column = $this->getDeletedColumn($builder);

            return $builder->update([
                $column => $builder->getModel()->freshTimestampString(),
            ]);
        });
    }

    /**
     * Get the "deleted at" column for the builder.
     *
     * @param Builder $builder
     * @return string
     */
    protected function getDeletedColumn (Builder $builder) {
        if (count((array)$builder->getQuery()->joins) > 0) {
            return $builder->getModel()->getQualifiedDeletedColumn();
        }

        return $builder->getModel()->getDeletedColumn();
    }

    /**
     * Add the restore extension to the builder.
     *
     * @param Builder $builder
     * @return void
     */
    protected function addRestore (Builder $builder) {
        $builder->macro('restore', function (Builder $builder) {
            $builder->withTrashed();

            return $builder->update([
                $builder->getModel()->getDeletedColumn() => false,
                $builder->getModel()->getDeletedAtColumn() => null,
            ]);
        });
    }

    /**
     * Add the with-trashed extension to the builder.
     *
     * @param Builder $builder
     * @return void
     */
    protected function addWithTrashed (Builder $builder) {
        $builder->macro('withTrashed', function (Builder $builder, $withTrashed = true) {
            if (!$withTrashed) {
                return $builder->withoutTrashed();
            }

            return $builder->withoutGlobalScope($this);
        });
    }

    /**
     * Add the without-trashed extension to the builder.
     *
     * @param Builder $builder
     * @return void
     */
    protected function addWithoutTrashed (Builder $builder) {
        $builder->macro('withoutTrashed', function (Builder $builder) {
            $model = $builder->getModel();

            $builder->withoutGlobalScope($this)->where(
                $model->getQualifiedDeletedColumn(),
                false
            );

            return $builder;
        });
    }

    /**
     * Add the only-trashed extension to the builder.
     *
     * @param Builder $builder
     * @return void
     */
    protected function addOnlyTrashed (Builder $builder) {
        $builder->macro('onlyTrashed', function (Builder $builder) {
            $model = $builder->getModel();

            $builder->withoutGlobalScope($this)->where(
                $model->getQualifiedDeletedColumn(), true
            );

            return $builder;
        });
    }
}
