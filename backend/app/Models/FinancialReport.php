<?php

namespace App\Models;

use App\Transformers\FinancialReportTransformer;

class FinancialReport extends BaseModel
{
    public $transformer  = FinancialReportTransformer::class;

    /**
     * Relationships
     */

    // A financial report belongs to a specific cause
    public function cause()
    {
        return $this->belongsTo(Cause::class);
    }
}
