<?php
namespace mwtech\thinkphp_app_session\controller;

use cmf\controller\AdminBaseController;
use mwtech\thinkphp_app_session\model\ProjectUserBaseInfoModel;
use think\db\Query;
use think\facade\Config;

class AppAdminIndexController extends AdminBaseController
{
    protected $appSessionHandler;  // 应用级的session句柄

    protected function initialize()
    {
        parent::initialize();
        $this->appSessionHandler = app('http')->getName();  // 使用ThinkPHP的应用名称
        $projectConfigPath = app_path() . DIRECTORY_SEPARATOR . $this->appSessionHandler . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'project.php';
        Config::load($projectConfigPath, 'project');
    }

    public function index()
    {
        $list = ProjectUserBaseInfoModel::where(function (Query $query) {
            $query->where('project_name', $this->appSessionHandler);
            $data = $this->request->param();
            if (!empty($data['uid'])) {
                $query->where('id', intval($data['uid']));
            }

            if (!empty($data['keyword'])) {
                $keyword = $data['keyword'];
                $query->where('openid|nickname|share_code', 'like', "%$keyword%");
            }

        })->order("create_time DESC")
            ->paginate(10);
        // 获取分页显示
        $page = $list->render();
        $this->assign('list', $list);
        $this->assign('page', $page);
        $this->assign('project_name', Config::get('project.project_name', ''));
        // 渲染模板输出
        return $this->fetch();
    }

    public function ban()
    {
        $id = $this->request->param('id', 0, 'intval');
        if ($id) {
            $result = ProjectUserBaseInfoModel::where(["id" => $id])->update(['status' => 0]);
            if ($result) {
                $this->success("用户拉黑成功！", "adminIndex/index");
            } else {
                $this->error('用户拉黑失败,用户不存在！');
            }
        } else {
            $this->error('数据传入失败！');
        }
    }

    public function cancelBan()
    {
        $id = $this->request->param('id', 0, 'intval');
        if ($id) {
            $result = ProjectUserBaseInfoModel::where(["id" => $id])->update(['status' => 1]);
            if ($result) {
                $this->success("用户启用成功！", "adminIndex/index");
            } else {
                $this->error('用户启用失败,用户不存在！');
            }
        } else {
            $this->error('数据传入失败！');
        }
    }
}