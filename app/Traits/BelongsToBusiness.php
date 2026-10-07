<?php

namespace App\Traits;

use App\Services\BusinessContextService;
use Illuminate\Database\Eloquent\Builder;

trait BelongsToBusiness
{
    protected static function bootBelongsToBusiness()
    {
        static::addGlobalScope('business', function (Builder $builder) {
            $context = app(BusinessContextService::class);
            
            if ($context->hasBusinessContext()) {
                $builder->where('business_id', $context->getBusinessId());
            }
        });

        static::creating(function ($model) {
            $context = app(BusinessContextService::class);
            
            if ($context->hasBusinessContext() && empty($model->business_id)) {
                $model->business_id = $context->getBusinessId();
            }
        });
    }

    public function business()
    {
        return $this->belongsTo(\App\Models\Business::class);
    }
}
