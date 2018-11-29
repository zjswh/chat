<?php
/**
 * Created by PhpStorm.
 * User: swh
 * Date: 2018/10/21
 * Time: 10:21
 */

namespace App\WebSocket\Logic;

use EasySwoole\Core\Component\Di;

class CS
{
    private static $userMapFd = 'userMapFd';
    private static $fdToUserList= 'fdToUserList';

    protected static function getRedis()
    {
        return Di::getInstance()->get('REDIS')->handler();
    }

    protected static function setUserFdList($userId,array $fdList){
        self::getRedis()->hSet(self::$userMapFd,$userId,json_encode($fdList));
    }

    protected static function findFdListByUserId($userId){
        return self::getRedis()->hGet(self::$userMapFd,$userId);
    }

    /**
     * userId 映射  Fd
     * @param $userId
     * @param $fd
     */
    protected static function setUserIdMapFd($userId,$fd){
        $fdList = json_decode(self::findFdListByUserId($userId),true);
        if(is_null($fdList)){
            $fdList = [];
        }
        array_push($fdList,$fd);
        self::setUserFdList($userId,$fdList);
    }

    /**
     * fd 映射 userId
     * @param $userId
     * @param $fd
     */
    protected  static function setFdUserId($userId,$fd){
        self::getRedis()->hSet(self::$fdToUserList,$fd,$userId);
    }

    protected static function findUserIdByFd($fd){
        return self::getRedis()->hGet(self::$fdToUserList,$fd);
    }

    protected static function deleteFdInMap($fd){
        $userId = self::findUserIdByFd($fd);
        if(false == $userId){
            return '';
        }
        $fdList = json_decode(self::findFdListByUserId($userId),true);
        foreach ($fdList as $key=>$val){
            if($val == $fd){
                unset($fdList[$key]);
            }
        }
        if(empty($fdList)){
            self::getRedis()->hDel(self::$userMapFd,$userId);
        }else{
            self::setUserFdList($userId,$fdList);
        }
    }


    protected static function roomPush($roomId,$userId,$fd){
        self::getRedis()->hSet("roomId:{$roomId}",$fd,$userId);
    }

    protected static function leaveRoom($roomId,$fd){
        self::getRedis()->hDel("roomId:{$roomId}",$fd);
    }

    /**
     * 获取房间所有的fd
     * @param $roomId
     * @return mixed
     */
    protected static function getAllFdByRoomId($roomId){
        return self::getRedis()->hkeys("roomId:{$roomId}");
    }

    /**
     * 获取房间所有的用户
     * @param $roomId
     * @return mixed
     */
    protected static function getAllUserByRoomId($roomId){
        return self::getRedis()->kvals("roomId:{$roomId}");
    }

    protected static function  deleteRoomFd($roomId,$fd){
        self::getRedis()->hDel("roomId:{$roomId}",$fd);
    }

    /**
     * fd 映射 room
     * @param $fd
     * @param $roomId
     */
    protected static function setFdToRoom($fd,$roomId){
        self::getRedis()->hSet('fdToMapRoom',$fd,$roomId);
    }

    /**
     * 删除fd的房间映射
     * @param $fd
     */
    protected static function deleteFdToRoom($fd){
        self::getRedis()->hDel('fdToMapRoom',$fd);
    }

    protected static function findRoomByFd($fd){
        return self::getRedis()->hGet('fdToMapRoom',$fd);
    }

//    protected static function getFdByRoomUser($roomId,$userId){
//        return
//    }

    public static function bindUser($userId,$fd){
        self::setUserIdMapFd($userId,$fd);
        self::setFdUserId($userId,$fd);
    }

    public static function joinRoom($roomId,$userId,$fd){
        self::roomPush($roomId,$userId,$fd);
        self::setFdToRoom($fd,$roomId);
    }

    public static function getUserId($fd){
        return self::findUserIdByFd($fd);
    }

    public static function  getFdByUserId($userId){
        return self::findFdListByUserId($userId);
    }

    public static function getRoomId($fd){
        return self::findRoomByFd($fd);
    }

    public static function selectRoomFd($roomId){
        return self::getAllFdByRoomId($roomId);
    }

    public static function selectRoomUserId($roomId){
        return self::getAllUserByRoomId($roomId);
    }

    public static function exitRoom($roomId,$fd){
        self::deleteRoomFd($roomId,$fd);
        self::deleteFdToRoom($fd);
    }

    public static function recyclingFd($fd){
        //接触user和fd的关系
        self::deleteFdInMap($fd);
        //解除fd和user的关系
        self::deleteFdUser($fd);
        //解除fd和房间的关系
        self::exitRoom(self::findRoomByFd($fd),$fd);
    }

    protected static function deleteFdUser($fd){
        self::getRedis()->hDel(self::$fdToUserList,$fd);
    }



//    protected static function deleteUserIdInMap($userId){
//        $fdList = self::findUserIdFd($userId);
//        if(is_null($fdList)){
//            return '';
//        }
//        foreach ($fdList as $key=>$val){
//            unset($fdList[$key]);
//        }
//        self::setUserFdList($userId,$fdList);
//    }





}