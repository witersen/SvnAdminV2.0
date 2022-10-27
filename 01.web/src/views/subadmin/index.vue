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
          <Button
            icon="md-add"
            type="primary"
            ghost
            @click="ModalCreateSubadmin"
            >新建子管理员</Button
          >
        </Col>
        <Col :xs="3" :sm="4" :md="5" :lg="6">
          <Input
            v-model="searchKeywordSubadmin"
            search
            enter-button
            placeholder="通过用户名、备注信息搜索..."
            style="width: 100%"
            @on-search="SearchGetSubadminList"
        /></Col>
      </Row>
      <Table
        @on-sort-change="SortChangeSubadmin"
        border
        :columns="tableColumnSubadmin"
        :data="tableDataSubadmin"
        :loading="loadingSubadmin"
        size="small"
      >
        <template slot-scope="{ index }" slot="index">
          {{ pageSizeUser * (pageCurrentSubadmin - 1) + index + 1 }}
        </template>
        <template slot-scope="{ row }" slot="subadmin_password">
          <Input
            :border="false"
            v-model="row.subadmin_password"
            readonly
            type="password"
            password
          />
        </template>
        <template slot-scope="{ row }" slot="subadmin_status">
          <Switch
            v-model="row.subadmin_status"
            false-color="#ff4949"
            @on-change="(value) => UpdSubadminStatus(value, row.subadmin_id)"
          >
            <Icon type="md-checkmark" slot="open"></Icon>
            <Icon type="md-close" slot="close"></Icon>
          </Switch>
        </template>
        <template slot-scope="{ row, index }" slot="subadmin_note">
          <Input
            :border="false"
            v-model="tableDataSubadmin[index].subadmin_note"
            @on-blur="UpdSubadminNote(index, row.subadmin_id)"
          />
        </template>
        <template slot-scope="{ row, index }" slot="action">
          <Button
            type="warning"
            size="small"
            @click="
              ModalUpdSubadminPass(index, row.subadmin_id, row.subadmin_name)
            "
            >重置</Button
          >
          <Button
            type="error"
            size="small"
            @click="DelSubadmin(index, row.subadmin_id)"
            >删除</Button
          >
        </template>
      </Table>
      <Card :bordered="false" :dis-hover="true">
        <Page
          v-if="totalUser != 0"
          :total="totalUser"
          :current="pageCurrentSubadmin"
          :page-size="pageSizeUser"
          @on-page-size-change="UserPageSizeChange"
          @on-change="UserPageChange"
          size="small"
          show-sizer
        />
      </Card>
    </Card>
    <Modal v-model="modalCreateSubadmin" :draggable="true" title="新建子管理员">
      <Form :model="formCreateSubadmin" :label-width="80">
        <FormItem label="用户名">
          <Input v-model="formCreateSubadmin.subadmin_name"></Input>
        </FormItem>
        <FormItem label="密码">
          <Input
            type="password"
            password
            v-model="formCreateSubadmin.subadmin_password"
          ></Input>
        </FormItem>
        <FormItem label="备注">
          <Input v-model="formCreateSubadmin.subadmin_note"></Input>
        </FormItem>
        <FormItem>
          <Button
            type="primary"
            @click="CreateSubadmin"
            :loading="loadingCreateSubadmin"
            >确定</Button
          >
        </FormItem>
      </Form>
      <div slot="footer">
        <Button type="primary" ghost @click="modalCreateSubadmin = false"
          >取消</Button
        >
      </div>
    </Modal>
    <Modal
      v-model="modalEditUserPass"
      :draggable="true"
      :title="titleEditUser"
      @on-ok="UpdSubadminPass"
    >
      <Form :model="formUpdSubadmin" :label-width="80">
        <FormItem label="新密码">
          <Input
            v-model="formUpdSubadmin.subadmin_password"
            type="password"
            password
          ></Input>
        </FormItem>
        <FormItem>
          <Button
            type="primary"
            @click="UpdSubadminPass"
            :loading="loadingUpdSubadminPass"
            >确定</Button
          >
        </FormItem>
      </Form>
      <div slot="footer">
        <Button type="primary" ghost @click="modalEditUserPass = false"
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
      //子管理员
      pageCurrentSubadmin: 1,
      pageSizeUser: 20,
      totalUser: 0,

      /**
       * 搜索关键词
       */
      searchKeywordSubadmin: "",

      /**
       * 排序数据
       */
      sortNameGetSubadminList: "subadmin_id",
      sortTypeGetSubadminList: "asc",

      /**
       * 加载
       */
      //子管理员列表
      loadingSubadmin: true,
      //创建子管理员
      loadingCreateSubadmin: false,
      //重置子管理员密码
      loadingUpdSubadminPass: false,

      /**
       * 临时变量
       */
      //输入的 passwd 文件内容
      tempPasswdContent: "",

      /**
       * 对话框
       */
      //新建仓库
      modalCreateSubadmin: false,
      //编辑仓库信息
      modalEditUserPass: false,
      //识别 passwd 文件
      modalScanPasswd: false,
      /**
       * 表单
       */
      //新建子管理员
      formCreateSubadmin: {
        subadmin_name: "",
        subadmin_password: "",
        subadmin_note: "",
      },
      //编辑子管理员
      formUpdSubadmin: {
        subadmin_id: 0,
        subadmin_name: "",
        subadmin_password: "",
      },
      /**
       * 标题
       */
      titleEditUser: "",
      /**
       * 表格
       */
      //仓库信息
      tableColumnSubadmin: [
        {
          title: "序号",
          slot: "index",
          fixed: "left",
          minWidth: 80,
        },
        {
          title: "用户名",
          key: "subadmin_name",
          tooltip: true,
          sortable: "custom",
          minWidth: 120,
        },
        {
          title: "启用状态",
          key: "subadmin_status",
          slot: "subadmin_status",
          sortable: "custom",
          minWidth: 120,
        },
        {
          title: "备注信息",
          slot: "subadmin_note",
          minWidth: 120,
        },
        {
          title: "创建时间",
          key: "create_time",
          minWidth: 120,
        },
        {
          title: "其它",
          slot: "action",
          minWidth: 180,
        },
      ],
      tableDataSubadmin: [],
    };
  },
  computed: {},
  created() {},
  mounted() {
    this.GetSubadminList();
  },
  methods: {
    /**
     * 每页数量改变
     */
    UserPageSizeChange(value) {
      //设置每页条数
      this.pageSizeUser = value;
      this.GetSubadminList();
    },
    /**
     * 页码改变
     */
    UserPageChange(value) {
      //设置当前页数
      this.pageCurrentSubadmin = value;
      this.GetSubadminList();
    },
    /**
     * 获取SVN子管理员列表
     */
    SearchGetSubadminList() {
      // if (this.searchKeywordSubadmin == "") {
      //   this.$Message.error("请输入搜索内容");
      //   return;
      // }
      this.GetSubadminList();
    },
    GetSubadminList() {
      var that = this;
      that.loadingSubadmin = true;
      that.tableDataSubadmin = [];
      // that.totalUser = 0;
      var data = {
        pageSize: that.pageSizeUser,
        currentPage: that.pageCurrentSubadmin,
        searchKeyword: that.searchKeywordSubadmin,
        sortName: that.sortNameGetSubadminList,
        sortType: that.sortTypeGetSubadminList,
      };
      that.$axios
        .post("/api.php?c=Subadmin&a=GetSubadminList&t=web", data)
        .then(function (response) {
          that.loadingSubadmin = false;
          var result = response.data;
          if (result.status == 1) {
            // that.$Message.success(result.message);
            that.tableDataSubadmin = result.data.data;
            that.totalUser = result.data.total;
          } else {
            that.$Message.error({ content: result.message, duration: 2 });
          }
        })
        .catch(function (error) {
          that.loadingSubadmin = false;
          console.log(error);
          that.$Message.error("出错了 请联系管理员！");
        });
    },
    /**
     * 启用或禁用子管理员
     */
    UpdSubadminStatus(status, subadmin_id) {
      var that = this;
      var data = {
        subadmin_id: subadmin_id,
        status: status,
      };
      that.$axios
        .post("/api.php?c=Subadmin&a=UpdSubadminStatus&t=web", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
            that.GetSubadminList();
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
     * 编辑子管理员备注信息
     */
    UpdSubadminNote(index, subadmin_id) {
      var that = this;
      var data = {
        subadmin_id: subadmin_id,
        subadmin_note: that.tableDataSubadmin[index].subadmin_note,
      };
      that.$axios
        .post("/api.php?c=Subadmin&a=UpdSubadminNote&t=web", data)
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
     * 子管理员排序
     */
    SortChangeSubadmin(value) {
      this.sortNameGetSubadminList = value.key;
      if (value.order == "desc" || value.order == "asc") {
        this.sortTypeGetSubadminList = value.order;
      }
      this.GetSubadminList();
    },
    /**
     * 新建子管理员
     */
    ModalCreateSubadmin() {
      this.modalCreateSubadmin = true;
    },
    CreateSubadmin() {
      var that = this;
      that.loadingCreateSubadmin = true;
      var data = {
        subadmin_name: that.formCreateSubadmin.subadmin_name,
        subadmin_password: that.formCreateSubadmin.subadmin_password,
        subadmin_note: that.formCreateSubadmin.subadmin_note,
      };
      that.$axios
        .post("/api.php?c=Subadmin&a=CreateSubadmin&t=web", data)
        .then(function (response) {
          that.loadingCreateSubadmin = false;
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
            that.modalCreateSubadmin = false;
            that.GetSubadminList();
          } else {
            that.$Message.error({ content: result.message, duration: 2 });
          }
        })
        .catch(function (error) {
          that.loadingCreateSubadmin = false;
          console.log(error);
          that.$Message.error("出错了 请联系管理员！");
        });
    },
    /**
     * 重置子管理员密码
     */
    ModalUpdSubadminPass(index, subadmin_id, subadmin_name) {
      //设置标题
      this.titleEditUser = "重置密码 - " + subadmin_name;
      //设置选中子管理员
      this.formUpdSubadmin.subadmin_id = subadmin_id;
      //显示对话框
      this.modalEditUserPass = true;
    },
    UpdSubadminPass() {
      var that = this;
      that.loadingUpdSubadminPass = true;
      var data = {
        subadmin_id: that.formUpdSubadmin.subadmin_id,
        subadmin_password: that.formUpdSubadmin.subadmin_password,
      };
      that.$axios
        .post("/api.php?c=Subadmin&a=UpdSubadminPass&t=web", data)
        .then(function (response) {
          that.loadingUpdSubadminPass = false;
          var result = response.data;
          if (result.status == 1) {
            that.modalEditUserPass = false;
            that.$Message.success(result.message);
            that.GetSubadminList();
          } else {
            that.$Message.error({ content: result.message, duration: 2 });
          }
        })
        .catch(function (error) {
          that.loadingUpdSubadminPass = false;
          console.log(error);
          that.$Message.error("出错了 请联系管理员！");
        });
    },
    /**
     * 删除子管理员
     */
    DelSubadmin(index, subadmin_id, subadmin_name) {
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
                        innerHTML: "删除子管理员 - " + subadmin_name,
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
                          "删除子管理员 - " + subadmin_name
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
                    innerHTML: "确定要删除该子管理员吗？<br/>该操作不可逆！",
                  },
                }),
              ]
            ),
          ]);
        },
        onOk: () => {
          var data = {
            subadmin_id: subadmin_id,
          };
          that.$axios
            .post("/api.php?c=Subadmin&a=DelSubadmin&t=web", data)
            .then(function (response) {
              var result = response.data;
              if (result.status == 1) {
                that.$Message.success(result.message);
                that.GetSubadminList();
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
  },
};
</script>

<style >
</style>