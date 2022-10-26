<template>
  <div>
    <Card :bordered="false" :dis-hover="true">
      <Row style="margin-bottom: 15px">
        <Col
          type="flex"
          justify="space-between"
          :xs="21"
          :sm="20"
          :md="19"
          :lg="18"
        >
          <Button icon="md-add" type="primary" ghost @click="ModalCreateGroup"
            >新建SVN分组</Button
          >
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
              @click="GetGroupList(true)"
              >手动刷新</Button
            >
          </Tooltip>
        </Col>
        <Col :xs="3" :sm="4" :md="5" :lg="6">
          <Input
            v-model="searchKeywordGroup"
            search
            enter-button
            placeholder="通过SVN分组名、备注搜索..."
            style="width: 100%"
            @on-search="GetGroupList()"
        /></Col>
      </Row>
      <Table
        @on-sort-change="SortChangeGroup"
        border
        :columns="tableGroupColumn"
        :data="tableGroupData"
        :loading="loadingGroup"
        size="small"
      >
        <template slot-scope="{ index }" slot="index">
          {{ pageSizeGroup * (pageCurrentGroup - 1) + index + 1 }}
        </template>
        <template slot-scope="{ row, index }" slot="svn_group_note">
          <Input
            :border="false"
            v-model="tableGroupData[index].svn_group_note"
            @on-blur="EditGroupNote(index, row.svn_group_name)"
          />
        </template>
        <template slot-scope="{ row }" slot="action">
          <Button
            type="success"
            size="small"
            @click="ModalGetGroupMember(row.svn_group_name)"
            >成员</Button
          >
          <Button
            type="warning"
            size="small"
            @click="ModalEditGroupName(row.svn_group_name)"
            >编辑</Button
          >
          <Button
            type="error"
            size="small"
            @click="DelGroup(row.svn_group_name)"
            >删除</Button
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
    </Card>
    <Modal v-model="modalAddGroup" title="新建SVN分组" @on-ok="CreateGroup">
      <Form :model="formCreateGroup" :label-width="80">
        <FormItem label="分组名">
          <Input v-model="formCreateGroup.svn_group_name"></Input>
        </FormItem>
        <FormItem>
          <Alert type="warning" show-icon
            >分组名只能包含字母、数字、破折号、下划线、点。</Alert
          >
        </FormItem>
        <FormItem label="备注">
          <Input v-model="formCreateGroup.svn_group_note"></Input>
        </FormItem>
        <FormItem>
          <Button
            type="primary"
            @click="CreateGroup"
            :loading="loadingCreateGroup"
            >确定</Button
          >
        </FormItem>
      </Form>
      <div slot="footer">
        <Button type="primary" ghost @click="modalAddGroup = false"
          >取消</Button
        >
      </div>
    </Modal>
    <Modal
      v-model="modalEditGroupName"
      :title="titleEditGroupName"
      @on-ok="EditGroupName"
    >
      <Form :model="formEditGroupName" :label-width="80">
        <FormItem label="分组名">
          <Input v-model="formEditGroupName.groupNameNew"></Input>
        </FormItem>
        <FormItem>
          <Alert type="warning" show-icon
            >分组名只能包含字母、数字、破折号、下划线、点。</Alert
          >
        </FormItem>
        <FormItem>
          <Button
            type="primary"
            @click="EditGroupName"
            :loading="loadingEditGroupName"
            >确定</Button
          >
        </FormItem>
      </Form>
      <div slot="footer">
        <Button type="primary" ghost @click="modalEditGroupName = false"
          >取消</Button
        >
      </div>
    </Modal>
    <Modal
      v-model="modalGetGroupMember"
      :draggable="true"
      :title="titleGetGroupMember"
    >
      <Row style="margin-bottom: 15px">
        <Col type="flex" justify="space-between" span="12">
          <Button icon="md-add" type="primary" ghost @click="ModalRepPathPri"
            >添加成员</Button
          >
        </Col>
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
        <template slot-scope="{ row }" slot="action">
          <Button
            type="error"
            size="small"
            @click="UpdGroupMember(row.objectName, row.objectType, 'delete')"
            >移除</Button
          >
        </template>
      </Table>
      <div slot="footer">
        <Button type="primary" ghost @click="modalGetGroupMember = false"
          >取消</Button
        >
      </div>
    </Modal>
    <!-- 对象列表弹出框 -->
    <Modal v-model="modalObject" :draggable="true" title="对象列表">
      <Tabs size="small" @on-click="ClickObjectTab">
        <TabPane :label="custom_tab_svn_user" name="user">
          <Row style="margin-bottom: 15px">
            <Col type="flex" justify="space-between" span="12"> </Col>
            <Col span="12">
              <Input
                search
                placeholder="通过用户名搜索..."
                v-model="searchKeywordUser"
                @on-change="GetAllUsers"
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
                @click.native="UpdGroupMember(row.svn_user_name, 'user', 'add')"
                >选择</Tag
              >
            </template>
          </Table>
        </TabPane>
        <TabPane :label="custom_tab_svn_group" name="group">
          <Row style="margin-bottom: 15px">
            <Col type="flex" justify="space-between" span="12"> </Col>
            <Col span="12">
              <Input
                search
                placeholder="通过分组名搜索..."
                v-model="searchKeywordGroup"
                @on-change="GetAllGroups"
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
            <template slot-scope="{ row }" slot="action">
              <Tag
                color="primary"
                @click.native="
                  UpdGroupMember(row.svn_group_name, 'group', 'add')
                "
                >选择</Tag
              >
            </template>
          </Table>
        </TabPane>
        <TabPane :label="custom_tab_svn_aliase" name="aliase">
          <Row style="margin-bottom: 15px">
            <Col type="flex" justify="space-between" span="12"> </Col>
            <Col span="12">
              <Input
                search
                placeholder="通过别名搜索..."
                v-model="searchKeywordAliase"
                @on-change="GetAllAliases"
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
                @click.native="UpdGroupMember(row.aliaseName, 'aliase', 'add')"
                >选择</Tag
              >
            </template>
          </Table>
        </TabPane>
      </Tabs>
      <Alert show-icon
        >如果对象信息用户等不是最新，需要回到对应的导航下刷新</Alert
      >
      <div slot="footer">
        <Button type="primary" ghost @click="modalObject = false">取消</Button>
      </div>
    </Modal>
  </div>
