<?php

namespace App\Transformers;

use App\Models\FinancialReport;
use Flugg\Responder\Transformers\Transformer;

class FinancialReportTransformer extends Transformer
{
    /**
     * List of available relations.
     *
     * @var string[]
     */
    protected $relations = [

    ];

    /**
     * List of autoloaded default relations.
     *
     * @var array
     */
    protected $load = [
        'cause' => CauseTransformer::class,
    ];

    /**
     * Transform the model.
     *
     * @param  \App\Models\FinancialReport $financialReport
     * @return array
     */
    public function transform(FinancialReport $financialReport)
    {
        return [
            'id' => (int) $financialReport->id,
            'total_expenditure' => $financialReport->total_expenditure,
            'total_donations' => $financialReport->total_donations,
            'period' => $financialReport->period,
            'created_at' => $financialReport->created_at,
        ];
    }
}
