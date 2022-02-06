<template>
  <Card :bordered="false" :dis-hover="true">
    <Tooltip max-width="150" :content="toolTipAddGroup">
      <Button type="primary" @click="ModalAddRepGroup()">新建分组</Button>
    </Tooltip>
    <div class="page-table">
      <Table :columns="tableColumnRepGroup" :data="tableDataRepGroup">
        <template slot-scope="{ index }" slot="id">
          <strong>{{ index + 1 }}</strong>
        </template>
        <template slot-scope="{ index }" slot="action">
          <Button
            type="primary"
            size="small"
            @click="ModalRepGroupUserGet(index)"
            >用户</Button
          >
          <Button
            type="primary"
            size="small"
            @click="ModalRepGroupGroupGet(index)"
            >分组</Button
          >
          <Button type="success" size="small" @click="ModalRepGroupEdit(index)"
            >编辑</Button
          >
          <Button type="error" size="small" @click="ModalRepGroupDel(index)"
            >删除</Button
          >
        </template>
      </Table>
      <Card :bordered="false" :dis-hover="true">
        <Page
          v-if="numRepGroupTotal != 0"
          :total="numRepGroupTotal"
          :page-size="numRepGroupPageSize"
          @on-change="PagesizeChange"
        />
      </Card>
    </div>
    <Modal
      v-model="modalRepGroupAdd"
      title="新建SVN分组"
      @on-ok="RepAddGroup()"
    >
      <Form
        ref="formRepGroup"
        :model="formRepGroup"
        :label-width="80"
        @submit.native.prevent
      >
        <FormItem label="组名称">
          <Input v-model="formRepGroup.groupName" />
        </FormItem>
      </Form>
    </Modal>
    <Modal
      v-model="modalRepGroupEdit"
      title="编辑SVN分组信息"
      @on-ok="RepEditGroup()"
    >
      <Form ref="formRepGroup" :model="formRepGroup" :label-width="80">
        <FormItem label="组名称">
          <Input v-model="formRepGroup.groupName" />
        </FormItem>
      </Form>
    </Modal>
    <Modal
      v-model="modalRepGroupUserGet"
      title="将选择的用户作为当前分组的成员"
      @on-ok="SetRepUserGroup"
    >
      <Table
        height="400"
        border
        :columns="tableColumnRepUser"
        :data="tableDataRepUser"
      >
        <template slot-scope="{ index }" slot="id">
          <strong>{{ index + 1 }}</strong>
        </template>
        <template slot-scope="{ index }" slot="slot_status">
          <RadioGroup type="button" v-model="tableDataRepUser[index].status">
            <Radio label="in"><span>成员</span></Radio>
            <Radio label="no"><span>非成员</span></Radio>
          </RadioGroup>
        </template>
      </Table>
    </Modal>
    <Modal
      v-model="modalRepGroupGroupGet"
      title="将选择的分组作为当前分组的成员"
      @on-ok="SetRepGroupGroup"
    >
      <Table
        height="400"
        border
        :columns="tableColumnRepGroupGroup"
        :data="tableDataRepGroupGroup"
      >
        <template slot-scope="{ index }" slot="id">
          <strong>{{ index + 1 }}</strong>
        </template>
        <template slot-scope="{ index }" slot="slot_status">
          <RadioGroup
            type="button"
            v-model="tableDataRepGroupGroup[index].status"
          >
            <Radio label="in"><span>成员</span></Radio>
            <Radio label="no"><span>非成员</span></Radio>
          </RadioGroup>
        </template>
      </Table>
    </Modal>
  </Card>
