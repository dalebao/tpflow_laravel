<?php

namespace App\Extend\Workflow\flowClass\Command;

use App\Extend\Workflow\Db\InfoDB;
use App\Extend\Workflow\Db\LogDb;
use App\Extend\Workflow\Db\ProcessDb;
use Illuminate\Support\Facades\DB;

/**
 *+------------------
 * 普通提交工作流
 *+------------------
 */
class TaskFlow
{
    /**
     * 任务执行
     *
     * @param  $config 参数信息
     * @param  $uid  用户ID
     */
    public function doTask($config, $uid)
    {
        //任务全局类
//        $wf_title = $config['wf_title'];
        $npid = $config['npid'];//下一步骤流程id
        $run_id = $config['run_id'];//运行中的id
        $run_process = $config['run_process'];//运行中的process
        $check_con = $config['check_con'];
//        $submit_to_save = $config['submit_to_save'];
        if (isset($config['todo'])) {
            $todo = $config['todo'];
        } else {
            $todo = '';
        }
        if ($npid != '') {//判断是否为最后
            //结束流程
            $end = $this->end_process($run_process, $check_con);
            if (!$end) {
                return ['msg' => '结束流程错误！！！', 'code' => '-1'];
            }
            //更新单据信息
            $run_update = $this->up($run_id, $npid);
            //记录下一个流程->消息记录
            $run = $this->Run($config, $uid, $todo);

        } else {
            //结束该流程
            $end = $this->end_flow($run_id);
            $end = $this->end_process($run_process, $check_con);
            $run_log = LogDb::AddrunLog($uid, $run_id, $config, 'ok');
            if (!$end) {
                return ['msg' => '结束流程错误！！！', 'code' => '-1'];
            }
            //更新单据状态
            $bill_update = InfoDB::UpdateBill($config['wf_fid'], $config['wf_type'], 2);
            if (!$bill_update) {
                return ['msg' => '流程步骤操作记录失败，数据库错误！！！', 'code' => '-1'];
            }
            //消息通知发起人
        }
    }

    /**
     *结束工作流
     *
     * @param $run_flow_process 工作流ID
     **/
    public function end_flow($run_id)
    {
        return DB::connection('workflow')->table('run')->where('id', $run_id)->update(['status' => 1, 'endtime' => time()]);
    }

    /**
     *结束工作流
     *
     * @param $run_flow_process 工作流ID
     **/
    public function end_process($run_process, $check_con)
    {
        return DB::connection('workflow')->table('run_process')->where('id', $run_process)->update(['status' => 2, 'remark' => $check_con, 'bl_time' => time()]);
    }

    /**
     *运行记录
     *
     * @param $run_flow_process 工作流ID
     **/
    public function Run($config, $uid, $todo)
    {
        $wf_process = ProcessDb::GetProcessInfo($config['npid']);
        //添加流程步骤日志
        $wf_process_log = InfoDB::addWorkflowProcess($config['flow_id'], $wf_process, $config['run_id'], $uid, $todo);
        if (!$wf_process_log) {
            return ['msg' => '流程步骤操作记录失败，数据库错误！！！', 'code' => '-1'];
        }
        //日志记录
        $run_log = LogDb::AddrunLog($uid, $config['run_id'], $config, 'ok');
        if (!$wf_process_log) {
            return ['msg' => '消息记录失败，数据库错误！！！', 'code' => '-1'];
        }
    }

    /**
     *更新单据信息
     *
     * @param $run_flow_process 工作流ID
     **/
    public function up($run_id, $flow_process)
    {
        return DB::connection('workflow')->table('run')->where('id', $run_id)->update(['run_flow_process' => $flow_process]);
    }
}