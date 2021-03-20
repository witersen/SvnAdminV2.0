<template>
  <Card :bordered="false" :dis-hover="true">
    <Button type="primary" @click="add_repo_status = true" :disabled="isadmin"
      >添加仓库</Button
    >
    <div class="page-table">
      <Table
        :columns="repository_column"
        :data="repository_data"
        :loading="repository_data_loadingStatus"
      >
        <template slot-scope="{ row }" slot="id">
          <strong>{{ row.id + 1 }}</strong>
        </template>
        <template slot-scope="{ index }" slot="action">
          <Button type="primary" size="small" @click="yonghuStatus(index)"
            >用户</Button
          >
          <Button type="primary" size="small" @click="shouquanStatus(index)"
            >授权</Button
          >
          <Button type="primary" size="small" @click="hooksStatus(index)"
            >Hooks</Button
          >
          <Button type="success" size="small" @click="bianji(index)"
            >编辑</Button
          >
          <Button type="error" size="small" @click="DeleteRepository(index)"
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
      v-model="add_repo_status"
      title="添加仓库"
      @on-ok="AddRepository()"
      :loading="add_repo_loading_status"
    >
      <Form
        ref="formValidate"
        :model="formValidate"
        :label-width="80"
        @submit.native.prevent
      >
        <FormItem label="仓库名称">
          <Input v-model="formValidate.projectName" />
        </FormItem>
      </Form>
    </Modal>
    <Modal v-model="yonghu_status" title="用户" width="600">
      <Button
        type="primary"
        size="default"
        style="margin-bottom: 16px"
        @click="AddAccount_status = true"
        >添加用户</Button
      >
      <Table
        :loading="yonghu_loading_status"
        height="400"
        border
        :columns="yonghu_comumns"
        :data="yonghu_data"
      >
        <template slot-scope="{ row }" slot="id">
          <strong>{{ row.id + 1 }}</strong>
        </template>
        <template slot-scope="{ index }" slot="slot_act">
          <ButtonGroup size="default">
            <Button @click="EditAccount_status(index)">编辑</Button>
            <Button @click="DeleteAccount(index)">删除</Button>
          </ButtonGroup>
        </template>
      </Table>
    </Modal>
    <Modal
      v-model="AddAccount_status"
      title="添加用户"
      @on-ok="AddAccount()"
      :loading="add_account_loading_status"
    >
      <Form ref="account_info" :model="account_info" :label-width="80">
        <FormItem label="用户名">
          <Input v-model="account_info.account" />
        </FormItem>
        <FormItem label="密码">
          <Input v-model="account_info.password" />
        </FormItem>
      </Form>
    </Modal>
    <Modal
      v-model="shouquan_status"
      title="授权"
      @on-ok="SetRepositoryPrivilege"
      :loading="modal_shouquan_loading_status"
    >
      <Table
        :loading="shouquan_loading_status"
        height="400"
        border
        :columns="shouquan_comumns"
        :data="shouquan_data"
      >
        <template slot-scope="{ row }" slot="id">
          <strong>{{ row.id + 1 }}</strong>
        </template>
        <template slot-scope="{ index }" slot="slot_privilege">
          <RadioGroup type="button" v-model="shouquan_data[index].privilege">
            <Radio label="rw"><span>读写</span></Radio>
            <Radio label="r"><span>读</span></Radio>
            <Radio label="no"><span>无</span></Radio>
          </RadioGroup>
        </template>
      </Table>
    </Modal>
    <Modal
      v-model="hooks_status"
      title="Hooks"
      width="600px"
      @on-ok="SetRepositoryHooks()"
      :loading="modal_hooks_loading_status"
    >
      <Form ref="formHooks" :model="formHooks" :label-width="90">
        <FormItem label="Hooks类型">
          <Select v-model="formHooks.select_hooks_type">
            <Option
              v-for="item in formHooks.hooks_type_list"
              :value="item.value"
              :key="item.value"
              >{{ item.label }}</Option
            >
          </Select>
        </FormItem>
        <FormItem label="脚本内容">
          <Input
            v-model="formHooks.hooks_type_list[formHooks.select_hooks_type].shell"
            maxlength="10000"
            :rows="15"
            show-word-limit
            type="textarea"
            placeholder="请输入hooks shell脚本 首行需为：#!/bin/bash 或 #!/bin/sh"
          />
        </FormItem>
      </Form>
    </Modal>
    <Modal
      v-model="bianji_status"
      title="编辑"
      @on-ok="SetRepositoryInfo()"
      :loading="modal_bianji_status"
    >
      <Form
        ref="now_repository_name"
        :model="now_repository_name"
        :label-width="80"
      >
        <FormItem label="仓库名称">
          <Input v-model="now_repository_name.name" />
        </FormItem>
      </Form>
    </Modal>
    <Modal
      v-model="EditAccount_tanchu_status"
      title="编辑"
      @on-ok="EditAccount()"
      :loading="edit_account_loading_status"
    >
      <Form ref="account_info" :model="account_info" :label-width="80">
        <FormItem label="密码">
          <Input v-model="account_info.password" />
        </FormItem>
      </Form>
    </Modal>
  </Card>
