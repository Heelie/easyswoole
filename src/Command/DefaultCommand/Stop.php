<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2019-01-24
 * Time: 23:57
 */

namespace EasySwoole\EasySwoole\Command\DefaultCommand;


use EasySwoole\Command\AbstractInterface\CallerInterface;
use EasySwoole\Command\AbstractInterface\ResultInterface;
use EasySwoole\Command\Result;
use EasySwoole\EasySwoole\Command\AbstractCommand;
use EasySwoole\EasySwoole\Config;

class Stop extends AbstractCommand
{
    protected $helps = [
        'stop',
        'stop [produce]',
        'stop [force]',
        'stop [produce] [force]'
    ];

    public function commandName(): string
    {
        return 'stop';
    }

    public function exec(CallerInterface $caller): ResultInterface
    {
        $pidFile = Config::getInstance()->getConf("MAIN_SERVER.SETTING.pid_file");
        $msg = '';
        if (file_exists($pidFile)) {
            $pid = intval(file_get_contents($pidFile));
            if (!\Swoole\Process::kill($pid, 0)) {
                $msg = "pid :{$pid} not exist ";
                unlink($pidFile);
            } else {
                if ($caller->getParams('force',false)) {
                    \Swoole\Process::kill($pid, SIGKILL);
                } else {
                    \Swoole\Process::kill($pid);
                }
                //等待5秒
                $time = time();
                while (true) {
                    usleep(1000);
                    if (!\Swoole\Process::kill($pid, 0)) {
                        if (is_file($pidFile)) {
                            unlink($pidFile);
                        }
                        $msg = "server stop for pid {$pid} at " . date("Y-m-d H:i:s");
                        break;
                    } else {
                        if (time() - $time > 15) {
                            $msg = "stop server fail for pid:{$pid} , try [php easyswoole stop force] again";
                            break;
                        }
                    }
                }
            }
        } else {
            $msg = "pid file does not exist, please check whether to run in the daemon mode!";
        }
        $result = new Result();
        $result->setMsg($msg);
        return $result;
    }
}
