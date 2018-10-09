<?php

namespace App\Extend\Workflow\Db;

use Illuminate\Support\Facades\DB;

/**
 *+------------------
 * 工作流日志消息
 *+------------------
 */
class LogDb
{

    /**
     * 工作流审批日志记录
     *
     * @param  $uid 实例id
     * @param  $run_id 运行的工作流id
     * @param  $content 审批意见
     * @param  $from_id 单据id
     * @param  $from_table 单据表
     * @param  $btn 操作按钮 ok 提交 back 回退 sing 会签  Send 发起
     **/
    public static function AddrunLog($uid, $run_id, $config, $btn)
    {
        if (!isset($config['art'])) {
            $config['art'] = '';
        }
        $run_log = array(
            'uid' => $uid,
            'from_id' => $config['wf_fid'],
            'from_table' => $config['wf_type'],
            'run_id' => $run_id,
            'content' => $config['check_con'],
            'art' => $config['art'],
            'btn' => $btn,//从 serialize 改用  json_encode 兼容其它语言
            'dateline' => time()
        );
        $run_log = DB::connection('workflow')->table('run_log')->insertGetId($run_log);
        if (!$run_log) {
            return false;
        }
        return $run_log;
    }


}