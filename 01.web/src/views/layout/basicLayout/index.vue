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
          style="
            line-height: 64px;
            position: absolute;
            top: 12px;
            left: 1%;
            cursor: pointer;
          "
          draggable="false"
          @click="toMyIndex"
        />
        <!-- 实时任务 -->
        <span
          style="cursor: pointer"
          @click="ModalTasks"
          v-if="currentRoleId != 2"
          >{{$t('menus.backendJobs')}}</span
        >
        <!-- 分割线 -->
        <Divider type="vertical" v-if="currentRoleId != 2" />
        <!-- 用户身份 -->
        <a style="margin-left: 8px; color: #fff; cursor: default">{{
          $t('roles.' + currentRoleName)
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
            <DropdownItem>{{ $t('menus.logout') }}</DropdownItem>
          </DropdownMenu>
        </Dropdown>
        <!-- 多语言切换 -->
        <Dropdown trigger="click" @on-click="translate">
          <a href="javascript:void(0)" style="margin-left: 8px; color: #fff">
            {{ $t(this.lang) }}
            <Icon type="md-arrow-dropdown" />
          </a>
          <DropdownMenu slot="list">
            <DropdownItem name="zh">中文</DropdownItem>
          </DropdownMenu>
          <DropdownMenu slot="list">
            <DropdownItem name="en">English</DropdownItem>
          </DropdownMenu>
        </Dropdown>
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
              :title="$t('menus.' + itemGroup.title)"
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
                  {{ $t('menus.' + itemItem.meta.title) }}
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
    <!-- 对话框-实时后台任务 -->
    <Modal v-model="modalTasks" :draggable="true" title="实时后台任务">
      <div style="height: 350px">
        <Tabs v-model="tabsTaskCurrent" @on-click="ClickTaskTab">
          <TabPane label="当前任务" icon="ios-loading" name="current">
            <Spin size="large" v-if="loadingTask" fix></Spin>
            <Alert v-if="!formTasks.task_running"
              >当前没有后台任务运行（如遇任务堆积不执行可重启守护进程解决）</Alert
            >
            <Input
              v-else
              :rows="13"
              v-model="formTasks.task_log"
              show-word-limit
              readonly
              type="textarea"
            />
          </TabPane>
          <TabPane label="排队任务" icon="ios-cafe" name="queue">
            <Table
              highlight-row
              border
              :height="300"
              size="small"
              :loading="loadingTaskQueue"
              :columns="tableColumnTaskQueue"
              :data="tableDataTaskQueue"
              style="margin-bottom: 10px"
            >
              <template slot-scope="{ row }" slot="task_status">
                <Tag color="success" v-if="row.task_status == 2">执行中</Tag>
                <Tag color="default" v-else>待执行</Tag>
              </template>
              <template slot-scope="{ row }" slot="action">
                <Button
                  type="error"
                  size="small"
                  @click="UpdTaskStop(row.task_id, row.task_status)"
                  v-if="row.task_status == 2"
                  >中断执行</Button
                >
                <Button
                  type="warning"
                  size="small"
                  @click="UpdTaskStop(row.task_id, row.task_status)"
                  v-else
                  >取消排队</Button
                >
              </template>
            </Table>
          </TabPane>
          <TabPane label="历史任务" icon="md-aperture" name="history">
            <Table
              highlight-row
              border
              :height="250"
              size="small"
              :loading="loadingTaskHistory"
              :columns="tableColumnTaskHistory"
              :data="tableDataTaskHistory"
              style="margin-bottom: 10px"
            >
              <template slot-scope="{ row }" slot="task_status">
                <Tag color="success" v-if="row.task_status == 3">已完成</Tag>
                <Tag color="warning" v-if="row.task_status == 4">已取消</Tag>
                <Tag color="error" v-if="row.task_status == 5">已中断</Tag>
              </template>
              <template slot-scope="{ row }" slot="action">
                <Button
                  type="primary"
                  size="small"
                  @click="GetTaskHistoryLog(row.task_id)"
                  >日志</Button
                >
                <Button
                  type="error"
                  size="small"
                  @click="DelTaskHistory(row.task_id)"
                  >删除</Button
                >
              </template>
            </Table>
            <Page
              v-if="totalTaskHistory != 0"
              :total="totalTaskHistory"
              :current="pageCurrentTaskHistory"
              :page-size="pageSizeTaskHistory"
              @on-page-size-change="PageSizeChangeTaskHistory"
              @on-change="PageChangeTaskHistory"
              size="small"
              show-sizer
            />
          </TabPane>
        </Tabs>
      </div>
      <div slot="footer">
        <Button type="primary" ghost @click="modalTasks = false">取消</Button>
      </div>
    </Modal>
    <!-- 对话框-历史任务日志 -->
    <Modal v-model="modalTaskLog" :draggable="true" title="历史任务日志">
      <Input
        v-model="tempTaskLog"
        readonly
        :rows="15"
        show-word-limit
        type="textarea"
      />
      <div slot="footer">
        <Button type="primary" ghost @click="modalTaskLog = false">取消</Button>
      </div>
    </Modal>
  </div>
</template>

<script>
export default {
  data() {
    return {
        //当前语言
        lang: navigator.language.substring(0, 2),
      //是否有更新
      hasUpdate: sessionStorage.hasUpdate == 1 ? true : false,
      //当前选中的导航
      currentActiveName: "",
      //logo文字内容
      logoContent: "SVN Admin",
      //用户名和角色
      currentUsername: sessionStorage.user_name,
      currentRoleName: sessionStorage.user_role_name,
      currentRoleId: sessionStorage.user_role_id,
      // 过滤后的导航
      navList: [],
      //面包屑
      breadcrumb: [],

      /**
       * 临时变量
       */
      //历史任务日志
      tempTaskLog: "",
      //后台任务标签
      tabsTaskCurrent: "current",

      /**
       * 分页数据
       */
      //用户仓库
      pageCurrentTaskHistory: 1,
      pageSizeTaskHistory: 10,
      totalTaskHistory: 0,

      /**
       * 表单
       */
      //后台任务
      formTasks: {
        task_name: "",
        count: 0,
        task_running: "",
        task_log: "",
      },

      /**
       * 对话框
       */
      //后台任务列表
      modalTasks: false,
      //历史任务日志
      modalTaskLog: false,

      /**
       * 表格
       */
      //任务队列
      tableColumnTaskQueue: [
        {
          title: "任务名称",
          key: "task_name",
          tooltip: true,
        },
        {
          title: "创建时间",
          key: "task_create_time",
          tooltip: true,
        },
        {
          title: "状态",
          slot: "task_status",
        },
        {
          title: "操作",
          slot: "action",
        },
      ],
      tableDataTaskQueue: [],
      //历史任务
      tableColumnTaskHistory: [
        {
          title: "任务名称",
          key: "task_name",
          tooltip: true,
          fixed: "left",
          width: 150,
        },
        {
          title: "状态",
          slot: "task_status",
          width: 110,
        },
        {
          title: "创建时间",
          key: "task_create_time",
          tooltip: true,
          width: 150,
        },
        {
          title: "结束时间",
          key: "task_update_time",
          tooltip: true,
          width: 150,
        },
        {
          title: "操作",
          slot: "action",
          width: 130,
        },
      ],
      tableDataTaskHistory: [],

      /**
       * 加载
       */
      //获取当前执行任务
      loadingTask: false,
      //获取后台任务队列
      loadingTaskQueue: false,
      //获取历史任务
      loadingTaskHistory: false,
    };
  },
  methods: {
    translate(lng) {
        console.log("default language is "+navigator.language.substring(0, 2));
        console.log("Translating to "+lng);
        this.lang = lng;    //this.lang === 'zh' ? 'en' : 'zh'
        this.$i18n.locale = this.lang
    },
    //点击logo回到当前用户有权限第一个页面
    toMyIndex() {
      this.$router.push({ name: sessionStorage.firstRoute });
    },
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
    //后台任务列表
    ModalTasks() {
      this.modalTasks = true;
      this.ClickTaskTab(this.tabsTaskCurrent);
    },
    //后台任务切换
    ClickTaskTab(name) {
      switch (name) {
        case "current":
          //获取当前执行日志
          this.GetTaskRun();
          break;
        case "queue":
          //获取排队任务
          this.GetTaskQueue();
          break;
        case "history":
          //获取历史任务
          this.GetTaskHistory();
          break;
        default:
          break;
      }
    },
    //获取当前执行日志
    GetTaskRun() {
      var that = this;
      that.loadingTask = true;
      var data = {};
      that.$axios
        .post("api.php?c=Tasks&a=GetTaskRun&t=web", data)
        .then(function (response) {
          that.loadingTask = false;
          var result = response.data;
          if (result.status == 1) {
            that.formTasks.task_running = result.data.task_running;
            that.formTasks.task_log = result.data.task_log;
            that.formTasks.count = result.data.task_queue_count;
            that.formTasks.task_name = result.data.task_name;
          } else {
            that.$Message.error({ content: result.message, duration: 2 });
          }
        })
        .catch(function (error) {
          that.loadingTask = false;
          console.log(error);
          that.$Message.error("出错了 请联系管理员！");
        });
    },
    //页码改变
    PageSizeChangeTaskQueue(value) {
      //设置当前页数
      this.pageCurrentTaskQueue = value;
      this.GetTaskQueue();
    },
    //每页数量改变
    PageChangeTaskQueue(value) {
      //设置每页条数
      this.pageSizeTaskQueue = value;
      this.GetTaskQueue();
    },
    //获取排队任务
    GetTaskQueue() {
      var that = this;
      that.tableDataTaskQueue = [];
      that.loadingTaskQueue = true;
      var data = {};
      that.$axios
        .post("api.php?c=Tasks&a=GetTaskQueue&t=web", data)
        .then(function (response) {
          that.loadingTaskQueue = false;
          var result = response.data;
          if (result.status == 1) {
            that.tableDataTaskQueue = result.data.data;
          } else {
            that.$Message.error({ content: result.message, duration: 2 });
          }
        })
        .catch(function (error) {
          that.loadingTaskQueue = false;
          console.log(error);
          that.$Message.error("出错了 请联系管理员！");
        });
    },
    //页码改变
    PageChangeTaskHistory(value) {
      //设置当前页数
      this.pageCurrentTaskHistory = value;
      this.GetTaskHistory();
    },
    //每页数量改变
    PageSizeChangeTaskHistory(value) {
      //设置每页条数
      this.pageSizeTaskHistory = value;
      this.GetTaskHistory();
    },
    //获取历史任务
    GetTaskHistory() {
      var that = this;
      that.tableDataTaskHistory = [];
      that.loadingTaskHistory = true;
      var data = {
        pageSize: that.pageSizeTaskHistory,
        currentPage: that.pageCurrentTaskHistory,
      };
      that.$axios
        .post("api.php?c=Tasks&a=GetTaskHistory&t=web", data)
        .then(function (response) {
          that.loadingTaskHistory = false;
          var result = response.data;
          if (result.status == 1) {
            that.tableDataTaskHistory = result.data.data;
            that.totalTaskHistory = result.data.total;
          } else {
            that.$Message.error({ content: result.message, duration: 2 });
          }
        })
        .catch(function (error) {
          that.loadingTaskHistory = false;
          console.log(error);
          that.$Message.error("出错了 请联系管理员！");
        });
    },
    //取消后台任务
    UpdTaskStop(task_id, task_status) {
      var that = this;
      var data = {
        task_id: task_id,
      };
      if (task_status == 2) {
        that.$Modal.confirm({
          title: "中断进程确认",
          content:
            "确定要中断执行吗？<br/>不保证该操作是否会产生无法清理的睡眠进程！",
          onOk: () => {
            that.$axios
              .post("api.php?c=Tasks&a=UpdTaskStop&t=web", data)
              .then(function (response) {
                var result = response.data;
                if (result.status == 1) {
                  that.$Message.success(result.message);
                  that.GetTaskQueue();
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
      } else {
        that.$axios
          .post("api.php?c=Tasks&a=UpdTaskStop&t=web", data)
          .then(function (response) {
            var result = response.data;
            if (result.status == 1) {
              that.$Message.success(result.message);
              that.GetTaskQueue();
            } else {
              that.$Message.error({ content: result.message, duration: 2 });
            }
          })
          .catch(function (error) {
            console.log(error);
            that.$Message.error("出错了 请联系管理员！");
          });
      }
    },
    //查看历史任务日志
    GetTaskHistoryLog(task_id) {
      this.modalTaskLog = true;
      var that = this;
      var data = {
        task_id: task_id,
      };
      that.$axios
        .post("api.php?c=Tasks&a=GetTaskHistoryLog&t=web", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.tempTaskLog = result.data.task_log;
          } else {
            that.$Message.error({ content: result.message, duration: 2 });
          }
        })
        .catch(function (error) {
          console.log(error);
          that.$Message.error("出错了 请联系管理员！");
        });
    },
    //删除历史任务
    DelTaskHistory(task_id) {
      var that = this;
      var data = {
        task_id: task_id,
      };
      that.$axios
        .post("api.php?c=Tasks&a=DelTaskHistory&t=web", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
            that.GetTaskHistory();
          } else {
            that.$Message.error({ content: result.message, duration: 2 });
          }
        })
        .catch(function (error) {
          console.log(error);
          that.$Message.error("出错了 请联系管理员！");
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