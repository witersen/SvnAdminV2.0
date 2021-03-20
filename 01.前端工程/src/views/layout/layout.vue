<style scoped lang="less">
@import "../../styles/common.css";
@import "./layout.less";
</style>

<template>
  <div class="layout">
    <Layout>
      <Header>
        <div class="layout-logo">
          <div class="logo">
            <img src="../../static/images/logo.png" style="display: block" />
          </div>
        </div>

        <div class="layout-user">
          <template>
            <Dropdown trigger="click" @on-click="dropdown_click">
              <a href="javascript:void(0)">
                <Avatar size="40" icon="ios-person" />
                <span style="color: #fff">{{ username }}</span>
                <Icon type="ios-arrow-down" color="#fff"></Icon>
              </a>
              <DropdownMenu slot="list">
                <!-- <DropdownItem name="info">个人信息</DropdownItem> -->
                <!-- <DropdownItem name="logout" divided>退出</DropdownItem> -->
                <DropdownItem name="logout">退出</DropdownItem>
              </DropdownMenu>
            </Dropdown>
          </template>
        </div>
        <div class="layout-role">
          <template>
            <Dropdown trigger="click">
              <a href="javascript:void(0)">当前角色：{{ rolename }}</a>
            </Dropdown>
          </template>
        </div>
      </Header>
      <Layout>
        <Sider
          :style="{
            left: 0,
            overflow: 'auto',
            background: '#fff',
            position: 'relative',
            zIndex: '99',
            minHeight: 'calc(100vh - 64px)',
          }"
        >
          <!-- 二级导航 -->
          <Menu
            theme="light"
            width="auto"
            :accordion="true"
            ref="MenuList"
            style="height: 100%"
            :active-name="activeName"
            :open-names="openNames"
          >
            <div v-for="(item, index) in moduleList" :key="index">
              <Submenu
                :name="item.name"
                :to="item.path"
                v-if="
                  item.children &&
                  item['children'].filter((node) => node.meta.isNav).length > 1
                "
              >
                <template slot="title">
                  <Icon :type="item.icon" size="18" />
                  {{ item.meta.title }}
                </template>
                <div
                  v-for="(node, index2) in item['children'].filter(
                    (item) => item.meta.isNav
                  )"
                  :key="index2"
                >
                  <MenuItem :name="node.name" :to="node.path">{{
                    node.meta.title
                  }}</MenuItem>
                </div>
              </Submenu>

              <div v-else>
                <MenuItem :name="item.name" :to="item.path">
                  <Icon :type="item.icon" size="18" />
                  {{ item.meta.title }}
                </MenuItem>
              </div>
            </div>
          </Menu>
        </Sider>
        <Layout :style="{ padding: '0 20px 20px' }">
          <Breadcrumb :style="{ margin: '14px 0' }">
            <BreadcrumbItem>首页</BreadcrumbItem>
            <span v-for="item in breadcrumbItems" :key="item.name">
              <BreadcrumbItem v-if="item.path != ''">{{
                item.title
              }}</BreadcrumbItem>
            </span>
          </Breadcrumb>
          <Content :style="{ padding: '0px', minHeight: '280px' }">
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
      rolename: window.sessionStorage.rolename, //角色
      username: window.sessionStorage.username, //用户名

      moduleList: [],
      activeName: "",
      openNames: [],
      breadcrumbItems: [],
    };
  },
  created() {
    // this.$router.options.routes['children'].filter((node)=> node.meta.isNav).length
    // console.log(this.$router.options.routes[0]['children'].filter((node)=> node.meta.isNav));
  },
  mounted() {
    let that = this;
    that.getModuleList();
    that.setNavActive();
  },
  watch: {
    $route() {
      this.setNavActive();
    },
  },
  methods: {
    //退出登录
    dropdown_click(name) {
      var that = this;
      var data = {};
      if (name == "logout") {
        window.sessionStorage.removeItem("token"); //删除userid
        window.sessionStorage.removeItem("userid"); //删除userid
        window.sessionStorage.removeItem("username"); //删除username
        window.sessionStorage.removeItem("roleid"); //删除roleid
        window.sessionStorage.removeItem("rolename"); //删除rolename

        let token = window.sessionStorage.token;

        if (token === "null" || token === "" || token === undefined) {
          that.$Message.success("退出登录成功");
          that.$router.push({ name: "login" }); // 跳转到首页
        } else {
          that.$Message.success("退出登录失败");
        }
      }
    },
    // 设置导航选中状态
    setNavActive() {
      let that = this;
      var _array = new Array();
      var i = 0;
      for (var e of that.$route.matched) {
        if (e.meta.parent !== undefined) {
          var obj = new Object();
          obj.path = e.path;
          obj.name = e.path;
          obj.title = e.meta.title;
          if (e.meta.isNav == undefined) {
            e.meta.isNav = false;
          }
          obj.isNav = e.meta.isNav;
          _array[i] = obj;
          i = i + 1;
        }
        var obj = new Object();
        obj.path = e.path;
        obj.name = e.name;
        obj.title = e.meta.title;
        if (e.meta.isNav == undefined) {
          e.meta.isNav = false;
        }
        obj.isNav = e.meta.isNav;
        _array[i] = obj;
        i = i + 1;
      }
      that.breadcrumbItems = _array;
      let navList = _array.filter((item) => item.isNav);
      if (navList.length >= 2) {
        that.activeName = navList[navList.length - 1].name;
        that.openNames = [navList[navList.length - 2].name];
      } else {
        that.activeName = navList[navList.length - 1].name;
      }
      that.$nextTick(() => {
        that.$refs.MenuList.updateOpened();
        that.$refs.MenuList.updateActiveName();
      });
    },
    // 生成导航
    getModuleList() {
      let that = this;
      let _modules = that.$router.options.routes.filter(
        (item) => item.name == "manage"
      );
      that.moduleList.splice(0, that.moduleList.length);
      _modules[0]["children"].forEach((element) => {
        if (
          element.meta.isNav &&
          element.meta.roles.indexOf(Number(window.sessionStorage.roleid)) != -1
        ) {
          that.moduleList.push(element);
        }
      });
    },
  }
};
</script>