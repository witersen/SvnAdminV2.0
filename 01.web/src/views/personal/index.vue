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
                      v-model="formUpdAdminUserName.displayUserName"
                      readonly
                    ></Input>
                  </Col>
                  <Col span="1"> </Col>
                  <Col span="6">
                    <Button type="success" @click="ModalUpdAdminUserName"
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
                  <Col span="1"> </Col>
                  <Col span="6">
                    <Button type="success" @click="ModalUpdAdminUserPass"
                      >修改</Button
                    ></Col
                  >
                </Row>
              </FormItem>
            </Form>
            <Form :label-width="100" v-if="user_role_id == 3">
              <FormItem label="子管理员账户">
                <Row>
                  <Col span="12">
                    <Input
                      v-model="formUpdAdminUserName.displayUserName"
                      readonly
                    ></Input>
                  </Col>
                  <Col span="1"> </Col>
                  <Col span="6"> </Col>
                </Row>
              </FormItem>
              <FormItem label="子管理员密码">
                <Row>
                  <Col span="12">
                    <Input type="password" value="******" readonly></Input>
                  </Col>
                  <Col span="1"> </Col>
                  <Col span="6">
                    <Button type="success" @click="ModalUpdSubadminPass"
                      >修改</Button
                    ></Col
                  >
                </Row>
              </FormItem>
            </Form>
            <Form
              :model="formUpdSvnUserPass"
              :label-width="100"
              v-if="user_role_id == 2"
            >
              <FormItem label="用户名">
                <Input readonly v-model="formUpdSvnUserPass.userName"></Input>
              </FormItem>
              <FormItem label="新密码">
                <Input
                  type="password"
                  password
                  v-model="formUpdSvnUserPass.newPassword"
                ></Input>
              </FormItem>
              <FormItem label="确认新密码">
                <Input
                  type="password"
                  password
                  v-model="formUpdSvnUserPass.confirm"
                ></Input>
              </FormItem>
              <FormItem>
                <Button
                  type="primary"
                  :loading="loadingUpdSvnUserPass"
                  @click="UpdSvnUserPass"
                  >确认修改</Button
                >
              </FormItem>
            </Form>
          </Card>
        </TabPane>
      </Tabs>
    </Card>
    <Modal
      v-model="modalUpdAdminUserName"
      :draggable="true"
      title="修改管理员账号"
    >
      <Form :model="formUpdAdminUserName" :label-width="80">
        <FormItem label="新账号">
          <Input v-model="formUpdAdminUserName.userName"></Input>
        </FormItem>
        <FormItem label="确认">
          <Input v-model="formUpdAdminUserName.confirm"></Input>
        </FormItem>
        <FormItem>
          <Button
            type="primary"
            :loading="loadingUpdAdminUserName"
            @click="UpdAdminUserName"
            >{{ $t('confirm') }}</Button
          >
        </FormItem>
      </Form>
      <div slot="footer">
        <Button type="primary" ghost @click="modalUpdAdminUserName = false"
          >{{ $t('cancel') }}</Button
        >
      </div>
    </Modal>
    <Modal
      v-model="modalUpdAdminUserPass"
      :draggable="true"
      title="修改管理员密码"
    >
      <Form :model="formUpdAdminUserPass" :label-width="80">
        <FormItem label="新密码">
          <Input
            v-model="formUpdAdminUserPass.password"
            type="password"
            password
          ></Input>
        </FormItem>
        <FormItem label="确认">
          <Input
            v-model="formUpdAdminUserPass.confirm"
            type="password"
            password
          ></Input>
        </FormItem>
        <FormItem>
          <Button
            type="primary"
            :loading="loadingUpdAdminUserPass"
            @click="UpdAdminUserPass"
            >{{ $t('confirm') }}</Button
          >
        </FormItem>
      </Form>
      <div slot="footer">
        <Button type="primary" ghost @click="modalUpdAdminUserPass = false"
          >{{ $t('cancel') }}</Button
        >
      </div>
    </Modal>
    <Modal
      v-model="modalUpdSubadminPass"
      :draggable="true"
      title="修改子管理员密码"
    >
      <Form :model="formUpdSubadminPass" :label-width="80">
        <FormItem label="新密码">
          <Input
            v-model="formUpdSubadminPass.password"
            type="password"
            password
          ></Input>
        </FormItem>
        <FormItem label="确认">
          <Input
            v-model="formUpdSubadminPass.confirm"
            type="password"
            password
          ></Input>
        </FormItem>
        <FormItem>
          <Button
            type="primary"
            :loading="loadingUpdSubadminPass"
            @click="UpdSubadminPass"
            >{{ $t('confirm') }}</Button
          >
        </FormItem>
      </Form>
      <div slot="footer">
        <Button type="primary" ghost @click="modalUpdSubadminPass = false"
          >{{ $t('cancel') }}</Button
        >
      </div>
    </Modal>
  </div>
</template>

