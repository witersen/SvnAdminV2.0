<template>
  <div>
    <!-- 对话框-对象列表 -->
    <Modal
      v-model="modalSvnObject"
      :draggable="true"
      @on-visible-change="ChangeModalVisible"
      title="对象列表"
    >
      <Tabs size="small" @on-click="ClickRepPathPriTab">
        <TabPane :label="custom_tab_svn_user" name="user" v-if="showSvnUserTab">
          <Row style="margin-bottom: 15px">
            <Col type="flex" justify="space-between" span="12">
              <Tooltip
                max-width="250"
                content="手动刷新才可获取最新用户列表"
                placement="bottom"
                :transfer="true"
              >
                <Button
                  icon="ios-sync"
                  type="warning"
                  ghost
                  @click="GetAllUsers(true)"
                  >手动刷新</Button
                >
              </Tooltip>
            </Col>
            <Col span="12">
              <Input
                search
                placeholder="通过用户名搜索..."
                v-model="searchKeywordUser"
                @on-change="GetAllUsers()"
              />
            </Col>
          </Row>
          <Table
            highlight-row
            border
            :height="250"
            size="small"
            :loading="loadingAllUsers"
            :columns="tableColumnAllUsers"
            :data="tableDataAllUsers"
            style="margin-bottom: 10px"
          >
            <template slot-scope="{ row }" slot="svn_user_status">
              <Tag
                color="blue"
                v-if="row.svn_user_status == '1' || row.svn_user_status == 1"
                >正常</Tag
              >
              <Tag color="red" v-else>禁用</Tag>
            </template>
            <template slot-scope="{ row }" slot="action">
              <Tag
                color="primary"
                @click.native="propSendParentObject('user', row.svn_user_name)"
                >选择</Tag
              >
            </template>
          </Table>
        </TabPane>
        <TabPane
          :label="custom_tab_svn_group"
          name="group"
          v-if="showSvnGroupTab"
        >
          <Row style="margin-bottom: 15px">
            <Col type="flex" justify="space-between" span="12">
              <Tooltip
                max-width="250"
                content="手动刷新才可获取最新分组列表"
                placement="bottom"
                :transfer="true"
              >
                <Button
                  icon="ios-sync"
                  type="warning"
                  ghost
                  @click="GetAllGroups(true)"
                  >手动刷新</Button
                >
              </Tooltip>
            </Col>
            <Col span="12">
              <Input
                search
                placeholder="通过分组名搜索..."
                v-model="searchKeywordGroup"
                @on-change="GetAllGroups()"
              />
            </Col>
          </Row>
          <Table
            highlight-row
            border
            :height="250"
            size="small"
            :loading="loadingAllGroups"
            :columns="tableColumnAllGroups"
            :data="tableDataAllGroups"
            style="margin-bottom: 10px"
          >
            <template slot-scope="{ row }" slot="member">
              <Tag
                color="primary"
                @click.native="ModalGetGroupMember(row.svn_group_name)"
                >成员</Tag
              >
            </template>
            <template slot-scope="{ row }" slot="action">
              <Tag
                color="primary"
                @click.native="
                  propSendParentObject('group', row.svn_group_name)
                "
                >选择</Tag
              >
            </template>
          </Table>
        </TabPane>
        <TabPane
          :label="custom_tab_svn_aliase"
          name="aliase"
          v-if="showSvnAliaseTab"
        >
          <Row style="margin-bottom: 15px">
            <Col type="flex" justify="space-between" span="12">
              <!-- <Tooltip
                max-width="250"
                content="手动刷新才可获取最新别名列表"
                placement="bottom"
                :transfer="true"
              >
                <Button
                  icon="ios-sync"
                  type="warning"
                  ghost
                  @click="GetAllAliases(true)"
                  >手动刷新</Button
                >
              </Tooltip> -->
            </Col>
            <Col span="12">
              <Input
                search
                placeholder="通过别名搜索..."
                v-model="searchKeywordAliase"
                @on-change="GetAllAliases()"
              />
            </Col>
          </Row>
          <Table
            highlight-row
            border
            :height="250"
            size="small"
            :columns="tableColumnAllAliases"
            :data="tableDataAllAliases"
            style="margin-bottom: 10px"
          >
            <template slot-scope="{ row }" slot="disabled">
              <Tag color="blue" v-if="row.disabled == '0' || row.disabled == 0"
                >正常</Tag
              >
              <Tag color="red" v-else>禁用</Tag>
            </template>
            <template slot-scope="{ row }" slot="action">
              <Tag
                color="primary"
                @click.native="propSendParentObject('aliase', row.aliaseName)"
                >选择</Tag
              >
            </template>
          </Table>
        </TabPane>
        <TabPane :label="custom_tab_svn_all" name="*" v-if="showSvnAllTab">
          <Row style="margin-bottom: 15px">
            <Col type="flex" justify="space-between" span="12"> </Col>
            <Col span="12">
              <Input search disabled placeholder="通过符号搜索..." />
            </Col>
          </Row>
          <Table
            highlight-row
            border
            :height="250"
            size="small"
            :columns="tableColumnAll"
            :data="tableDataAll"
            style="margin-bottom: 10px"
          >
            <template slot="action" slot-scope="{ index }">
              <template v-if="false">{{ index }}</template>
              <Tag
                color="primary"
                @click.native="propSendParentObject('*', '*')"
                >选择</Tag
              >
            </template>
          </Table>
        </TabPane>
        <TabPane
          :label="custom_tab_svn_authenticated"
          name="$authenticated"
          v-if="showSvnAuthenticatedTab"
        >
          <Row style="margin-bottom: 15px">
            <Col type="flex" justify="space-between" span="12"> </Col>
            <Col span="12">
              <Input search disabled placeholder="通过符号搜索..." />
            </Col>
          </Row>
          <Table
            highlight-row
            border
            :height="250"
            size="small"
            :columns="tableColumnAuthenticated"
            :data="tableDataAuthenticated"
            style="margin-bottom: 10px"
          >
            <template slot="action" slot-scope="{ index }">
              <template v-if="false">{{ index }}</template>
              <Tag
                color="primary"
                @click.native="
                  propSendParentObject('$authenticated', '$authenticated')
                "
                >选择</Tag
              >
            </template>
          </Table>
        </TabPane>
        <TabPane
          :label="custom_tab_svn_anonymous"
          name="$anonymous"
          v-if="showSvnAnonymousTab"
        >
          <Row style="margin-bottom: 15px">
            <Col type="flex" justify="space-between" span="12"> </Col>
            <Col span="12">
              <Input search disabled placeholder="通过符号搜索..." />
            </Col>
          </Row>
          <Table
            highlight-row
            border
            :height="250"
            size="small"
            :columns="tableColumnAnonymous"
            :data="tableDataAnonymous"
            style="margin-bottom: 10px"
          >
            <template slot="action" slot-scope="{ index }">
              <template v-if="false">{{ index }}</template>
              <Tag
                color="primary"
                @click.native="propSendParentObject('$anonymous', '$anonymous')"
                >选择</Tag
              >
            </template>
          </Table>
        </TabPane>
      </Tabs>
      <Alert show-icon>授权的对象权限默认为读写</Alert>
      <!-- <Alert show-icon
        >如果对象信息用户等不是最新，需要回到对应的导航下刷新</Alert
      > -->
      <div slot="footer">
        <Button type="primary" ghost @click="CloseModalObject">取消</Button>
      </div>
    </Modal>
    <!-- 对话框-分组成员列表 -->
    <Modal
      v-model="modalGetGroupMember"
      :draggable="true"
      :title="titleGetGroupMember"
    >
      <Row style="margin-bottom: 15px">
        <Col type="flex" justify="space-between" span="12"> </Col>
        <Col span="12">
          <Input
            search
            placeholder="通过对象名称搜索..."
            v-model="searchKeywordGroupMember"
            @on-change="GetGroupMember"
          />
        </Col>
      </Row>
      <Table
        border
        :height="310"
        size="small"
        :loading="loadingGetGroupMember"
        :columns="tableColumnGroupMember"
        :data="tableDataGroupMember"
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
      </Table>
      <div slot="footer">
        <Button type="primary" ghost @click="modalGetGroupMember = false"
          >取消</Button
        >
      </div>
    </Modal>
  </div>
