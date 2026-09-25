<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class FarmScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (! Auth::check()) {
            // Migrations, seeders and artisan commands run without a user; web requests must not.
            if (! app()->runningInConsole()) {
                $builder->whereRaw('1 = 0');
            }

            return;
        }

        $user = Auth::user();

        if ($user->role === 'super_admin') {
            return;
        }

        // A farm user with no farm would otherwise match every NULL-farm row.
        if ($user->farm_id === null) {
            $builder->whereRaw('1 = 0');

            return;
        }

        $builder->where($model->getTable().'.farm_id', $user->farm_id);
    }
}
