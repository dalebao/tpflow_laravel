<?php
/**
 *+------------------
 * 流信息处理
 *+------------------
 */

namespace App\Extend\Workflow\Db;

use function Couchbase\defaultDecoder;
use Illuminate\Support\Facades\DB;

class InfoDB
{

    /**
     * 判断业务是否存在，避免已经删除导致错误
     *
     * @param $wf_fid  业务id
     * @param $wf_type 业务表名
     */
    public static function getbill($wf_fid, $wf_type)
    {
        if ($wf_fid == '' || $wf_type == '') {
            return false;
        }
        $info = (array)DB::connection('workflow')->table($wf_type)->find($wf_fid);
        //TODO::业务相关数据库查询
        if ($info) {
            return $info;
        } else {
            return false;
        }
    }

    /**
     * 添加工作流
     *
     * @param $wf_id  流程主ID
     * @param $wf_process 流程信息
     * @param $wf_fid  业务id
     * @param $wf_type 业务表名
     */
    public static function addWorkflowRun($wf_id, $wf_process, $wf_fid, $wf_type, $uid)
    {
        $data = array(
            'pid' => 0,
            'uid' => $uid,
            'flow_id' => $wf_id,
            'from_table' => $wf_type,
            'from_id' => $wf_fid,
            'run_name' => $wf_fid,
            'run_flow_id' => $wf_id,
            'run_flow_process' => $wf_process,
            'dateline' => time(),
        );
        $run_id = DB::connection('workflow')->table('run')->insertGetId($data);
        if (!$run_id) {
            return false;
        } else {
            return $run_id;
        }
    }

    /**
     * 添加运行步骤信息
     *
     * @param $wf_id  流程主ID
     * @param $wf_process 流程信息
     * @param $run_id  运行的id
     * @param $wf_type 业务表名
     */
    public static function addWorkflowProcess($wf_id, $wf_process, $run_id, $uid, $todo = '')
    {
        //非自由
        if ($todo == '') {
            if ($wf_process['auto_person'] == 3) { //办理人员
                $sponsor_ids = $wf_process['range_user_ids'];
                $sponsor_text = $wf_process['range_user_text'];
            }
            if ($wf_process['auto_person'] == 4) { //办理人员
                $sponsor_ids = $wf_process['auto_sponsor_ids'];
                $sponsor_text = $wf_process['auto_sponsor_text'];
            }
            if ($wf_process['auto_person'] == 5) { //办理角色
                $sponsor_text = $wf_process['auto_role_text'];
                $sponsor_ids = $wf_process['auto_sponsor_ids'];
            }
        } else {
            $todo = explode("*%*", $todo);
            $sponsor_text = $todo[1];
            $sponsor_ids = $todo[0];
        }
        $data = array(
            'uid' => $uid,
            'run_id' => $run_id,
            'run_flow' => $wf_id,
            'run_flow_process' => $wf_process['id'],
            'parent_flow' => 0,
            'parent_flow_process' => 0,
            'run_child' => 0,//未处理，第一步不能进入子流程
            'remark' => '',
            'is_sponsor' => 0,
            'status' => 0,
            'sponsor_ids' => $sponsor_ids,//办理人id
            'sponsor_text' => $sponsor_text,//办理人信息
            'auto_person' => $wf_process['auto_person'],//办理类别
            'js_time' => time(),
            'dateline' => time(),
        );
        $process_id = DB::connection('workflow')->table('run_process')->insertGetId($data);
        if (!$process_id) {
            return false;
        }
        return $process_id;
    }

    /**
     * 缓存信息
     *
     * @param $wf_fid  单据编号
     * @param $flow_process 流程信息
     * @param $run_id  运行的id
     * @param $wf 流程信息
     */
    public static function addWorkflowCache($run_id, $wf, $flow_process, $wf_fid)
    {
        $run_cache = array(
            'run_id' => $run_id,
            'form_id' => $wf_fid,
            'flow_id' => $wf['id'],
            'run_form' => '',//从 serialize 改用  json_encode 兼容其它语言
            'run_flow' => json_encode($wf),
            'run_flow_process' => json_encode($flow_process), //这里未缓存 子流程 数据是不完善的， 后期会完善
            'dateline' => time()
        );
        $run_cache = DB::connection('workflow')->table('run_cache')->insertGetId($run_cache);
        if (!$run_cache) {
            return false;
        }
        return $run_cache;
    }

