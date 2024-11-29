<?php

namespace App\Http\Controllers;

use Flugg\Responder\Http\Responses\SuccessResponseBuilder;
use Flugg\Responder\Responder;
use Illuminate\Database\Eloquent\Model;

abstract class Controller extends \Illuminate\Routing\Controller
{
    use \Dingo\Api\Routing\Helpers,
        \Flugg\Responder\Http\MakesResponses;

    public function __construct()
    {
        $this->middleware('api.auth', ['only' => ['store', 'update', 'destroy']]);
    }

    public function success($data = null, $transformer = null, string $resourceKey = null): SuccessResponseBuilder
    {
        /** @var \Flugg\Responder\Http\Responses\SuccessResponseBuilder $response */
        $response = app(Responder::class)->success(...func_get_args());

        if ($data instanceof \Illuminate\Pagination\Paginator) {
          return  $response->meta([
              'pagination' => [
                  'current_page' => $data->currentPage(),
                  'next_page_url' => $data->nextPageUrl(),
                  'prev_page_url' => $data->previousPageUrl(),
                  'hasMore' => $data->hasMorePages(),
              ]
          ]);
        }
        return $response;
    }
}
