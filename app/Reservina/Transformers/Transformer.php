<?php
namespace App\Reservina\Transformers;


use Illuminate\Pagination\LengthAwarePaginator;

abstract class Transformer
{
    public function transformCollection(array $items)
    {
        return array_map([$this, 'transform'], $items);
    }

    public function transformPaginationCollection(LengthAwarePaginator $paginationObj)
    {
        return array_map([$this, 'transform'], $paginationObj->all());
    }


    public abstract function transform($item);
}