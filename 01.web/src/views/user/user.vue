<template>
  <Card :bordered="false" :dis-hover="true">
    <Tooltip max-width="150" :content="toolTipAddUser">
      <Button type="primary" @click="ModalAddRepUser()">新建用户</Button>
    </Tooltip>
    <div class="page-table">
      <Table :columns="tableColumnRepUser" :data="tableDataRepUser">
        <template slot-scope="{ index }" slot="id">
          <strong>{{ index + 1 }}</strong>
        </template>
        <template slot-scope="{ row }" slot="disabled">
          {{ row.disabled == 1 ? "禁用" : "启用" }}
        </template>
        <template slot-scope="{ row, index }" slot="action">
          <Button
            type="info"
            size="small"
            v-if="row.disabled == 1"
            @click="RepUserEnabled(index)"
            >启用</Button
          >
          <Button
            type="info"
            size="small"
            v-else
            @click="RepUserDisabled(index)"
            >禁用</Button
          >
          <Button type="success" size="small" @click="ModalRepUserEdit(index)"
            >密码</Button
          >
          <Button type="error" size="small" @click="ModalRepUserDel(index)"
            >删除</Button
          >
        </template>
      </Table>
      <Card :bordered="false" :dis-hover="true">
        <Page
          v-if="numRepUserTotal != 0"
          :total="numRepUserTotal"
          :page-size="numRepUserPageSize"
          @on-change="PagesizeChange"
        />
      </Card>
    </div>
    <Modal
      v-model="ModalRepUserAdd"
      title="新建SVN用户"
      @on-ok="RepAddUser()"
      @submit.native.prevent
    >
      <Form ref="formRepUser" :model="formRepUser" :label-width="80">
        <FormItem label="用户名">
          <Input v-model="formRepUser.userName" />
        </FormItem>
        <FormItem label="密码">
          <Input v-model="formRepUser.userPass" />
        </FormItem>
        <FormItem label="确认密码">
          <Input v-model="formRepUser.userPass2" />
        </FormItem>
      </Form>
    </Modal>
    <Modal
      v-model="modalRepUserEdit"
      title="编辑SVN用户信息"
      @on-ok="RepEditUser()"
    >
      <Form ref="formRepUser" :model="formRepUser" :label-width="80">
        <FormItem label="用户名">
          <Input v-model="formRepUser.userName" disabled />
        </FormItem>
        <FormItem label="密码">
          <Input v-model="formRepUser.userPass" />
        </FormItem>
        <FormItem label="确认密码">
          <Input v-model="formRepUser.userPass2" />
        </FormItem>
      </Form>
    </Modal>
  </Card>
