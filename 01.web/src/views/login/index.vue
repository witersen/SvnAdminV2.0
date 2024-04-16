<style lang="less">
@import "./login.less";
</style>

<template>
  <div class="login">
    <div class="login-con">
      <Card icon="log-in" title="SVNAdmin V2.5.9" :bordered="false">
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
                :placeholder="$t('login.inputUsername')"
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
                :placeholder="$t('login.inputPassword')"
              >
                <span slot="prepend">
                  <Icon :size="14" type="md-lock"></Icon>
                </span>
              </Input>
            </FormItem>
            <FormItem>
              <Select
                v-model="formUserLogin.user_role"
                :transfer="true"
                @on-change="ChangeSelect"
              >
                <Option value="1">{{ $t('roles.管理员') }}</Option>
                <Option value="3">{{ $t('roles.子管理员') }}</Option>
                <Option value="2">{{ $t('roles.SVN用户') }}</Option>
              </Select>
            </FormItem>
            <FormItem prop="code" v-if="verifyOption">
              <Row>
                <Col span="11"
                  ><Input
                    v-model="formUserLogin.code"
                    :placeholder="$t('login.inputCode')"
                  ></Input
                ></Col>
                <Col span="1"></Col>
                <Col span="12">
                  <img
                    @click="GetVerifyCode"
                    :src="formUserLogin.base64"
                    :alt="loadingGetVerifyCode"
                    style="width: 100%; cursor: pointer"
                  />
                </Col>
              </Row>
            </FormItem>
            <FormItem prop="lang">
              <Row>
                <Col span="1"></Col>
                <Col span="11">
                    <Dropdown trigger="click" @on-click="translate">
                    <a href="javascript:void(0)">
                        {{ $t(this.lang ? this.lang : 'en') }}
                        <Icon type="md-arrow-dropdown" />
                    </a>
                    <DropdownMenu slot="list">
                        <DropdownItem name="zh">中文</DropdownItem>
                    </DropdownMenu>
                    <DropdownMenu slot="list">
                        <DropdownItem name="en">English</DropdownItem>
                    </DropdownMenu>
                    </Dropdown>
                </Col>
                <Col span="12"></Col>
              </Row>
            </FormItem>
            <FormItem>
              <Button
                type="primary"
                long
                @click="Submit('formUserLogin')"
                :loading="loadingLogin"
                >{{ $t('login.login') }}</Button
              >
            </FormItem>
          </Form>
        </div>
      </Card>
    </div>
  </div>
</template>

<script>
import i18n from '@/i18n'
export default {
  data() {
    return {
        //当前语言
        lang: this.$i18n.locale,
      /**
       * 加载
       */
      loadingGetVerifyCode: "loading......",
      loadingLogin: false,

      /**
       * 组件状态
       */
      verifyOption: false,

      /**
       * 表单
       */
      // 登录表单
      formUserLogin: {
        user_name: "",
        user_pass: "",
        user_role: "",
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
          { required: true, message: i18n.t("login.usernameCannotBeEmpty"), trigger: "blur" },
        ],
        user_pass: [
          { required: true, message: i18n.t("login.passwordCannotBeEmpty"), trigger: "blur" },
        ],
        code: [{ required: true, message: i18n.t("login.codeCannotBeEmpty"), trigger: "blur" }],
      },
    };
  },
  computed: {},
  created() {},
  mounted() {
    var that = this;
    //还原下拉
    that.formUserLogin.user_role = localStorage.user_role
      ? localStorage.user_role
      : "2";
    if (sessionStorage.token) {
      that.$Message.success(i18n.t("login.userAlreadyLogin"));
      setTimeout(function () {
        that.$router.push({ name: sessionStorage.firstRoute });
      }, 2000);
    } else {
      that.GetVerifyOption();
    }
  },
  methods: {
    translate(lng) {
        // console.log("browser language is "+navigator.language.substring(0, 2));
        // console.log("Translating to "+lng);
        this.lang = lng;
        this.$i18n.locale = this.lang
        this.ruleValidateLogin ={
            user_name: [
            { required: true, message: i18n.t("login.usernameCannotBeEmpty"), trigger: "blur" },
            ],
            user_pass: [
            { required: true, message: i18n.t("login.passwordCannotBeEmpty"), trigger: "blur" },
            ],
            code: [{ required: true, message: i18n.t("login.codeCannotBeEmpty"), trigger: "blur" }],
        };
    },
    //记录下拉
    ChangeSelect(value) {
      localStorage.setItem("user_role", value);
    },
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
     * 获取验证码选项
     */
    GetVerifyOption() {
      var that = this;
      var data = {};
      that.$axios
        .post("api.php?c=Setting&a=GetVerifyOption&t=web", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            if (result.data.enable == true) {
              that.verifyOption = true;
              that.GetVerifyCode();
            } else {
              that.verifyOption = false;
            }
          } else {
            // 取返回错误消息有效信息来翻译（key 里不支持中括号）：
            // 登录失败[验证码错误] -> 验证码错误 by result.message.substr(5, result.message.length - 6)
            that.$Message.error({ content: i18n.t('login.' + result.message.substr(5, result.message.length - 6)), duration: 2 });
          }
        })
        .catch(function (error) {
          console.log(error);
          that.$Message.error(i18n.t('errors.contactAdmin'));
        });
    },
    /**
     * 请求验证码
     */
    GetVerifyCode() {
      var that = this;
      that.formUserLogin.base64 = "";
      that.loadingGetVerifyCode = "loading......";
      var data = {};
      that.$axios
        .post("api.php?c=Common&a=GetVerifyCode&t=web", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.formUserLogin.uuid = result.data.uuid;
            that.formUserLogin.base64 = result.data.base64;
          } else {
            that.$Message.error({ content: i18n.t('login.' + result.message.substr(5, result.message.length - 6)), duration: 2 });
          }
        })
        .catch(function (error) {
          console.log(error);
          that.$Message.error(i18n.t('errors.contactAdmin'));
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
        .post("api.php?c=Common&a=Login&t=web", data)
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
            sessionStorage.setItem("route", JSON.stringify(result.data.route));
            sessionStorage.setItem(
              "functions",
              JSON.stringify(result.data.functions)
            );

            that.$Message.success(i18n.t('login.' + result.message));

            if (result.data.user_role_id == 1) {
              //管理员跳转到首页
              sessionStorage.setItem("firstRoute", "index");
            } else if (result.data.user_role_id == 2) {
              //用户跳转到仓库页
              sessionStorage.setItem("firstRoute", "repositoryInfo");
            } else if (result.data.user_role_id == 3) {
              //子管理员跳转到有权限的首个页面
              sessionStorage.setItem(
                "firstRoute",
                result.data.route.children[0].name
              );
            }
            that.$router.push({ name: sessionStorage.firstRoute });
          } else {
            that.GetVerifyOption();
            that.$Message.error({ content: i18n.t('login.' + result.message.substr(5, result.message.length - 6)), duration: 2 });
          }
        })
        .catch(function (error) {
          that.loadingLogin = false;
          console.log(error);
          that.$Message.error(i18n.t('errors.contactAdmin'));
        });
    },
  },
};
</script>