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
        <template slot-scope="{ index }" slot="action">
          <Button type="success" size="small" @click="ModalRepUserEdit(index)"
            >编辑</Button
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
          <Input v-model="formRepUser.username" />
        </FormItem>
        <FormItem label="密码">
          <Input v-model="formRepUser.password" />
        </FormItem>
        <FormItem label="确认密码">
          <Input v-model="formRepUser.password2" />
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
          <Input v-model="formRepUser.username" disabled />
        </FormItem>
        <FormItem label="密码">
          <Input v-model="formRepUser.password" />
        </FormItem>
        <FormItem label="确认密码">
          <Input v-model="formRepUser.password2" />
        </FormItem>
      </Form>
    </Modal>
  </Card>
</template>
<script>
export default {
  data() {
    return {
      toolTipAddUser: "用户名只能由数字、字母、下划线组成 密码可由数字、字母、下划线、点、@组成",
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
        username: "", //添加用户时的用户名称
        password: "", //密码
        password2: "", //重复密码
        realname: "", //姓名
        email: "", //邮件
        phone: "", //电话
        roleid: "", //角色id
        rolename: "", //角色名称
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
          key: "username",
        },
        {
          title: "密码",
          key: "password",
        },
        {
          title: "角色",
          key: "rolename",
        },
        {
          title: "操作",
          slot: "action",
          width: 200,
          align: "center",
        },
      ],
      tableDataRepUser: [
        // {
        //   id: 1,
        //   uid: "",
        //   roleid: "",
        //   username: "",
        //   password: "",
        //   realname: "",
        //   email: "",
        //   rolename: "",
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
    ModalRepUserEdit(index) {
      var that = this;
      that.modalRepUserEdit = true;

      that.formRepUser.username = that.tableDataRepUser[index]["username"];
      that.formRepUser.password = that.tableDataRepUser[index]["password"];
      that.formRepUser.password2 = that.tableDataRepUser[index]["password"];
    },
    //添加用户对话框
    ModalAddRepUser() {
      var that = this;
      that.ModalRepUserAdd = true;
      that.formRepUser.username = "";
      that.formRepUser.password = "";
      that.formRepUser.password2 = "";
    },
    RepEditUser() {
      var that = this;
      var data = {
        edit_username: that.formRepUser.username,
        edit_password: that.formRepUser.password,
        edit_password2: that.formRepUser.password2,
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
        username: that.formRepUser.username,
        password: that.formRepUser.password,
        password2: that.formRepUser.password2,
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
        del_username: that.tableDataRepUser[index]["username"],
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