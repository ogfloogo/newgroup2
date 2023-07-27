<?php

namespace app\admin\command;
use app\api\model\Financeorder;
use think\Config;
use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;
use think\Log;
use think\Validate;
use app\common\library\Email;




class Sendemail extends Command
{
    protected $model = null;

    protected function configure()
    {
        $this->setName('Sendemail')
            ->setDescription('邮件发送');
    }

    protected function execute(Input $input, Output $output)
    {
        set_time_limit(0);
        $this->sendemail();
    }

    /**
     * 邮件发送
     */
    protected function sendemail()
    {
        $filepath = ROOT_PATH."public/email.html";
        $email_content = file_get_contents($filepath);
        dump($email_content);
        $email_title = "";
        $row = [
            'mail_type' => 1,
            'mail_smtp_host' => "smtpdm-ap-southeast-1.aliyun.com",
            'mail_smtp_port' => 465,
            'mail_smtp_user' => "amazons@amazons.email",
            'mail_smtp_pass' => "lqLQ123456",
            'mail_verify_type' => 2,
            'mail_from' => "amazons@amazons.email",
            'email_content' => $email_content,
            'email_title' => $email_title,
        ];
        // $list = db('email')->where('email','not null')->field('id,email')->order('id asc')->limit(50000)->select();
        $list = db('email')->where('email','ogfloogo@gmail.com ')->field('id,email')->order('id asc')->limit(1)->select();
        foreach($list as $key=>$value){
            $receiver = $value['email'];
            if ($receiver) {
                if (!Validate::is($receiver, "email")) {
                    //$this->error(__('Please input correct email'));
                    continue;
                }
                \think\Config::set('site', array_merge(\think\Config::get('site'), $row));
                $email = new Email;
                $result = $email
                    ->to($receiver)
                    ->subject($email_content)
                    ->message(
                        '<div style="min-height:550px; padding: 100px 55px 200px;">' .  $email_content . '</div>')
                    ->send();
                if ($result) {
                    echo "邮箱：".$value['email'].",发送成功";
                    Log::mylog('发送成功',$value['email'],'sendemail_succeed');
                    echo "\n";
                } else {
                    Log::mylog('发送失败邮箱',$value['email'],'sendemail_error');
                    Log::mylog('发送失败原因',$email->getError(),'sendemail_error');
                    echo "邮箱：".$value['email'].",发送失败";
                    echo "\n";
                    continue;
                }
            } else {
                //$this->error(__('Invalid parameters'));
                continue;
            }
        }
        Log::mylog('结束',"over",'sendemail');
    }

}
