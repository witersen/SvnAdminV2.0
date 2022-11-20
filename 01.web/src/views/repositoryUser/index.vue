<template>
  <div>
    <Card :bordered="false" :dis-hover="true">
      <!-- <Alert show-icon
        >SVN用户账户登录本系统 后登录的账号将会顶掉之前登录的账号</Alert
      > -->
      <Row style="margin-bottom: 15px">
        <Col
          type="flex"
          justify="space-between"
          :xs="21"
          :sm="20"
          :md="19"
          :lg="18"
        >
          <Button icon="md-add" type="primary" ghost @click="ModalCreateUser"
            >新建SVN用户</Button
          >
          <Button icon="ios-sync" type="primary" ghost @click="ModalScanPasswd"
            >用户迁入</Button
          >
          <Tooltip
            max-width="250"
            content="1、同步才可获取最新用户列表 
2、手动写入passwd文件的用户需要同步才能登录系统"
            placement="bottom"
            :transfer="true"
          >
            <Button
              icon="ios-sync"
              type="warning"
              ghost
              @click="GetUserList(true)"
              >同步列表</Button
            >
          </Tooltip>
        </Col>
        <Col :xs="3" :sm="4" :md="5" :lg="6">
          <Input
            v-model="searchKeywordUser"
            search
            enter-button
            placeholder="通过SVN用户名、备注搜索..."
            style="width: 100%"
            @on-search="GetUserList()"
        /></Col>
      </Row>
      <Table
        @on-sort-change="SortChangeUser"
        border
        :columns="tableColumnUser"
        :data="tableDataUser"
        :loading="loadingUser"
        size="small"
      >
        <template slot-scope="{ index }" slot="index">
          {{ pageSizeUser * (pageCurrentUser - 1) + index + 1 }}
        </template>
        <template slot-scope="{ row }" slot="svn_user_pass">
          <Input
            :border="false"
            v-model="row.svn_user_pass"
            readonly
            type="password"
            password
          />
        </template>
        <template slot-scope="{ row }" slot="svn_user_status">
          <Switch
            v-model="row.svn_user_status"
            false-color="#ff4949"
            @on-change="(value) => UpdUserStatus(value, row.svn_user_name)"
          >
            <Icon type="md-checkmark" slot="open"></Icon>
            <Icon type="md-close" slot="close"></Icon>
          </Switch>
        </template>
        <template slot-scope="{ row, index }" slot="svn_user_note">
          <Input
            :border="false"
            v-model="tableDataUser[index].svn_user_note"
            @on-blur="UpdUserNote(index, row.svn_user_name)"
          />
        </template>
        <template slot-scope="{ row }" slot="svn_user_rep_list">
          <Button
            type="info"
            size="small"
            @click="ModalSvnUserPriPath(row.svn_user_name)"
            >查看</Button
          >
        </template>
        <template slot-scope="{ row }" slot="online">
          <Tag color="success" v-if="row.online == true">在线</Tag>
          <Tag v-else>离线</Tag>
        </template>
        <template slot-scope="{ row, index }" slot="action">
          <Button
            type="warning"
            size="small"
            @click="ModalEditUserPass(index, row.svn_user_name)"
            >修改</Button
          >
          <Button
            type="error"
            size="small"
            @click="DelUser(index, row.svn_user_name)"
            >删除</Button
          >
        </template>
      </Table>
      <Card :bordered="false" :dis-hover="true">
        <Page
          v-if="totalUser != 0"
          :total="totalUser"
          :current="pageCurrentUser"
          :page-size="pageSizeUser"
          @on-page-size-change="UserPageSizeChange"
          @on-change="UserPageChange"
          size="small"
          show-sizer
        />
      </Card>
    </Card>
    <!-- 对话框-新建SVN用户 -->
    <Modal v-model="modalCreateUser" :draggable="true" title="新建SVN用户">
      <Form :model="formCreateUser" :label-width="80">
        <FormItem label="用户名">
          <Input v-model="formCreateUser.svn_user_name"></Input>
        </FormItem>
        <FormItem>
          <Alert type="warning" show-icon
            >用户名只能包含字母、数字、破折号、下划线、点。</Alert
          >
        </FormItem>
        <FormItem label="密码">
          <Input
            type="password"
            password
            v-model="formCreateUser.svn_user_pass"
          ></Input>
        </FormItem>
        <FormItem label="备注">
          <Input v-model="formCreateUser.svn_user_note"></Input>
        </FormItem>
        <FormItem>
          <Button
            type="primary"
            @click="CreateUser"
            :loading="loadingCreateUser"
            >确定</Button
          >
        </FormItem>
      </Form>
      <div slot="footer">
        <Button type="primary" ghost @click="modalCreateUser = false"
          >取消</Button
        >
      </div>
    </Modal>
    <Modal v-model="modalScanPasswd" :draggable="true" title="步骤一：用户识别">
      <Input
        v-model="tempPasswdContent"
        placeholder="请粘贴 passwd 文件内容

