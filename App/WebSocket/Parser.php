<?php
/**
 * Created by PhpStorm.
 * User: swh
 * Date: 2018/10/20
 * Time: 23:51
 */

namespace App\WebSocket;

use EasySwoole\Core\Socket\AbstractInterface\ParserInterface;
use EasySwoole\Core\Socket\Common\CommandBean;

class Parser implements ParserInterface
{
    public static function decode($raw, $client)
    {
        // TODO: Implement decode() method.
        $CommandBean = new CommandBean();
        //这里的$raw是请求服务器的信息，你可以自行设计，这里使用了JSON字符串的形式。
        $commandLine = json_decode($raw, true);
        //这里会获取JSON数据中class键对应的值，并且设置一些默认值
        //当用户传递class键的时候，会去App/WebSocket命名空间下寻找类
        $control = isset($commandLine['class']) ? 'App\\WebSocket\\Controller\\'. ucfirst($commandLine['class']) : '';
        $action = $commandLine['action'] ?? 'none';
        $data = $commandLine['data'] ?? null;
        //先检查这个类是否存在，如果不存在则使用Index默认类
        $CommandBean->setControllerClass(class_exists($control) ? $control : App\Websocket\Controller\Index::class);
        //检查传递的action键是否存在，如果不存在则访问默认方法
        $CommandBean->setAction(class_exists($control) ? $action : 'controllerNotFound');
        $CommandBean->setArg('data', $data);
        return $CommandBean;

    }

    public static function encode(string $raw, $client): ?string
    {
        // TODO: Implement encode() method.
        /*
         * 注意，return ''与return null不一样，空字符串一样会回复给客户端，比如在服务端主动心跳测试的场景
         */
        if(strlen($raw) == 0){
            return null;
        }
        return $raw;
    }
}