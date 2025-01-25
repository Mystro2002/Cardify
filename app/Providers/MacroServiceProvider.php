<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Builder;

class MacroServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Builder::macro('searchMany', function ($fields, $string, $perPage = 10) {
            if ($string) {
                $this->where(function ($query) use ($fields, $string) {
                    foreach ($fields as $field) {
                        // Check if the field is in the main table (Content) or in the related table (Menu)
                        if (strpos($field, 'menu.') === 0) {
                            // Field is in the related table (Menu)
                            $query->orWhereHas('menu', function ($subQuery) use ($field, $string) {
                                $fieldWithoutPrefix = str_replace('menu.', '', $field);
                                $subQuery->where($fieldWithoutPrefix, 'like', '%' . $string . '%');
                            });
                        } else {
                            // Field is in the main table (Content)
                            $query->orWhere($field, 'like', '%' . $string . '%');
                        }
                    }
                });
            }
    
            return $this->paginate($perPage);
        });
    }
}
