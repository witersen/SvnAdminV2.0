<template>
  <Card :bordered="false" :dis-hover="true">
    <Button type="primary" @click="add_user_status_click()">添加用户</Button>
    <div class="page-table">
      <Table :columns="column1" :data="data1" :loading="data1_loading_status">
        <template slot-scope="{ row }" slot="id">
          <strong>{{ row.id + 1 }}</strong>
        </template>
        <template slot-scope="{ index }" slot="action">
          <Button
            v-bind:disabled="data1[index]['roleid'] != 3"
            type="primary"
            size="small"
            @click="GetUserRepositoryList(index)"
            >授权</Button
          >
          <Button type="success" size="small" @click="edituserStatus(index)"
            >编辑</Button
          >
          <Button type="error" size="small" @click="DelUser(index)"
            >删除</Button
          >
        </template>
      </Table>
      <Card :bordered="false" :dis-hover="true">
        <Page
          v-if="content_total != 0"
          :total="content_total"
          :page-size="page_size"
          @on-change="pageChange"
        />
      </Card>
    </div>
    <Modal
      v-model="add_user_status"
      title="添加用户"
      @on-ok="AddUser()"
      :loading="modal_add_user_loading_status"
    >
      <Form ref="formUser" :model="formUser" :label-width="80">
        <FormItem label="用户名">
          <Input v-model="formUser.username" />
        </FormItem>
        <FormItem label="密码">
          <Input v-model="formUser.password" />
        </FormItem>
        <FormItem label="确认密码">
          <Input v-model="formUser.password2" />
        </FormItem>
        <FormItem label="角色">
          <RadioGroup v-model="formUser.roleid">
            <Radio label="1" disabled><span>超级管理员</span></Radio>
            <Radio label="2"><span>系统管理员</span></Radio>
            <Radio label="3"><span>普通用户</span></Radio>
          </RadioGroup>
        </FormItem>
        <FormItem label="姓名">
          <Input v-model="formUser.realname" />
        </FormItem>
        <FormItem label="邮件">
          <Input v-model="formUser.email" />
        </FormItem>
        <FormItem label="电话">
          <Input v-model="formUser.phone" />
        </FormItem>
      </Form>
    </Modal>
    <Modal
      v-model="shouquan_status"
      title="用户授权"
      @on-ok="SetUserRepositoryList"
      :loading="modal_shouquan_loading_status"
    >
      <Table
        :loading="shouquan_loading_status"
        height="400"
        border
        :columns="user_repository_list_comumns"
        :data="user_repository_list_data"
      >
        <template slot-scope="{ row }" slot="id">
          <strong>{{ row.id + 1 }}</strong>
        </template>
        <template slot-scope="{ index }" slot="slot_privilege">
          <RadioGroup
            type="button"
            v-model="user_repository_list_data[index].privilege"
          >
            <Radio label="1"><span>授权</span></Radio>
            <Radio label="0"><span>无</span></Radio>
          </RadioGroup>
        </template>
      </Table>
    </Modal>
    <Modal
      v-model="editUser_status"
      title="编辑用户信息"
      @on-ok="EditUser()"
      :loading="modal_editUser_loading_status"
    >
      <Form ref="formUser" :model="formUser" :label-width="80">
        <FormItem label="用户名">
          <Input
            v-model="formUser.username"
            v-bind:disabled="formUser.roleid == 1"
          />
        </FormItem>
        <FormItem label="密码">
          <Input v-model="formUser.password" />
        </FormItem>
        <FormItem label="确认密码">
          <Input v-model="formUser.password2" />
        </FormItem>
        <FormItem label="角色">
          <RadioGroup v-model="formUser.roleid">
            <Radio label="1" disabled><span>超级管理员</span></Radio>
            <Radio label="2" v-bind:disabled="formUser.roleid == 1"
              ><span>系统管理员</span></Radio
            >
            <Radio label="3" v-bind:disabled="formUser.roleid == 1"
              ><span>普通用户</span></Radio
            >
          </RadioGroup>
        </FormItem>
        <FormItem label="姓名">
          <Input v-model="formUser.realname" />
        </FormItem>
        <FormItem label="邮件">
          <Input v-model="formUser.email" />
        </FormItem>
        <FormItem label="电话">
          <Input v-model="formUser.phone" />
        </FormItem>
      </Form>
    </Modal>
  </Card>
