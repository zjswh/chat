<?php
/**
 * Created by PhpStorm.
 * User: swh
 * Date: 2018/10/28
 * Time: 16:27
 */

namespace App\Model;
use think\Model;

class Friend extends Model
{
    protected $name = 'friend';
    protected $updateTime = 'update_time';
    protected $createTime = 'create_time';
    protected $autoWriteTimestamp = 'datetime';
}