示例：  

[users]
user1=passwd1
user2=passwd2
user3=passwd3"
        :rows="15"
        show-word-limit
        type="textarea"
      />
      <div slot="footer">
        <Button
          type="primary"
          ghost
          @click="ScanPasswd"
          :loading="loadingScanPasswd"
          >识别</Button
        >
      </div>
    </Modal>
    <!-- 对话框-更新SVN用户密码 -->
    <Modal
      v-model="modalEditUserPass"
      :draggable="true"
      :title="titleEditUser"
      @on-ok="UpdUserPass"
    >
      <Form :model="formEditUser" :label-width="80">
        <FormItem label="新密码">
          <Input v-model="formEditUser.svn_user_pass"></Input>
        </FormItem>
        <FormItem>
          <Button
            type="primary"
            @click="UpdUserPass"
            :loading="loadingEditUserPass"
            >确定</Button
          >
        </FormItem>
      </Form>
      <div slot="footer">
        <Button type="primary" ghost @click="modalEditUserPass = false"
          >取消</Button
        >
      </div>
    </Modal>
    <!-- 对话框-查看权限路径列表 -->
    <Modal
      v-model="modalSvnUserPriPath"
      :title="titleSvnUserPriPath"
      :width="800"
      :draggable="true"
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
          <Tooltip
            max-width="250"
            content="同步才可获取最新权限列表"
            placement="bottom"
            :transfer="true"
          >
            <Button
              icon="ios-sync"
              type="warning"
              ghost
              @click="GetSvnUserRepList2(true)"
              >同步列表</Button
            >
          </Tooltip>
        </Col>
        <Col :xs="3" :sm="4" :md="5" :lg="6">
          <Input
            search
            placeholder="通过SVN仓库名搜索..."
            @on-change="GetSvnUserRepList2()"
            v-model="searchKeywordGetSvnUserRepList2"
          />
        </Col>
      </Row>
      <Table
        @on-sort-change="SortChangeUserRep"
        border
        :height="320"
        :loading="loadingUserRep"
        :columns="tableColumnUserRep"
        :data="tableDataUserRep"
        size="small"
      >
        <template slot-scope="{ row }" slot="second_pri">
          <Switch
            v-model="row.second_pri"
            @on-change="
              (value) => UpdSecondpri(value, row.svnn_user_pri_path_id)
            "
          >
            <Icon type="md-checkmark" slot="open"></Icon>
            <Icon type="md-close" slot="close"></Icon>
          </Switch>
        </template>
        <template slot-scope="{ row }" slot="action">
          <Button
            type="info"
            size="small"
            @click="ModalGetSecondpriObject(row.svnn_user_pri_path_id)"
            :disabled="!row.second_pri"
            >配置</Button
          >
        </template>
      </Table>
      <div slot="footer">
        <Button type="primary" ghost @click="modalSvnUserPriPath = false"
          >取消</Button
        >
      </div>
    </Modal>
    <!-- 对话框-二次授权对象 -->
    <Modal
      v-model="modalGetSecondpriObject"
      :draggable="true"
      title="二次授权对象"
    >
      <Row style="margin-bottom: 15px">
        <Col type="flex" justify="space-between" span="12">
          <Button
            icon="md-add"
            type="primary"
            ghost
            @click="modalSvnObject = true"
            >添加成员</Button
          >
        </Col>
        <Col span="12">
          <Input
            search
            placeholder="通过对象名称搜索..."
            v-model="searchKeywordSecondpriObject"
            @on-change="GetSecondpriObjectList"
          />
        </Col>
      </Row>
      <Table
        border
        :height="310"
        size="small"
        :loading="loadingGetSecondpriObject"
        :columns="tableColumnSecondpriObject"
        :data="tableDataSecondpriObject"
        style="margin-top: 20px"
      >
        <template slot-scope="{ row }" slot="objectType">
          <Tag
            color="blue"
            v-if="row.objectType == 'user'"
            style="width: 90px; text-align: center"
            >SVN用户</Tag
          >
          <Tag
            color="geekblue"
            v-if="row.objectType == 'group'"
            style="width: 90px; text-align: center"
            >SVN分组</Tag
          >
          <Tag
            color="purple"
            v-if="row.objectType == 'aliase'"
            style="width: 90px; text-align: center"
            >SVN别名</Tag
          >
        </template>
        <template slot-scope="{ row }" slot="action">
          <Button
            type="error"
            size="small"
            @click="DelSecondpriObject(row.svn_second_pri_id)"
            >移除</Button
          >
        </template>
      </Table>
      <div slot="footer">
        <Button type="primary" ghost @click="modalGetSecondpriObject = false"
          >取消</Button
        >
      </div>
    </Modal>
    <!-- SVN对象列表组件 -->
    <ModalSvnObject
      :propModalSvnObject="modalSvnObject"
      :propChangeParentModalObject="CloseModalObject"
      :propSendParentObject="CreateSecondpriObject"
      :propShowSvnAllTab="false"
      :propShowSvnAuthenticatedTab="false"
      :propShowSvnAnonymousTab="false"
    />
  </div>
