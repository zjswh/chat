<?php
/**
 * Created by PhpStorm.
 * User: swh
 * Date: 2018/10/20
 * Time: 23:54
 */

namespace App\WebSocket\Controller;

use EasySwoole\Core\Socket\AbstractInterface\WebSocketController;
use EasySwoole\Core\Swoole\ServerManager;
use EasySwoole\Core\Swoole\Task\TaskManager;

use App\WebSocket\Logic\Im;

class Test extends WebSocketController
{

    /**
     * 访问找不到的action
     * @param  ?string $actionName 找不到的name名
     * @return string
     */
    public function actionNotFound(?string $actionName)
    {
        $this->response()->write("action call {$actionName} not found");
    }

    public function index()
    {
    }

    /**
     * 进入房间
     */
    public function intosRoom()
    {
        // TODO: 业务逻辑自行实现
        $param = $this->request()->getArg('data');
        $userId = (int)$param['userId'];
        $roomId = (int)$param['roomId'];

        $fd = $this->client()->getFd();
        Im::bindUser($userId, $fd);
        Im::joinRoom($roomId, $fd, $userId);
        $this->response()->write("加入{$roomId}房间");
    }

    /**
     * 发送信息到房间
     */
    public function sendToRoom()
    {
        // TODO: 业务逻辑自行实现
        $param = $this->request()->getArg('data');
        $message = $param['message'];
        $roomId = (int)$param['roomId'];

        // 注：单例Redis 可以将获取$list操作放在TaskManager中执行
        // 连接池的Redis 则不可以, 因为默认Task进程没有RedisPool对象。
        $list = Im::selectRoomFd($roomId);
        //异步推送
        TaskManager::async(function ()use($list, $roomId, $message){
            foreach ($list as $fd) {
                ServerManager::getInstance()->getServer()->push((int)$fd, $message);
            }
        });
    }

    /**
     * 发送私聊
     */
    public function sendToUser()
    {
        // TODO: 业务逻辑自行实现
        $param = $this->request()->getArg('data');
        $message = $param['message'];
        $userId = (int)$param['userId'];

        // 注：单例Redis 可以将获取$list操作放在TaskManager中执行
        // 连接池的Redis 则不可以, 因为默认Task进程没有RedisPool对象。
        $fdList = Im::getUserFd($userId);
        // 异步推送
        TaskManager::async(function ()use($fdList, $userId, $message){
            foreach ($fdList as $fd) {
                ServerManager::getInstance()->getServer()->push($fd, $message);
            }
        });
    }
}