<script>
import i18n from "@/i18n";
export default {
  data() {
    return {
      user_role_id: sessionStorage.user_role_id,
      /**
       * 对话框
       */
      modalUpdAdminUserName: false,
      modalUpdAdminUserPass: false,
      modalUpdSubadminPass: false,
      /**
       * 加载
       */
      loadingUpdAdminUserName: false,
      loadingUpdAdminUserPass: false,
      loadingUpdSvnUserPass: false,
      loadingUpdSubadminPass: false,
      /**
       * 表单
       */
      formUpdAdminUserName: {
        displayUserName: sessionStorage.user_name,
        userName: "",
        confirm: "",
      },
      formUpdAdminUserPass: {
        password: "",
        confirm: "",
      },
      formUpdSvnUserPass: {
        userName: sessionStorage.user_name,
        newPassword: "",
        confirm: "",
      },
      formUpdSubadminPass: {
        password: "",
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
    ModalUpdAdminUserName() {
      this.modalUpdAdminUserName = true;
    },
    UpdAdminUserName() {
      var that = this;
      that.loadingUpdAdminUserName = true;
      var data = {
        userName: that.formUpdAdminUserName.userName,
        confirm: that.formUpdAdminUserName.confirm,
      };
      that.$axios
        .post("api.php?c=Personal&a=EditAdminUserName&t=web", data)
        .then(function (response) {
          var result = response.data;
          that.loadingUpdAdminUserName = false;
          if (result.status == 1) {
            that.modalUpdAdminUserName = false;
            that.$Message.success(result.message);
            that.Logout();
          } else {
            that.$Message.error({ content: result.message, duration: 2 });
          }
        })
        .catch(function (error) {
          that.loadingUpdAdminUserName = false;
          console.log(error);
          that.$Message.error(i18n.t('errors.contactAdmin'));
        });
    },
    /**
     * 管理人员修改密码
     */
    ModalUpdAdminUserPass() {
      this.modalUpdAdminUserPass = true;
    },
    UpdAdminUserPass() {
      var that = this;
      that.loadingUpdAdminUserPass = true;
      var data = {
        password: that.formUpdAdminUserPass.password,
        confirm: that.formUpdAdminUserPass.confirm,
      };
      that.$axios
        .post("api.php?c=Personal&a=EditAdminUserPass&t=web", data)
        .then(function (response) {
          var result = response.data;
          that.loadingUpdAdminUserPass = false;
          if (result.status == 1) {
            that.modalUpdAdminUserPass = false;
            that.$Message.success(result.message);
            that.Logout();
          } else {
            that.$Message.error({ content: result.message, duration: 2 });
          }
        })
        .catch(function (error) {
          that.loadingUpdAdminUserPass = false;
          console.log(error);
          that.$Message.error(i18n.t('errors.contactAdmin'));
        });
    },
    /**
     * 子管理员修改密码
     */
    ModalUpdSubadminPass() {
      this.modalUpdSubadminPass = true;
    },
    UpdSubadminPass() {
      var that = this;
      that.loadingUpdSubadminPass = true;
      var data = {
        password: that.formUpdSubadminPass.password,
        confirm: that.formUpdSubadminPass.confirm,
      };
      that.$axios
        .post("api.php?c=Personal&a=UpdSubadminUserPass&t=web", data)
        .then(function (response) {
          var result = response.data;
          that.loadingUpdSubadminPass = false;
          if (result.status == 1) {
            that.modalUpdSubadminPass = false;
            that.$Message.success(result.message);
            that.Logout();
          } else {
            that.$Message.error({ content: result.message, duration: 2 });
          }
        })
        .catch(function (error) {
          that.loadingUpdSubadminPass = false;
          console.log(error);
          that.$Message.error(i18n.t('errors.contactAdmin'));
        });
    },
    /**
     * SVN用户修改自己的密码
     */
    UpdSvnUserPass() {
      var that = this;
      that.loadingUpdSvnUserPass = true;
      var data = {
        userName: sessionStorage.user_name,
        newPassword: that.formUpdSvnUserPass.newPassword,
        confirm: that.formUpdSvnUserPass.confirm,
      };
      that.$axios
        .post("api.php?c=Personal&a=EditSvnUserPass&t=web", data)
        .then(function (response) {
          that.loadingUpdSvnUserPass = false;
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
            that.Logout();
          } else {
            that.$Message.error({ content: result.message, duration: 2 });
          }
        })
        .catch(function (error) {
          that.loadingUpdSvnUserPass = false;
          console.log(error);
          that.$Message.error(i18n.t('errors.contactAdmin'));
        });
    },
    /**
     * 退出登录
     */
    Logout() {
      var that = this;
      var data = {};
      that.$axios
        .post("api.php?c=Common&a=Logout&t=web", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            sessionStorage.clear();
            that.$Message.success(result.message);
            that.$router.push({ name: "login" });
          } else {
            that.$Message.error({ content: result.message, duration: 2 });
          }
        })
        .catch(function (error) {
          console.log(error);
          that.$Message.error(i18n.t('errors.contactAdmin'));
        });
    },
  },
};
</script>

<style >
</style>