<?php
/**
 * Created by PhpStorm.
 * User: swh
 * Date: 2018/10/21
 * Time: 14:27
 */

namespace App\WebSocket\Controller;

use EasySwoole\Core\Socket\AbstractInterface\WebSocketController;
use EasySwoole\Core\Swoole\ServerManager;
use EasySwoole\Core\Swoole\Task\TaskManager;
use App\WebSocket\Logic\CS;


class Chat extends  WebSocketController
{
    public function actionNotFound(?string $actionName)
    {
        $this->response()->write("action call {$actionName} not found");
    }

    public function intoRoom(){
        $param = $this->request()->getArg('data');
        $userId = (int)$param['userId'];
        $roomId = (int)$param['roomId'];
        $nowfd = $this->client()->getFd();
        CS::bindUser($userId,$nowfd);
        CS::joinRoom($roomId,$userId,$nowfd);
        $fdList = CS::selectRoomFd($roomId);
        $message = "{$userId}进入了{$roomId}";
//         异步推送
        TaskManager::async(function ()use($fdList, $userId, $message,$nowfd){
            foreach ($fdList as $fd) {
//                if($nowfd != $fd){
                    ServerManager::getInstance()->getServer()->push((int)$fd, $message);
//                }
            }
        });
        $this->response()->write("{$nowfd}加入{$roomId}房间");
    }

    public function sendMess(){
        $param = $this->request()->getArg('data');
        $userId = (int)$param['userId'];
        $roomId = (int)$param['roomId'];
        $mess = $param['msg'];
        $fdList = CS::selectRoomFd($roomId);
        TaskManager::async(function() use($fdList,$userId,$roomId,$mess){
            $data = [
                'msg_type'=>'public',
                'user_id'=>$userId,
                'room_id'=>$roomId,
                'msg'=>$mess
            ];
            foreach ($fdList as $fd){
                ServerManager::getInstance()->getServer()->push($fd,json_encode($data));
            }
        });
    }
    public function sendMsgToUser()
    {
        $param = $this->request()->getArg('data');
        $userId = (int)$param['userId'];
        $roomId = (int)$param['roomId'];
        $toUser = (int)$param['touser_id'];
        $mess = $param['msg'];
        $targetFd = (int)CS::getFdByUserId($toUser);
        var_dump($targetFd);
        $data = [
            'msg_type' => 'private',
            'user_id' => $userId,
            'tartget_user_id' => $toUser,
            'room_id' => $roomId,
            'msg' => $mess
        ];
        TaskManager::async(function () use ($targetFd, $data) {
            ServerManager::getInstance()->getServer()->push($targetFd,json_encode($data));
        });
    }
}