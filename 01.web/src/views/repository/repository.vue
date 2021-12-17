<template>
  <Card :bordered="false" :dis-hover="true">
    <Tooltip max-width="150" :content="toolTipAddRep">
      <Button
        type="primary"
        @click="modalAddRep = true"
        :disabled="!boolIsAdmin"
        >新建仓库</Button
      >
    </Tooltip>
    <div class="page-table">
      <Table :columns="tableColumnRep" :data="tableDataRep">
        <template slot-scope="{ index }" slot="id">
          <strong>{{ index + 1 }}</strong>
        </template>
        <template slot-scope="{ index }" slot="action">
          <Button
            type="primary"
            size="small"
            @click="ModelRepUserGet(index)"
            :disabled="!boolIsAdmin"
            >用户</Button
          >
          <Button
            type="primary"
            size="small"
            @click="ModelRepGroupGet(index)"
            :disabled="!boolIsAdmin"
            >分组</Button
          >
          <Button
            type="primary"
            size="small"
            @click="ModelRepHooksGet(index)"
            :disabled="!boolIsAdmin"
            >Hooks</Button
          >
          <Button
            type="success"
            size="small"
            @click="ModelRepRename(index)"
            :disabled="!boolIsAdmin"
            >编辑</Button
          >
          <Button
            type="error"
            size="small"
            @click="ModelRepDel(index)"
            :disabled="!boolIsAdmin"
            >删除</Button
          >
        </template>
      </Table>
      <Card :bordered="false" :dis-hover="true">
        <Page
          v-if="numRepTotal != 0"
          :total="numRepTotal"
          :page-size="numRepPageSize"
          @on-change="PagesizeChange"
        />
      </Card>
    </div>
    <Modal v-model="modalAddRep" title="新建仓库" @on-ok="AddRep()">
      <Form :label-width="80" @submit.native.prevent>
        <FormItem label="仓库名称">
          <Input v-model="tempRepAddName" />
        </FormItem>
      </Form>
    </Modal>
    <Modal
      v-model="modalRepUserPriGet"
      title="SVN用户授权"
      @on-ok="SetRepUserPri"
    >
      <Table
        height="400"
        border
        :columns="tableColumnRepUserPri"
        :data="tableDataRepUserPri"
      >
        <template slot-scope="{ index }" slot="id">
          <strong>{{ index + 1 }}</strong>
        </template>
        <template slot-scope="{ index }" slot="slot_privilege">
          <RadioGroup
            type="button"
            v-model="tableDataRepUserPri[index].privilege"
          >
            <Radio label="rw"><span>读写</span></Radio>
            <Radio label="r"><span>读</span></Radio>
            <Radio label="no"><span>无</span></Radio>
          </RadioGroup>
        </template>
      </Table>
    </Modal>
    <Modal
      v-model="modalRepGroupPriGet"
      title="SVN用户组授权"
      @on-ok="SetRepGroupPri"
    >
      <Table
        height="400"
        border
        :columns="tableColumnRepGroupPri"
        :data="tableDataRepGroupPri"
      >
        <template slot-scope="{ index }" slot="id">
          <strong>{{ index + 1 }}</strong>
        </template>
        <template slot-scope="{ index }" slot="slot_privilege">
          <RadioGroup
            type="button"
            v-model="tableDataRepGroupPri[index].privilege"
          >
            <Radio label="rw"><span>读写</span></Radio>
            <Radio label="r"><span>读</span></Radio>
            <Radio label="no"><span>无</span></Radio>
          </RadioGroup>
        </template>
      </Table>
    </Modal>
    <Modal
      v-model="modalRepHooksGet"
      title="设置仓库钩子"
      width="600px"
      @on-ok="SetRepHooks()"
    >
      <Form ref="formRepHooks" :model="formRepHooks" :label-width="90">
        <FormItem label="Hooks类型">
          <Select v-model="formRepHooks.select_hooks_type">
            <Option
              v-for="item in formRepHooks.hooks_type_list"
              :value="item.value"
              :key="item.value"
              >{{ item.label }}</Option
            >
          </Select>
        </FormItem>
        <FormItem label="脚本内容">
          <Input
            v-model="formRepHooks.hooks_type_list[formRepHooks.select_hooks_type].shell"
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
      v-model="modalRepRenameGet"
      title="修改仓库名称"
      @on-ok="SetRepRename()"
    >
      <Form :label-width="80">
        <FormItem label="仓库名称">
          <Input v-model="tempRepEditName" />
        </FormItem>
      </Form>
    </Modal>
  </Card>