    /**
     * 根据单据ID，单据表 获取流程信息
     *
     * @param $run_id  运行的id
     * @param $wf_type 业务表名
     */
    public static function workflowInfo($wf_fid, $wf_type)
    {
        $workflow = [];
        require(BEASE_URL . '/config/config.php');//
        $count = DB::connection('workflow')->table('run')->where('from_id', '=', $wf_fid)->where('from_table', '=', $wf_type)->where('is_del', '=', 0)->count();
        if ($count > 0) {
            $result = (array)DB::connection('workflow')->table('run')->where('from_id', '=', $wf_fid)->where('from_table', '=', $wf_type)->where('is_del', '=', 0)->where('status', '=', 0)->first();
            $info = (array)DB::connection('workflow')->table('run_process')->where('run_id', '=', $result['id'])->where('run_flow', '=', $result['flow_id'])->where('run_flow_process', '=', $result['run_flow_process'])->where('status', '=', 0)->first();
            if ($result) {
                $workflow ['sing_st'] = 0;
                $workflow ['flow_id'] = $result['flow_id'];
                $workflow ['run_id'] = $result['id'];
                $workflow ['status'] = $info;
                $workflow ['flow_process'] = $info['run_flow_process'];
                $workflow ['run_process'] = $info['id'];
                $workflow ['flow_name'] = FlowDb::GetFlowInfo($result['flow_id']);
                $workflow ['process'] = ProcessDb::GetProcessInfo($info['run_flow_process']);
                $workflow ['nexprocess'] = ProcessDb::GetNexProcessInfo($wf_type, $wf_fid, $info['run_flow_process']);
                $workflow ['preprocess'] = ProcessDb::GetPreProcessInfo($info['id']);
                $workflow ['singuser'] = 1;
                $workflow ['singuser'] = UserDb::GetUser();
                //TODO::获取用户数据
                $workflow ['log'] = ProcessDb::RunLog($wf_fid, $wf_type);
                if ($result['is_sing'] == 1) {
                    $info = (array)DB::connection('workflow')->table('run_process')->where('run_id', '=', $result['id'])->where('run_flow', '=', $result['flow_id'])->where('run_flow_process', '=', $result['run_flow_process'])->first();
                    $workflow ['sing_st'] = 1;
                    $workflow ['flow_process'] = $result['run_flow_process'];
                    $workflow ['nexprocess'] = ProcessDb::GetNexProcessInfo($wf_type, $wf_fid, $result['run_flow_process']);
                    $workflow ['run_process'] = $info['id'];
                }
            } else {
                $workflow ['bill_check'] = '';
                $workflow ['bill_time'] = '';
            }
        } else {
            $workflow ['bill_check'] = '';
            $workflow ['bill_time'] = '';
        }
        return $workflow;
    }

    /**
     * 根据单据ID，单据表 获取流程信息
     *
     * @param $run_id  运行的id
     * @param $wf_type 业务表名
     */
    public static function workrunInfo($run_id)
    {
        $result = DB::connection('workflow')->table('run')->find($run_id);
        return $result;
    }

    /**
     * 更新单据信息
     *
     * @param $wf_fid  运行的id
     * @param $wf_type 业务表名
     * @param $status  单据状态
     */
    public static function UpdateBill($wf_fid, $wf_type, $status = 1)
    {
        $result = DB::connection('workflow')->table($wf_type)->where('id', '=', $wf_fid)->update(['status' => $status, 'uptime' => time()]);
        //TODO::业务相关数据
        if (!$result) {
            return false;
        }
        return $result;

    }

    /**
     * 工作流列表
     *
     */
    public static function worklist()
    {
        $result = DB::connection('workflow')->table('run')->where('status', '=', 0)->select()->get()->toArray();
        foreach ($result as $k => &$v) {
            $v = (array)$v;
            $result[$k]['flow_name'] = DB::connection('workflow')->table('flow')->where('id', '=', $v['flow_id'])->value('flow_name');

            $process = (array)DB::connection('workflow')->table('flow_process')->where('id', '=', $v['run_flow_process'])->first();
            if ($process['auto_person'] == 4) {
                $result[$k]['user'] = $process['auto_sponsor_text'];
            } else {
                $result[$k]['user'] = $process['auto_role_text'];
            }
        }
        unset($v);
        return $result;
    }
}