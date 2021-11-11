<?php

namespace APP\Abase\Repository;

use Prettus\Repository\Eloquent\BaseRepository as PrettusBaseRepository;

/**
 * Class BaseRepository
 * @package Prettus\Repository\Eloquent
 * @author Anderson Andrade <zhengyiunity@gmail.com>
 */
abstract class BaseRepository extends PrettusBaseRepository implements RepositoryInterface
{

    /**
     * Specify Model class name
     *
     * @return string
     */
    abstract public function model();


    /*
    |--------------------------------------------------------------------------
    | 增：Create - Add
    |--------------------------------------------------------------------------
    */


    /*
    |--------------------------------------------------------------------------
    | 删：Delete
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | 改：Update - Wave
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | 查：Find  -  Get
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | 管：Manage - Can
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | 统：Count
    |--------------------------------------------------------------------------
    */


    /*
    |--------------------------------------------------------------------------
    | 私：Private
    |--------------------------------------------------------------------------
    */


    /*
    |--------------------------------------------------------------------------
    | 外：引用外部组件
    |--------------------------------------------------------------------------
    */
}