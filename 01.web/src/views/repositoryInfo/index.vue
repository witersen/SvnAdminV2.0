<template>
  <div>
    <Card :bordered="false" :dis-hover="true">
      <!-- SVNserve服务非正常状态提示 -->
      <Alert
        v-if="formStatusSubversion.status == false"
        type="error"
        show-icon
        >{{ formStatusSubversion.info }}</Alert
      >
      <Row style="margin-bottom: 15px">
        <Col
          type="flex"
          justify="space-between"
          :xs="21"
          :sm="20"
          :md="19"
          :lg="18"
        >
          <Button
            icon="md-add"
            type="primary"
            ghost
            @click="ModalCreateRep"
            v-if="user_role_id == 1"
            >新建SVN仓库</Button
          >
        </Col>
        <Col :xs="3" :sm="4" :md="5" :lg="6">
          <Input
            search
            enter-button
            placeholder="通过SVN仓库名、备注搜索..."
            @on-search="GetRepList"
            v-model="searchKeywordRep"
            v-if="user_role_id == 1"
          />
          <Input
            search
            enter-button
            placeholder="通过SVN仓库名搜索..."
            @on-search="GetSvnUserRepList"
            v-model="searchKeywordRep"
            v-if="user_role_id == 2"
          />
        </Col>
      </Row>
      <!-- 管理人员仓库列表 -->
      <Table
        v-if="user_role_id == 1"
        @on-sort-change="SortChangeRep"
        border
        :loading="loadingRep"
        :columns="tableColumnRep"
        :data="tableDataRep"
        size="small"
      >
        <template slot-scope="{ row, index }" slot="rep_note">
          <Input
            :border="false"
            v-model="tableDataRep[index].rep_note"
            @on-blur="EditRepNote(index, row.rep_name)"
          />
        </template>
        <template slot-scope="{ row }" slot="repScan">
          <Button type="info" size="small" @click="ModalViewRep(row.rep_name)"
            >浏览</Button
          >
        </template>
        <template slot-scope="{ row }" slot="repBackup">
          <Button type="info" size="small" @click="ModalRepDump(row.rep_name)"
            >管理</Button
          >
        </template>
        <template slot-scope="{ row }" slot="repPri">
          <Button type="info" size="small" @click="ModalRepPri(row.rep_name)"
            >配置</Button
          >
        </template>
        <template slot-scope="{ row }" slot="repHooks">
          <Button type="info" size="small" @click="ModalRepHooks(row.rep_name)"
            >编辑</Button
          >
        </template>
        <template slot-scope="{ row }" slot="action">
          <Button
            type="success"
            size="small"
            @click="ModalRepAdvance(row.rep_name)"
            >高级</Button
          >
          <Button
            type="warning"
            size="small"
            @click="ModalEditRepName(row.rep_name)"
            >修改</Button
          >
          <Button type="error" size="small" @click="DelRep(row.rep_name)"
            >删除</Button
          >
        </template>
      </Table>
      <!-- 用户仓库列表 -->
      <Table
        v-if="user_role_id == 2"
        @on-sort-change="SortChangeUserRep"
        border
        :loading="loadingUserRep"
        :columns="tableColumnUserRep"
        :data="tableDataUserRep"
        size="small"
      >
        <template slot-scope="{ row }" slot="action">
          <Button
            type="info"
            size="small"
            @click="ModalViewUserRep(row.rep_name, row.pri_path)"
            >浏览</Button
          >
        </template>
      </Table>
      <!-- 管理人员SVN仓库分页 -->
      <Card :bordered="false" :dis-hover="true" v-if="user_role_id == 1">
        <Page
          v-if="totalRep != 0"
          :total="totalRep"
          :current="pageCurrentRep"
          :page-size="pageSizeRep"
          @on-page-size-change="PageSizeChangeRep"
          @on-change="PageChangeRep"
          size="small"
          show-sizer
        />
      </Card>
      <!-- 用户SVN仓库分页 -->
      <Card :bordered="false" :dis-hover="true" v-if="user_role_id == 2">
        <Page
          v-if="totalUserRep != 0"
          :total="totalUserRep"
          :current="pageCurrentUserRep"
          :page-size="pageSizeUserRep"
          @on-page-size-change="PageSizeChangeUserRep"
          @on-change="PageChangeUserRep"
          size="small"
          show-sizer
        />
      </Card>
    </Card>
    <!-- 对话框-新建SVN仓库 -->
    <Modal v-model="modalCreateRep" title="新建SVN仓库" @on-ok="CreateRep">
      <Form :model="formRepAdd" :label-width="80">
        <FormItem label="仓库名称">
          <Input v-model="formRepAdd.rep_name"></Input>
        </FormItem>
        <FormItem>
          <Alert type="warning" show-icon
            >仓库名称只能包含中文、字母、数字、破折号、下划线、点，不能以点开头或结尾</Alert
          >
        </FormItem>
        <FormItem label="备注信息">
          <Input v-model="formRepAdd.rep_note"></Input>
        </FormItem>
        <FormItem label="仓库类型">
          <RadioGroup vertical v-model="formRepAdd.rep_type">
            <Radio label="1">
              <Icon type="social-apple"></Icon>
              <span>空仓库</span>
            </Radio>
            <Radio label="2">
              <Icon type="social-android"></Icon>
              <span>指定结构的仓库(包含 "trunk" "branches" "tags" 文件夹)</span>
            </Radio>
          </RadioGroup>
        </FormItem>
      </Form>
    </Modal>
    <!-- 对话框-仓库浏览 -->
    <Modal v-model="modalViewRep" fullscreen :title="titleModalViewRep">
      <Row style="margin-bottom: 15px">
        <Col span="16">
          <Breadcrumb>
            <BreadcrumbItem
              v-for="(item, index) in breadRepPath.name"
              :key="index"
              @click.native="ClickBreadGetRepCon(breadRepPath.path[index])"
              >{{ item }}</BreadcrumbItem
            >
          </Breadcrumb>
        </Col>
        <Col span="8">
          <Input readonly v-model="tempCheckout">
            <Button slot="append" icon="md-copy" @click="CopyCheckout"
              >复制</Button
            >
          </Input>
        </Col>
      </Row>
      <Card :bordered="true" :dis-hover="true">
        <Table
          height="450"
          highlight-row
          :border="false"
          :loading="loadingRepCon"
          :show-header="false"
          :columns="tableColumnRepCon"
          :data="tableDataRepCon"
          @on-row-click="ClickRowGetRepCon"
        >
          <template slot-scope="{ row }" slot="resourceType">
            <Icon
              v-if="row.resourceType == 1"
              size="20"
              type="ios-document-outline"
            />
            <Icon
              v-if="row.resourceType == 2"
              size="20"
              color="#65a0d5"
              type="ios-folder-open"
            />
          </template>
        </Table>
      </Card>
      <div slot="footer">
        <Button type="primary" @click="modalViewRep = false">取消</Button>
      </div>
    </Modal>
    <!-- 对话框-备份仓库 -->
    <Modal v-model="modalRepDump" :title="titleModalRepBackup">
      <Row style="margin-bottom: 15px">
        <Col span="18">
          <Button
            type="primary"
            ghost
            size="small"
            @click="RepDump"
            :loading="loadingRepDump"
            >备份(svndump)</Button
          >
        </Col>
      </Row>
      <Table
        height="200"
        border
        :columns="tableColumnBackup2"
        :data="tableDataBackup"
        size="small"
        :loading="loadingRepBackupList"
      >
        <template slot-scope="{ row }" slot="action">
          <Button
            type="success"
            size="small"
            @click="DownloadRepBackup(row.fileName)"
            >下载</Button
          >
          <Button type="error" size="small" @click="DelRepBackup(row.fileName)"
            >删除</Button
          >
        </template>
      </Table>
      <div slot="footer">
        <Button type="primary" @click="modalRepDump = false">取消</Button>
      </div>
    </Modal>
    <!-- 对话框-仓库权限 -->
    <Modal v-model="modalRepPri" :title="titleModalRepPri" fullscreen>
      <Row type="flex" justify="center" :gutter="16">
        <Col span="11">
          <Scroll :height="550">
            <Tree
              :data="treeRep"
              :load-data="LoadingRepTree"
              :render="RenderContent"
              @on-select-change="ChangeSelectTreeNode"
            ></Tree>
            <Spin size="large" fix v-if="loadingRepTree"></Spin>
          </Scroll>
        </Col>
        <Col span="11">
          <Card :bordered="true" :dis-hover="true" style="height: 550px">
            <Tabs type="card">
              <TabPane label="用户">
                <Form :label-width="60">
                  <FormItem label="信息">
                    <Table
                      highlight-row
                      border
                      :height="200"
                      size="small"
                      :columns="tableColumnRepPathUserPri"
                      :data="tableDataRepPathUserPri"
                      :loading="loadingRepPathUserPri"
                      @on-current-change="ChangeSelectRepUserPri"
                    ></Table>
                  </FormItem>
                  <FormItem label="操作">
                    <ButtonGroup>
                      <Button icon="ios-add" @click="ModalRepAllUser"></Button>
                      <Button
                        icon="ios-remove"
                        @click="DelRepPathUserPri"
                      ></Button>
                    </ButtonGroup>
                  </FormItem>
                  <FormItem label="权限">
                    <RadioGroup
                      vertical
                      v-model="radioRepUserPri"
                      @on-change="ChangeRadioRepUserPri"
                    >
                      <Radio label="no">
                        <span>无权限</span>
                      </Radio>
                      <Radio label="r">
                        <span>只读</span>
                      </Radio>
                      <Radio label="rw">
                        <span>读写</span>
                      </Radio>
                    </RadioGroup>
                  </FormItem>
                  <FormItem>
                    <Button ghost type="primary" @click="EditRepPathUserPri"
                      >修改权限（针对当前）</Button
                    >
                  </FormItem>
                </Form>
              </TabPane>
              <TabPane label="分组">
                <Form :label-width="60">
                  <FormItem label="信息">
                    <Table
                      highlight-row
                      border
                      :height="200"
                      size="small"
                      :columns="tableColumnRepPathGroupPri"
                      :data="tableDataRepPathGroupPri"
                      :loading="loadingRepPathGroupPri"
                      @on-current-change="ChangeSelectRepGroupPri"
                    ></Table>
                  </FormItem>
                  <FormItem label="操作">
                    <ButtonGroup>
                      <Button icon="ios-add" @click="ModalRepAllGroup"></Button>
                      <Button
                        icon="ios-remove"
                        @click="DelRepPathGroupPri"
                      ></Button>
                    </ButtonGroup>
                  </FormItem>
                  <FormItem label="权限">
                    <RadioGroup
                      vertical
                      v-model="radioRepGroupPri"
                      @on-change="ChangeRadioRepGroupPri"
                    >
                      <Radio label="no">
                        <span>无权限</span>
                      </Radio>
                      <Radio label="r">
                        <span>只读</span>
                      </Radio>
                      <Radio label="rw">
                        <span>读写</span>
                      </Radio>
                    </RadioGroup>
                  </FormItem>
                  <FormItem>
                    <Button ghost type="primary" @click="EditRepPathGroupPri"
                      >修改权限（针对当前）</Button
                    >
                  </FormItem>
                </Form>
              </TabPane>
            </Tabs>
          </Card>
        </Col>
      </Row>
      <div slot="footer">
        <Button type="primary" @click="modalRepPri = false">取消</Button>
      </div>
    </Modal>
    <!-- 对话框-钩子配置 -->
    <Modal v-model="modalRepHooks" :title="titleModalRepHooks">
      <Form ref="formRepHooks" :model="formRepHooks" :label-width="60">
        <FormItem label="类型">
          <Select v-model="formRepHooks.select">
            <Option
              v-for="item in formRepHooks.type"
              :value="item.value"
              :key="item.value"
              >{{ item.label }}</Option
            >
          </Select>
        </FormItem>
        <FormItem label="脚本">
          <Input
            v-model="formRepHooks.type[formRepHooks.select].shell"
            :rows="10"
            show-word-limit
            type="textarea"
            placeholder="请输入hooks shell脚本 首行需为：#!/bin/bash 或 #!/bin/sh"
          />
        </FormItem>
        <FormItem>
          <Button ghost type="primary" @click="EditRepHook"
            >应用（针对当前）</Button
          >
        </FormItem>
      </Form>
      <div slot="footer">
        <Button type="primary" @click="modalRepAdvance = false">取消</Button>
      </div>
    </Modal>
    <!-- 对话框-高级 -->
    <Modal v-model="modalRepAdvance" :title="titleModalRepAdvance">
      <Tabs type="card">
        <TabPane label="属性">
          <Table
            :show-header="false"
            :columns="tableColumnRepDetail"
            :data="tableDataRepDetail"
            :loading="loadingRepDetail"
            size="small"
            height="350"
          >
            <template slot-scope="{ index }" slot="copy">
              <Button
                icon="md-copy"
                type="text"
                @click="CopyRepDetail(index)"
              ></Button>
            </template>
          </Table>
        </TabPane>
        <TabPane label="转储">
          <Form :model="formRepImport" :label-width="90">
            <FormItem label="dump文件">
              <RadioGroup
                vertical
                @on-change="ChangeRadioUploadType"
                v-model="formUploadBackup.selectType"
              >
                <Radio label="1">
                  <span>从本地上传</span>
                </Radio>
                <Alert type="warning" show-icon
                  >1、大文件建议通过FTP等方式上传<br />2、注意重复文件会自动覆盖</Alert
                >
                <Radio label="2">
                  <span>从服务器选择</span>
                </Radio>
              </RadioGroup>
            </FormItem>
            <FormItem v-if="formUploadBackup.selectType == '1'">
              <Upload
                multiple
                :on-success="UploadSuccess"
                :before-upload="BeforeUpload"
                action="/api.php?c=svnrep&a=UploadBackup&t=web"
                name="file"
                :headers="{ token: token }"
              >
                <Button
                  icon="ios-cloud-upload-outline"
                  :loading="loadingUploadBackup"
                  >上传文件</Button
                >
              </Upload>
            </FormItem>
            <FormItem
              label="备份文件夹"
              v-if="formUploadBackup.selectType == '2'"
            >
              <Table
                height="200"
                border
                highlight-row
                :loading="loadingRepBackupList"
                :columns="tableColumnBackup1"
                :data="tableDataBackup"
                size="small"
                @on-row-click="ClickRowUploadBackup"
              ></Table>
            </FormItem>
            <FormItem label="已选择" v-if="formUploadBackup.selectType == '2'">
              <Input readonly v-model="formUploadBackup.fileName"></Input>
            </FormItem>
            <FormItem
              label="执行结果"
              v-if="formUploadBackup.selectType == '2'"
            >
              <Input
                readonly
                type="textarea"
                :rows="4"
                placeholder="如果导入失败 错误信息会显示在此处"
                v-model="formUploadBackup.errorInfo"
              ></Input>
            </FormItem>
            <FormItem v-if="formUploadBackup.selectType == '2'">
              <Alert type="warning" show-icon
                >不了解svnadmin
                dump指令的用户建议只将转储文件导入到空仓库而不是已经包含修订版本的非空仓库</Alert
              >
              <Button
                type="primary"
                :loading="loadingImportBackup"
                @click="ImportRep"
                ghost
                >导入</Button
              >
            </FormItem>
          </Form>
        </TabPane>
      </Tabs>
      <div slot="footer">
        <Button type="primary" @click="modalRepAdvance = false">取消</Button>
      </div>
    </Modal>
    <!-- 对话框-编辑仓库名称 -->
    <Modal
      v-model="modalEditRepName"
      :title="titleModalEditRepName"
      @on-ok="EditRepName"
    >
      <Form :model="formRepEdit" :label-width="80">
        <FormItem label="仓库名称">
          <Input v-model="formRepEdit.new_rep_name"></Input>
        </FormItem>
      </Form>
    </Modal>
    <!-- 对话框-选择SVN用户 -->
    <Modal
      v-model="modalRepAllUser"
      title="选择SVN用户（添加的用户权限都会被重置为rw）"
      @on-ok="AddRepPathUserPri"
    >
      <Table
        height="350"
        highlight-row
        :show-header="false"
        :columns="tableColumnAllUser"
        :data="tableDataAllUser"
        :loading="loadingAllUserList"
        @on-row-click="ClickRowAddRepPathUser"
      >
        <template slot-scope="{ row }" slot="disabled">
          <Tag color="blue" v-if="row.disabled == 0">正常</Tag>
          <Tag color="red" v-else>禁用</Tag>
        </template>
      </Table>
    </Modal>
    <!-- 对话框-选择SVN分组 -->
    <Modal
      v-model="modalRepAllGroup"
      title="选择SVN分组（添加的用户权限都会被重置为rw）"
      @on-ok="AddRepPathGroupPri"
    >
      <Table
        height="350"
        highlight-row
        :show-header="false"
        :columns="tableColumnAllGroup"
        :data="tableDataAllGroup"
        :loading="loadingAllGroupList"
        @on-row-click="ClickRowAddRepPathGroup"
      ></Table>
    </Modal>
  </div>
