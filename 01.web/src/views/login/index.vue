<style lang="less">
@import "./login.less";
</style>

<template>
  <div class="login">
    <div class="login-con">
      <Card icon="log-in" title="SVNAdmin V2.3" :bordered="false">
        <div class="form-con">
          <Form
            ref="formUserLogin"
            :model="formUserLogin"
            :rules="ruleValidateLogin"
            @keydown.enter.native="Submit('formUserLogin')"
          >
            <FormItem prop="user_name">
              <Input
                v-model="formUserLogin.user_name"
                placeholder="请输入用户名"
              >
                <span slot="prepend">
                  <Icon :size="16" type="ios-person"></Icon>
                </span>
              </Input>
            </FormItem>
            <FormItem prop="user_pass">
              <Input
                type="password"
                password
                v-model="formUserLogin.user_pass"
                placeholder="请输入密码"
              >
                <span slot="prepend">
                  <Icon :size="14" type="md-lock"></Icon>
                </span>
              </Input>
            </FormItem>
            <FormItem>
              <Select v-model="formUserLogin.user_role">
                <Option value="1">管理人员</Option>
                <Option value="2">SVN用户</Option>
              </Select>
            </FormItem>
            <FormItem prop="code">
              <Row>
                <Col span="11"
                  ><Input
                    v-model="formUserLogin.code"
                    placeholder="请输入验证码"
                  ></Input
                ></Col>
                <Col span="1"></Col>
                <Col span="12">
                  <img
                    @click="GetVeryfyCode"
                    :src="formUserLogin.base64"
                    :alt="loadingGetVeryfyCode"
                    style="width: 100%; cursor: pointer"
                  />
                </Col>
              </Row>
            </FormItem>
            <FormItem>
              <Button
                type="primary"
                long
                @click="Submit('formUserLogin')"
                :loading="loadingLogin"
                >登录</Button
              >
            </FormItem>
          </Form>
        </div>
      </Card>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      /**
       * 加载
       */
      loadingGetVeryfyCode: "loading......",
      loadingLogin: false,

      /**
       * 表单
       */
      // 登录表单
      formUserLogin: {
        user_name: "",
        user_pass: "",
        user_role: "2",
        code: "",
        uuid: "",
        base64: "",
      },

      /**
       * 校验规则
       */
      // 登录校验规则
      ruleValidateLogin: {
        user_name: [
          { required: true, message: "用户名不能为空", trigger: "blur" },
        ],
        user_pass: [
          { required: true, message: "密码不能为空", trigger: "blur" },
        ],
        code: [{ required: true, message: "验证码不能为空", trigger: "blur" }],
      },
    };
  },
  computed: {},
  created() {},
  mounted() {
    var that =this;
    if (sessionStorage.token) {
      that.$Message.success("已有登录信息 自动跳转中...");
      setTimeout(function () {
        if (sessionStorage.user_role_id == 1) {
          //管理员跳转到首页
          that.$router.push({ name: "index" });
        } else if (sessionStorage.user_role_id == 2) {
          //用户跳转到仓库页
          that.$router.push({ name: "repositoryInfo" });
        }
      }, 2000);
    } else {
      that.GetVeryfyCode();
    }
  },
  methods: {
    //表单提交
    Submit(formName) {
      this.$refs[formName].validate((valid) => {
        if (valid) {
          this.Login();
        } else {
          return false;
        }
      });
    },
    /**
     * 请求验证码
     */
    GetVeryfyCode() {
      var that = this;
      that.formUserLogin.base64 = "";
      that.loadingGetVeryfyCode = "loading......";
      var data = {};
      that.$axios
        .post("/api.php?c=common&a=GetVeryfyCode&t=web", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.formUserLogin.uuid = result.data.uuid;
            that.formUserLogin.base64 = result.data.base64;
          } else {
            that.$Message.error(result.message);
          }
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    //登录
    Login() {
      var that = this;
      that.loadingLogin = true;
      var data = {
        user_name: that.formUserLogin.user_name,
        user_pass: that.formUserLogin.user_pass,
        user_role: that.formUserLogin.user_role,
        uuid: that.formUserLogin.uuid,
        code: that.formUserLogin.code,
      };
      that.$axios
        .post("/api.php?c=common&a=Login&t=web", data)
        .then(function (response) {
          that.loadingLogin = false;
          var result = response.data;
          if (result.status == 1) {
            //存储
            sessionStorage.setItem("token", result.data.token);
            sessionStorage.setItem("user_name", result.data.user_name);
            sessionStorage.setItem("user_role_id", result.data.user_role_id);
            sessionStorage.setItem(
              "user_role_name",
              result.data.user_role_name
            );

            that.$Message.success(result.message);

            if (result.data.user_role_id == 1) {
              //管理员跳转到首页
              that.$router.push({ name: "index" });
            } else if (result.data.user_role_id == 2) {
              //用户跳转到仓库页
              that.$router.push({ name: "repositoryInfo" });
            }
          } else {
            that.GetVeryfyCode();
            that.$Message.error(result.message);
          }
        })
        .catch(function (error) {
          that.loadingLogin = false;
          console.log(error);
        });
    },
  },
};
</script>