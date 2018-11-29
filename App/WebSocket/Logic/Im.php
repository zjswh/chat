<?php
/**
 * Created by PhpStorm.
 * User: swh
 * Date: 2018/10/21
 * Time: 0:05
 */

namespace App\WebSocket\Logic;

use EasySwoole\Core\Component\Di;

class Im
{
    /**
     * 获取Redis连接实例
     * @return object Redis
     */
    protected static function getRedis()
    {
        return Di::getInstance()->get('REDIS')->handler();
    }

    /**
     * 设置User => Fd 映射
     * @param int $userId userId
     * @param int $fd     fd
     * @return void
     */
    protected static function setUserFdMap(int $userId, int $fd)
    {
        $fdList = self::findFdListToUserId($userId);
        // 检查此user 是否已经存在fd
        if (is_null($fdList)) {
            $fdList = [];
        }
        array_push($fdList, $fd);
        self::setUserFdList($userId, $fdList);
    }

    /**
     * 设置User Fd list
     * @param int   $userId userId
     * @param array $fdList fd List
     */
    protected static function setUserFdList(int $userId, array $fdList)
    {
        self::getRedis()->hSet('userIdFdMap', $userId, json_encode($fdList));
    }

    /**
     * 通过userId 查询 fd list
     * @param  int    $userId userId
     * @return array|null    此userId 的fdList
     */
    protected static function findFdListToUserId(int $userId)
    {
        return json_decode(self::getRedis()->hGet('userIdFdMap', $userId), true);
    }

    /**
     * 通过Fd 删除UserId => Fd Map
     * @param  int    $fd fd
     * @return void
     */
    protected static function deleteUserIdFdMapByFd(int $fd)
    {
        $userId = self::findUserIdByFd($fd);
        if (false === $userId) {
            return;
        }
        $fdList = self::findFdListToUserId($userId);
        foreach ($fdList as $number => $valFd) {
            if ($valFd == $fd) {
                unset($fdList[$number]);
            }
        }
        self::setUserFdList($userId, $fdList);
    }

    /**
     * 设置Fd => userId 映射
     * @param int $userId userId
     * @param int $fd     fd
     * @return void
     */
    protected static function setFdUserMap(int $userId, int $fd)
    {
        self::getRedis()->hSet('fdUserIdMap', $fd, $userId);
    }

    /**
     * 通过Fd 删除 Fd => UserId Map
     * @param  int    $fd fd
     * @return void
     */
    protected static function deleteFdUserIdMapByFd(int $fd)
    {
        self::getRedis()->hDel('fdUserIdMap', $fd);
    }

    /**
     * 通过fd 查询 userId
     * @param  int    $fd fd
     * @return int     userId
     */
    protected static function findUserIdByFd(int $fd)
    {
        return (int)self::getRedis()->hGet('fdUserIdMap', $fd);
    }

    /**
     * 将fd 推入 room list
     * @param int $roomId roomId
     * @param int $fd     fd
     * @param int $userId userId
     */
    protected static function roomPush(int $roomId, int $fd, int $userId)
    {
        self::getRedis()->hSet("room:{$roomId}", $fd, $userId);
    }

    /**
     * 获取Room 中全部 fd list
     * @param  int $roomId roomId
     * @return array|null         fd list
     */
    protected static function getRoomFdList(int $roomId)
    {
        return self::getRedis()->hKeys("room:{$roomId}");
    }

    /**
     * 获取Room 中的全 userId list
     * @param  int    $roomId roomId
     * @return array|null         userId list
     */
    protected static function getRoomUserIdList(int $roomId)
    {
        return self::getRedis()->hVals("room:{$roomId}");
    }

    /**
     * 删除Room中的Fd
     * @param  int    $fd fd
     * @return void
     */
    protected static function deleteRoomFd(int $roomId, int $fd)
    {
        self::getRedis()->hDel("room:{$roomId}", $fd);
    }

    /**
     * 设置 Fd => RoomId 映射
     * @param int $fd     fd
     * @param int $userId userId
     */
    protected static function setFdRoomIdMap(int $fd, int $roomId)
    {
        self::getRedis()->hSet('roomIdFdMap', $fd, $roomId);
    }

    /**
     * 删除fd 在 RoomId => fd 映射
     * @param  int    $fd fd
     * @return void
     */
    protected static function deleteRoomIdMapByFd(int $fd)
    {
        self::getRedis()->hDel('roomIdFdMap', $fd);
    }

    /**
     * 通过Fd 查询 RoomId
     * @param  int    $fd fd
     * @return int     RoomdId
     */
    protected static function findRoomIdToFd(int $fd)
    {
        return (int)self::getRedis()->hGet('roomIdFdMap', $fd);
    }

    /**
     * 绑定User和fd的关系
     * @param  int    $userId userId
     * @param  int    $fd     fd
     * @return void
     */
    public static function bindUser(int $userId, int $fd)
    {
        self::setFdUserMap($userId, $fd);
        self::setUserFdMap($userId, $fd);
    }

    /**
     * 进入房间
     * @param  int    $roomId roomId
     * @param  int    $fd     fd
     * @return void
     */
    public static function joinRoom(int $roomId, int $fd, int $userId)
    {
        self::roomPush($roomId, $fd, $userId);
        self::setFdRoomIdMap($fd, $roomId);
    }

    /**
     * 获取UserId
     * @param  int    $fd fd
     * @return int  userId
     */
    public static function getUserId(int $fd)
    {
        return self::findUserIdByFd($fd);
    }

    /**
     * 获取User的Fd
     * @param  int    $userId userId
     * @return array         fdList
     */
    public static function getUserFd(int $userId)
    {
        return self::findFdListToUserId($userId);
    }

    /**
     * 获取RoomId
     * @param  int    $fd fd
     * @return int     roomId
     */
    public static function getRoomId(int $fd)
    {
        return self::findRoomIdToFd($fd);
    }

    /**
     * 查询房间内的全部fd
     * @param  int    $roomId roomId
     * @return array|null         fd列表
     */
    public static function selectRoomFd(int $roomId)
    {
        return self::getRoomFdList($roomId);
    }

    /**
     * 查询房间内的全部userId
     * @param  int    $roomId roomId
     * @return array|null $
     */
    public static function selectRoomUserId(int $roomId)
    {
        return self::getRoomUserIdList($roomId);
    }

    /**
     * 退出房间
     * @param  int    $roomId roomId
     * @param  int    $fd      fd
     * @return void
     */
    public static function exitRoom(int $roomId, int $fd)
    {
        self::deleteRoomIdMapByFd($fd);
        self::deleteRoomFd($roomId, $fd);
    }

    /**
     * 回收fd
     * 解除fd的全部关联关系
     * @param  int    $fd fd
     * @return void
     */
    public static function recyclingFd(int $fd)
    {
        // 解除UserId => Fd 关系
        self::deleteUserIdFdMapByFd($fd);
        // 解除Fd => UserId 关系
        self::deleteFdUserIdMapByFd($fd);
        // 解除RoomId => Fd 关系
        self::exitRoom(self::getRoomId($fd), $fd);
    }
}