<style lang="less">
@import "./login.less";
</style>

<template>
  <div class="login">
    <div class="login-con">
      <Card icon="log-in" :bordered="false">
        <p slot="title">
          <Icon type="ios-finger-print" />
          SVN Admin 2.0
        </p>
        <div class="form-con">
          <login-form @on-success-valid="handleSubmit"></login-form>
        </div>
      </Card>
    </div>
    <Modal v-model="showVali" footer-hide :closable="false" width="340">
      <slide-verify
        :r="10"
        ref="slideblock"
        @success="onSuccess"
        @again="onAgain"
        @fulfilled="onFulfilled"
        @fail="onFail"
        @refresh="onRefresh"
        :slider-text="text"
        :imgs="imgs"
        :accuracy="accuracy"
      ></slide-verify>
      <div>{{ msg }}</div>
    </Modal>
  </div>
</template>

<script>
import img0 from "../../static/images/validate0.jpg";
import LoginForm from "../../components/login-form";
export default {
  components: {
    LoginForm,
  },
  data() {
    return {
      msg: "",
      demo: true,
      text: "向右滑动->",
      imgs: [img0],
      accuracy: 5,
      showVali: false, //验证码弹出框

      loginForm: {
        username: "",
        password: "",
      },

      ruleValidate: {
        username: [
          {
            required: true,
            message: "用户名不能为空.",
            trigger: "blur",
          },
        ],
        password: [
          {
            required: true,
            message: "密码不能为空.",
            trigger: "blur",
          },
        ],
      },
    };
  },
  methods: {
    handleSubmit({ userName, password }) {
      var that = this;
      that.loginForm.username = userName;
      that.loginForm.password = password;
      that.showVali = true;
    },
    onSuccess(times) {
      var that = this;
      that.showVali = false;
      that.login();
      that.onRest();
    },
    onFail() {
      this.msg = "验证失败，请重新验证";
    },
    onRefresh() {
      this.msg = "";
    },
    onFulfilled() {
      //刷新后的回调
    },
    onAgain() {
      var that = this;
      that.msg = "操作异常 请重试";
      that.onRest();
    },
    onRest() {
      var that = this;
      that.$refs.slideblock.reset();
      that.msg = "";
    },
    login() {
      var that = this;
      var data = {
        username: that.loginForm.username,
        password: that.loginForm.password,
      };
      that.$axios
        .post("/api.php?c=user&a=Login", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            window.sessionStorage.setItem("token", result.token);
            // window.sessionStorage.setItem("userid", result.userid);
            window.sessionStorage.setItem("username", result.username);
            window.sessionStorage.setItem("roleid", result.roleid);
            window.sessionStorage.setItem("rolename", result.rolename);

            that.$Message.success(result.message);
            that.$router.push({ name: "repository" }); // 跳转到首页
          } else {
            that.$Message.error(result.message);
          }
        })
        .catch(function (error) {
          console.log(error);
        });
    },
  },
};
</script>