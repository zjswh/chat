<?php

namespace App\HttpController\Admin;

use EasySwoole\Core\Http\AbstractInterface\Controller;
use App\ViewController;

class User extends ViewController
{
    function index()
    {
        $this->response()->write('Hello easySwoole!');
    }

    protected function actionNotFound($action):void{
        $this->response()->withHeader('Content-type', 'text/html;charset=utf-8');
        $this->response()->write("不存在{$action}该控制器！");
    }

    protected function onRequest($action): ?bool
    {
        $data = $this->request()->getRequestParam();
        $this->response()->withHeader('Content-type', 'text/html;charset=utf-8');
//        $this->response()->write(json_encode($data));
        return true;
//        if(auth_fail){
//            $this->response()->write('auth fail');
//            return false;
//        }else{
//            return true or null;
//        }
    }

    function test(){
        $this->response()->write('Hello easySwoole test!');
    }

    function param(){
        $data = $this->request()->getRequestParam('id','type');
        $this->response()->write(json_encode($data));
    }

    function cs(){
        $this->fetch('cs');
    }

}