</template>
<script>
export default {
  data() {
    return {
      modal_bianji_status: true, //编辑仓库确认加载中
      modal_hooks_loading_status: true, //钩子确定加载中
      modal_shouquan_loading_status: true, //授权确定加载中
      edit_account_loading_status: true, //编辑用户加载中
      add_account_loading_status: true, //添加用户加载中
      add_repo_loading_status: true, //添加仓库加载中
      delete_resp_loading_status: true, //仓库删除中
      formHooks: {
        select_hooks_type: "start-commit",
        hooks_type_list: {
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
      isadmin: Boolean(
        Number(window.sessionStorage.roleid) == 3 ? true : false
      ),
      EditAccount_tanchu_status: false, //仓库列表 仓库账户 点击编辑弹出框状态
      AddAccount_status: false, //仓库列表 仓库账户 点击添加弹出框状态
      current: 1, //当前在第几页
      page_size: 10, //每一页有几条数据
      content_total: 20, //总共有多少条数据
      selectedReposotoryItem: "", //当前选中的仓库
      now_repository_name: {
        name: "", //用于双向绑定的仓库名称值
      },
      old_repository_name: "", //用于提交的原仓库名称值
      new_repository_name: "", //用于提交的修改后仓库名称值
      add_repo_status: false, //添加仓库弹出框
      bianji_status: false, //编辑弹出框
      shouquan_status: false, //授权弹出框
      hooks_status: false, //hooks管理弹出框
      shouquan_loading_status: true, //授权弹出框加载中
      yonghu_status: false, //仓库用户管理 弹出框
      yonghu_loading_status: true, //仓库用户管理 弹出框加载中
      formValidate: {
        projectName: "", //添加仓库时的仓库名称
      },
      account_info: {
        account: "", //要提交修改的账户
        password: "", //要提交修改的密码
      },
      repository_data_loadingStatus: true, //数据加载动画
      repository_column: [
        {
          title: "序号",
          key: "id",
          slot: "id",
          width: 150,
        },
        {
          title: "仓库名称",
          key: "repository_name",
        },
        {
          title: "检出路径",
          key: "repository_checkout_url",
        },
        {
          title: "web路径",
          key: "repository_web_url",
        },
        {
          title: "服务器路径",
          key: "repository_url",
        },
        {
          title: "仓库体积(MB)",
          key: "repository_size",
        },
        {
          title: "操作",
          slot: "action",
          width: 300,
          align: "center",
        },
      ],
      repository_data: [
        // {
        //   id: 1,
        //   repository_name: "测试",
        //   repository_checkout_url: "svn:127.0.0.1/测试",
        //   repository_url: "/var/svn/",
        //   repository_web_url: "",
        //   repository_size: "",
        // },
      ],
      shouquan_comumns: [
        {
          title: "序号",
          key: "id",
          slot: "id",
          width: 70,
        },
        {
          title: "用户",
          key: "account",
        },
        {
          title: "读写权限",
          key: "privilege",
          slot: "slot_privilege",
        },
      ],
      shouquan_data: [
        // {
        //   id: 1,
        //   account: "root",
        //   privilege: "",
        // },
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
    SetRepositoryHooks() {
      var that = this;
      that.modal_hooks_loading_status = true;
      var data = {
        repository_name: that.selectedReposotoryItem,
        hooks_type_list: that.formHooks.hooks_type_list,
      };
      that.$axios
        .post("/api.php?c=svnserve&a=SetRepositoryHooks", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
            that.hooks_status = false;
            that.modal_hooks_loading_status = false;
          } else {
            that.$Message.error(result.message);
            that.hooks_status = false;
            that.modal_hooks_loading_status = false;
          }
        })
        .catch(function (error) {
          console.log(error);
          that.hooks_status = false;
          that.modal_hooks_loading_status = false;
        });
    },
    GetRepositoryHooks(repository_name) {
      var that = this;
      var data = {
        repository_name: repository_name,
      };
      that.$axios
        .post("/api.php?c=svnserve&a=GetRepositoryHooks", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.formHooks.hooks_type_list = result.data;
          } else {
            that.$Message.error(result.message);
          }
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    DeleteAccount(index) {
      var that = this;
      var account = that.yonghu_data[index]["account"];
      var data = {
        repository_name: that.selectedReposotoryItem,
        account: account,
      };
      that.$Modal.confirm({
        title: "警告",
        content: "确定要删除该账户吗？",
        loading: true,
        onOk: () => {
          that.$axios
            .post("/api.php?c=svnserve&a=DeleteAccount", data)
            .then(function (response) {
              var result = response.data;
              if (result.status == 1) {
                that.$Message.success(result.message);
                that.$Modal.remove();
                that.GetRepositoryUserList(that.selectedReposotoryItem);
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
    EditAccount_status(index) {
      var that = this;
      that.EditAccount_tanchu_status = true;
      that.edit_account_loading_status = true;
      that.account_info.account = that.yonghu_data[index]["account"];
      that.account_info.password = that.yonghu_data[index]["password"];
    },
    EditAccount() {
      var that = this;
      that.edit_account_loading_status = true;
      var data = {
        repository_name: that.selectedReposotoryItem,
        account: that.account_info.account,
        password: that.account_info.password,
      };
      that.$axios
        .post("/api.php?c=svnserve&a=SetCountInfo", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
            that.EditAccount_tanchu_status = false;
            that.edit_account_loading_status = false;
            that.GetRepositoryUserList(that.selectedReposotoryItem);
          } else {
            that.$Message.error(result.message);
            that.EditAccount_tanchu_status = false;
            that.edit_account_loading_status = false;
          }
        })
        .catch(function (error) {
          console.log(error);
          that.EditAccount_tanchu_status = false;
          that.edit_account_loading_status = false;
        });
    },
    AddAccount() {
      var that = this;
      that.add_account_loading_status = true;
      var data = {
        repository_name: that.selectedReposotoryItem,
        account: that.account_info.account,
        password: that.account_info.password,
      };
      that.$axios
        .post("/api.php?c=svnserve&a=AddAccount", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
            that.AddAccount_status = false;
            that.add_account_loading_status = false;
            that.GetRepositoryUserList(that.selectedReposotoryItem);
          } else {
            that.$Message.error(result.message);
            that.AddAccount_status = false;
            that.add_account_loading_status = false;
          }
        })
        .catch(function (error) {
          console.log(error);
          that.AddAccount_status = false;
          that.add_account_loading_status = false;
        });
    },
    pageChange(value) {
      var that = this;
      that.current = value; //设置当前页数
      that.GetRepositoryList();
    },
    bianji(index) {
      var that = this;
      that.bianji_status = true;
      that.modal_bianji_status = true;
      that.now_repository_name.name =
        that.repository_data[index]["repository_name"];
      that.old_repository_name = that.repository_data[index]["repository_name"];
    },
    SetRepositoryInfo() {
      var that = this;
      that.modal_bianji_status = true;
      that.new_repository_name = that.now_repository_name.name;
      var data = {
        old_repository_name: that.old_repository_name,
        new_repository_name: that.new_repository_name,
      };
      that.$axios
        .post("/api.php?c=svnserve&a=SetRepositoryInfo", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
            that.bianji_status = false;
            that.modal_bianji_status = false;
            that.GetRepositoryList();
          } else {
            that.$Message.error(result.message);
            that.bianji_status = false;
            that.modal_bianji_status = false;
          }
        })
        .catch(function (error) {
          console.log(error);
          that.bianji_status = false;
          that.modal_bianji_status = false;
        });
    },
    DeleteRepository(index) {
      var that = this;
      var repository_name = that.repository_data[index]["repository_name"];
      var data = {
        repository_name: repository_name,
      };
      that.$Modal.confirm({
        title: "警告",
        content: "确定要删除该仓库吗？",
        loading: true,
        onOk: () => {
          that.$axios
            .post("/api.php?c=svnserve&a=DeleteRepository", data)
            .then(function (response) {
              var result = response.data;
              if (result.status == 1) {
                that.$Message.success(result.message);
                that.$Modal.remove();
                that.GetRepositoryList();
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
    SetRepositoryPrivilege() {
      var that = this;
      that.modal_shouquan_loading_status = true;
      var data = {
        repository_name: that.selectedReposotoryItem,
        this_account_list: that.shouquan_data,
      };
      that.$axios
        .post("/api.php?c=svnserve&a=SetRepositoryPrivilege", data)
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
    yonghuStatus(index) {
      var that = this;
      that.yonghu_status = true;
      that.selectedReposotoryItem =
        that.repository_data[index]["repository_name"];
      that.GetRepositoryUserList(
        that.repository_data[index]["repository_name"]
      );
    },
    hooksStatus(index) {
      var that = this;
      that.hooks_status = true;
      that.modal_hooks_loading_status = true;
      that.selectedReposotoryItem =
        that.repository_data[index]["repository_name"];
      that.GetRepositoryHooks(that.selectedReposotoryItem);
    },
    shouquanStatus(index) {
      var that = this;
      that.shouquan_status = true;
      that.modal_shouquan_loading_status = true;
      that.selectedReposotoryItem =
        that.repository_data[index]["repository_name"];
      that.GetRepositoryUserPrivilegeList(
        that.repository_data[index]["repository_name"]
      );
    },
    AddRepository() {
      var that = this;
      that.add_repo_loading_status = true;
      var data = {
        repository_name: that.formValidate.projectName,
      };
      that.$axios
        .post("/api.php?c=svnserve&a=AddRepository", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
            that.add_repo_status = false;
            that.add_repo_loading_status = false;
            that.formValidate.projectName = "";

            that.GetRepositoryList();
          } else {
            that.$Message.error(result.message);
            that.add_repo_status = false;
            that.add_repo_loading_status = false;
            that.formValidate.projectName = "";
          }
        })
        .catch(function (error) {
          console.log(error);
          that.add_repo_status = false;
          that.add_repo_loading_status = false;
          that.formValidate.projectName = "";
        });
    },
    GetRepositoryList() {
      var that = this;
      that.repository_data_loadingStatus = true;
      var data = {
        pageSize: that.page_size,
        currentPage: that.current,
      };
      that.$axios
        .post("/api.php?c=svnserve&a=GetRepositoryList", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.repository_data_loadingStatus = false;
            that.repository_data = result.data;
            that.content_total = result.total;
          } else {
            that.$Message.error(result.message);
            that.content_total = 0;
            that.repository_data_loadingStatus = false;
          }
        })
        .catch(function (error) {
          console.log(error);
          that.repository_data_loadingStatus = false;
        });
    },
    GetRepositoryUserPrivilegeList(repository_name) {
      var that = this;
      that.shouquan_loading_status = true;
      var data = {
        repository_name: repository_name,
      };
      that.$axios
        .post("/api.php?c=svnserve&a=GetRepositoryUserPrivilegeList", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.shouquan_data = result.data;
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
    GetRepositoryUserList(repository_name) {
      var that = this;
      that.yonghu_loading_status = true;
      var data = {
        repository_name: repository_name,
      };
      that.$axios
        .post("/api.php?c=svnserve&a=GetRepositoryUserList", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.yonghu_data = result.data;
            that.yonghu_loading_status = false;
          } else {
            that.$Message.error(result.message);
            that.yonghu_loading_status = false;
          }
        })
        .catch(function (error) {
          console.log(error);
          that.yonghu_loading_status = false;
        });
    },
  },
  created() {},
  mounted() {
    var that = this;
    that.GetRepositoryList();
  },
};
</script>