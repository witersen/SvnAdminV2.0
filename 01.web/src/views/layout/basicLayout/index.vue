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
          align-items: center;
          display: flex;
          justify-content: flex-end;
          line-height: 0px;
        "
      >
        <img
          :src="require('@/assets/images/logo.png')"
          style="line-height: 64px; position: absolute; top: 12px; left: 1%"
        />
        <!-- 实时任务 -->
        <!-- <Badge dot style="margin-right: 8px; cursor: pointer">
          <Icon type="md-notifications-outline" size="26"></Icon>
        </Badge> -->
        <!-- 分割线 -->
        <!-- <Divider type="vertical" /> -->
        <!-- 用户身份 -->
        <a style="margin-left: 8px; color: #fff; cursor: default">{{
          currentRoleName
        }}</a>
        <!-- 分割线 -->
        <Divider type="vertical" />
        <!-- 当前登录用户 -->
        <Dropdown :transfer="true" trigger="click" @on-click="Logout">
          <a href="javascript:void(0)" style="margin-left: 8px; color: #fff">
            {{ currentUsername }}
            <Icon type="md-arrow-dropdown" />
          </a>
          <DropdownMenu slot="list">
            <DropdownItem>退出</DropdownItem>
          </DropdownMenu>
        </Dropdown>
        <!-- 多语言切换 -->
        <!-- <Dropdown trigger="click">
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
        <!-- 分割线 -->
        <!-- <Divider type="vertical" /> -->
      </Header>
      <Layout style="margin-top: 64px">
        <Sider
          style="
            min-height: calc(100vh - 64px);
            position: fixed;
            z-index: 99;
            height: 0;
            background: #ffffff;
            overflow-y: auto;
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
                <Badge
                  :dot="hasUpdate"
                  :count="itemItem.name == 'setting' && hasUpdate ? 1 : 0"
                  :offset="[0, -10]"
                >
                  <Icon :type="itemItem.meta.icon" />
                  {{ itemItem.meta.title }}
                </Badge>
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
      //是否有更新
      hasUpdate: sessionStorage.hasUpdate == 1 ? true : false,
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
          that.$Message.error("出错了 请联系管理员！");
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
      //母版组件的子页面
      var result = JSON.parse(sessionStorage.route).children;

      //过滤出导航分组
      var arrGroupNav = this.UniqueObjectArray(
        result.map((item) => item.meta.group).filter((item) => item.name != "")
      );

      //过滤掉不需要展示的导航
      result = result.filter((item) => item.meta.group.num > 0);

      //转换为两层结构
      var navList = [];
      for (var i = 0; i < arrGroupNav.length; i++) {
        var itemGroupNav = [];
        for (var j = 0; j < result.length; j++) {
          if (result[j].meta.group.num == arrGroupNav[i].num) {
            itemGroupNav.push(result[j]);
          }
        }
        if (itemGroupNav.length == 0) {
          continue;
        }
        navList.push({
          title: arrGroupNav[i].name,
          value: itemGroupNav,
        });
      }
      this.navList = navList;
    },
    //对象数组去重
    UniqueObjectArray(objectArray) {
      var objectArray = JSON.parse(JSON.stringify(objectArray));
      const res = new Map();
      var result = objectArray.filter(
        (a) => !res.has(a.name) && res.set(a.name, 1)
        // &&
        // !res.has(a.num) &&
        // res.set(a.num, 1)
      );
      for (var i = 0; i < result.length; i++) {
        result[i].num = Math.abs(result[i].num);
      }
      return result;
    },
    //动态成面包屑
    SetBreadcrumb() {
      this.breadcrumb = this.$route.matched;
    },
    //路由变化后自动设置导航选中状态
    SetActiveName() {
      this.currentActiveName = this.$route.name;
    },
    //检测更新
    CheckUpdate() {
      var that = this;
      var data = {};
      that.$axios
        .post("api.php?c=Setting&a=CheckUpdate&t=web", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            if (result.data != "") {
              that.hasUpdate = true;
              //有新版本
              //0 未检测 1 有新版本 2 无新版本
              sessionStorage.setItem("hasUpdate", 1);
            } else {
              //无新版本
              sessionStorage.setItem("hasUpdate", 2);
            }
          } else {
            // that.$Message.error({ content: result.message, duration: 2 });
          }
        })
        .catch(function (error) {
          console.log(error);
        });
    },
  },
  mounted() {
    var that = this;
    if (sessionStorage.hasUpdate == null) {
      //未检测更新 有新版本 0 未检测 1 有新版本 2 无新版本
      sessionStorage.setItem("hasUpdate", 0);
    }
    //生成导航
    that.CreateNav();
    //生成面包屑
    that.SetBreadcrumb();
    //设置导航选中状态
    that.SetActiveName();
    //管理员或者子管理员登录才可自动检测更新
    if (sessionStorage.user_role_id == 1 || sessionStorage.user_role_id == 3) {
      //未检测过才检测更新
      if (sessionStorage.hasUpdate == 0) {
        that.CheckUpdate();
      }
    }
  },
  watch: {
    //监听路由变化
    $route() {
      this.SetBreadcrumb();
      this.SetActiveName();
    },
  },
};
</script>