</template>

<script>
export default {
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

      /**
       * 分页数据
       */
      //分组
      pageCurrentGroup: 1,
      pageSizeGroup: 20,
      totalGroup: 0,

      /**
       * 搜索关键词
       */
      searchKeywordGroup: "",
      searchKeywordUser: "",
      searchKeywordGroup: "",
      searchKeywordAliase: "",
      //搜索分组的成员列表
      searchKeywordGroupMember: "",

      /**
       * 排序数据
       */
      //获取SVN分组列表
      sortNameGetGroupList: "svn_group_name",
      sortTypeGetGroupList: "asc",

      /**
       * 加载
       */
      //分组列表
      loadingGroup: true,
      //创建分组
      loadingCreateGroup: false,
      //编辑分组名称
      loadingEditGroupName: false,
      //分组的成员列表
      loadingGetGroupMember: true,
      //全部的SVN用户列表
      loadingAllUsers: true,
      //全部的SVN分组列表
      loadingAllGroups: true,
      //全部的SVN别名列表
      loadingAllAliases: true,

      /**
       * 临时变量
       */
      currentSelectGroupName: "",

      /**
       * 标题
       */
      titleEditGroupName: "",
      titleGetGroupMember: "",

      /**
       * 对话框
       */
      //新建分组
      modalAddGroup: false,
      //编辑分组信息
      modalEditGroupName: false,
      //配置分组成员
      modalGetGroupMember: false,
      //对象列表弹出框
      modalObject: false,

      /**
       * 表单
       */
      //新建分组
      formCreateGroup: {
        svn_group_name: "",
        svn_group_note: "",
      },
      //编辑分组
      formEditGroupName: {
        groupNameOld: "",
        groupNameNew: "",
      },
      /**
       * 表格
       */
      //分组信息
      tableGroupColumn: [
        {
          title: "序号",
          slot: "index",
          fixed: "left",
          minWidth: 80,
        },
        {
          title: "分组名",
          key: "svn_group_name",
          tooltip: true,
          sortable: "custom",
          minWidth: 120,
        },
        {
          title: "包含用户数",
          key: "include_user_count",
          sortable: "custom",
          minWidth: 130,
        },
        {
          title: "包含分组数",
          key: "include_group_count",
          sortable: "custom",
          minWidth: 130,
        },
        {
          title: "包含别名用户数",
          key: "include_aliase_count",
          sortable: "custom",
          minWidth: 130,
        },
        {
          title: "备注信息",
          slot: "svn_group_note",
          minWidth: 120,
        },
        {
          title: "其它",
          slot: "action",
          minWidth: 180,
        },
      ],
      tableGroupData: [],

      //分组的成员列表
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
        {
          title: "操作",
          slot: "action",
        },
      ],
      tableDataGroupMember: [
        {
          objectType: "user",
          objectName: "user1",
          status: 1,
        },
      ],

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
    };
  },
  computed: {},
  created() {},
  mounted() {
    this.GetGroupList();
  },
  methods: {
    /**
     * 每页数量改变
     */
    GroupPageSizeChange(value) {
      //设置每页条数
      this.pageSizeGroup = value;
      this.GetGroupList();
    },
    /**
     * 页码改变
     */
    GroupPageChange(value) {
      //设置当前页数
      this.pageCurrentGroup = value;
      this.GetGroupList();
    },
    /**
     * 分组排序
     */
    SortChangeGroup(value) {
      this.sortNameGetGroupList = value.key;
      if (value.order == "desc" || value.order == "asc") {
        this.sortTypeGetGroupList = value.order;
      }
      this.GetGroupList();
    },
    GetGroupList(sync = false, page = true) {
      var that = this;
      that.loadingGroup = true;
      that.tableGroupData = [];
      // that.totalGroup = 0;
      var data = {
        pageSize: that.pageSizeGroup,
        currentPage: that.pageCurrentGroup,
        searchKeyword: that.searchKeywordGroup,
        sortName: that.sortNameGetGroupList,
        sortType: that.sortTypeGetGroupList,
        sync: sync,
        page: page,
      };
      that.$axios
        .post("/api.php?c=Svngroup&a=GetGroupList&t=web", data)
        .then(function (response) {
          that.loadingGroup = false;
          var result = response.data;
          if (result.status == 1) {
            // that.$Message.success(result.message);
            that.tableGroupData = result.data.data;
            that.totalGroup = result.data.total;
          } else {
            that.$Message.error({ content: result.message, duration: 2 });
          }
        })
        .catch(function (error) {
          that.loadingGroup = false;
          console.log(error);
          that.$Message.error("出错了 请联系管理员！");
        });
    },
    /**
     * 编辑分组备注信息
     */
    EditGroupNote(index, svn_group_name) {
      var that = this;
      var data = {
        svn_group_name: svn_group_name,
        svn_group_note: that.tableGroupData[index].svn_group_note,
      };
      that.$axios
        .post("/api.php?c=Svngroup&a=EditGroupNote&t=web", data)
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
     * 添加分组
     */
    ModalCreateGroup() {
      this.modalAddGroup = true;
    },
    CreateGroup() {
      var that = this;
      that.loadingCreateGroup = true;
      var data = {
        svn_group_name: that.formCreateGroup.svn_group_name,
        svn_group_note: that.formCreateGroup.svn_group_note,
      };
      that.$axios
        .post("/api.php?c=Svngroup&a=CreateGroup&t=web", data)
        .then(function (response) {
          that.loadingCreateGroup = false;
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
            that.modalAddGroup = false;
            that.GetGroupList();
          } else {
            that.$Message.error({ content: result.message, duration: 2 });
          }
        })
        .catch(function (error) {
          that.loadingCreateGroup = false;
          console.log(error);
          that.$Message.error("出错了 请联系管理员！");
        });
    },
    /**
     * 编辑分组名称
     */
    ModalEditGroupName(svn_group_name) {
      //备份旧名称
      this.formEditGroupName.groupNameOld = svn_group_name;
      //自动显示输入信息
      this.formEditGroupName.groupNameNew = svn_group_name;
      //标题
      this.titleEditGroupName = "编辑SVN分组名 - " + svn_group_name;
      //对话框
      this.modalEditGroupName = true;
    },
    EditGroupName() {
      var that = this;
      that.loadingEditGroupName = true;
      var data = {
        groupNameOld: that.formEditGroupName.groupNameOld,
        groupNameNew: that.formEditGroupName.groupNameNew,
      };
      that.$axios
        .post("/api.php?c=Svngroup&a=EditGroupName&t=web", data)
        .then(function (response) {
          that.loadingEditGroupName = false;
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
            that.modalEditGroupName = false;
            that.GetGroupList();
          } else {
            that.$Message.error({ content: result.message, duration: 2 });
          }
        })
        .catch(function (error) {
          that.loadingEditGroupName = false;
          console.log(error);
          that.$Message.error("出错了 请联系管理员！");
        });
    },
    /**
     * 删除分组
     */
    DelGroup(svn_group_name) {
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
                        innerHTML: "删除SVN分组 - " + svn_group_name,
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
                          "删除SVN分组 - " + svn_group_name
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
                      "确定要删除该分组吗？<br/>将会从所有仓库和分组下将该分组移除！<br/>该操作不可逆！",
                  },
                }),
              ]
            ),
          ]);
        },
        onOk: () => {
          var data = {
            svn_group_name: svn_group_name,
          };
          that.$axios
            .post("/api.php?c=Svngroup&a=DelGroup&t=web", data)
            .then(function (response) {
              var result = response.data;
              if (result.status == 1) {
                that.$Message.success(result.message);
                that.GetGroupList();
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
     * 配置分组成员
     */
    ModalGetGroupMember(grouName) {
      //设置当前选中的分组名称
      this.currentSelectGroupName = grouName;
      //显示对话框
      this.modalGetGroupMember = true;
      //标题
      this.titleGetGroupMember = "编辑分组成员信息 - " + grouName;
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
    /**
     * 为分组添加或者删除所包含的对象
     * 对象包括：用户、分组、用户别名
     */
    UpdGroupMember(objectName, objectType, actionType) {
      var that = this;
      var data = {
        svn_group_name: that.currentSelectGroupName,
        objectName: objectName,
        objectType: objectType,
        actionType: actionType,
      };
      that.$axios
        .post("/api.php?c=Svngroup&a=UpdGroupMember&t=web", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
            that.modalObject = false;
            that.GetGroupMember();
            that.GetGroupList();
          } else {
            that.$Message.error({ content: result.message, duration: 2 });
            that.GetGroupMember();
          }
        })
        .catch(function (error) {
          console.log(error);
          that.$Message.error("出错了 请联系管理员！");
        });
    },
    /**
     * 点击对象列表弹出框下的 tab 触发
     */
    ClickObjectTab(name) {
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
        default:
          break;
      }
    },
    /**
     * 获取所有的SVN用户列表
     */
    GetAllUsers() {
      var that = this;
      //清空上次数据
      that.tableDataAllUsers = [];
      //开始加载动画
      that.loadingAllUsers = true;
      var data = {
        searchKeyword: that.searchKeywordUser,
        sortName: "svn_user_name",
        sortType: "asc",
        sync: false,
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
    GetAllGroups() {
      var that = this;
      //清空上次数据
      that.tableDataAllGroups = [];
      //开始加载动画
      that.loadingAllGroups = true;
      var data = {
        searchKeyword: that.searchKeywordGroup,
        sortName: "svn_group_name",
        sortType: "asc",
        sync: false,
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
    GetAllAliases() {
      var that = this;
      //清空上次数据
      that.tableDataAllAliases = [];
      //开始加载动画
      that.loadingAllAliases = true;
      var data = {
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
     * 显示路径授权对话框
     */
    ModalRepPathPri() {
      this.modalObject = true;
      //默认加载第一个tab内的数据
      this.GetAllUsers();
    },
  },
};
</script>

<style >
</style>