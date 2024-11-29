<?php

namespace App\Services;

use Flugg\Responder\Pagination\PaginatorFactory as BasePaginatorFactory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use League\Fractal\Pagination\Cursor;
use League\Fractal\Pagination\PaginatorInterface;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;

class CustomPaginatorFactory extends BasePaginatorFactory
{
    /**
     * Override the make method to include pagination meta data in the response.
     *
     * @param  \Illuminate\Contracts\Pagination\LengthAwarePaginator $paginator
     * @return \League\Fractal\Pagination\PaginatorInterface
     */
    public function make(LengthAwarePaginator $paginator): PaginatorInterface
    {
        $paginator->appends($this->parameters);

        // Generate the pagination metadata
        $paginationMeta = [
            'total' => $paginator->total(),
            'count' => $paginator->count(),
            'per_page' => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
            'total_pages' => $paginator->lastPage(),
        ];

        // Include metadata in the response
        app('responder')->meta(['pagination' => $paginationMeta]);

        return new IlluminatePaginatorAdapter($paginator);
    }

    /**
     * Override the makeCursor method to include cursor meta data in the response.
     *
     * @param  \Flugg\Responder\Pagination\CursorPaginator $paginator
     * @return \League\Fractal\Pagination\Cursor
     */
    public function makeCursor(CursorPaginator|\Flugg\Responder\Pagination\CursorPaginator $paginator): Cursor
    {
        // Generate the cursor metadata
        $cursorMeta = [
            'current_cursor' => $paginator->cursor(),
            'previous_cursor' => $paginator->previous(),
            'next_cursor' => $paginator->next(),
        ];

        // Include metadata in the response
        app('responder')->meta(['cursor' => $cursorMeta]);

        return new Cursor($paginator->cursor(), $paginator->previous(), $paginator->next(), $paginator->get()->count());
    }
}