</template>

<script>
//SVN对象列表组件
import ModalSvnObject from "@/components/modalSvnObject.vue";

export default {
  data() {
    return {
      /**
       * 分页数据
       */
      //用户
      pageCurrentUser: 1,
      pageSizeUser: 20,
      totalUser: 0,

      /**
       * 搜索关键词
       */
      searchKeywordUser: "",
      //二次授权对象
      searchKeywordSecondpriObject: "",
      //根据SVN仓库名称搜索用户有权限的路径列表
      searchKeywordGetSvnUserRepList2: "",

      /**
       * 排序数据
       */
      //SVN用户列表
      sortNameUserRepList: "",
      sortTypeUserRepList: "asc",

      //SVN用户有权限的仓库路径列表
      sortNameUserList: "svn_user_name",
      sortTypeUserList: "asc",

      /**
       * 加载
       */
      //用户列表
      loadingUser: true,
      //创建用户
      loadingCreateUser: false,
      //修改用户密码
      loadingEditUserPass: false,
      //识别 passwd 文件
      loadingScanPasswd: false,
      //用户有权限的仓库列表
      loadingUserRep: true,
      //获取二次授权对象
      loadingGetSecondpriObject: true,

      /**
       * 临时变量
       */
      //输入的 passwd 文件内容
      tempPasswdContent: "",
      //当前选中的svn用户名
      currentSvnUserName: "",
      //当前选中的SVN用户权限路径id
      currentSvnUserPriPathId: 0,

      /**
       * 对话框
       */
      //新建仓库
      modalCreateUser: false,
      //编辑仓库信息
      modalEditUserPass: false,
      //识别 passwd 文件
      modalScanPasswd: false,
      //SVN用户权限路径列表
      modalSvnUserPriPath: false,
      //SVN对象列表组件
      modalSvnObject: false,
      //二次授权对象
      modalGetSecondpriObject: false,

      /**
       * 表单
       */
      //新建用户
      formCreateUser: {
        svn_user_name: "",
        svn_user_pass: "",
        svn_user_note: "",
      },
      //编辑用户
      formEditUser: {
        svn_user_name: "",
        svn_user_pass: "",
        index: -1,
      },

      /**
       * 标题
       */
      //更新SVN用户密码
      titleEditUser: "",
      //SVN用户有权限的仓库列表
      titleSvnUserPriPath: "",

      /**
       * 表格
       */
      //仓库信息
      tableColumnUser: [
        {
          title: "序号",
          slot: "index",
          fixed: "left",
          minWidth: 80,
        },
        {
          title: "用户名",
          key: "svn_user_name",
          tooltip: true,
          sortable: "custom",
          minWidth: 120,
        },
        {
          title: "密码",
          slot: "svn_user_pass",
          minWidth: 145,
        },
        {
          title: "启用状态",
          key: "svn_user_status",
          slot: "svn_user_status",
          sortable: "custom",
          minWidth: 120,
        },
        {
          title: "备注信息",
          slot: "svn_user_note",
          minWidth: 120,
        },
        {
          title: "有权路径",
          slot: "svn_user_rep_list",
          minWidth: 120,
        },
        {
          title: "上次登录",
          key: "svn_user_last_login",
          tooltip: true,
          sortable: "custom",
          minWidth: 150,
        },
        {
          title: "在线状态",
          slot: "online",
          minWidth: 90,
        },
        {
          title: "其它",
          slot: "action",
          minWidth: 180,
        },
      ],
      tableDataUser: [],
      //svn用户有权限的仓库路径
      tableDataUserRep: [],
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
          slot: "second_pri",
          minWidth: 120,
          renderHeader(h, params) {
            return h(
              "tooltip",
              {
                props: {
                  transfer: true,
                  placement: "left",
                  "max-width": "400",
                },
              },
              [
                h("span", [
                  h("span", "二次授权状态"),
                  h("Icon", {
                    props: {
                      type: "ios-help-circle-outline",
                      size: "15",
                    },
                    class: { iconClass: true },
                  }),
                ]),
                h(
                  "div",
                  {
                    slot: "content",
                    style: {
                      fontSize: "14px",
                    },
                  },
                  [
                    h("p", "二次授权可赋予普通SVN用户分配路径权限的能力"),
                    h("p", " "),
                    h(
                      "p",
                      {
                        style: {
                          color: "#479af1",
                        },
                      },
                      "举例来讲"
                    ),
                    h("p", " "),
                    h("p", "projects 仓库包含众多项目 project1 project2 ..."),
                    h("p", "user1 user2 user3 负责项目 project1"),
                    h("p", "user1 为项目组长"),
                    h("p", "user2 为研发同学"),
                    h("p", "user2 为测试同学"),
                    h("p", " "),
                    h("p", "(1) 管理员为 user1 开启此路径二次授权开关"),
                    h(
                      "p",
                      "(2) 管理员选择二次授权管理对象(此处为 user2 user3)"
                    ),
                    h("p", " "),
                    h("p", "user1 可随意为管理对象授权而无需管理员介入"),
                    h("p", " "),
                    h("p", "关闭二次授权将会同步清理配置的二次授权对象"),
                  ]
                ),
              ]
            );
          },
        },
        {
          title: "二次授权对象",
          slot: "action",
          width: 180,
          // fixed:"right"
        },
      ],
      //二次授权对象
      tableColumnSecondpriObject: [
        {
          title: "对象类型",
          slot: "objectType",
          // width: 125,
        },
        {
          title: "对象名称",
          key: "objectName",
          tooltip: true,
          // width: 115,
        },
        {
          title: "操作",
          slot: "action",
        },
      ],
      tableDataSecondpriObject: [],
    };
  },
  components: {
    ModalSvnObject,
  },
  computed: {},
  created() {},
  mounted() {
    this.GetUserList();
  },
  methods: {
    /**
     * 子组件传递变量给父组件
     */
    CloseModalObject() {
      this.modalSvnObject = false;
    },
    /**
     * 每页数量改变
     */
    UserPageSizeChange(value) {
      //设置每页条数
      this.pageSizeUser = value;
      this.GetUserList();
    },
    /**
     * 页码改变
     */
    UserPageChange(value) {
      //设置当前页数
      this.pageCurrentUser = value;
      this.GetUserList();
    },
    GetUserList(sync = false, page = true) {
      var that = this;
      that.loadingUser = true;
      that.tableDataUser = [];
      // that.totalUser = 0;
      var data = {
        pageSize: that.pageSizeUser,
        currentPage: that.pageCurrentUser,
        searchKeyword: that.searchKeywordUser,
        sortName: that.sortNameUserList,
        sortType: that.sortTypeUserList,
        sync: sync,
        page: page,
      };
      that.$axios
        .post("/api.php?c=Svnuser&a=GetUserList&t=web", data)
        .then(function (response) {
          that.loadingUser = false;
          var result = response.data;
          if (result.status == 1) {
            // that.$Message.success(result.message);
            that.tableDataUser = result.data.data;
            that.totalUser = result.data.total;
          } else {
            that.$Message.error({ content: result.message, duration: 2 });
          }
        })
        .catch(function (error) {
          that.loadingUser = false;
          console.log(error);
          that.$Message.error("出错了 请联系管理员！");
        });
    },
    /**
     * 启用或禁用用户
     */
    UpdUserStatus(status, svn_user_name) {
      var that = this;
      var data = {
        svn_user_name: svn_user_name,
        status: status,
      };
      that.$axios
        .post("/api.php?c=Svnuser&a=UpdUserStatus&t=web", data)
        .then(function (response) {
          var result = response.data;
          that.GetUserList();
          if (result.status == 1) {
            that.$Message.success(result.message);
          } else {
            that.$Message.error({ content: result.message, duration: 2 });
          }
        })
        .catch(function (error) {
          console.log(error);
          that.$Message.error("出错了 请联系管理员！");
        });
    },
    /**
     * 编辑用户备注信息
     */
    UpdUserNote(index, svn_user_name) {
      var that = this;
      var data = {
        svn_user_name: svn_user_name,
        svn_user_note: that.tableDataUser[index].svn_user_note,
      };
      that.$axios
        .post("/api.php?c=Svnuser&a=UpdUserNote&t=web", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
          } else {
            that.$Message.error({ content: result.message, duration: 2 });
          }
        })
        .catch(function (error) {
          console.log(error);
          that.$Message.error("出错了 请联系管理员！");
        });
    },
    /**
     * 用户排序
     */
    SortChangeUser(value) {
      this.sortNameUserList = value.key;
      if (value.order == "desc" || value.order == "asc") {
        this.sortTypeUserList = value.order;
      }
      this.GetUserList();
    },
    /**
     * 新建用户
     */
    ModalCreateUser() {
      this.modalCreateUser = true;
    },
    CreateUser() {
      var that = this;
      that.loadingCreateUser = true;
      var data = {
        svn_user_name: that.formCreateUser.svn_user_name,
        svn_user_pass: that.formCreateUser.svn_user_pass,
        svn_user_note: that.formCreateUser.svn_user_note,
      };
      that.$axios
        .post("/api.php?c=Svnuser&a=CreateUser&t=web", data)
        .then(function (response) {
          that.loadingCreateUser = false;
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
            that.modalCreateUser = false;
            that.GetUserList();
          } else {
            that.$Message.error({ content: result.message, duration: 2 });
          }
        })
        .catch(function (error) {
          that.loadingCreateUser = false;
          console.log(error);
          that.$Message.error("出错了 请联系管理员！");
        });
    },
    /**
     * 识别 passwd 文件
     */
    ModalScanPasswd() {
      this.modalScanPasswd = true;
    },
    ScanPasswd() {
      var that = this;
      that.loadingScanPasswd = true;
      var data = {
        passwdContent: that.tempPasswdContent,
      };
      that.$axios
        .post("/api.php?c=Svnuser&a=ScanPasswd&t=web", data)
        .then(function (response) {
          that.loadingScanPasswd = false;
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
          } else {
            that.$Message.error({ content: result.message, duration: 2 });
          }
        })
        .catch(function (error) {
          that.loadingScanPasswd = false;
          console.log(error);
          that.$Message.error("出错了 请联系管理员！");
        });
    },
    /**
     * 修改用户密码
     */
    ModalEditUserPass(index, svn_user_name) {
      //设置标题
      this.titleEditUser = "修改密码 - " + svn_user_name;
      //设置选中用户
      this.formEditUser.svn_user_name = svn_user_name;
      //设置密码同步到输入框
      this.formEditUser.svn_user_pass = this.tableDataUser[index].svn_user_pass;
      //设置选中下标
      this.formEditUser.index = index;
      //显示对话框
      this.modalEditUserPass = true;
    },
    UpdUserPass() {
      var that = this;
      that.loadingEditUserPass = true;
      var data = {
        svn_user_name: that.formEditUser.svn_user_name,
        svn_user_pass: that.formEditUser.svn_user_pass,
        svn_user_status:
          that.tableDataUser[that.formEditUser.index].svn_user_status,
      };
      that.$axios
        .post("/api.php?c=Svnuser&a=UpdUserPass&t=web", data)
        .then(function (response) {
          that.loadingEditUserPass = false;
          var result = response.data;
          if (result.status == 1) {
            that.modalEditUserPass = false;
            that.$Message.success(result.message);
            that.GetUserList();
          } else {
            that.$Message.error({ content: result.message, duration: 2 });
          }
        })
        .catch(function (error) {
          that.loadingEditUserPass = false;
          console.log(error);
          that.$Message.error("出错了 请联系管理员！");
        });
    },
    /**
     * 删除SVN用户
     */
    DelUser(index, svn_user_name) {
      var that = this;
      that.$Modal.confirm({
        render: (h) => {
          return h("div", [
            h(
              "div",
              {
                class: { "modal-title": true },
                style: {
                  display: "flex",
                  height: "42px",
                  alignItems: "center",
                },
              },
              [
                h("Icon", {
                  props: {
                    type: "ios-help-circle",
                  },
                  style: {
                    width: "28px",
                    height: "28px",
                    fontSize: "28px",
                    color: "#f90",
                  },
                }),
                h(
                  "tooltip",
                  {
                    props: {
                      transfer: true,
                      placement: "bottom",
                      "max-width": "400",
                    },
                  },
                  [
                    h("span", {
                      style: {
                        marginLeft: "12px",
                        fontSize: "16px",
                        color: "#17233d",
                        fontWeight: 500,
                        whiteSpace: "nowrap",
                        overflow: "hidden",
                        textOverflow: "ellipsis",
                        width: "285px",
                        display: "inline-block",
                      },
                      domProps: {
                        innerHTML: "删除SVN用户 - " + svn_user_name,
                      },
                    }),
                    h(
                      "div",
                      {
                        slot: "content",
                        style: {
                          fontSize: "10px",
                        },
                      },
                      [
                        h(
                          "p",
                          {
                            style: {
                              fontSize: "15px",
                            },
                          },
                          "删除SVN用户 - " + svn_user_name
                        ),
                      ]
                    ),
                  ]
                ),
              ]
            ),
            h(
              "div",
              {
                class: { "modal-content": true },
                style: { paddingLeft: "40px" },
              },
              [
                h("p", {
                  style: { marginBottom: "15px" },
                  domProps: {
                    innerHTML:
                      "确定要删除该用户吗？<br/>将会从所有仓库和分组下将该用户移除！<br/>该操作不可逆！",
                  },
                }),
              ]
            ),
          ]);
        },
        onOk: () => {
          var data = {
            svn_user_name: svn_user_name,
            svn_user_status: that.tableDataUser[index].svn_user_status,
          };
          that.$axios
            .post("/api.php?c=Svnuser&a=DelUser&t=web", data)
            .then(function (response) {
              var result = response.data;
              if (result.status == 1) {
                that.$Message.success(result.message);
                that.GetUserList();
              } else {
                that.$Message.error({ content: result.message, duration: 2 });
              }
            })
            .catch(function (error) {
              console.log(error);
              that.$Message.error("出错了 请联系管理员！");
            });
        },
      });
    },
    /**
     * 查看权限路径列表
     */
    ModalSvnUserPriPath(svn_user_name) {
      this.titleSvnUserPriPath = "用户有权限路径列表 - " + svn_user_name;
      this.modalSvnUserPriPath = true;
      this.currentSvnUserName = svn_user_name;
      this.GetSvnUserRepList2();
    },
    /**
     * 管理人员获取SVN用户有权限的仓库路径列表
     */
    GetSvnUserRepList2(sync = false) {
      var that = this;
      that.loadingUserRep = true;
      that.tableDataUserRep = [];
      // that.totalUserRep = 0;
      var data = {
        searchKeyword: that.searchKeywordGetSvnUserRepList2,
        sortType: that.sortTypeUserRepList,
        sync: sync,
        page: false,
        userName: that.currentSvnUserName,
      };
      that.$axios
        .post("/api.php?c=Svnrep&a=GetSvnUserRepList2&t=web", data)
        .then(function (response) {
          that.loadingUserRep = false;
          var result = response.data;
          if (result.status == 1) {
            // that.$Message.success(result.message);
            that.tableDataUserRep = result.data.data;
            // that.totalUserRep = result.data.total;
          } else {
            that.$Message.error({ content: result.message, duration: 2 });
          }
        })
        .catch(function (error) {
          that.loadingUserRep = false;
          console.log(error);
          that.$Message.error("出错了 请联系管理员！");
        });
    },
    /**
     * 用户仓库排序
     */
    SortChangeUserRep(value) {
      if (value.order == "desc" || value.order == "asc") {
        this.sortTypeUserRepList = value.order;
      }
      this.GetSvnUserRepList2();
    },
    /**
     * 设置二次授权状态
     */
    UpdSecondpri(type, svnn_user_pri_path_id) {
      var that = this;
      var data = {
        svnn_user_pri_path_id: svnn_user_pri_path_id,
        type: type ? 1 : 0,
      };
      that.$axios
        .post("/api.php?c=Secondpri&a=UpdSecondpri&t=web", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
            that.GetSvnUserRepList2();
          } else {
            that.$Message.error({ content: result.message, duration: 2 });
          }
        })
        .catch(function (error) {
          console.log(error);
          that.$Message.error("出错了 请联系管理员！");
        });
    },
    /**
     * 配置二次授权可管理对象
     */
    ModalGetSecondpriObject(svnn_user_pri_path_id) {
      this.modalGetSecondpriObject = true;
      this.currentSvnUserPriPathId = svnn_user_pri_path_id;
      this.GetSecondpriObjectList();
    },
    /**
     * 获取二次授权对象
     */
    GetSecondpriObjectList() {
      var that = this;
      that.loadingGetSecondpriObject = true;
      that.tableDataSecondpriObject = [];
      var data = {
        searchKeyword: that.searchKeywordSecondpriObject,
        svnn_user_pri_path_id: that.currentSvnUserPriPathId,
      };
      that.$axios
        .post("/api.php?c=Secondpri&a=GetSecondpriObjectList&t=web", data)
        .then(function (response) {
          that.loadingGetSecondpriObject = false;
          var result = response.data;
          if (result.status == 1) {
            // that.$Message.success(result.message);
            that.tableDataSecondpriObject = result.data;
          } else {
            that.$Message.error({ content: result.message, duration: 2 });
          }
        })
        .catch(function (error) {
          that.loadingGetSecondpriObject = false;
          console.log(error);
          that.$Message.error("出错了 请联系管理员！");
        });
    },
    /**
     * 添加二次授权可管理对象
     */
    CreateSecondpriObject(objectType, objectName) {
      var that = this;
      var data = {
        svnn_user_pri_path_id: that.currentSvnUserPriPathId,
        objectType: objectType,
        objectName: objectName,
      };
      that.$axios
        .post("/api.php?c=Secondpri&a=CreateSecondpriObject&t=web", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
            that.modalSvnObject = false;
            that.GetSecondpriObjectList();
          } else {
            that.$Message.error({ content: result.message, duration: 2 });
          }
        })
        .catch(function (error) {
          console.log(error);
          that.$Message.error("出错了 请联系管理员！");
        });
    },
    /**
     * 删除二次授权可管理对象
     */
    DelSecondpriObject(svn_second_pri_id) {
      var that = this;
      var data = {
        svn_second_pri_id: svn_second_pri_id,
      };
      that.$axios
        .post("/api.php?c=Secondpri&a=DelSecondpriObject&t=web", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
            that.GetSecondpriObjectList();
          } else {
            that.$Message.error({ content: result.message, duration: 2 });
          }
        })
        .catch(function (error) {
          console.log(error);
          that.$Message.error("出错了 请联系管理员！");
        });
    },
  },
};
</script>

<style >
</style>