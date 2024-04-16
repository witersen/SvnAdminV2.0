<template>
  <div>
    <!-- 对话框-对象列表 -->
    <Modal
      v-model="modalSvnObject"
      :draggable="true"
      @on-visible-change="ChangeModalVisible"
      :title="$t('modalSvnObject.objectList')"
    >
      <Tabs size="small" @on-click="ClickRepPathPriTab">
        <TabPane :label="custom_tab_svn_user" name="user" v-if="showSvnUserTab">
          <Row style="margin-bottom: 15px">
            <Col type="flex" justify="space-between" span="12">
              <Tooltip
                max-width="250"
                :content="$t('modalSvnObject.syncUserTip')"
                placement="bottom"
                :transfer="true"
              >
                <Button
                  icon="ios-sync"
                  type="warning"
                  ghost
                  @click="GetAllUsers(true)"
                  >{{ $t('repositoryUser.syncList') }}</Button
                >
              </Tooltip>
            </Col>
            <Col span="12">
              <Input
                search
                :placeholder="$t('modalSvnObject.searchByUserName')"
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
            <template slot-scope="{ index }" slot="index">
              {{ pageSizeUser * (pageCurrentUser - 1) + index + 1 }}
            </template>
            <template slot-scope="{ row }" slot="svn_user_status">
              <Tag
                color="blue"
                v-if="row.svn_user_status == '1' || row.svn_user_status == 1"
                >{{ $t('repositoryUser.enabled') }}</Tag
              >
              <Tag color="red" v-else>{{ $t('repositoryUser.disabled') }}</Tag>
            </template>
            <template slot-scope="{ row }" slot="action">
              <Tag
                style="cursor: pointer"
                color="primary"
                @click.native="propSendParentObject('user', row.svn_user_name)"
                >{{ $t('modalSvnObject.select') }}</Tag
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
                :content="$t('repositoryGroup.syncGroupTip')"
                placement="bottom"
                :transfer="true"
              >
                <Button
                  icon="ios-sync"
                  type="warning"
                  ghost
                  @click="GetAllGroups(true)"
                  >{{ $t('repositoryGroup.syncGroupList') }}</Button
                >
              </Tooltip>
            </Col>
            <Col span="12">
              <Input
                search
                :placeholder="$t('modalSvnObject.searchByGroupName')"
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
            <template slot-scope="{ index }" slot="index">
              {{ pageSizeGroup * (pageCurrentGroup - 1) + index + 1 }}
            </template>
            <template slot-scope="{ row }" slot="member">
              <Tag
                style="cursor: pointer"
                color="primary"
                @click.native="ModalGetGroupMember(row.svn_group_name)"
                >{{ $t('repositoryGroup.groupMember') }}</Tag
              >
            </template>
            <template slot-scope="{ row }" slot="action">
              <Tag
                style="cursor: pointer"
                color="primary"
                @click.native="
                  propSendParentObject('group', row.svn_group_name)
                "
                >{{ $t('modalSvnObject.select') }}</Tag
              >
            </template>
          </Table>
          <Card :bordered="false" :dis-hover="true">
            <Page
              v-if="totalGroup != 0"
              :total="totalGroup"
              :current="pageCurrentGroup"
              :page-size="pageSizeGroup"
              @on-page-size-change="GroupPageSizeChange"
              @on-change="GroupPageChange"
              size="small"
              show-sizer
            />
          </Card>
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
                content="同步才可获取最新别名列表"
                placement="bottom"
                :transfer="true"
              >
                <Button
                  icon="ios-sync"
                  type="warning"
                  ghost
                  @click="GetAliaseList(true)"
                  >同步列表</Button
                >
              </Tooltip> -->
            </Col>
            <Col span="12">
              <Input
                search
                :placeholder="$t('modalSvnObject.searchByAliase')"
                v-model="searchKeywordAliase"
                @on-change="GetAliaseList()"
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
                >{{ $t('repositoryUser.enabled') }}</Tag
              >
              <Tag color="red" v-else>{{ $t('repositoryUser.disabled') }}</Tag>
            </template>
            <template slot-scope="{ row }" slot="action">
              <Tag
                style="cursor: pointer"
                color="primary"
                @click.native="propSendParentObject('aliase', row.aliaseName)"
                >{{ $t('modalSvnObject.select') }}</Tag
              >
            </template>
          </Table>
        </TabPane>
        <TabPane :label="custom_tab_svn_all" name="*" v-if="showSvnAllTab">
          <Row style="margin-bottom: 15px">
            <Col type="flex" justify="space-between" span="12"> </Col>
            <Col span="12">
              <Input search disabled :placeholder="$t('modalSvnObject.searchBySymbol')" />
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
                style="cursor: pointer"
                color="primary"
                @click.native="propSendParentObject('*', '*')"
                >{{ $t('modalSvnObject.select') }}</Tag
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
              <Input search disabled :placeholder="$t('modalSvnObject.searchBySymbol')" />
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
                style="cursor: pointer"
                color="primary"
                @click.native="
                  propSendParentObject('$authenticated', '$authenticated')
                "
                >{{ $t('modalSvnObject.select') }}</Tag
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
              <Input search disabled :placeholder="$t('modalSvnObject.searchBySymbol')" />
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
                style="cursor: pointer"
                color="primary"
                @click.native="propSendParentObject('$anonymous', '$anonymous')"
                >{{ $t('modalSvnObject.select') }}</Tag
              >
            </template>
          </Table>
        </TabPane>
      </Tabs>
      <Alert show-icon>{{ $t('modalSvnObject.defaultPermissionTip') }}</Alert>
      <!-- <Alert show-icon
        >如果对象信息用户等不是最新，需要回到对应的导航下同步</Alert
      > -->
      <div slot="footer">
        <Button type="primary" ghost @click="CloseModalObject">{{ $t('cancel') }}</Button>
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
            :placeholder="$t('repositoryGroup.searchMember')"
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
            >{{ $t('repositoryGroup.user') }}</Tag
          >
          <Tag
            color="geekblue"
            v-if="row.objectType == 'group'"
            style="width: 90px; text-align: center"
            >{{ $t('repositoryGroup.group') }}</Tag
          >
          <Tag
            color="purple"
            v-if="row.objectType == 'aliase'"
            style="width: 90px; text-align: center"
            >{{ $t('repositoryGroup.aliase') }}</Tag
          >
        </template>
      </Table>
      <div slot="footer">
        <Button type="primary" ghost @click="modalGetGroupMember = false"
          >{{ $t('cancel') }}</Button
        >
      </div>
    </Modal>
  </div>
