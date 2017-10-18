<?php
/**
 * ChartController.php.
 * 文件描述
 * Created on: 2017/10/17 下午3:49
 * Create by jwf
 */

namespace App\Http\Controllers;
use GatewayWorker\Lib\Gateway;
use Illuminate\Http\Request;

class ChatController extends Controller{
    public function __construct()
    {
        Gateway::$registerAddress = '127.0.0.1:1238';
    }

    public function index()
    {
        return view('chat.index');
    }

    //更新绑定uid和client_id
    public function bind(Request $request)
    {
        $room_id = $request->input('room_id',1);
        $client_id = $request->input('client_id','');
        // 判断是否有房间号
        if(!isset($room_id))
        {
            throw new \Exception("\$message_data['room_id'] not set. client_ip:{$_SERVER['REMOTE_ADDR']} ");
        }

        if(empty(session('uid')) ){
            session(['uid'=>time()]);
            session(['client_id'=>$client_id]);
            session(['client_name'=>$this->generate_username(6)]);
            session(['room_id'=>$room_id]);
        }
        $client_name = session('client_name');


        Gateway::joinGroup($client_id, $room_id);

        // 转播给当前房间的所有客户端，xx进入聊天室 message {type:login, client_id:xx, name:xx}
        $new_message = array('type'=>'login', 'client_id'=>$client_id, 'client_name'=>htmlspecialchars($client_name),'time'=>date('Y-m-d H:i:s'));
        Gateway::sendToGroup($room_id, json_encode($new_message));


        return response()->json([
            'code'=>0,
            'msg'=>'',
            'data'=>[
                'username'=>$client_name,
                'uid'=>session('uid')
            ]
        ]);
    }

    //说话
    public function say(Request $request)
    {

        $to_client_id = $request->input('to_client_id');
        $to_client_name = $request->input('to_client_name');
        $content = $request->input('content');
        $room_id = session('room_id');
        // 非法请求
        if(!isset($room_id))
        {
            throw new \Exception("\$_SESSION['room_id'] not set. client_ip:{$_SERVER['REMOTE_ADDR']}");
        }

        $client_name = session('client_name');
        $client_id = session('client_id');
        // 私聊
        if($to_client_id != 'all')
        {
            $new_message = array(
                'type'=>'say',
                'from_client_id'=>$client_id,
                'from_client_name' =>$client_name,
                'to_client_id'=>$to_client_id,
                'content'=>"<b>对你说: </b>".nl2br(htmlspecialchars($content)),
                'time'=>date('Y-m-d H:i:s'),
            );
            Gateway::sendToClient($to_client_id, json_encode($new_message));
            $new_message['content'] = "<b>你对".htmlspecialchars($to_client_name)."说: </b>".nl2br(htmlspecialchars($content));
             Gateway::sendToClient($client_id,json_encode($new_message));
        }

        $new_message = array(
            'type'=>'say',
            'from_client_id'=>$client_id,
            'from_client_name' =>$client_name,
            'to_client_id'=>'all',
            'content'=>nl2br(htmlspecialchars($content)),
            'time'=>date('Y-m-d H:i:s'),
        );

        Gateway::sendToGroup($room_id ,json_encode($new_message));

        return response()->json([
            'code'=>0,
            'msg'=>'',
            'data'=>$new_message
        ]);
    }

    private function generate_username( $length = 6 ) {
        // 密码字符集，可任意添加你需要的字符
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $username = '';
        for ( $i = 0; $i < $length; $i++ )
        {
            // 这里提供两种字符获取方式
            // 第一种是使用substr 截取$chars中的任意一位字符；
            // 第二种是取字符数组$chars 的任意元素
            // $password .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
            $username .= $chars[ mt_rand(0, strlen($chars) - 1) ];
        }
        return $username;
    }
    
}