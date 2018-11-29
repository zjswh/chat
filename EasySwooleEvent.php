<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2018/1/9
 * Time: 下午1:04
 */

namespace EasySwoole;

use \EasySwoole\Core\AbstractInterface\EventInterface;
use \EasySwoole\Core\Swoole\ServerManager;
use \EasySwoole\Core\Swoole\EventRegister;
use \EasySwoole\Core\Http\Request;
use \EasySwoole\Core\Http\Response;
use \EasySwoole\Core\Swoole\EventHelper;
use \EasySwoole\Core\Swoole\Task\TaskManager;
use \App\WebSocket\Parser as WebSocketParser;
// 引入Di
use \EasySwoole\Core\Component\Di;
// 引入上文Redis连接
use \App\Utility\Redis;
// 引入上文Room文件
//use \App\WebSocket\Logic\Im;
use \App\WebSocket\Logic\CS;
//use \think\Db as TpDb;
use \think\Db;

Class EasySwooleEvent implements EventInterface {

    public static function frameInitialize(): void
    {
        // TODO: Implement frameInitialize() method.
        date_default_timezone_set('Asia/Shanghai');
        // 获得数据库配置
        $dbConf = Config::getInstance()->getConf('database');
        // 全局初始化
        Db::setConfig($dbConf);

    }

    public static function mainServerCreate(ServerManager $server,EventRegister $register): void
    {
        // TODO: Implement mainServerCreate() method.
        // // 注册WebSocket处理
        EventHelper::registerDefaultOnMessage($register, WebSocketParser::class);
//        //注册onClose事件
        $register->add($register::onClose, function (\swoole_server $server, $fd, $reactorId) {
            // 检查是否是ws连接
            if (isset($server->connection_info($fd)['websocket_status']) && 3 ===  $server->connection_info($fd)['websocket_status']) {
                //清除Redis fd的全部关联
                CS::recyclingFd($fd);
            }
        });
        // 注册Redis
        Di::getInstance()->set('REDIS', new Redis(Config::getInstance()->getConf('REDIS')));
    }

    public static function onRequest(Request $request,Response $response): void
    {
        // TODO: Implement onRequest() method.
    }

    public static function afterAction(Request $request,Response $response): void
    {
        // TODO: Implement afterAction() method.
    }
}