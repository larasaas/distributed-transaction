<?php
/**
 * Created by PhpStorm.
 * User: zhoujun
 * Date: 2018/6/20
 * Time: 上午10:57
 */

namespace Larasaas\DistributedTransaction;


interface DTS
{
    public function save_message($trans_data);

    public function send_message($props);
}
