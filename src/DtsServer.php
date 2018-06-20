<?php
/**
 * Created by PhpStorm.
 * User: zhoujun
 * Date: 2018/6/20
 * Time: 上午11:02
 */

namespace Larasaas\DistributedTransaction;


interface DtsServer
{
    public function addApplied($app_data=[]);
}