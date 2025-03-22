<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Dingo\Api\Routing\Helpers;
use Illuminate\Http\Request;

class BaseController extends Controller
{
    use Helpers;
    
    /**
     * Get the requested includes from the request
     *
     * @param Request $request
     * @return array
     */
    protected function getRequestedIncludes(Request $request): array
    {
        if (!$request->has('include')) {
            return [];
        }
        
        return explode(',', $request->input('include'));
    }
    
    /**
     * Success response with optional pagination
     *
     * @param mixed $data
     * @param null $transformer
     * @param string|null $resourceKey
     * @param int $statusCode
     * @return \Dingo\Api\Http\Response
     */
    protected function respondWithData($data, $transformer = null, string $resourceKey = null, int $statusCode = 200)
    {
        if ($transformer) {
            $parameters = ['key' => $resourceKey];
            return $this->response->item($data, $transformer, $parameters)->setStatusCode($statusCode);
        }
        
        return $this->response->array(['data' => $data])->setStatusCode($statusCode);
    }
    
    /**
     * Success response with collection data and optional pagination
     *
     * @param $collection
     * @param $transformer
     * @param string|null $resourceKey
     * @param int $statusCode
     * @return \Dingo\Api\Http\Response
     */
    protected function respondWithCollection($collection, $transformer, string $resourceKey = null, int $statusCode = 200)
    {
        $parameters = ['key' => $resourceKey];
        return $this->response->collection($collection, $transformer, $parameters)->setStatusCode($statusCode);
    }
    
    /**
     * Success response with pagination
     *
     * @param $paginator
     * @param $transformer
     * @param string|null $resourceKey
     * @param int $statusCode
     * @return \Dingo\Api\Http\Response
     */
    protected function respondWithPagination($paginator, $transformer, string $resourceKey = null, int $statusCode = 200)
    {
        $parameters = ['key' => $resourceKey];
        return $this->response->paginator($paginator, $transformer, $parameters)->setStatusCode($statusCode);
    }
    
    /**
     * Error response
     *
     * @param string $message
     * @param int $statusCode
     * @return \Dingo\Api\Http\Response
     */
    protected function respondWithError(string $message, int $statusCode = 400)
    {
        return $this->response->error($message, $statusCode);
    }
} 