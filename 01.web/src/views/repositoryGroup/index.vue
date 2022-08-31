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
        </Col>
        <Col :xs="3" :sm="4" :md="5" :lg="6">
          <Input
            v-model="searchKeywordGroup"
            search
            enter-button
            placeholder="通过SVN分组名、备注搜索..."
            style="width: 100%"
            @on-search="SearchGetGroupList"
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
    <Modal v-model="modalGetGroupMember" :title="titleGetGroupMember">
      <Tabs type="card">
        <TabPane label="用户成员">
          <!-- <Row style="margin-bottom: 15px">
            <Col span="14"> </Col>
            <Col span="10">
              <Input
                v-model="xxx"
                search
                placeholder="搜索..."
                style="width: 100%"
                @on-search="xxx"
            /></Col>
          </Row> -->
          <Table
            height="350"
            :show-header="true"
            :columns="tableRepAllUserColumn"
            :data="tableRepAllUserData"
            :loading="loadingRepAllUser"
            size="small"
          >
            <template slot-scope="{ row }" slot="isMember">
              <Switch
                v-model="row.isMember"
                @on-change="(value) => ChangeUserMember(value, row.userName)"
              >
                <span slot="open">是</span>
                <span slot="close">否</span>
              </Switch>
            </template>
            <template slot-scope="{ row }" slot="disabled">
              <Tag color="blue" v-if="row.disabled == 0">正常</Tag>
              <Tag color="red" v-else>禁用</Tag>
            </template>
          </Table>
        </TabPane>
        <TabPane label="分组成员">
          <Table
            height="350"
            :show-header="true"
            :columns="tableRepAllGroupColumn"
            :data="tableRepAllGroupData"
            :loading="loadingRepAllGroup"
            size="small"
          >
            <template slot-scope="{ row }" slot="isMember">
              <Switch
                v-model="row.isMember"
                @on-change="(value) => ChangeGroupMember(value, row.groupName)"
              >
                <span slot="open">是</span>
                <span slot="close">否</span>
              </Switch>
            </template>
          </Table>
        </TabPane>
      </Tabs>
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
  data() {
    return {
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

      /**
       * 排序数据
       */
      sortName: "svn_group_name",
      sortType: "asc",

      /**
       * 加载
       */
      //分组列表
      loadingGroup: true,
      //分组用户成员
      loadingRepAllUser: true,
      //分组分组成员
      loadingRepAllGroup: true,
      //创建分组
      loadingCreateGroup: false,
      //编辑分组名称
      loadingEditGroupName: false,

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
          title: "包含用户数量",
          key: "include_user_count",
          sortable: "custom",
          minWidth: 130,
        },
        {
          title: "包含分组数量",
          key: "include_group_count",
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

      //仓库的所有用户
      tableRepAllUserColumn: [
        {
          title: "用户名",
          key: "userName",
        },
        {
          title: "是否为成员",
          slot: "isMember",
        },
        {
          title: "当前状态",
          slot: "disabled",
        },
      ],
      tableRepAllUserData: [],

      //仓库的所有分组
      tableRepAllGroupColumn: [
        {
          title: "分组名",
          key: "groupName",
        },
        {
          title: "是否为成员",
          slot: "isMember",
        },
      ],
      tableRepAllGroupData: [],
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
      this.sortName = value.key;
      if (value.order == "desc" || value.order == "asc") {
        this.sortType = value.order;
      }
      this.GetGroupList();
    },
    /**
     * 获取SVN分组列表
     */
    SearchGetGroupList() {
      // if (this.searchKeywordGroup == "") {
      //   this.$Message.error("请输入搜索内容");
      //   return;
      // }
      this.GetGroupList();
    },
    GetGroupList() {
      var that = this;
      that.loadingGroup = true;
      that.tableGroupData = [];
      // that.totalGroup = 0;
      var data = {
        pageSize: that.pageSizeGroup,
        currentPage: that.pageCurrentGroup,
        searchKeyword: that.searchKeywordGroup,
        sortName: that.sortName,
        sortType: that.sortType,
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
            that.$Message.error(result.message);
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
            that.$Message.error(result.message);
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
            that.$Message.error(result.message);
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
            that.$Message.error(result.message);
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
                that.$Message.error(result.message);
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
     * 获取SVN分组的用户成员和分组成员
     */
    GetGroupMember() {
      var that = this;
      that.loadingRepAllUser = true;
      that.loadingRepAllGroup = true;
      that.tableRepAllUserData = [];
      that.tableRepAllGroupData = [];
      var data = {
        svn_group_name: that.currentSelectGroupName,
      };
      that.$axios
        .post("/api.php?c=Svngroup&a=GetGroupMember&t=web", data)
        .then(function (response) {
          that.loadingRepAllUser = false;
          that.loadingRepAllGroup = false;
          var result = response.data;
          if (result.status == 1) {
            that.tableRepAllUserData = result.data.userList;
            that.tableRepAllGroupData = result.data.groupList;
          } else {
            that.$Message.error(result.message);
          }
        })
        .catch(function (error) {
          that.loadingRepAllUser = false;
          that.loadingRepAllGroup = false;
          console.log(error);
          that.$Message.error("出错了 请联系管理员！");
        });
    },
    /**
     * 设置分组的用户成员
     */
    ChangeUserMember(value, userName) {
      if (value == true) {
        this.UpdGroupMember(userName, "user", "add");
      } else {
        this.UpdGroupMember(userName, "user", "delete");
      }
    },
    /**
     * 设置分组的分组成员
     */
    ChangeGroupMember(value, groupName) {
      if (value == true) {
        this.UpdGroupMember(groupName, "group", "add");
      } else {
        this.UpdGroupMember(groupName, "group", "delete");
      }
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
            that.GetGroupMember();
          } else {
            that.$Message.error(result.message);
            that.GetGroupMember();
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