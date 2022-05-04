<template>
  <div>
    <Card :bordered="false" :dis-hover="true">
      <Tabs value="name1">
        <TabPane label="修改密码" name="name1">
          <Card
            :bordered="false"
            :dis-hover="true"
            style="width: 450px; min-height: 321px"
          >
            <Form :label-width="100" v-if="user_role_id == 1">
              <FormItem label="管理员账户">
                <Row>
                  <Col span="12">
                    <Input
                      v-model="formEditAdminUserName.displayUserName"
                      readonly
                    ></Input>
                  </Col>
                  <Col span="6">
                    <Button type="success" @click="ModalEditAdminUserName"
                      >修改</Button
                    ></Col
                  >
                </Row>
              </FormItem>
              <FormItem label="管理员密码">
                <Row>
                  <Col span="12">
                    <Input type="password" value="******" readonly></Input>
                  </Col>
                  <Col span="6">
                    <Button type="success" @click="ModalEditAdminUserPass"
                      >修改</Button
                    ></Col
                  >
                </Row>
              </FormItem>
            </Form>
            <Form
              :model="formEditSvnUserPass"
              :label-width="100"
              v-if="user_role_id == 2"
            >
              <FormItem label="用户名">
                <Input readonly v-model="formEditSvnUserPass.userName"></Input>
              </FormItem>
              <FormItem label="旧密码">
                <Input
                  type="password"
                  password
                  v-model="formEditSvnUserPass.oldPassword"
                ></Input>
              </FormItem>
              <FormItem label="新密码">
                <Input
                  type="password"
                  password
                  v-model="formEditSvnUserPass.newPassword"
                ></Input>
              </FormItem>
              <FormItem label="确认新密码">
                <Input
                  type="password"
                  password
                  v-model="formEditSvnUserPass.confirm"
                ></Input>
              </FormItem>
              <FormItem>
                <Button
                  type="primary"
                  :loading="loadingEditSvnUserPass"
                  @click="EditSvnUserPass"
                  >确认修改</Button
                >
              </FormItem>
            </Form>
          </Card>
        </TabPane>
      </Tabs>
    </Card>
    <Modal v-model="modalEditAdminUserName" title="修改管理员账号">
      <Form :model="formEditAdminUserName" :label-width="80">
        <FormItem label="新账号">
          <Input v-model="formEditAdminUserName.userName"></Input>
        </FormItem>
        <FormItem label="确认">
          <Input v-model="formEditAdminUserName.confirm"></Input>
        </FormItem>
        <FormItem>
          <Button
            type="primary"
            :loading="loadingEditAdminUserName"
            @click="EditAdminUserName"
            >确定</Button
          >
        </FormItem>
      </Form>
      <div slot="footer">
        <Button type="primary" ghost @click="modalEditAdminUserName = false"
          >取消</Button
        >
      </div>
    </Modal>
    <Modal v-model="modalEditAdminUserPass" title="修改管理员密码">
      <Form :model="formEditAdminUserPass" :label-width="80">
        <FormItem label="新密码">
          <Input v-model="formEditAdminUserPass.password"></Input>
        </FormItem>
        <FormItem label="确认">
          <Input v-model="formEditAdminUserPass.confirm"></Input>
        </FormItem>
        <FormItem>
          <Button
            type="primary"
            :loading="loadingEditAdminUserPass"
            @click="EditAdminUserPass"
            >确定</Button
          >
        </FormItem>
      </Form>
      <div slot="footer">
        <Button type="primary" ghost @click="modalEditAdminUserPass = false"
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
      user_role_id: sessionStorage.user_role_id,
      /**
       * 对话框
       */
      modalEditAdminUserName: false,
      modalEditAdminUserPass: false,
      /**
       * 加载
       */
      loadingEditAdminUserName: false,
      loadingEditAdminUserPass: false,
      loadingEditSvnUserPass: false,
      /**
       * 表单
       */
      formEditAdminUserName: {
        displayUserName: sessionStorage.user_name,
        userName: "",
        confirm: "",
      },
      formEditAdminUserPass: {
        password: "",
        confirm: "",
      },
      formEditSvnUserPass: {
        userName: sessionStorage.user_name,
        oldPassword: "",
        newPassword: "",
        confirm: "",
      },
    };
  },
  computed: {},
  created() {},
  mounted() {},
  methods: {
    /**
     * 管理人员修改账号
     */
    ModalEditAdminUserName() {
      this.modalEditAdminUserName = true;
    },
    EditAdminUserName() {
      var that = this;
      that.loadingEditAdminUserName = true;
      var data = {
        userName: that.formEditAdminUserName.userName,
        confirm: that.formEditAdminUserName.confirm,
      };
      that.$axios
        .post("/api/Personal/EditAdminUserName?t=web", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.loadingEditAdminUserName = false;
            that.modalEditAdminUserName = false;
            that.$Message.success(result.message);
            that.LogOut();
          } else {
            that.loadingEditAdminUserName = false;
            that.$Message.error(result.message);
          }
        })
        .catch(function (error) {
          that.loadingEditAdminUserName = false;
          console.log(error);
          that.$Message.error("出错了 请联系管理员！");
        });
    },
    /**
     * 管理人员修改密码
     */
    ModalEditAdminUserPass() {
      this.modalEditAdminUserPass = true;
    },
    EditAdminUserPass() {
      var that = this;
      that.loadingEditAdminUserPass = true;
      var data = {
        password: that.formEditAdminUserPass.password,
        confirm: that.formEditAdminUserPass.confirm,
      };
      that.$axios
        .post("/api/Personal/EditAdminUserPass?t=web", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.loadingEditAdminUserPass = false;
            that.modalEditAdminUserPass = false;
            that.$Message.success(result.message);
            that.LogOut();
          } else {
            that.loadingEditAdminUserPass = false;
            that.$Message.error(result.message);
          }
        })
        .catch(function (error) {
          that.loadingEditAdminUserPass = false;
          console.log(error);
          that.$Message.error("出错了 请联系管理员！");
        });
    },
    /**
     * SVN用户修改自己的密码
     */
    EditSvnUserPass() {
      var that = this;
      that.loadingEditSvnUserPass = true;
      var data = {
        userName: sessionStorage.user_name,
        oldPassword: that.formEditSvnUserPass.oldPassword,
        newPassword: that.formEditSvnUserPass.newPassword,
        confirm: that.formEditSvnUserPass.confirm,
      };
      that.$axios
        .post("/api/Personal/EditSvnUserPass?t=web", data)
        .then(function (response) {
          that.loadingEditSvnUserPass = false;
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
            that.LogOut();
          } else {
            that.$Message.error(result.message);
          }
        })
        .catch(function (error) {
          that.loadingEditSvnUserPass = false;
          console.log(error);
          that.$Message.error("出错了 请联系管理员！");
        });
    },
    /**
     * 退出登录
     */
    //退出登录
    LogOut() {
      var that = this;
      var data = {};
      that.$axios
        .post("/api/Unimportant/Logout?t=web", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            sessionStorage.removeItem("token");
            sessionStorage.removeItem("user_name");
            sessionStorage.removeItem("user_role_id");
            sessionStorage.removeItem("user_role_name");
            that.$Message.success(result.message);
            that.$router.push({ name: "login" });
          } else {
            that.$Message.error(result.message);
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