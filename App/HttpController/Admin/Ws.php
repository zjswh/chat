<?php
/**
 * Created by PhpStorm.
 * User: swh
 * Date: 2018/10/28
 * Time: 11:59
 */

namespace App\HttpController\Admin;
use App\Model\Friend;
use EasySwoole\Core\Http\AbstractInterface\Controller;
use App\WebSocket\Logic\CS;
use EasySwoole\Core\Http\Request;
use EasySwoole\Core\Http\Response;

class Ws extends Controller
{
    private $friendModel = '';
    public function __construct(string $actionName, Request $request, Response $response)
    {
        parent::__construct($actionName, $request, $response);
        $this->friendModel = new Friend();
    }

    public function index(){
//        $friend = new Friend();
//        $now = date('Y-m-d H:i:s');
        $this->friendModel->save(['fromId'=>'2222','toId'=>'3333']);
        $this->response()->write('Hello easySwoole!');
    }
    public function sendFriendRequest(){
        $param = $this->request()->getRequestParam();
        $data['fromId'] = $param['fromId'];
        $data['toId'] = $param['toId'];
        //检查是否已经添加为好友
        $friend = Friend::get(['from_state'=>1,'to_state'=>1,'toId'=>data['toId']]);
        if($friend){
            $this->response()->write('你们已经是好友了!');
        }else{
            Friend::create($data);
            $this->response()->write('请求已发出，等待回复!');
//            var_dump('add');
        }

    }

    public function showRequest(){
     $currentId =  $this->request()->getRequestParam('currentId');
     $list = Friend::where('toId',$currentId)->select();
     $this->response()->write(json_encode($list));
    }

    public function passRequest(){
//        $fromId =  $this->request()->getRequestParam('fromId');
        $id =  $this->request()->getRequestParam('id');
        //todo 检测是否已经通过
        Friend::update(['to_state'=>1],['id'=>$id]);
        $this->response()->write('添加成功！');
    }

    public function userList(){
        $currentId =  $this->request()->getRequestParam('currentId');
//        $list = Friend::where(['to_state'=>1,'from_state'=>1,'toId'=>$currentId])
//                ->whereOr(['to_state'=>1,'from_state'=>1,'fromId'=>$currentId])
//                ->select();

        $map1 = [
            ['to_state','=', 1],
            ['from_state','=',1],
            ['toId','=',$currentId]
        ];
        $map2 = [
            ['to_state','=', 1],
            ['from_state','=',1],
            ['fromId','=',$currentId]
        ];
        $list = Friend::whereOr([$map1,$map2])->select();
//        var_dump($list);
        $this->response()->write($list);
    }

}