</template>

<script>
import i18n from "@/i18n";
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
    //SVN用户权限路径id
    propSvnnUserPriPathId: {
      type: Number,
      default: -1,
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
            i18n.t('repositoryGroup.user')
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
            i18n.t('repositoryGroup.group')
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
            i18n.t('repositoryGroup.aliase')
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
            i18n.t('modalRepPri.allUsers')
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
            i18n.t('modalRepPri.authenticatedUsers')
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
            i18n.t('modalRepPri.anonymousUsers')
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

      //SVN用户权限路径id
      svnn_user_pri_path_id: this.propSvnnUserPriPathId,

      /**
       * 临时变量
       */
      currentSelectGroupName: "",

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

      tableDataAllUsers: [],
      
      tableDataAllGroups: [],
      
      tableDataAllAliases: [],
      
      tableDataAll: [
        {
          all: "*",
        },
      ],
      
      tableDataAuthenticated: [
        {
          authenticated: "$authenticated",
        },
      ],
      
      tableDataAnonymous: [
        {
          anonymous: "$anonymous",
        },
      ],
      //分组的成员列表
      tableDataGroupMember: [],
      
    };
  },
  computed: {
      //对象列表-SVN用户列表
      tableColumnAllUsers: [
        {
          title: i18n.t("serial"),    //"序号",
          slot: "index",
          fixed: "left",
          // minWidth: 40,
        },
        {
          title: i18n.t("username"),    //"用户名",
          key: "svn_user_name",
          tooltip: true,
        },
        {
          title: i18n.t("repositoryUser.userStatus"),    //"用户状态",
          slot: "svn_user_status",
        },
        {
          title: i18n.t("note"),    //"备注信息",
          key: "svn_user_note",
          tooltip: true,
        },
        {
          title: i18n.t("action"),    //"操作",
          slot: "action",
          width: 90,
        },
      ],
      tableDataAllUsers: [],
      //对象列表-SVN分组列表
      tableColumnAllGroups: [
        {
          title: i18n.t("serial"),    //"序号",
          slot: "index",
          fixed: "left",
          // minWidth: 80,
        },
        {
          title: i18n.t("modalSvnObject.aliase"),    //"别名",
          key: "aliaseName",
          tooltip: true,
        },
        {
          title: i18n.t("modalSvnObject.aliaseCon"),    //"别名内容",
          key: "aliaseCon",
          tooltip: true,
        },
        {
          title: i18n.t("action"),    //"操作",
          slot: "action",
        },
      ],
      //对象列表-SVN分组列表
      tableColumnAllGroups() {
        return [
        {
          title: i18n.t("repositoryGroup.groupName"),    //"分组名",
          key: "svn_group_name",
          tooltip: true,
        },
        {
          title: i18n.t('note'),   //"备注信息",
          key: "svn_group_note",
          tooltip: true,
        },
        {
          title: i18n.t("repositoryGroup.groupMember"),    //"成员",
          slot: "member",
        },
        {
          title: i18n.t("action"),    //"操作",
          slot: "action",
        },
      ]},
      //对象列表-所有人
      tableColumnAll() {
        return [
        {
          title: i18n.t("modalRepPri.allUsers"),    //"所有人",
          key: "all",
        },
        {
          title: i18n.t("action"),    //"操作",
          slot: "action",
        },
      ]},
      //对象列表-所有已认证者
      tableColumnAuthenticated() {
        return [
        {
          title: i18n.t("modalRepPri.authenticatedUsers"),    //"所有已认证者",
          key: "authenticated",
        },
        {
          title: i18n.t("action"),    //"操作",
          slot: "action",
        },
      ]},
      //对象列表-所有匿名者
      tableColumnAnonymous() {
        return [
        {
          title: i18n.t("modalRepPri.anonymousUsers"),    //"所有匿名者",
          key: "anonymous",
        },
        {
          title: i18n.t("action"),    //"操作",
          slot: "action",
        },
      ]},
      tableColumnGroupMember() {
        return [
        {
          title: i18n.t("repositoryGroup.objectType"),    //"对象类型",
          slot: "objectType",
          // width: 125,
        },
        {
          title: i18n.t("repositoryGroup.objectName"),    //"对象名称",
          key: "objectName",
          tooltip: true,
          // width: 115,
        },
      ]},
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
    //SVN用户权限路径id
    propSvnnUserPriPathId: function (value) {
      this.svnn_user_pri_path_id = value;
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
          this.GetAliaseList();
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
     * 每页数量改变
     */
    UserPageSizeChange(value) {
      //设置每页条数
      this.pageSizeUser = value;
      this.GetAllUsers();
    },
    /**
     * 页码改变
     */
    UserPageChange(value) {
      //设置当前页数
      this.pageCurrentUser = value;
      this.GetAllUsers();
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
        pageSize: that.pageSizeUser,
        currentPage: that.pageCurrentUser,

        searchKeyword: that.searchKeywordUser,
        sortName: "svn_user_name",
        sortType: "asc",
        sync: sync,
        page: true,
        svnn_user_pri_path_id: that.svnn_user_pri_path_id,
      };
      that.$axios
        .post("api.php?c=Svnuser&a=GetUserList&t=web", data)
        .then(function (response) {
          that.loadingAllUsers = false;
          var result = response.data;
          if (result.status == 1) {
            that.tableDataAllUsers = result.data.data;
            that.totalUser = result.data.total;
          } else {
            that.$Message.error({ content: result.message, duration: 2 });
          }
        })
        .catch(function (error) {
          that.loadingAllUsers = false;
          console.log(error);
          that.$Message.error(i18n.t('errors.contactAdmin'));
        });
    },
    /**
     * 每页数量改变
     */
    GroupPageSizeChange(value) {
      //设置每页条数
      this.pageSizeGroup = value;
      this.GetAllGroups();
    },
    /**
     * 页码改变
     */
    GroupPageChange(value) {
      //设置当前页数
      this.pageCurrentGroup = value;
      this.GetAllGroups();
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
        pageSize: that.pageSizeGroup,
        currentPage: that.pageCurrentGroup,

        searchKeyword: that.searchKeywordGroup,
        sortName: "svn_group_name",
        sortType: "asc",
        sync: sync,
        page: true,
        svnn_user_pri_path_id: that.svnn_user_pri_path_id,
      };
      that.$axios
        .post("api.php?c=Svngroup&a=GetGroupList&t=web", data)
        .then(function (response) {
          that.loadingAllGroups = false;
          var result = response.data;
          if (result.status == 1) {
            that.tableDataAllGroups = result.data.data;
            that.totalGroup = result.data.total;
          } else {
            that.$Message.error({ content: result.message, duration: 2 });
          }
        })
        .catch(function (error) {
          that.loadingAllGroups = false;
          console.log(error);
          that.$Message.error(i18n.t('errors.contactAdmin'));
        });
    },
    /**
     * 获取所有的别名列表
     */
    GetAliaseList(sync = false) {
      var that = this;
      //清空上次数据
      that.tableDataAllAliases = [];
      //开始加载动画
      that.loadingAllAliases = true;
      var data = {
        sync: sync,
        searchKeyword: that.searchKeywordAliase,
        svnn_user_pri_path_id: that.svnn_user_pri_path_id,
      };
      that.$axios
        .post("api.php?c=Svnaliase&a=GetAliaseList&t=web", data)
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
          that.$Message.error(i18n.t('errors.contactAdmin'));
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
      this.titleGetGroupMember = i18n.t('modalSvnObject.groupMemberInfo') + " - " + grouName;
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
        svnn_user_pri_path_id: that.svnn_user_pri_path_id,
      };
      that.$axios
        .post("api.php?c=Svngroup&a=GetGroupMember&t=web", data)
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
          that.$Message.error(i18n.t('errors.contactAdmin'));
        });
    },
  },
};
</script>

<style>
</style>