</template>
<script>
export default {
  data() {
    return {
      modal_editUser_loading_status: true, //编辑用户信息确认加载中
      modal_shouquan_loading_status: true, //授权确认加载中
      modal_add_user_loading_status: true, //添加用户确认加载中
      data1_loading_status: true, //用户列表加载中
      isadmin: window.sessionStorage.roleid == 1 ? true : false,
      add_user_status: false, //添加用户
      current: 1, //当前在第几页
      page_size: 10, //每一页有几条数据
      content_total: 20, //总共有多少条数据
      selectedReposotoryPrivilegeItem: "", //仓库授权 仓库账户 弹出框中选中
      editUser_status: false, //编辑弹出框
      shouquan_status: false, //授权弹出框
      shouquan_loading_status: true, //授权弹出框加载中
      formUser: {
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
      column1: [
        {
          title: "序号",
          key: "id",
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
          title: "真实姓名",
          key: "realname",
        },
        {
          title: "邮件",
          key: "email",
        },
        {
          title: "电话",
          key: "phone",
        },
        {
          title: "操作",
          slot: "action",
          width: 200,
          align: "center",
        },
      ],
      data1: [
        {
          id: 1,
          uid: "",
          roleid: "",
          username: "",
          password: "",
          realname: "",
          email: "",
          rolename: "",
          phone: "",
        },
      ],
      user_repository_list_comumns: [
        {
          title: "序号",
          key: "id",
          slot: "id",
          width: 70,
        },
        {
          title: "仓库",
          key: "repository_name",
        },
        {
          title: "权限",
          key: "privilege",
          slot: "slot_privilege",
        },
      ],
      user_repository_list_data: [
        {
          id: 0,
          repository_name: "root",
          privilege: "",
        },
      ],
      yonghu_comumns: [
        {
          title: "序号",
          key: "id",
          slot: "id",
          width: 70,
        },
        {
          title: "账户",
          key: "account",
        },
        {
          title: "密码",
          key: "password",
        },
        {
          title: "操作",
          key: "act",
          slot: "slot_act",
        },
      ],
      yonghu_data: [
        // {
        //   id: 0,
        //   account: "root",
        //   password: "123456",
        // },
      ],
    };
  },
  methods: {
    pageChange(value) {
      var that = this;
      that.current = value; //设置当前页数
      that.GetUserList();
    },
    edituserStatus(index) {
      var that = this;
      that.editUser_status = true;
      that.modal_editUser_loading_status = true;

      that.formUser.userid = that.data1[index]["uid"];
      that.formUser.username = that.data1[index]["username"];
      that.formUser.password = that.data1[index]["password"];
      that.formUser.password2 = that.data1[index]["password"];
      that.formUser.roleid = that.data1[index]["roleid"];
      that.formUser.realname = that.data1[index]["realname"];
      that.formUser.email = that.data1[index]["email"];
      that.formUser.phone = that.data1[index]["phone"];
    },
    add_user_status_click() {
      var that = this;
      that.modal_add_user_loading_status = true;
      that.add_user_status = true;
      that.formUser.username = "";
      that.formUser.password = "";
      that.formUser.password2 = "";
      that.formUser.realname = "";
      that.formUser.email = "";
      that.formUser.phone = "";
      that.formUser.roleid = "";
    },
    EditUser() {
      var that = this;
      that.modal_editUser_loading_status = true;
      var data = {
        edit_userid: that.formUser.userid,
        edit_username: that.formUser.username,
        edit_password: that.formUser.password,
        edit_password2: that.formUser.password2,
        edit_roleid: that.formUser.roleid,
        edit_realname: that.formUser.realname,
        edit_email: that.formUser.email,
        edit_phone: that.formUser.phone,
      };
      that.$axios
        .post("/api.php?c=user&a=EditUser", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
            that.editUser_status = false;
            that.modal_editUser_loading_status = false;
            that.GetUserList();
          } else {
            that.$Message.error(result.message);
            that.editUser_status = false;
            that.modal_editUser_loading_status = false;
          }
        })
        .catch(function (error) {
          console.log(error);
          that.editUser_status = false;
          that.modal_editUser_loading_status = false;
        });
    },
    AddUser() {
      var that = this;
      that.modal_add_user_loading_status = true;
      var data = {
        username: that.formUser.username,
        password: that.formUser.password,
        password2: that.formUser.password2,
        roleid: that.formUser.roleid,
        realname: that.formUser.realname,
        email: that.formUser.email,
        phone: that.formUser.phone,
      };
      that.$axios
        .post("/api.php?c=user&a=AddUser", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
            that.add_user_status = false;
            that.modal_add_user_loading_status = false;
            that.GetUserList();
          } else {
            that.$Message.error(result.message);
            that.add_user_status = false;
            that.modal_add_user_loading_status = false;
          }
        })
        .catch(function (error) {
          console.log(error);
          that.add_user_status = false;
          that.modal_add_user_loading_status = false;
        });
    },
    GetUserList() {
      var that = this;
      that.data1_loading_status = true;
      var data = {
        pageSize: that.page_size,
        currentPage: that.current,
      };
      that.$axios
        .post("/api.php?c=user&a=GetUserList", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.data1 = result.data;
            that.content_total = result.total;
            that.data1_loading_status = false;
          } else {
            that.$Message.error(result.message);
            that.data1_loading_status = false;
          }
        })
        .catch(function (error) {
          console.log(error);
          that.data1_loading_status = false;
        });
    },
    DelUser(index) {
      var that = this;
      var data = {
        del_userid: that.data1[index]["uid"],
      };
      that.$Modal.confirm({
        title: "警告",
        content: "确定要删除该账户吗？",
        loading: true,
        onOk: () => {
          that.$axios
            .post("/api.php?c=user&a=DelUser", data)
            .then(function (response) {
              var result = response.data;
              if (result.status == 1) {
                that.$Message.success(result.message);
                that.$Modal.remove();
                that.GetUserList();
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
    SetUserRepositoryList() {
      var that = this;
      that.modal_shouquan_loading_status = true;
      var data = {
        userid: that.formUser.userid,
        this_account_list: that.user_repository_list_data,
      };
      that.$axios
        .post("/api.php?c=user&a=SetUserRepositoryList", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
            that.shouquan_status = false;
            that.modal_shouquan_loading_status = false;
          } else {
            that.$Message.error(result.message);
            that.shouquan_status = false;
            that.modal_shouquan_loading_status = false;
          }
        })
        .catch(function (error) {
          console.log(error);
          that.shouquan_status = false;
          that.modal_shouquan_loading_status = false;
        });
    },
    GetUserRepositoryListAct() {
      var that = this;
      that.shouquan_loading_status = true;
      var data = {
        userid: that.formUser.userid,
      };
      that.$axios
        .post("/api.php?c=user&a=GetUserRepositoryList", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.user_repository_list_data = result.data;
            that.shouquan_loading_status = false;
          } else {
            that.$Message.error(result.message);
            that.shouquan_loading_status = false;
          }
        })
        .catch(function (error) {
          console.log(error);
          that.shouquan_loading_status = false;
        });
    },
    GetUserRepositoryList(index) {
      var that = this;
      that.modal_shouquan_loading_status = true;
      that.shouquan_status = true;
      that.formUser.userid = that.data1[index]["uid"];
      that.GetUserRepositoryListAct();
    },
  },
  created() {},
  mounted() {
    var that = this;
    that.GetUserList();
  },
};
</script>