</template>

<script>
export default {
  props: {
    //父组件控制子组件显示状态
    propModalSvnObject: {
      type: Boolean,
      default: false,
    },
    //向父组件发送对话框状态变量
    propChangeParentModalObject: {
      type: Function,
    },
    //向父组件发送选择数据
    propSendParentObject: {
      type: Function,
    },
    /**
     * tab显示
     */
    propShowSvnUserTab: {
      type: Boolean,
      default: true,
    },
    propShowSvnGroupTab: {
      type: Boolean,
      default: true,
    },
    propShowSvnAliaseTab: {
      type: Boolean,
      default: true,
    },
    propShowSvnAllTab: {
      type: Boolean,
      default: true,
    },
    propShowSvnAuthenticatedTab: {
      type: Boolean,
      default: true,
    },
    propShowSvnAnonymousTab: {
      type: Boolean,
      default: true,
    },
  },
  data() {
    return {
      /**
       * 特定风格的路径授权弹出框的 tab
       */
      custom_tab_svn_user: (h) => {
        return h("div", [
          h(
            "span",
            {
              style: {
                color: "#1890ff",
              },
            },
            "SVN用户"
          ),
        ]);
      },
      custom_tab_svn_group: (h) => {
        return h("div", [
          h(
            "span",
            {
              style: {
                color: "#2f54eb",
              },
            },
            "SVN分组"
          ),
        ]);
      },
      custom_tab_svn_aliase: (h) => {
        return h("div", [
          h(
            "span",
            {
              style: {
                color: "#722ed1",
              },
            },
            "SVN别名"
          ),
        ]);
      },
      custom_tab_svn_all: (h) => {
        return h("div", [
          h(
            "span",
            {
              style: {
                color: "#f5222d",
              },
            },
            "所有人"
          ),
        ]);
      },
      custom_tab_svn_authenticated: (h) => {
        return h("div", [
          h(
            "span",
            {
              style: {
                color: "#eb2f96",
              },
            },
            "所有已认证者"
          ),
        ]);
      },
      custom_tab_svn_anonymous: (h) => {
        return h("div", [
          h(
            "span",
            {
              style: {
                color: "#fa541c",
              },
            },
            "所有匿名者"
          ),
        ]);
      },

      /**
       * 关键词
       */
      searchKeywordAliase: "",
      searchKeywordGroup: "",
      searchKeywordUser: "",
      //搜索分组的成员的列表
      searchKeywordGroupMember: "",

      /**
       * 对话框
       */
      //显示路径授权对话框
      modalSvnObject: this.propModalSvnObject,
      //查看分组的成员列表
      modalGetGroupMember: false,

      /**
       * 标题
       */
      //分组成员对话框的标题
      titleGetGroupMember: "",

      /**
       * 显示状态
       */
      showSvnUserTab: this.propShowSvnUserTab,
      showSvnGroupTab: this.propShowSvnGroupTab,
      showSvnAliaseTab: this.propShowSvnAliaseTab,
      showSvnAllTab: this.propShowSvnAllTab,
      showSvnAuthenticatedTab: this.propShowSvnAuthenticatedTab,
      showSvnAnonymousTab: this.propShowSvnAnonymousTab,

      /**
       * 加载
       */
      //全部的SVN用户列表
      loadingAllUsers: true,
      //全部的SVN分组列表
      loadingAllGroups: true,
      //全部的SVN别名列表
      loadingAllAliases: true,
      //获取分组成员列表
      loadingGetGroupMember: true,

      //对象列表-SVN用户列表
      tableColumnAllUsers: [
        {
          title: "用户名",
          key: "svn_user_name",
          tooltip: true,
        },
        {
          title: "用户状态",
          slot: "svn_user_status",
        },
        {
          title: "备注信息",
          key: "svn_user_note",
          tooltip: true,
        },
        {
          title: "操作",
          slot: "action",
          width: 90,
        },
      ],
      tableDataAllUsers: [],
      //对象列表-SVN分组列表
      tableColumnAllGroups: [
        {
          title: "分组名",
          key: "svn_group_name",
          tooltip: true,
        },
        {
          title: "备注信息",
          key: "svn_group_note",
          tooltip: true,
        },
        {
          title: "成员",
          slot: "member",
        },
        {
          title: "操作",
          slot: "action",
        },
      ],
      tableDataAllGroups: [],
      //对象列表-SVN别名列表
      tableColumnAllAliases: [
        {
          title: "别名",
          key: "aliaseName",
          tooltip: true,
        },
        {
          title: "别名内容",
          key: "aliaseCon",
          tooltip: true,
        },
        {
          title: "操作",
          slot: "action",
        },
      ],
      tableDataAllAliases: [],
      //对象列表-所有人
      tableColumnAll: [
        {
          title: "所有人",
          key: "all",
        },
        {
          title: "操作",
          slot: "action",
        },
      ],
      tableDataAll: [
        {
          all: "*",
        },
      ],
      //对象列表-所有已认证者
      tableColumnAuthenticated: [
        {
          title: "所有已认证者",
          key: "authenticated",
        },
        {
          title: "操作",
          slot: "action",
        },
      ],
      tableDataAuthenticated: [
        {
          authenticated: "$authenticated",
        },
      ],
      //对象列表-所有匿名者
      tableColumnAnonymous: [
        {
          title: "所有匿名者",
          key: "anonymous",
        },
        {
          title: "操作",
          slot: "action",
        },
      ],
      tableDataAnonymous: [
        {
          anonymous: "$anonymous",
        },
      ],
      //分组的成员列表
      tableDataGroupMember: [],
      tableColumnGroupMember: [
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
      ],
    };
  },
  watch: {
    //父组件控制子组件显示状态
    propModalSvnObject: function (value) {
      this.modalSvnObject = value;
      if (value) {
        this.GetAllUsers();
      }
    },
    propShowSvnUserTab: function (value) {
      this.showSvnUserTab = value;
    },
    propShowSvnGroupTab: function (value) {
      this.showSvnGroupTab = value;
    },
    propShowSvnAliaseTab: function (value) {
      this.showSvnAliaseTab = value;
    },
    propShowSvnAllTab: function (value) {
      this.showSvnAllTab = value;
    },
    propShowSvnAuthenticatedTab: function (value) {
      this.showSvnAuthenticatedTab = value;
    },
    propShowSvnAnonymousTab: function (value) {
      this.showSvnAnonymousTab = value;
    },
  },
  methods: {
    /**
     * 调用父组件方法
     * 更改父组件的变量状态
     */
    CloseModalObject() {
      this.modalSvnObject = false;
      this.propChangeParentModalObject();
    },
    /**
     * Modal右上角叉号触发父组件修改变量状态
     */
    ChangeModalVisible(value) {
      if (!value) {
        this.propChangeParentModalObject();
      }
    },
    /**
     * 点击对象列表弹出框下的 tab 触发
     */
    ClickRepPathPriTab(name) {
      switch (name) {
        case "user":
          this.GetAllUsers();
          break;
        case "group":
          this.GetAllGroups();
          break;
        case "aliase":
          this.GetAllAliases();
          break;
        case "*":
          //xxx
          break;
        case "$authenticated":
          //xxx
          break;
        case "$anonymous":
          //xxx
          break;
        default:
          break;
      }
    },
    /**
     * 获取所有的SVN用户列表
     */
    GetAllUsers(sync = false) {
      var that = this;
      //清空上次数据
      that.tableDataAllUsers = [];
      //开始加载动画
      that.loadingAllUsers = true;
      var data = {
        searchKeyword: that.searchKeywordUser,
        sortName: "svn_user_name",
        sortType: "asc",
        sync: sync,
        page: false,
      };
      that.$axios
        .post("/api.php?c=Svnuser&a=GetUserList&t=web", data)
        .then(function (response) {
          that.loadingAllUsers = false;
          var result = response.data;
          if (result.status == 1) {
            that.tableDataAllUsers = result.data.data;
          } else {
            that.$Message.error({ content: result.message, duration: 2 });
          }
        })
        .catch(function (error) {
          that.loadingAllUsers = false;
          console.log(error);
          that.$Message.error("出错了 请联系管理员！");
        });
    },
    /**
     * 获取所有的SVN分组列表
     */
    GetAllGroups(sync = false) {
      var that = this;
      //清空上次数据
      that.tableDataAllGroups = [];
      //开始加载动画
      that.loadingAllGroups = true;
      var data = {
        searchKeyword: that.searchKeywordGroup,
        sortName: "svn_group_name",
        sortType: "asc",
        sync: sync,
        page: false,
      };
      that.$axios
        .post("/api.php?c=Svngroup&a=GetGroupList&t=web", data)
        .then(function (response) {
          that.loadingAllGroups = false;
          var result = response.data;
          if (result.status == 1) {
            that.tableDataAllGroups = result.data.data;
          } else {
            that.$Message.error({ content: result.message, duration: 2 });
          }
        })
        .catch(function (error) {
          that.loadingAllGroups = false;
          console.log(error);
          that.$Message.error("出错了 请联系管理员！");
        });
    },
    /**
     * 获取所有的别名列表
     */
    GetAllAliases(sync = false) {
      var that = this;
      //清空上次数据
      that.tableDataAllAliases = [];
      //开始加载动画
      that.loadingAllAliases = true;
      var data = {
        sync: sync,
        searchKeywordAliase: that.searchKeywordAliase,
      };
      that.$axios
        .post("/api.php?c=Svnaliase&a=GetAllAliaseList&t=web", data)
        .then(function (response) {
          that.loadingAllAliases = false;
          var result = response.data;
          if (result.status == 1) {
            that.tableDataAllAliases = result.data;
          } else {
            that.$Message.error({ content: result.message, duration: 2 });
          }
        })
        .catch(function (error) {
          that.loadingAllAliases = false;
          console.log(error);
          that.$Message.error("出错了 请联系管理员！");
        });
    },

    /**
     * 获取SVN分组的成员列表
     */
    ModalGetGroupMember(grouName) {
      //设置当前选中的分组名称
      this.currentSelectGroupName = grouName;
      //显示对话框
      this.modalGetGroupMember = true;
      //标题
      this.titleGetGroupMember = "分组成员信息 - " + grouName;
      //请求数据
      this.GetGroupMember();
    },
    /**
     * 获取SVN分组的成员列表
     */
    GetGroupMember() {
      var that = this;
      that.loadingGetGroupMember = true;
      that.tableDataGroupMember = [];
      var data = {
        searchKeyword: that.searchKeywordGroupMember,
        svn_group_name: that.currentSelectGroupName,
      };
      that.$axios
        .post("/api.php?c=Svngroup&a=GetGroupMember&t=web", data)
        .then(function (response) {
          var result = response.data;
          that.loadingGetGroupMember = false;
          if (result.status == 1) {
            that.tableDataGroupMember = result.data;
          } else {
            that.$Message.error({ content: result.message, duration: 2 });
          }
        })
        .catch(function (error) {
          that.loadingGetGroupMember = false;
          console.log(error);
          that.$Message.error("出错了 请联系管理员！");
        });
    },
  },
};
</script>

<style>
</style>