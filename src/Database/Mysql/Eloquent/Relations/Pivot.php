<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
namespace Mini\Database\Mysql\Eloquent\Relations;

use Mini\Database\Mysql\Eloquent\Model;
use Mini\Database\Mysql\Eloquent\Relations\Concerns\AsPivot;

class Pivot extends Model
{
    use AsPivot;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];
}