</template>
<script>
export default {
  data() {
    return {
      toolTipAddRep: "仓库名可由中文、数字、字母、下划线组成",
      /**
       * 分页数据
       */
      numRepPageCurrent: 1,
      numRepPageSize: 10,
      numRepTotal: 20,

      /**
       * 对话框控制
       */
      modalAddRep: false, //新建仓库
      modalRepRenameGet: false, //编辑仓库
      modalRepUserPriGet: false, //仓库用户授权
      modalRepGroupPriGet: false, //仓库用户组授权
      modalRepHooksGet: false, //仓库钩子

      /**
       * 布尔值
       */
      boolIsAdmin: Boolean(
        Number(window.sessionStorage.roleid) == 1 ? true : false
      ),

      /**
       * 临时变量
       */
      tempRepAddName: "", //添加仓库时的仓库名称
      tempRepEditName: "", //编辑仓库时的仓库名称
      tempRepCurrentSelect: "", //当前选中的仓库
      old_repository_name: "", //用于提交的原仓库名称值
      new_repository_name: "", //用于提交的修改后仓库名称值

      account_info: {
        account: "", //要提交修改的账户
        password: "", //要提交修改的密码
      },
      formRepHooks: {
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

      /**
       * 仓库 字段和数据
       */
      tableColumnRep: [
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
      tableDataRep: [
        // {
        //   id: 1,
        //   repository_name: "测试",
        //   repository_checkout_url: "svn:127.0.0.1/测试",
        //   repository_url: "/var/svn/",
        //   repository_web_url: "",
        //   repository_size: "",
        // },
      ],

      /**
       * 仓库用户组 字段和数据
       */
      tableColumnRepGroupPri: [
        {
          title: "序号",
          key: "id",
          slot: "id",
          width: 70,
        },
        {
          title: "名称",
          key: "account",
        },
        {
          title: "读写权限",
          key: "privilege",
          slot: "slot_privilege",
        },
      ],
      tableDataRepGroupPri: [],

      /**
       * 仓库用户 字段和数据
       */
      tableColumnRepUserPri: [
        {
          title: "序号",
          key: "id",
          slot: "id",
          width: 70,
        },
        {
          title: "名称",
          key: "account",
        },
        {
          title: "读写权限",
          key: "privilege",
          slot: "slot_privilege",
        },
      ],
      tableDataRepUserPri: [
        // {
        //   id: 1,
        //   account: "root",
        //   privilege: "no",
        // },
      ],
    };
  },
  methods: {
    SetRepHooks() {
      var that = this;
      var data = {
        repository_name: that.tempRepCurrentSelect,
        hooks_type_list: that.formRepHooks.hooks_type_list,
      };
      that.$axios
        .post("/api.php?c=svnserve&a=SetRepHooks", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
            that.modalRepHooksGet = false;
          } else {
            that.$Message.error(result.message);
            that.modalRepHooksGet = false;
          }
        })
        .catch(function (error) {
          console.log(error);
          that.modalRepHooksGet = false;
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
            that.formRepHooks.hooks_type_list = result.data;
          } else {
            that.$Message.error(result.message);
          }
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    PagesizeChange(value) {
      var that = this;
      that.numRepPageCurrent = value; //设置当前页数
      that.GetRepositoryList();
    },
    //显示重命名仓库对话框
    ModelRepRename(index) {
      var that = this;
      that.modalRepRenameGet = true;
      that.tempRepEditName = that.tableDataRep[index]["repository_name"];
      that.old_repository_name = that.tableDataRep[index]["repository_name"];
    },
    //修改仓库名称
    SetRepRename() {
      var that = this;
      that.new_repository_name = that.tempRepEditName;
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
            that.modalRepRenameGet = false;
            that.GetRepositoryList();
          } else {
            that.$Message.error(result.message);
            that.modalRepRenameGet = false;
          }
        })
        .catch(function (error) {
          console.log(error);
          that.modalRepRenameGet = false;
        });
    },
    //显示删除仓库对话框
    ModelRepDel(index) {
      var that = this;
      var repository_name = that.tableDataRep[index]["repository_name"];
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
    //设置仓库用户组权限
    SetRepGroupPri() {
      var that = this;
      var data = {
        repository_name: that.tempRepCurrentSelect,
        this_account_list: that.tableDataRepGroupPri,
      };
      that.$axios
        .post("/api.php?c=svnserve&a=SetRepositoryGroupPrivilege", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
            that.modalRepGroupPriGet = false;
          } else {
            that.$Message.error(result.message);
          }
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    //设置仓库用户权限
    SetRepUserPri() {
      var that = this;
      var data = {
        repository_name: that.tempRepCurrentSelect,
        this_account_list: that.tableDataRepUserPri,
      };
      that.$axios
        .post("/api.php?c=svnserve&a=SetRepositoryUserPrivilege", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
            that.modalRepUserPriGet = false;
          } else {
            that.$Message.error(result.message);
          }
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    ModelRepHooksGet(index) {
      var that = this;
      that.modalRepHooksGet = true;
      that.tempRepCurrentSelect = that.tableDataRep[index]["repository_name"];
      that.GetRepositoryHooks(that.tempRepCurrentSelect);
    },
    //svn用户授权
    ModelRepUserGet(index) {
      var that = this;
      that.modalRepUserPriGet = true;
      that.tempRepCurrentSelect = that.tableDataRep[index]["repository_name"];
      that.GetRepUserPri(that.tableDataRep[index]["repository_name"]);
    },
    //svn用户组授权
    ModelRepGroupGet(index) {
      var that = this;
      that.modalRepGroupPriGet = true;
      that.tempRepCurrentSelect = that.tableDataRep[index]["repository_name"];
      that.GetRepGroupPri(that.tableDataRep[index]["repository_name"]);
    },
    AddRep() {
      var that = this;
      var data = {
        repository_name: that.tempRepAddName,
      };
      that.$axios
        .post("/api.php?c=svnserve&a=AddRepository", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
            that.modalAddRep = false;

            that.GetRepositoryList();
          } else {
            that.$Message.error(result.message);
            that.modalAddRep = false;
          }
        })
        .catch(function (error) {
          console.log(error);
          that.modalAddRep = false;
        });
    },
    GetRepositoryList() {
      var that = this;
      var data = {
        pageSize: that.numRepPageSize,
        currentPage: that.numRepPageCurrent,
      };
      that.$axios
        .post("/api.php?c=svnserve&a=GetRepositoryList", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.tableDataRep = result.data;
            that.numRepTotal = result.total;
          } else {
            that.$Message.error(result.message);
            that.numRepTotal = 0;
          }
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    //获取仓库的用户列表和权限
    GetRepUserPri(repository_name) {
      var that = this;
      var data = {
        repository_name: repository_name,
      };
      that.$axios
        .post("/api.php?c=svnserve&a=GetRepositoryUserPrivilegeList", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.tableDataRepUserPri = result.data;
          } else {
            that.$Message.error(result.message);
          }
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    //获取仓库的用户组列表和权限
    GetRepGroupPri(repository_name) {
      var that = this;
      var data = {
        repository_name: repository_name,
      };
      that.$axios
        .post("/api.php?c=svnserve&a=GetRepositoryGroupPrivilegeList", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.tableDataRepGroupPri = result.data;
          } else {
            that.$Message.error(result.message);
          }
        })
        .catch(function (error) {
          console.log(error);
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