</template>

<script>
export default {
  data() {
    return {
      /**
       * 权限相关
       */
      token: sessionStorage.token,
      user_role_id: sessionStorage.user_role_id,

      //仓库目录树
      treeRep: [],

      /**
       * 对话框
       */
      //新建SVN仓库
      modalCreateRep: false,
      //浏览仓库
      modalViewRep: false,
      //仓库备份
      modalRepDump: false,
      //仓库权限
      modalRepPri: false,
      //仓库钩子配置
      modalRepHooks: false,
      //高级
      modalRepAdvance: false,
      //编辑仓库信息
      modalEditRepName: false,
      //SVN仓库所有用户
      modalRepAllUser: false,
      //SVN仓库所有分组
      modalRepAllGroup: false,

      /**
       * 排序数据
       */
      sortName: "rep_name",
      sortType: "asc",

      /**
       * 分页数据
       */
      //所有仓库
      pageCurrentRep: 1,
      pageSizeRep: 10,
      totalRep: 0,
      //用户仓库
      pageCurrentUserRep: 1,
      pageSizeUserRep: 10,
      totalUserRep: 0,

      /**
       * 搜索关键词
       */
      searchKeywordRep: "",

      /**
       * 加载
       */
      //所有仓库列表
      loadingRep: true,
      //用户仓库列表
      loadingUserRep: true,
      //仓库内容列表
      loadingRepCon: true,
      //仓库目录树
      loadingRepTree: true,
      //某个仓库路径的用户权限
      loadingRepPathUserPri: true,
      //某个仓库路径的分组权限
      loadingRepPathGroupPri: true,
      //全部的SVN用户列表
      loadingAllUserList: true,
      //全部的SVN分组列表
      loadingAllGroupList: true,
      //获取仓库的详细信息
      loadingRepDetail: true,
      //获取仓库的备份文件夹文件内容
      loadingRepBackupList: true,
      //备份仓库按钮
      loadingRepDump: false,
      //上传备份文件
      loadingUploadBackup: false,
      //导入备份文件
      loadingImportBackup: false,

      /**
       * 临时变量
       */
      //临时选中的仓库名称
      currentRepName: "",
      //展开目录树时选中的仓库路径
      currentRepTreePath: "/",
      //点击目录树查看权限时的仓库路径
      currentRepTreePriPath: "/",
      //检出路径
      tempCheckout: "",
      //单选 仓库路径的用户权限列表
      radioRepUserPri: "",
      //单选 仓库路径的分组权限列表
      radioRepGroupPri: "",
      //仓库路径的用户权限列表 当前选中的用户以及下标
      currentRepPriUser: "",
      currentRepPriUserIndex: -1,
      //仓库路径的分组权限列表 当前选中的分组以及下标
      currentRepPriGroup: "",
      currentRepPriGroupIndex: -1,
      //仓库路径的权限新增用户列表 当前选中的用户
      currentRepPriAddUser: "",
      //仓库路径的权限新增分组列表 当前选中的分组
      currentRepPriAddGroup: "",

      /**
       * 对话框标题
       */
      //浏览仓库内容
      titleModalViewRep: "",
      //仓库备份
      titleModalRepBackup: "",
      //配置仓库权限
      titleModalRepPri: "",
      //仓库钩子
      titleModalRepHooks: "",
      //编辑仓库名称
      titleModalEditRepName: "",
      //高级
      titleModalRepAdvance: "",

      /**
       * 表单
       */
      //新建SVN仓库
      formRepAdd: {
        rep_name: "",
        rep_note: "",
        rep_type: "1",
      },
      //编辑仓库
      formRepEdit: {
        old_rep_name: "",
        new_rep_name: "",
      },
      //导入仓库
      formRepImport: {},
      //钩子结构
      formRepHooks: {
        select: "start-commit",
        type: {
          "start-commit": {
            value: "start-commit",
            label: "start-commit---事务创建前",
            shell: "",
          },
          "pre-commit": {
            value: "pre-commit",
            label: "pre-commit---事务提交前",
            shell: "",
          },
          "post-commit": {
            value: "post-commit",
            label: "post-commit---事务提交后",
            shell: "",
          },
          "pre-lock": {
            value: "pre-lock",
            label: "pre-lock---锁定文件前",
            shell: "",
          },
          "post-lock": {
            value: "post-lock",
            label: "post-lock---锁定文件后",
            shell: "",
          },
          "pre-unlock": {
            value: "pre-unlock",
            label: "pre-unlock---解锁文件前",
            shell: "",
          },
          "post-unlock": {
            value: "post-unlock",
            label: "post-unlock---解锁文件后",
            shell: "",
          },
          "pre-revprop-change": {
            value: "pre-revprop-change",
            label: "pre-revprop-change---修改修订版属性前",
            shell: "",
          },
          "post-revprop-change": {
            value: "post-revprop-change",
            label: "post-revprop-change---修改修订版属性后",
            shell: "",
          },
        },
      },
      //页头提示信息
      formStatusSubversion: {
        status: true,
        info: "",
      },
      //目录浏览的检出地址
      checkInfo: {
        protocal: "",
        prefix: "",
      },
      //上传状态
      formUploadBackup: {
        selectType: "1",
        fileName: "",
        errorInfo: "",
      },

      /**
       * 浏览仓库面包屑
       */
      breadRepPath: [],

      /**
       * 表格
       */
      //所有仓库
      tableColumnRep: [
        {
          title: "序号",
          type: "index",
          fixed: "left",
          minWidth: 80,
        },
        {
          title: "仓库名",
          key: "rep_name",
          tooltip: true,
          sortable: "custom",
          minWidth: 120,
        },
        {
          title: "版本数",
          key: "rep_rev",
          sortable: "custom",
          minWidth: 120,
        },
        {
          title: "体积",
          key: "rep_size",
          sortable: "custom",
          minWidth: 120,
        },
        {
          title: "备注信息",
          slot: "rep_note",
          minWidth: 120,
        },
        {
          title: "仓库内容",
          slot: "repScan",
          minWidth: 120,
        },
        {
          title: "仓库备份",
          slot: "repBackup",
          minWidth: 120,
        },
        {
          title: "仓库权限",
          slot: "repPri",
          minWidth: 120,
        },
        {
          title: "仓库钩子",
          slot: "repHooks",
          width: 120,
        },
        {
          title: "其它",
          slot: "action",
          width: 180,
          // fixed:"right"
        },
      ],
      tableDataRep: [],
      //SVN用户仓库
      tableColumnUserRep: [
        {
          title: "序号",
          type: "index",
          fixed: "left",
          minWidth: 80,
        },
        {
          title: "仓库名",
          key: "rep_name",
          tooltip: true,
          sortable: "custom",
          minWidth: 120,
        },
        {
          title: "路径/文件",
          tooltip: true,
          key: "pri_path",
          minWidth: 120,
        },
        {
          title: "权限",
          key: "rep_pri",
          minWidth: 120,
        },
        {
          title: "其它",
          slot: "action",
          width: 180,
          // fixed:"right"
        },
      ],
      tableDataUserRep: [],
      //仓库内容浏览
      tableColumnRepCon: [
        {
          title: "类型",
          slot: "resourceType",
          width: 60,
        },
        {
          title: "文件",
          key: "resourceName",
          tooltip: true,
        },
        {
          title: "体积",
          key: "fileSize",
          tooltip: true,
        },
        {
          title: "作者",
          key: "revAuthor",
          tooltip: true,
        },
        {
          title: "版本",
          key: "revNum",
          tooltip: true,
        },
        {
          title: "日期",
          key: "revTime",
          tooltip: true,
          width: 350,
        },
        {
          title: "日志",
          key: "revLog",
          tooltip: true,
        },
      ],
      tableDataRepCon: [],
      //备份文件夹
      //导入仓库文件浏览用
      tableColumnBackup1: [
        {
          title: "文件名",
          key: "fileName",
        },
        {
          title: "文件大小",
          key: "fileSize",
          tooltip: true,
        },
        {
          title: "修改时间",
          key: "fileEditTime",
          tooltip: true,
        },
      ],
      //仓库备份管理用
      tableColumnBackup2: [
        {
          title: "文件名",
          key: "fileName",
        },
        {
          title: "大小",
          key: "fileSize",
          tooltip: true,
        },
        {
          title: "修改时间",
          key: "fileEditTime",
          tooltip: true,
        },
        {
          title: "其它",
          slot: "action",
          width: 130,
        },
      ],
      tableDataBackup: [],
      //某节点的用户权限
      tableColumnRepPathUserPri: [
        {
          title: "用户名",
          key: "userName",
        },
        {
          title: "权限",
          key: "userPri",
        },
      ],
      tableDataRepPathUserPri: [],
      //某节点的分组权限
      tableColumnRepPathGroupPri: [
        {
          title: "分组名",
          key: "groupName",
        },
        {
          title: "权限",
          key: "groupPri",
        },
      ],
      tableDataRepPathGroupPri: [],
      //仓库的详细信息 uuid等
      tableColumnRepDetail: [
        {
          title: "属性",
          key: "repKey",
          tooltip: true,
        },
        {
          title: "信息",
          key: "repValue",
          tooltip: true,
        },
        {
          title: "复制",
          slot: "copy",
          width: 60,
        },
      ],
      tableDataRepDetail: [],
      //仓库的所有用户
      tableColumnAllUser: [
        {
          title: "用户名",
          key: "userName",
        },
        {
          title: "启用状态",
          slot: "disabled",
        },
      ],
      tableDataAllUser: [],
      //仓库的所有分组
      tableColumnAllGroup: [
        {
          title: "分组名",
          key: "groupName",
        },
      ],
      tableDataAllGroup: [],
    };
  },
  computed: {},
  created() {},
  mounted() {
    this.GetStatus();
    if (this.user_role_id == 1) {
      this.GetRepList();
    } else if (this.user_role_id == 2) {
      this.GetSvnUserRepList();
    }
  },
  methods: {
    /**
     * 获取svnserve运行状态
     */
    GetStatus() {
      var that = this;
      var data = {};
      that.$axios
        .post("/api.php?c=subversion&a=GetStatus&t=web", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
          } else {
            that.formStatusSubversion.status = false;
            that.formStatusSubversion.info = result.message;
          }
        })
        .catch(function (error) {
          console.log(error);
        });
    },

    /**
     * 添加仓库
     */
    ModalCreateRep() {
      this.modalCreateRep = true;
    },
    CreateRep() {
      var that = this;
      var data = {
        rep_name: that.formRepAdd.rep_name,
        rep_note: that.formRepAdd.rep_note,
        rep_type: that.formRepAdd.rep_type,
      };
      that.$axios
        .post("/api.php?c=svnrep&a=CreateRep&t=web", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
            that.GetRepList();
          } else {
            that.$Message.error(result.message);
          }
        })
        .catch(function (error) {
          console.log(error);
        });
    },

    /**
     * 获取仓库列表
     */
    GetRepList() {
      var that = this;
      that.loadingRep = true;
      that.tableDataRep = [];
      that.totalRep = 0;
      var data = {
        pageSize: that.pageSizeRep,
        currentPage: that.pageCurrentRep,
        searchKeyword: that.searchKeywordRep,
        sortName: that.sortName,
        sortType: that.sortType,
      };
      that.$axios
        .post("/api.php?c=svnrep&a=GetRepList&t=web", data)
        .then(function (response) {
          that.loadingRep = false;
          var result = response.data;
          if (result.status == 1) {
            // that.$Message.success(result.message);
            that.tableDataRep = result.data.data;
            that.totalRep = result.data.total;
          } else {
            that.$Message.error(result.message);
          }
        })
        .catch(function (error) {
          that.loadingRep = false;
          console.log(error);
        });
    },
    /**
     * 所有仓库列表页码改变
     */
    PageChangeRep(value) {
      //设置当前页数
      this.pageCurrentRep = value;
      this.GetRepList();
    },
    /**
     * 所有仓库列表每页数量改变
     */
    PageSizeChangeRep(value) {
      //设置每页条数
      this.pageSizeRep = value;
      this.GetRepList();
    },
    /**
     * 所有仓库排序
     */
    SortChangeRep(value) {
      this.sortName = value.key;
      if (value.order == "desc" || value.order == "asc") {
        this.sortType = value.order;
      }
      this.GetRepList();
    },

    /**
     * 获取用户仓库列表
     */
    GetSvnUserRepList() {
      var that = this;
      that.loadingUserRep = true;
      that.tableDataUserRep = [];
      that.totalUserRep = 0;
      var data = {
        pageSize: that.pageSizeUserRep,
        currentPage: that.pageCurrentUserRep,
        searchKeyword: that.searchKeywordRep,
        sortType: that.sortType,
      };
      that.$axios
        .post("/api.php?c=svnrep&a=GetSvnUserRepList&t=web", data)
        .then(function (response) {
          that.loadingUserRep = false;
          var result = response.data;
          if (result.status == 1) {
            // that.$Message.success(result.message);
            that.tableDataUserRep = result.data.data;
            that.totalUserRep = result.data.total;
          } else {
            that.$Message.error(result.message);
          }
        })
        .catch(function (error) {
          that.loadingUserRep = false;
          console.log(error);
        });
    },
    /**
     * 用户仓库列表页码改变
     */
    PageChangeUserRep(value) {
      //设置当前页数
      this.pageCurrentUserRep = value;
      this.GetSvnUserRepList();
    },
    /**
     * 用户仓库每页数量改变
     */
    PageSizeChangeUserRep(value) {
      //设置每页条数
      this.pageSizeUserRep = value;
      this.GetSvnUserRepList();
    },
    /**
     * 用户仓库排序
     */
    SortChangeUserRep(value) {
      this.sortName = value.key;
      if (value.order == "desc" || value.order == "asc") {
        this.sortType = value.order;
      }
      this.GetSvnUserRepList();
    },

    /**
     * 编辑仓库备注信息
     */
    EditRepNote(index, rep_name) {
      var that = this;
      var data = {
        rep_name: rep_name,
        rep_note: that.tableDataRep[index].rep_note,
      };
      that.$axios
        .post("/api.php?c=svnrep&a=EditRepNote&t=web", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
          } else {
            that.$Message.error(result.message);
          }
        })
        .catch(function (error) {
          console.log(error);
        });
    },

    /**
     * 管理人员浏览仓库
     */
    ModalViewRep(rep_name) {
      var that = this;
      //通过按钮点击浏览 初始化路径和仓库名称
      that.currentRepTreePath = "/";
      that.currentRepName = rep_name;
      //设置标题
      that.titleModalViewRep = "仓库内容-" + rep_name;
      //显示对话框
      that.modalViewRep = true;
      //请求检出地址信息
      that.GetCheckout().then(function (response) {
        //在检出地址的成功回调中开始请求仓库内容
        that.GetRepCon();
      });
    },
    /**
     * 用户浏览仓库
     */
    ModalViewUserRep(rep_name, pri_path) {
      var that = this;
      //通过按钮点击浏览 初始化路径和仓库名称
      that.currentRepTreePath = pri_path;
      that.currentRepName = rep_name;
      //设置标题
      that.titleModalViewRep = "仓库内容-" + rep_name;
      //显示对话框
      that.modalViewRep = true;
      //请求检出地址信息
      that.GetCheckout().then(function (response) {
        //在检出地址的成功回调中开始请求仓库内容
        that.GetUserRepCon();
      });
    },
    /**
     * 获取检出地址前缀
     */
    GetCheckout() {
      var that = this;
      //清空之前的检出地址
      that.tempCheckout = "";
      //清空之前的表格内容
      that.tableDataRepCon = [];
      //清空之前的面包屑
      that.breadRepPath = [];
      //重置加载动画
      that.loadingRepCon = true;
      var data = {};
      return new Promise(function (resolve, reject) {
        that.$axios
          .post("/api.php?c=subversion&a=GetCheckout&t=web", data)
          .then(function (response) {
            var result = response.data;
            if (result.status == 1) {
              that.checkInfo = result.data;
            } else {
              that.loadingRepCon = false;
              that.$Message.error(result.message);
            }
            resolve(response);
          })
          .catch(function (error) {
            console.log(error);
            reject(error);
          });
      });
    },
    /**
     * 获取仓库内容
     */
    GetRepCon() {
      var that = this;
      that.loadingRepCon = true;
      var data = {
        rep_name: that.currentRepName,
        path: that.currentRepTreePath,
      };
      that.$axios
        .post("/api.php?c=svnrep&a=GetRepCon&t=web", data)
        .then(function (response) {
          that.loadingRepCon = false;
          var result = response.data;
          if (result.status == 1) {
            that.tableDataRepCon = result.data.data;
            that.breadRepPath = result.data.bread;
            //更新检出地址
            that.tempCheckout =
              that.checkInfo.protocal +
              that.checkInfo.prefix +
              "/" +
              that.currentRepName +
              that.currentRepTreePath;
          } else {
            that.$Message.error(result.message);
          }
        })
        .catch(function (error) {
          that.loadingRepCon = false;
          console.log(error);
        });
    },
    /**
     * 获取用户仓库内容
     */
    GetUserRepCon() {
      var that = this;
      that.loadingRepCon = true;
      var data = {
        rep_name: that.currentRepName,
        path: that.currentRepTreePath,
      };
      that.$axios
        .post("/api.php?c=svnrep&a=GetUserRepCon&t=web", data)
        .then(function (response) {
          that.loadingRepCon = false;
          var result = response.data;
          if (result.status == 1) {
            that.tableDataRepCon = result.data.data;
            that.breadRepPath = result.data.bread;
            //更新检出地址
            that.tempCheckout =
              that.checkInfo.protocal +
              that.checkInfo.prefix +
              "/" +
              that.currentRepName +
              that.currentRepTreePath;
          } else {
            that.$Message.error(result.message);
          }
        })
        .catch(function (error) {
          that.loadingRepCon = false;
          console.log(error);
        });
    },
    /**
     * 点击某行获取仓库路径内容
     */
    ClickRowGetRepCon(row, index) {
      if (this.tableDataRepCon[index].resourceType == "2") {
        this.currentRepTreePath = this.tableDataRepCon[index].fullPath;
        this.GetRepCon();
      }
    },
    /**
     * 点击面包屑获取仓库路径内容
     */
    ClickBreadGetRepCon(fullPath) {
      this.currentRepTreePath = fullPath;
      this.GetRepCon();
    },
    /**
     * 复制检出地址
     */
    CopyCheckout() {
      var that = this;
      that.$copyText(that.tempCheckout).then(
        function (e) {
          that.$Message.success("复制成功");
        },
        function (e) {
          that.$Message.error("复制失败，请手动复制");
        }
      );
    },

    /**
     * 备份仓库
     */
    ModalRepDump(rep_name) {
      //设置标题
      this.titleModalRepBackup = "仓库备份-" + rep_name;
      //显示对话框
      this.modalRepDump = true;
      //设置当前选中的仓库名
      this.currentRepName = rep_name;
      //请求数据
      this.GetBackupList();
    },
    /**
     * 获取备份文件夹下的文件列表
     */
    GetBackupList() {
      var that = this;
      that.loadingRepBackupList = true;
      that.tableDataBackup = [];
      var data = {};
      that.$axios
        .post("/api.php?c=svnrep&a=GetBackupList&t=web", data)
        .then(function (response) {
          that.loadingRepBackupList = false;
          var result = response.data;
          if (result.status == 1) {
            that.tableDataBackup = result.data;
          } else {
            that.$Message.error(result.message);
          }
        })
        .catch(function (error) {
          that.loadingRepBackupList = false;
          console.log(error);
        });
    },
    RepDump() {
      var that = this;
      that.loadingRepDump = true;
      var data = {
        rep_name: that.currentRepName,
      };
      that.$axios
        .post("/api.php?c=svnrep&a=RepDump&t=web", data)
        .then(function (response) {
          that.loadingRepDump = false;
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
            that.GetBackupList();
          } else {
            that.$Message.error(result.message);
          }
        })
        .catch(function (error) {
          that.loadingRepDump = false;
          console.log(error);
        });
    },
    /**
     * 下载备份文件
     */
    DownloadRepBackup(fileName) {
      var that = this;
      var data = {
        fileName: fileName,
      };
      that.$axios.setAttribute;
      that.$axios
        .post("/api.php?c=svnrep&a=DownloadRepBackup&t=web", data, {
          responseType: "blob",
        })
        .then(function (response) {
          let url = window.URL.createObjectURL(
            new Blob([response.data], { type: "application/octet-stream" })
          );
          let link = document.createElement("a");
          link.style.display = "none";
          link.href = url;
          link.setAttribute("download", fileName);
          document.body.appendChild(link);
          link.click();
          //释放url对象所占资源
          window.URL.revokeObjectURL(url);
          //用完删除
          document.body.removeChild(link);
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    /**
     * 删除备份文件
     */
    DelRepBackup(fileName) {
      var that = this;
      that.$Modal.confirm({
        title: "删除文件",
        content: "确定要删除该文件吗？<br/>该操作不可逆！",
        onOk: () => {
          var data = {
            fileName: fileName,
          };
          that.$axios
            .post("/api.php?c=svnrep&a=DelRepBackup&t=web", data)
            .then(function (response) {
              var result = response.data;
              if (result.status == 1) {
                that.$Message.success(result.message);
                that.GetBackupList();
              } else {
                that.$Message.error(result.message);
              }
            })
            .catch(function (error) {
              console.log(error);
            });
        },
      });
    },

    /**
     * 仓库权限
     */
    ModalRepPri(rep_name) {
      var that = this;
      //通过按钮点击浏览 初始化路径和仓库名称
      that.currentRepTreePath = "/";
      that.currentRepTreePriPath = "/";
      that.currentRepName = rep_name;
      //设置标题
      that.titleModalRepPri = "仓库权限-" + rep_name;
      //显示对话框
      that.modalRepPri = true;
      //显示加载动画
      that.loadingRepTree = true;
      //清空数据
      that.treeRep = [];
      //请求目录树
      that.GetRepTree().then(function (response) {
        that.loadingRepTree = false;
        var result = response.data;
        if (result.status == 1) {
          that.treeRep = result.data;
        } else {
          that.$Message.error(result.message);
        }
      });
      //获取仓库根路径的用户权限列表
      that.GetRepPathUserPri();
      //获取仓库根路径的分组权限列表
      that.GetRepPathGroupPri();
    },
    /**
     * 获取目录树
     */
    GetRepTree() {
      var that = this;
      var data = {
        rep_name: that.currentRepName,
        path: that.currentRepTreePath,
      };
      return new Promise(function (resolve, reject) {
        that.$axios
          .post("/api.php?c=svnrep&a=GetRepTree&t=web", data)
          .then(function (response) {
            resolve(response);
          })
          .catch(function (error) {
            console.log(error);
            reject(error);
          });
      });
    },
    /**
     * 渲染目录树 给文件夹和文件设置对应的图标
     */
    RenderContent(h, { root, node, data }) {
      return h("span", [
        h("Icon", {
          props: {
            type:
              data.resourceType == "1"
                ? "ios-document-outline"
                : "ios-folder-open",
          },
          style: {
            marginRight: "8px",
          },
        }),
        h("span", data.title),
      ]);
    },
    /**
     * 点击目录树节点触发
     */
    ChangeSelectTreeNode(selectArray, currentItem) {
      this.currentRepTreePriPath = currentItem.fullPath;
      this.GetRepPathUserPri();
      this.GetRepPathGroupPri();
    },
    /**
     * 异步加载目录下的内容
     */
    LoadingRepTree(item, callback) {
      var that = this;
      var data = [];
      that.currentRepTreePath = item.fullPath;
      that.GetRepTree().then(function (response) {
        var result = response.data;
        if (result.status == 1) {
          data = result.data;
          if (data.length > 0) {
            if (data[0].fullPath != "/") {
              callback(data);
            } else {
              callback([]);
              //根目录下没有内容时 直接覆盖掉
              that.treeRep = [
                {
                  resourceType: 2,
                  title: that.currentRepName + "/",
                  fullPath: "/",
                },
              ];
            }
          } else {
            callback([]);
          }
        } else {
          that.$Message.error(result.message);
          callback(data);
        }
      });
    },

    /**
     * 获取某个仓库路径的用户权限列表
     */
    GetRepPathUserPri() {
      var that = this;
      //清空上次表格数据
      that.tableDataRepPathUserPri = [];
      //清空选中的用户数据
      that.currentRepPriUser = "";
      that.currentRepPriUserIndex = -1;
      //开始加载动画
      that.loadingRepPathUserPri = true;
      var data = {
        rep_name: that.currentRepName,
        path: that.currentRepTreePriPath,
      };
      that.$axios
        .post("/api.php?c=svnrep&a=GetRepPathUserPri&t=web", data)
        .then(function (response) {
          that.loadingRepPathUserPri = false;
          var result = response.data;
          if (result.status == 1) {
            that.tableDataRepPathUserPri = result.data;
          } else {
            that.$Message.error(result.message);
          }
        })
        .catch(function (error) {
          that.loadingRepPathUserPri = false;
          console.log(error);
        });
    },
    /**
     * 点击仓库路径的用户权限列表的某行
     */
    ChangeSelectRepUserPri(currentRow, oldCurrentRow) {
      //将当前选中的用户和下标进行同步
      this.currentRepPriUser = currentRow.userName;
      this.currentRepPriUserIndex = currentRow.index;
      //将当前选中行的权限同步到下方的单选
      this.radioRepUserPri = currentRow.userPri;
    },
    /**
     * 单选组合 为选中的用户更换权限
     */
    ChangeRadioRepUserPri(value) {
      //如果没有选中用户则做出提示
      if (this.currentRepPriUser == "") {
        this.$Message.error("未选择用户");
      } else {
        this.tableDataRepPathUserPri[this.currentRepPriUserIndex].userPri =
          value;
      }
    },
    /**
     * 单选按钮 删除某个仓库路径的用户权限
     */
    DelRepPathUserPri() {
      var that = this;
      //如果没有选中用户则做出提示
      if (that.currentRepPriUser == "") {
        that.$Message.error("未选择用户");
        return;
      }
      var data = {
        rep_name: that.currentRepName,
        path: that.currentRepTreePriPath,
        user: that.currentRepPriUser,
      };
      that.$axios
        .post("/api.php?c=svnrep&a=DelRepPathUserPri&t=web", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
            that.GetRepPathUserPri();
          } else {
            that.$Message.error(result.message);
          }
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    /**
     * 单选按钮 修改某个仓库路径的用户权限
     */
    EditRepPathUserPri() {
      var that = this;
      //如果没有选中用户则做出提示
      if (that.currentRepPriUser == "") {
        that.$Message.error("未选择用户");
        return;
      }
      var data = {
        rep_name: that.currentRepName,
        path: that.currentRepTreePriPath,
        pri: that.tableDataRepPathUserPri[that.currentRepPriUserIndex].userPri,
        user: that.tableDataRepPathUserPri[that.currentRepPriUserIndex]
          .userName,
      };
      that.$axios
        .post("/api.php?c=svnrep&a=EditRepPathUserPri&t=web", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
            that.GetRepPathUserPri();
          } else {
            that.$Message.error(result.message);
          }
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    /**
     * 增加某个仓库路径的用户权限
     */
    AddRepPathUserPri() {
      var that = this;
      var data = {
        rep_name: that.currentRepName,
        path: that.currentRepTreePriPath,
        pri: "rw",
        user: that.currentRepPriAddUser,
      };
      that.$axios
        .post("/api.php?c=svnrep&a=AddRepPathUserPri&t=web", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
            that.GetRepPathUserPri();
          } else {
            that.$Message.error(result.message);
          }
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    /**
     * 获取所有的SVN用户列表
     */
    ModalRepAllUser() {
      this.modalRepAllUser = true;
      //获取所有SVN用户列表
      this.GetAllUserList();
    },
    /**
     * 获取所有的SVN用户列表
     */
    GetAllUserList() {
      var that = this;
      //清空上次数据
      that.tableDataAllUser = [];
      //开始加载动画
      that.loadingAllUserList = true;
      var data = {};
      that.$axios
        .post("/api.php?c=svnuser&a=GetAllUserList&t=web", data)
        .then(function (response) {
          that.loadingAllUserList = false;
          var result = response.data;
          if (result.status == 1) {
            that.tableDataAllUser = result.data;
          } else {
            that.$Message.error(result.message);
          }
        })
        .catch(function (error) {
          that.loadingAllUserList = false;
          console.log(error);
        });
    },
    /**
     * 选中用户添加权限
     */
    ClickRowAddRepPathUser(currentRow, oldCurrentRow) {
      this.currentRepPriAddUser = currentRow.userName;
    },

    /**
     * 获取某个仓库路径的分组权限列表
     */
    GetRepPathGroupPri() {
      var that = this;
      //清空上次表格数据
      that.tableDataRepPathGroupPri = [];
      //清空选中的分组名称数据
      that.currentRepPriGroup = "";
      that.currentRepPriGroupIndex = -1;
      //开始加载动画
      that.loadingRepPathGroupPri = true;
      var data = {
        rep_name: that.currentRepName,
        path: that.currentRepTreePriPath,
      };
      that.$axios
        .post("/api.php?c=svnrep&a=GetRepPathGroupPri&t=web", data)
        .then(function (response) {
          that.loadingRepPathGroupPri = false;
          var result = response.data;
          if (result.status == 1) {
            that.tableDataRepPathGroupPri = result.data;
          } else {
            that.$Message.error(result.message);
          }
        })
        .catch(function (error) {
          that.loadingRepPathGroupPri = false;
          console.log(error);
        });
    },
    /**
     * 点击仓库路径的分组权限列表的某行
     */
    ChangeSelectRepGroupPri(currentRow, oldCurrentRow) {
      //将当前选中的分组和下标进行同步
      this.currentRepPriGroup = currentRow.groupName;
      this.currentRepPriGroupIndex = currentRow.index;
      //将当前选中行的权限同步到下方的单选
      if (currentRow.groupPri == "") {
        this.radioRepGroupPri = "no";
      } else {
        this.radioRepGroupPri = currentRow.groupPri;
      }
    },
    /**
     * 单选组合 为选中的分组更换权限
     */
    ChangeRadioRepGroupPri(value) {
      //如果没有选中分组则做出提示
      if (this.currentRepPriGroup == "") {
        this.$Message.error("未选择分组");
      } else {
        this.tableDataRepPathGroupPri[this.currentRepPriGroupIndex].groupPri =
          value;
      }
    },
    /**
     * 单选按钮 删除某个仓库路径的分组权限
     */
    DelRepPathGroupPri() {
      var that = this;
      //如果没有选中用户则做出提示
      if (that.currentRepPriGroup == "") {
        that.$Message.error("未选择分组");
        return;
      }
      var data = {
        rep_name: that.currentRepName,
        path: that.currentRepTreePriPath,
        group: that.currentRepPriGroup,
      };
      that.$axios
        .post("/api.php?c=svnrep&a=DelRepPathGroupPri&t=web", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
            that.GetRepPathGroupPri();
          } else {
            that.$Message.error(result.message);
          }
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    /**
     * 单选按钮 修改某个仓库路径的分组权限
     */
    EditRepPathGroupPri() {
      var that = this;
      //如果没有选中分组则做出提示
      if (that.currentRepPriGroup == "") {
        that.$Message.error("未选择分组");
        return;
      }
      var data = {
        rep_name: that.currentRepName,
        path: that.currentRepTreePriPath,
        pri: that.tableDataRepPathGroupPri[that.currentRepPriGroupIndex]
          .groupPri,
        group:
          that.tableDataRepPathGroupPri[that.currentRepPriGroupIndex].groupName,
      };
      that.$axios
        .post("/api.php?c=svnrep&a=EditRepPathGroupPri&t=web", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
            that.GetRepPathGroupPri();
          } else {
            that.$Message.error(result.message);
          }
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    /**
     * 增加某个仓库路径的分组权限
     */
    AddRepPathGroupPri() {
      var that = this;
      var data = {
        rep_name: that.currentRepName,
        path: that.currentRepTreePriPath,
        pri: "rw",
        group: that.currentRepPriAddGroup,
      };
      that.$axios
        .post("/api.php?c=svnrep&a=AddRepPathGroupPri&t=web", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
            that.GetRepPathGroupPri();
          } else {
            that.$Message.error(result.message);
          }
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    /**
     * 获取所有的SVN分组列表
     */
    ModalRepAllGroup() {
      this.modalRepAllGroup = true;
      //获取所有SVN分组列表
      this.GetAllGroupList();
    },
    /**
     * 获取所有的SVN分组列表
     */
    GetAllGroupList() {
      var that = this;
      //清空上次数据
      that.tableDataAllGroup = [];
      //开始加载动画
      that.loadingAllGroupList = true;
      var data = {};
      that.$axios
        .post("/api.php?c=svngroup&a=GetAllGroupList&t=web", data)
        .then(function (response) {
          that.loadingAllGroupList = false;
          var result = response.data;
          if (result.status == 1) {
            that.tableDataAllGroup = result.data;
          } else {
            that.$Message.error(result.message);
          }
        })
        .catch(function (error) {
          that.loadingAllGroupList = false;
          console.log(error);
        });
    },
    /**
     * 选中分组添加权限
     */
    ClickRowAddRepPathGroup(currentRow, oldCurrentRow) {
      this.currentRepPriAddGroup = currentRow.groupName;
    },

    /**
     * 仓库钩子
     */
    ModalRepHooks(rep_name) {
      //设置标题
      this.titleModalRepHooks = "仓库钩子-" + rep_name;
      //显示对话框
      this.modalRepHooks = true;
      //设置当前选中仓库
      this.currentRepName = rep_name;
      //请求数据
      this.GetRepHooks();
    },
    /**
     * 获取仓库的钩子和对应的内容列表
     */
    GetRepHooks() {
      var that = this;
      var data = {
        rep_name: that.currentRepName,
      };
      that.$axios
        .post("/api.php?c=svnrep&a=GetRepHooks&t=web", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.formRepHooks.type = result.data;
          } else {
            that.$Message.error(result.message);
          }
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    /**
     * 修改仓库的钩子内容（针对单个钩子）
     */
    EditRepHook() {
      var that = this;
      var data = {
        rep_name: that.currentRepName,
        type: that.formRepHooks.select,
        content: that.formRepHooks.type[that.formRepHooks.select].shell,
      };
      that.$axios
        .post("/api.php?c=svnrep&a=EditRepHook&t=web", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
            that.GetRepHooks();
          } else {
            that.$Message.error(result.message);
          }
        })
        .catch(function (error) {
          console.log(error);
        });
    },

    /**
     * 高级
     */
    ModalRepAdvance(rep_name) {
      //设置当前仓库名称
      this.currentRepName = rep_name;
      //设置标题
      this.titleModalRepAdvance = "高级-" + rep_name;
      //重置
      this.formUploadBackup.selectType = "1";
      this.formUploadBackup.fileName = "";
      this.formUploadBackup.errorInfo = "";
      //显示对话框
      this.modalRepAdvance = true;
      //请求数据
      this.GetRepDetail();
    },
    /**
     * 获取仓库的属性内容（key-vlaue的形式）
     */
    GetRepDetail() {
      var that = this;
      that.loadingRepDetail = true;
      var data = {
        rep_name: that.currentRepName,
      };
      that.$axios
        .post("/api.php?c=svnrep&a=GetRepDetail&t=web", data)
        .then(function (response) {
          that.loadingRepDetail = false;
          var result = response.data;
          if (result.status == 1) {
            that.tableDataRepDetail = result.data;
          } else {
            that.$Message.error(result.message);
          }
        })
        .catch(function (error) {
          that.loadingRepDetail = false;
          console.log(error);
        });
    },
    /**
     * 复制仓库属性
     */
    CopyRepDetail(index) {
      var that = this;
      var copyContent =
        that.tableDataRepDetail[index].repKey +
        ":" +
        that.tableDataRepDetail[index].repValue;
      that.$copyText(copyContent).then(
        function (e) {
          that.$Message.success("复制成功");
        },
        function (e) {
          that.$Message.error("复制失败，请手动复制");
        }
      );
    },
    /**
     * 单选按钮 选择导入
     */
    ChangeRadioUploadType(value) {
      this.formUploadBackup.selectType = value;
      if (value == "2") {
        this.GetBackupList();
      }
    },
    //上传前
    BeforeUpload() {
      this.loadingUploadBackup = true;
      return true;
    },
    //上传成功
    UploadSuccess(res, file, fileList) {
      this.loadingUploadBackup = false;
      var result = res;
      if (result.status == 1) {
        this.$Message.success(result.message);
      } else {
        this.$Message.error(result.message);
      }
    },
    /**
     * 选中文件进行导入
     */
    ClickRowUploadBackup(currentRow, oldCurrentRow) {
      //当前选中的文件
      this.formUploadBackup.fileName = currentRow.fileName;
    },
    ImportRep() {
      var that = this;
      that.loadingImportBackup = true;
      var data = {
        rep_name: that.currentRepName,
        fileName: that.formUploadBackup.fileName,
      };
      that.$axios
        .post("/api.php?c=svnrep&a=ImportRep&t=web", data)
        .then(function (response) {
          that.loadingImportBackup = false;
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
            that.formUploadBackup.errorInfo = result.data;
          } else {
            that.$Message.error(result.message);
            that.formUploadBackup.errorInfo = result.data;
          }
        })
        .catch(function (error) {
          that.loadingImportBackup = false;
          console.log(error);
        });
    },

    /**
     * 编辑仓库名称
     */
    ModalEditRepName(rep_name) {
      //备份旧名称
      this.formRepEdit.old_rep_name = JSON.parse(JSON.stringify(rep_name));
      //设置新名称
      this.formRepEdit.new_rep_name = JSON.parse(JSON.stringify(rep_name));
      //配置标题
      this.titleModalEditRepName = "修改仓库名称-" + rep_name;
      //显示对话框
      this.modalEditRepName = true;
    },
    EditRepName() {
      var that = this;
      var data = {
        old_rep_name: that.formRepEdit.old_rep_name,
        new_rep_name: that.formRepEdit.new_rep_name,
      };
      that.$axios
        .post("/api.php?c=svnrep&a=EditRepName&t=web", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
            that.GetRepList();
          } else {
            that.$Message.error(result.message);
          }
        })
        .catch(function (error) {
          console.log(error);
        });
    },

    /**
     * 删除仓库
     */
    DelRep(rep_name) {
      var that = this;
      that.$Modal.confirm({
        title: "删除仓库-" + rep_name,
        content:
          "确定要删除该仓库吗？<br/>该操作不可逆！<br/>如果该仓库有正在进行的网络传输，可能会删除失败，请注意提示信息！",
        onOk: () => {
          var data = {
            rep_name: rep_name,
          };
          that.$axios
            .post("/api.php?c=svnrep&a=DelRep&t=web", data)
            .then(function (response) {
              var result = response.data;
              if (result.status == 1) {
                that.$Message.success(result.message);
                that.GetRepList();
              } else {
                that.$Message.error(result.message);
              }
            })
            .catch(function (error) {
              console.log(error);
            });
        },
      });
    },
  },
};
</script>

<style >
</style>