</template>
<script>
export default {
  data() {
    return {
      toolTipAddGroup: "SVN分组名称只能包含字母、数字、破折号、下划线、点",
      /**
       * 布尔值
       */
      boolIsAdmin: window.sessionStorage.roleid == 1 ? true : false,

      /**
       * 对话框控制
       */
      modalRepGroupAdd: false, //添加分组
      modalRepGroupEdit: false, //编辑弹出框
      modalRepGroupUserGet: false,
      modalRepGroupGroupGet: false,

      /**
       * 临时数据
       */
      tempRepGroupName: "",
      tempRepGroupUserCurrentSelect: "",
      tempRepGroupGroupCurrentSelect: "",

      /**
       * 分页数据
       */
      numRepGroupPageCurrent: 1,
      numRepGroupPageSize: 10,
      numRepGroupTotal: 20,

      formRepGroup: {
        groupName: "", //添加用户时的用户名称
      },

      /**
       * 分组表格数据
       */
      tableColumnRepGroup: [
        {
          title: "序号",
          slot: "id",
          width: 150,
        },
        {
          title: "组名称",
          key: "groupName",
        },
        {
          title: "包含用户数",
          key: "includeUserCount",
        },
        {
          title: "包含分组数",
          key: "includeGroupCount",
        },
        {
          title: "操作",
          slot: "action",
          align: "center",
        },
      ],
      tableDataRepGroup: [
        // {
        //   groupName: "",
        //   includeUserCount: 0,
        // },
      ],

      /**
       * 分组的用户表格
       */
      tableColumnRepUser: [
        {
          title: "序号",
          key: "id",
          slot: "id",
          width: 70,
        },
        {
          title: "用户名",
          key: "username",
        },
        {
          title: "状态",
          key: "status",
          slot: "slot_status",
        },
      ],
      tableDataRepUser: [],

      /**
       * 分组的分组表格
       */
      tableColumnRepGroupGroup: [
        {
          title: "序号",
          key: "id",
          slot: "id",
          width: 70,
        },
        {
          title: "分组名",
          key: "groupName",
        },
        {
          title: "状态",
          key: "status",
          slot: "slot_status",
        },
      ],
      tableDataRepGroupGroup: [],
    };
  },
  methods: {
    PagesizeChange(value) {
      var that = this;
      that.numRepGroupPageCurrent = value; //设置当前页数
      that.RepGetGroupList();
    },
    //查看分组的用户
    ModalRepGroupUserGet(index) {
      var that = this;
      that.modalRepGroupUserGet = true;
      that.tempRepGroupUserCurrentSelect =
        that.tableDataRepGroup[index]["groupName"];
      that.GetRepGroupUser(that.tableDataRepGroup[index]["groupName"]);
    },
    //查看分组的分组
    ModalRepGroupGroupGet(index) {
      var that = this;
      that.modalRepGroupGroupGet = true;
      that.tempRepGroupGroupCurrentSelect =
        that.tableDataRepGroup[index]["groupName"];
      that.GetRepGroupGroup(that.tableDataRepGroup[index]["groupName"]);
    },
    //获取分组的用户
    GetRepGroupUser(groupName) {
      var that = this;
      var data = {
        groupName: groupName,
      };
      that.$axios
        .post("/api.php?c=svnserve&a=RepGetGroupUserList", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.tableDataRepUser = result.data;
          } else {
            that.$Message.error(result.message);
          }
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    //获取分组的分组
    GetRepGroupGroup(groupName) {
      var that = this;
      var data = {
        groupName: groupName,
      };
      that.$axios
        .post("/api.php?c=svnserve&a=RepGetGroupGroupList", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.tableDataRepGroupGroup = result.data;
          } else {
            that.$Message.error(result.message);
          }
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    //为分组设置用户
    SetRepUserGroup() {
      var that = this;
      var data = {
        group_name: that.tempRepGroupUserCurrentSelect,
        this_account_list: that.tableDataRepUser,
      };
      that.$axios
        .post("/api.php?c=svnserve&a=RepSetGroupUserList", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
            that.modalRepGroupUserGet = false;
            that.RepGetGroupList();
          } else {
            that.$Message.error(result.message);
          }
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    //为分组设置分组
    SetRepGroupGroup() {
      var that = this;
      var data = {
        groupName: that.tempRepGroupGroupCurrentSelect,
        thisAccountList: that.tableDataRepGroupGroup,
      };
      that.$axios
        .post("/api.php?c=svnserve&a=RepSetGroupGroupList", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
            that.modalRepGroupGroupGet = false;
            that.RepGetGroupList();
          } else {
            that.$Message.error(result.message);
          }
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    //编辑分组
    ModalRepGroupEdit(index) {
      var that = this;
      that.modalRepGroupEdit = true;

      that.tempRepGroupName = that.tableDataRepGroup[index]["groupName"];

      that.formRepGroup.groupName = that.tableDataRepGroup[index]["groupName"];
    },
    //添加分组对话框
    ModalAddRepGroup() {
      var that = this;
      that.modalRepGroupAdd = true;
      that.formRepGroup.groupName = "";
    },
    //编辑分组
    RepEditGroup() {
      var that = this;
      var data = {
        oldGroup: that.tempRepGroupName,
        newGroup: that.formRepGroup.groupName,
      };
      that.$axios
        .post("/api.php?c=svnserve&a=RepEditGroup", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
            that.modalRepGroupEdit = false;
            that.RepGetGroupList();
          } else {
            that.$Message.error(result.message);
            that.modalRepGroupEdit = false;
          }
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    //添加仓库分组
    RepAddGroup() {
      var that = this;
      var data = {
        groupName: that.formRepGroup.groupName,
      };
      that.$axios
        .post("/api.php?c=svnserve&a=RepAddGroup", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
            that.modalRepGroupAdd = false;
            that.RepGetGroupList();
          } else {
            that.$Message.error(result.message);
            that.modalRepGroupAdd = false;
          }
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    //获取仓库分组列表
    RepGetGroupList() {
      var that = this;
      var data = {
        pageSize: that.numRepGroupPageSize,
        currentPage: that.numRepGroupPageCurrent,
      };
      that.$axios
        .post("/api.php?c=svnserve&a=RepGetGroupList", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.tableDataRepGroup = result.data;
            that.numRepGroupTotal = result.total;
          } else {
            that.$Message.error(result.message);
            that.numRepGroupTotal = 0;
          }
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    //删除仓库分组
    ModalRepGroupDel(index) {
      var that = this;
      var data = {
        del_groupname: that.tableDataRepGroup[index]["groupName"],
      };
      that.$Modal.confirm({
        title: "警告",
        content: "确定要删除该账户吗？",
        loading: true,
        onOk: () => {
          that.$axios
            .post("/api.php?c=svnserve&a=RepGroupDel", data)
            .then(function (response) {
              var result = response.data;
              if (result.status == 1) {
                that.$Message.success(result.message);
                that.$Modal.remove();
                that.RepGetGroupList();
              } else {
                that.$Message.error(result.message);
                that.$Modal.remove();
              }
            })
            .catch(function (error) {
              console.log(error);
            });
        },
        onCancel: () => {},
      });
    },
  },
  created() {},
  mounted() {
    var that = this;
    that.RepGetGroupList();
  },
};
</script>