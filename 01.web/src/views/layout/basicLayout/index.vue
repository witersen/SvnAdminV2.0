<style scoped>
@import "./index.css";
</style>

<template>
  <div>
    <Layout>
      <Header
        style="
          background-color: #9b1be5;
          background-image: -webkit-linear-gradient(
            0,
            #9b1be5 0%,
            #2085ea 100%
          );
          background-image: -o-linear-gradient(0, #9b1be5 0%, #2085ea 100%);
          background-image: -moz-linear-gradient(0, #9b1be5 0%, #2085ea 100%);
          background-image: linear-gradient(90deg, #9b1be5 0%, #2085ea 100%);
          color: #fff;
          position: fixed;
          width: 100%;
          z-index: 99;
        "
      >
        <img
          src="../../../assets/images/logo.png"
          style="line-height: 64px; position: absolute; top: 12px; left: 1%"
        />
        <Dropdown
        :transfer="true"
          trigger="click"
          @on-click="LogOut"
          style="float: right; zindex: 99"
        >
          <a href="javascript:void(0)" style="margin-left: 8px; color: #fff">
            {{ currentUsername }}
            <Icon type="md-arrow-dropdown" />
          </a>
          <DropdownMenu slot="list">
            <DropdownItem>退出</DropdownItem>
          </DropdownMenu>
        </Dropdown>
        <!-- <div style="float: right">
          <Divider type="vertical" />
        </div>
        <Dropdown trigger="click" style="float: right; zindex: 99">
          <a href="javascript:void(0)" style="margin-left: 8px; color: #fff">
            语言
            <Icon type="md-arrow-dropdown" />
          </a>
          <DropdownMenu slot="list">
            <DropdownItem>中文简体</DropdownItem>
          </DropdownMenu>
          <DropdownMenu slot="list">
            <DropdownItem>中文繁体</DropdownItem>
          </DropdownMenu>
          <DropdownMenu slot="list">
            <DropdownItem>English</DropdownItem>
          </DropdownMenu>
        </Dropdown> -->
        <div style="float: right">
          <Divider type="vertical" />
        </div>
        <div style="float: right">
          <!-- <Avatar icon="ios-person" /> -->
          <a style="margin-left: 8px; color: #fff; cursor: default">{{
            currentRoleName
          }}</a>
        </div>
      </Header>
      <Layout style="margin-top: 64px">
        <Sider
          style="
            min-height: calc(100vh - 64px);
            position: fixed;
            z-index: 99;
            height: 100%;
          "
        >
          <Menu
            theme="light"
            width="auto"
            :active-name="currentActiveName"
            style="height: 100%"
          >
            <MenuGroup
              :title="itemGroup.title"
              v-for="(itemGroup, indexGroup) in navList"
              :key="indexGroup"
            >
              <MenuItem
                :name="itemItem.name"
                :to="itemItem.path"
                v-for="(itemItem, indexItem) in itemGroup.value"
                :key="indexGroup + '-' + indexItem"
              >
                <Icon :type="itemItem.meta.icon" />
                {{ itemItem.meta.title }}
              </MenuItem>
            </MenuGroup>
          </Menu>
        </Sider>
        <Layout
          style="padding: 20px 30px 0px 220px; height: calc(100vh - 64px)"
        >
          <Breadcrumb style="padding: 0px 0px 20px 0px">
            <BreadcrumbItem v-for="(item, index) in breadcrumb" :key="index">{{
              item.meta.title
            }}</BreadcrumbItem>
          </Breadcrumb>
          <Content>
            <router-view></router-view>
          </Content>
        </Layout>
      </Layout>
    </Layout>
  </div>
</template>

<script>
export default {
  data() {
    return {
      //当前选中的导航
      currentActiveName: "",
      //logo文字内容
      logoContent: "SVN Admin",
      //用户名和角色
      currentUsername: sessionStorage.user_name,
      currentRoleName: sessionStorage.user_role_name,
      // 过滤后的导航
      navList: [],
      //面包屑
      breadcrumb: [],
    };
  },
  methods: {
    //退出登录
    LogOut() {
      var that = this;
      var data = {};
      that.$axios
        .post("/api.php?c=common&a=Logout&t=web", data)
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
        });
    },
    // 退出登录下拉组件
    handleCommand(command) {
      switch (command) {
        case "logOut":
          this.logOUt();
          break;
      }
    },
    //是否有效
    //动态生成侧边导航
    CreateNav() {
      var that = this;
      //过滤出母版组件的子页面
      var result = that.$router.options.routes.filter(
        (item) => item.name == "manage"
      );
      // that.navList = result[0].children;
      result = result[0].children;

      //标题分组
      var groupTitleArray = result
        .map((item) => item.meta.group)
        .filter((item) => item.name != "");

      //需要展示的导航
      result = result.filter((item) => item.meta.group.num > 0);

      //这里进行基于角色的过滤
      result = result.filter(
        (item) =>
          item.meta.user_role_id.indexOf(sessionStorage.user_role_id) != -1
      );

      //转换为两层结构
      var navList = [];
      for (var i = 0; i < groupTitleArray.length; i++) {
        var groupItemArray = [];
        for (var j = 0; j < result.length; j++) {
          if (result[j].meta.group.num == groupTitleArray[i].num) {
            groupItemArray.push(result[j]);
          }
        }
        if (groupItemArray.length == 0) {
          continue;
        }
        navList.push({
          title: groupTitleArray[i].name,
          value: groupItemArray,
        });
      }
      that.navList = navList;
    },
    //动态成面包屑
    SetBreadcrumb() {
      var that = this;
      that.breadcrumb = that.$route.matched;
    },
    //路由变化后自动设置导航选中状态
    SetActiveName() {
      this.currentActiveName = this.$route.name;
    },
  },
  mounted() {
    var that = this;
    //生成导航
    that.CreateNav();
    //生成面包屑
    that.SetBreadcrumb();
    //设置导航选中状态
    this.SetActiveName();
  },
  watch: {
    //监听路由变化
    $route() {
      //设置面包屑
      this.SetBreadcrumb();
    },
  },
};
</script>