</template>
<script>
export default {
  data() {
    return {
      toolTipAddUser: "SVN用户名称只能包含字母、数字、破折号、下划线、点",
      /**
       * 布尔值
       */
      boolIsAdmin: window.sessionStorage.roleid == 1 ? true : false,

      /**
       * 对话框控制
       */
      ModalRepUserAdd: false, //添加用户
      modalRepUserEdit: false, //编辑弹出框

      /**
       * 分页数据
       */
      numRepUserPageCurrent: 1,
      numRepUserPageSize: 10,
      numRepUserTotal: 20,

      formRepUser: {
        userid: "", //用户id
        userName: "", //添加用户时的用户名称
        userPass: "", //密码
        userPass2: "", //重复密码
        realname: "", //姓名
        email: "", //邮件
        phone: "", //电话
        roleid: "", //角色id
      },

      /**
       * 用户表格数据
       */
      tableColumnRepUser: [
        {
          title: "序号",
          slot: "id",
          width: 150,
        },
        {
          title: "用户名",
          key: "userName",
        },
        {
          title: "密码",
          key: "userPass",
        },
        {
          title: "状态",
          key: "disabled",
          slot: "disabled",
        },
        {
          title: "操作",
          slot: "action",
          align: "center",
        },
      ],
      tableDataRepUser: [
        // {
        //   id: 1,
        //   uid: "",
        //   roleid: "",
        //   userName: "",
        //   userPass: "",
        //   realname: "",
        //   email: "",
        //   phone: "",
        // },
      ],
    };
  },
  methods: {
    PagesizeChange(value) {
      var that = this;
      that.numRepUserPageCurrent = value; //设置当前页数
      that.RepGetUserList();
    },
    //启用用户
    RepUserEnabled(index) {
      var that = this;
      var data = {
        userName: that.tableDataRepUser[index]["userName"],
      };
      that.$axios
        .post("/api.php?c=svnserve&a=RepEnabledUser", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
            that.RepGetUserList();
          } else {
            that.$Message.error(result.message);
          }
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    //禁用用户
    RepUserDisabled(index) {
      var that = this;
      var data = {
        userName: that.tableDataRepUser[index]["userName"],
      };
      that.$axios
        .post("/api.php?c=svnserve&a=RepDisabledUser", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
            that.RepGetUserList();
          } else {
            that.$Message.error(result.message);
          }
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    ModalRepUserEdit(index) {
      var that = this;
      that.modalRepUserEdit = true;

      that.formRepUser.userName = that.tableDataRepUser[index]["userName"];
      that.formRepUser.userPass = that.tableDataRepUser[index]["userPass"];
      that.formRepUser.userPass2 = that.tableDataRepUser[index]["userPass"];
    },
    //添加用户对话框
    ModalAddRepUser() {
      var that = this;
      that.ModalRepUserAdd = true;
      that.formRepUser.userName = "";
      that.formRepUser.userPass = "";
      that.formRepUser.userPass2 = "";
    },
    RepEditUser() {
      var that = this;
      var data = {
        edit_username: String(that.formRepUser.userName),
        edit_password: String(that.formRepUser.userPass),
        edit_password2: String(that.formRepUser.userPass2),
      };
      that.$axios
        .post("/api.php?c=svnserve&a=RepEditUser", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
            that.modalRepUserEdit = false;
            that.RepGetUserList();
          } else {
            that.$Message.error(result.message);
            that.modalRepUserEdit = false;
          }
        })
        .catch(function (error) {
          console.log(error);
          that.modalRepUserEdit = false;
        });
    },
    //添加仓库用户
    RepAddUser() {
      var that = this;
      var data = {
        userName: that.formRepUser.userName,
        userPass: that.formRepUser.userPass,
        userPass2: that.formRepUser.userPass2,
      };
      that.$axios
        .post("/api.php?c=svnserve&a=RepAddUser", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
            that.ModalRepUserAdd = false;
            that.RepGetUserList();
          } else {
            that.$Message.error(result.message);
            that.ModalRepUserAdd = false;
          }
        })
        .catch(function (error) {
          console.log(error);
          that.ModalRepUserAdd = false;
        });
    },
    //获取仓库用户列表
    RepGetUserList() {
      var that = this;
      var data = {
        pageSize: that.numRepUserPageSize,
        currentPage: that.numRepUserPageCurrent,
      };
      that.$axios
        .post("/api.php?c=svnserve&a=RepGetUserList", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.tableDataRepUser = result.data;
            that.numRepUserTotal = result.total;
          } else {
            that.$Message.error(result.message);
            that.numRepUserTotal = 0;
          }
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    //删除仓库用户
    ModalRepUserDel(index) {
      var that = this;
      var data = {
        del_username: that.tableDataRepUser[index]["userName"],
      };
      that.$Modal.confirm({
        title: "警告",
        content: "确定要删除该账户吗？",
        loading: true,
        onOk: () => {
          that.$axios
            .post("/api.php?c=svnserve&a=RepUserDel", data)
            .then(function (response) {
              var result = response.data;
              if (result.status == 1) {
                that.$Message.success(result.message);
                that.$Modal.remove();
                that.RepGetUserList();
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
    that.RepGetUserList();
  },
};
</script>