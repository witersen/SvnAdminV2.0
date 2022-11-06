<?php
/*
 * @Author: witersen
 * @Date: 2022-04-24 23:37:05
 * @LastEditors: witersen
 * @LastEditTime: 2022-08-28 18:07:43
 * @Description: QQ:1801168257
 */

namespace app\controller;

use app\service\Svnrep as ServiceSvnrep;

class Svnrep extends Base
{
    public $param;

    /**
     * 服务层对象
     *
     * @var object
     */
    private $ServiceSvnrep;

    function __construct($parm)
    {
        $this->param = $parm;

        parent::__construct($parm);

        $this->ServiceSvnrep = new ServiceSvnrep($parm);
    }

    /**
     * 新建仓库
     */
    public function CreateRep()
    {
        $result = $this->ServiceSvnrep->CreateRep();
        json2($result);
    }

    /**
     * 获取仓库列表
     */
    public function GetRepList()
    {
        $result = $this->ServiceSvnrep->GetRepList();
        json2($result);
    }

    /**
     * SVN用户获取自己有权限的仓库列表
     */
    public function GetSvnUserRepList()
    {
        $result = $this->ServiceSvnrep->GetSvnUserRepList();
        json2($result);
    }

    /**
     * 管理人员获取SVN用户有权限的仓库路径列表
     */
    public function GetSvnUserRepList2()
    {
        unset($this->param['token']);
        $result = (new ServiceSvnrep($this->param))->GetSvnUserRepList();
        json2($result);
    }

    /**
     * 修改仓库的备注信息
     */
    public function EditRepNote()
    {
        $result = $this->ServiceSvnrep->EditRepNote();
        json2($result);
    }

    /**
     * SVN用户根据目录名称获取该目录下的文件和文件夹列表
     */
    public function GetUserRepCon()
    {
        $result = $this->ServiceSvnrep->GetUserRepCon();
        json2($result);
    }

    /**
     * 管理人员根据目录名称获取该目录下的文件和文件夹列表
     */
    public function GetRepCon()
    {
        $result = $this->ServiceSvnrep->GetRepCon();
        json2($result);
    }

    /**
     * 根据目录名称获取该目录下的目录树
     * 
     * 管理员配置目录授权用
     */
    public function GetRepTree()
    {
        $result = $this->ServiceSvnrep->GetRepTree();
        json2($result);
    }

    /**
     * 根据目录名称获取该目录下的目录树
     * 
     * SVN用户配置目录授权用
     */
    public function GetRepTree2()
    {
        $result = $this->ServiceSvnrep->GetRepTree2();
        json2($result);
    }

    /**
     * 获取某个仓库路径的所有权限列表
     */
    public function GetRepPathAllPri()
    {
        $result = $this->ServiceSvnrep->GetRepPathAllPri();
        json2($result);
    }

    /**
     * 为某仓库路径下增加权限
     *
     * @return array
     */
    public function AddRepPathPri()
    {
        $result = $this->ServiceSvnrep->AddRepPathPri();
        json2($result);
    }

    /**
     * 修改某个仓库路径下的权限
     */
    public function EditRepPathPri()
    {
        $result = $this->ServiceSvnrep->EditRepPathPri();
        json2($result);
    }

    /**
     * 删除某个仓库下的权限
     */
    public function DelRepPathPri()
    {
        $result = $this->ServiceSvnrep->DelRepPathPri();
        json2($result);
    }

    /**
     * 修改仓库名称
     */
    public function EditRepName()
    {
        $result = $this->ServiceSvnrep->EditRepName();
        json2($result);
    }

    /**
     * 删除仓库
     */
    public function DelRep()
    {
        $result = $this->ServiceSvnrep->DelRep();
        json2($result);
    }

    /**
     * 获取仓库的属性内容（key-value的形式）
     */
    public function GetRepDetail()
    {
        $result = $this->ServiceSvnrep->GetRepDetail();
        json2($result);
    }

    /**
     * 重设仓库的UUID
     */
    public function SetUUID()
    {
        $result = $this->ServiceSvnrep->SetUUID();
        json2($result);
    }

    /**
     * 获取备份文件夹下的文件列表
     */
    public function GetBackupList()
    {
        $result = $this->ServiceSvnrep->GetBackupList();
        json2($result);
    }

    /**
     * 立即备份当前仓库
     */
    public function RepDump()
    {
        $result = $this->ServiceSvnrep->RepDump();
        json2($result);
    }

    /**
     * 删除备份文件
     */
    public function DelRepBackup()
    {
        $result = $this->ServiceSvnrep->DelRepBackup();
        json2($result);
    }

    /**
     * 下载备份文件
     */
    public function DownloadRepBackup()
    {
        $this->ServiceSvnrep->DownloadRepBackup();
    }

    /**
     * 获取上传限制
     */
    public function GetUploadLimit()
    {
        $result = $this->ServiceSvnrep->GetUploadLimit();
        json2($result);
    }

    /**
     * 上传文件到备份文件夹
     */
    public function UploadBackup()
    {
        $result = $this->ServiceSvnrep->UploadBackup();
        json2($result);
    }

    /**
     * 从本地备份文件导入仓库
     */
    public function ImportRep()
    {
        $result = $this->ServiceSvnrep->ImportRep();
        json2($result);
    }

    /**
     * 获取仓库的钩子和对应的内容列表
     */
    public function GetRepHooks()
    {
        $result = $this->ServiceSvnrep->GetRepHooks();
        json2($result);
    }

    /**
     * 移除仓库钩子
     */
    public function DelRepHook()
    {
        $result = $this->ServiceSvnrep->DelRepHook();
        json2($result);
    }

    /**
     * 修改仓库的钩子内容（针对单个钩子）
     */
    public function EditRepHook()
    {
        $result = $this->ServiceSvnrep->EditRepHook();
        json2($result);
    }

    /**
     * 获取常用钩子列表
     */
    public function GetRecommendHooks()
    {
        $result = $this->ServiceSvnrep->GetRecommendHooks();
        json2($result);
    }
}
