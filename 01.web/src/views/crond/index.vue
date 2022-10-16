<template>
  <div>
    <Card :bordered="false" :dis-hover="true">
      <!-- crond 服务非正常状态提示 -->
      <Alert v-if="tempCrondError != ''" type="error" show-icon>{{
        tempCrondError
      }}</Alert>
      <Row style="margin-bottom: 15px">
        <Col
          type="flex"
          justify="space-between"
          :xs="21"
          :sm="20"
          :md="19"
          :lg="18"
        >
          <Tooltip
            :transfer="true"
            max-width="350"
            content="此功能需要系统中安装 crontab 和 at 服务"
          >
            <Button icon="md-add" type="primary" ghost @click="ModalAddCrond"
              >添加任务计划</Button
            >
          </Tooltip>
        </Col>
        <Col :xs="3" :sm="4" :md="5" :lg="6">
          <Input
            v-model="searchKeywordCrond"
            search
            enter-button
            placeholder="通过任务名称和描述搜索..."
            style="width: 100%"
            @on-search="GetCrondList"
        /></Col>
      </Row>
      <Table
        @on-sort-change="SortChangeCrond"
        border
        :columns="tableColumnCrond"
        :data="tableDataCrond"
        :loading="loadingGetCrondList"
        size="small"
      >
        <template slot-scope="{ row }" slot="status">
          <Switch
            v-model="row.status"
            false-color="#ff4949"
            @on-change="(value) => UpdCrondStatus(value, row.crond_id)"
          >
            <Icon type="md-checkmark" slot="open"></Icon>
            <Icon type="md-close" slot="close"></Icon>
          </Switch>
        </template>
        <template slot-scope="{ row }" slot="notice">
          <Tag
            color="red"
            style="width: 90px; text-align: center"
            v-if="row.notice.length == 0"
            >通知关闭</Tag
          >
          <Tag
            color="purple"
            style="width: 90px; text-align: center"
            v-if="row.notice.length == 1 && row.notice.indexOf('success') != -1"
            >仅成功通知</Tag
          >
          <Tag
            color="magenta"
            style="width: 90px; text-align: center"
            v-if="row.notice.length == 1 && row.notice.indexOf('fail') != -1"
            >仅失败通知</Tag
          >
          <Tag
            color="blue"
            style="width: 90px; text-align: center"
            v-if="
              row.notice.indexOf('fail') != -1 &&
              row.notice.indexOf('success') != -1
            "
            >全部通知</Tag
          >
        </template>
        <template slot-scope="{ row, index }" slot="action">
          <Button
            type="info"
            size="small"
            @click="ModalViewCrondLog(row.crond_id)"
            >日志</Button
          >
          <Button type="warning" size="small" @click="ModalUpdCrond(index)"
            >编辑</Button
          >
          <Button type="error" size="small" @click="DelCrond(row.crond_id)"
            >删除</Button
          >
          <Tooltip
            :transfer="true"
            placement="left"
            max-width="200"
            content="不确定任务是否配置成功可手动执行一次通过分析日志查看具体情况"
          >
            <Button type="error" size="small" @click="CrondNow(row.crond_id)"
              >执行</Button
            >
          </Tooltip>
        </template>
      </Table>
      <Card :bordered="false" :dis-hover="true">
        <Page
          v-if="totalCrond != 0"
          :total="totalCrond"
          :current="pageCurrentCrond"
          :page-size="pageSizeCrond"
          @on-page-size-change="CrondPageSizeChange"
          @on-change="CrondPageChange"
          size="small"
          show-sizer
        />
      </Card>
    </Card>
    <Modal v-model="modalCrond" :title="titleModalCrond">
      <Form :model="cycle" :label-width="80">
        <FormItem label="任务类型">
          <Select
            :disabled="statusCrond == 'upd'"
            style="width: 200px"
            v-model="cycle.task_type"
            @on-change="ChangeCrondType"
          >
            <Option
              v-for="(type, index) in taskType"
              :value="type.key"
              :key="index"
              >{{ type.value }}</Option
            >
          </Select>
        </FormItem>
        <FormItem label="任务名称">
          <Input
            v-model="cycle.task_name"
            :readonly="[1, 2, 3, 4, 5].indexOf(cycle.task_type) != -1"
          ></Input>
        </FormItem>
        <FormItem label="执行周期">
          <!-- 周期类型 -->
          <Select style="width: 130px" v-model="cycle.cycle_type">
            <Option
              v-for="(type, index) in cycleType"
              :value="type.value"
              :key="index"
              >{{ type.name }}</Option
            >
          </Select>
          <!-- 周 -->
          <Select
            style="width: 100px"
            v-model="cycle.week"
            v-if="['week'].indexOf(cycle.cycle_type) != -1"
          >
            <Option
              v-for="(week, index) in weekList"
              :value="week.value"
              :key="index"
              >{{ week.name }}</Option
            >
          </Select>
          <!-- 日 -->
          <InputNumber
            :min="1"
            :max="31"
            :formatter="(value) => `${value}日`"
            :parser="(value) => value.replace('日', '')"
            v-model="cycle.day"
            v-if="['month'].indexOf(cycle.cycle_type) != -1"
          ></InputNumber>
          <!-- 天 -->
          <InputNumber
            :min="1"
            :max="31"
            :formatter="(value) => `${value}天`"
            :parser="(value) => value.replace('天', '')"
            v-model="cycle.day"
            v-if="['day_n'].indexOf(cycle.cycle_type) != -1"
          ></InputNumber>
          <!-- 小时 -->
          <InputNumber
            :min="0"
            :max="23"
            :formatter="(value) => `${value}小时`"
            :parser="(value) => value.replace('小时', '')"
            v-model="cycle.hour"
            v-if="
              ['month', 'week', 'day', 'day_n', 'hour_n'].indexOf(
                cycle.cycle_type
              ) != -1
            "
          ></InputNumber>
          <!-- 分钟 -->
          <InputNumber
            :min="0"
            :max="59"
            :formatter="(value) => `${value}分钟`"
            :parser="(value) => value.replace('分钟', '')"
            v-model="cycle.minute"
            v-if="
              [
                'month',
                'week',
                'day',
                'day_n',
                'hour',
                'hour_n',
                'minute_n',
              ].indexOf(cycle.cycle_type) != -1
            "
          ></InputNumber>
        </FormItem>
        <FormItem
          label="仓库选择"
          v-if="[1, 2, 3, 4, 5].indexOf(cycle.task_type) != -1"
        >
          <Select
            style="width: 200px"
            v-model="cycle.rep_key"
            @on-change="ChangeRep"
            filterable
          >
            <Option
              v-for="(rep, index) in repList"
              :value="rep.rep_key"
              :key="index"
              >{{ rep.rep_name }}</Option
            >
          </Select>
        </FormItem>
        <FormItem
          label="消息通知"
          v-if="[1, 2, 3, 4, 5, 6].indexOf(cycle.task_type) != -1"
        >
          <CheckboxGroup v-model="cycle.notice">
            <Checkbox label="success">成功通知</Checkbox>
            <Checkbox label="fail">失败通知</Checkbox>
          </CheckboxGroup>
        </FormItem>
        <FormItem
          label="保存数量"
          v-if="[1, 2, 3, 4].indexOf(cycle.task_type) != -1"
        >
          <InputNumber :min="1" v-model="cycle.save_count"></InputNumber>
        </FormItem>
        <FormItem label="脚本内容" v-if="[6].indexOf(cycle.task_type) != -1">
          <Input
            v-model="cycle.shell"
            :rows="8"
            show-word-limit
            type="textarea"
            placeholder="请输入脚本内容"
          />
        </FormItem>
        <FormItem>
          <Button
            v-if="statusCrond == 'add'"
            type="primary"
            @click="SetCrond"
            :loading="loadingAddCrond"
            >确定</Button
          >
          <Button
            v-else
            type="primary"
            @click="UpdCrond"
            :loading="loadingUpdCrond"
            >确定</Button
          >
        </FormItem>
      </Form>
      <div slot="footer">
        <Button type="primary" ghost @click="modalCrond = false">取消</Button>
      </div>
    </Modal>
    <Modal v-model="modalViewCrondLog" title="查看任务计划日志">
      <Input
        readonly
        type="textarea"
        v-model="tempCrondLogCon"
        show-word-limit
        :rows="15"
      >
      </Input>
      <br /><br />日志文件: {{ tempCrondLogPath }}
      <Spin fix v-if="laodingGetLog"></Spin>
      <div slot="footer">
        <Button type="primary" ghost @click="modalViewCrondLog = false"
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
      //任务计划
      pageCurrentCrond: 1,
      pageSizeCrond: 20,
      totalCrond: 0,

      /**
       * 状态
       */
      statusCrond: "add",

      /**
       * 标题
       */
      titleModalCrond: "",

      /**
       * 搜索关键词
       */
      searchKeywordCrond: "",

      /**
       * 排序数据
       */
      sortName: "crond_id",
      sortType: "asc",

      /**
       * 加载
       */
      loadingAddCrond: false,
      loadingUpdCrond: false,
      loadingGetCrondList: true,
      laodingGetLog: true,
      /**
       * 临时变量
       */
      tempCrondLogCon: "",
      tempCrondLogPath: "",
      tempCrondError: "",
      /**
       * 表单
       */
      //任务计划
      cycle: {
        //主键id
        crond_id: 0,
        //文件标识
        sign: "",
        //任务计划类型
        task_type: 1,
        //任务名称
        task_name: "",
        //周期类型
        cycle_type: "month",
        //执行周期描述
        cycle_desc: "",
        //启用状态
        status: false,
        //保存数量
        save_count: 3,
        //默认周一
        week: 1,
        //默认每月3日 或 每3天
        day: 3,
        //默认每1小时
        hour: 1,
        //默认每30分钟
        minute: 30,
        //成功和失败通知
        notice: ["success", "fail"],
        //上次执行时间
        last_exec_time: "",
        //仓库选择 -1 为全部 其他值为指定仓库名称
        rep_key: "-1",
        //脚本内容
        shell: "",
      },
      /**
       * 下拉
       */
      //任务计划类型
      taskType: [
        {
          key: 1,
          value: "仓库备份[dump-全量]",
        },
        // {
        //   key: 2,
        //   value: "仓库备份[dump-增量]",
        // },
        // {
        //   key: 3,
        //   value: "仓库备份[hotcopy-全量]",
        // },
        // {
        //   key: 4,
        //   value: "仓库备份[hotcopy-增量]",
        // },
        {
          key: 5,
          value: "仓库检查",
        },
        {
          key: 6,
          value: "shell脚本",
        },
      ],
      //周期类型
      cycleType: [
        {
          name: "每分钟",
          value: "minute",
        },
        {
          name: "每隔N分钟",
          value: "minute_n",
        },
        {
          name: "每小时",
          value: "hour",
        },
        {
          name: "每隔N小时",
          value: "hour_n",
        },
        {
          name: "每天",
          value: "day",
        },
        {
          name: "每隔N天",
          value: "day_n",
        },
        {
          name: "每周",
          value: "week",
        },
        {
          name: "每月",
          value: "month",
        },
      ],
      //周一到周日
      weekList: [
        {
          name: "周一",
          value: 1,
        },
        {
          name: "周二",
          value: 2,
        },
        {
          name: "周三",
          value: 3,
        },
        {
          name: "周四",
          value: 4,
        },
        {
          name: "周五",
          value: 5,
        },
        {
          name: "周六",
          value: 6,
        },
        {
          name: "周日",
          value: 0,
        },
      ],
      //仓库列表
      repList: [
        {
          rep_key: "-1",
          rep_name: "所有仓库",
        },
      ],
      /**
       * 对话框
       */
      //新建任务计划
      modalCrond: false,
      //查看任务计划日志
      modalViewCrondLog: false,
      //编辑任务计划
      modalCrond: false,
      /**
       * 表格
       */
      //任务计划信息
      tableColumnCrond: [
        {
          title: "序号",
          type: "index",
          fixed: "left",
          minWidth: 80,
        },
        {
          title: "任务名称",
          key: "task_name",
          tooltip: true,
          width: 220,
          minWidth: 220,
        },
        {
          title: "执行周期描述",
          tooltip: true,
          key: "cycle_desc",
          width: 200,
          minWidth: 200,
        },
        {
          title: "消息通知",
          slot: "notice",
          minWidth: 120,
        },
        {
          title: "启用状态",
          key: "status",
          slot: "status",
          sortable: true,
          minWidth: 120,
        },
        {
          title: "保存数量",
          key: "save_count",
          width: 100,
          minWidth: 100,
        },
        {
          title: "上次执行时间",
          key: "last_exec_time",
          tooltip: true,
          // width: 180,
          minWidth: 140,
        },
        {
          title: "其它",
          slot: "action",
          width: 240,
        },
      ],
      tableDataCrond: [],

      //任务计划日志
      tableColumnCrondLog: [
        {
          title: "时间",
          key: "time",
        },
        {
          title: "内容",
          key: "content",
        },
      ],
      tableDataCrondLog: [
        {
          time: "xxx",
          content: "xxxxxxxxxxxx",
          tooltip: true,
        },
      ],
    };
  },
  computed: {},
  created() {},
  mounted() {
    this.GetCronStatus();
    this.GetCrondList();
    this.GetRepList();
  },
  methods: {
    /**
     * 每页数量改变
     */
    CrondPageSizeChange(value) {
      //设置每页条数
      this.pageSizeCrond = value;
      this.GetCrondList();
    },
    /**
     * 页码改变
     */
    CrondPageChange(value) {
      //设置当前页数
      this.pageCurrentCrond = value;
      this.GetCrondList();
    },
    /**
     * 排序
     */
    SortChangeCrond(value) {
      this.sortName = value.key;
      if (value.order == "desc" || value.order == "asc") {
        this.sortType = value.order;
      }
      this.GetCrondList();
    },
    /**
     * 启用或禁用用户
     */
    UpdCrondStatus(status, crond_id) {
      var that = this;
      var data = {
        crond_id: crond_id,
        status: status,
      };
      that.$axios
        .post("/api.php?c=Crond&a=UpdCrondStatus&t=web", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
            that.GetCrondList();
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
     * 获取特殊结构的下拉列表
     */
    GetRepList() {
      var that = this;
      var data = {};
      that.$axios
        .post("/api.php?c=Crond&a=GetRepList&t=web", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.repList = result.data;
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
     * 获取任务计划列表
     */
    GetCrondList() {
      var that = this;
      that.loadingGetCrondList = true;
      that.tableDataCrond = [];
      // that.totalUser = 0;
      var data = {
        pageSize: that.pageSizeCrond,
        currentPage: that.pageCurrentCrond,
        searchKeyword: that.searchKeywordCrond,
        sortName: that.sortName,
        sortType: that.sortType,
      };
      that.$axios
        .post("/api.php?c=Crond&a=GetCrondList&t=web", data)
        .then(function (response) {
          that.loadingGetCrondList = false;
          var result = response.data;
          if (result.status == 1) {
            // that.$Message.success(result.message);
            that.tableDataCrond = result.data.data;
            that.totalCrond = result.data.total;
          } else {
            that.$Message.error({ content: result.message, duration: 2 });
          }
        })
        .catch(function (error) {
          that.loadingGetCrondList = false;
          console.log(error);
          that.$Message.error("出错了 请联系管理员！");
        });
    },
    /**
     * 任务类型改变
     */
    ChangeCrondType(value) {
      switch (value) {
        case 1:
        case 2:
        case 3:
        case 4:
        case 5:
          //修改任务名称
          this.cycle.task_name =
            this.taskType.find((item) => item.key === this.cycle.task_type)
              .value +
            "[" +
            this.repList.find((item) => item.rep_key === this.cycle.rep_key)
              .rep_name +
            "]";
          break;
        case 6:
          //修改任务名称
          this.cycle.task_name = "";
          break;
      }
    },
    /**
     * 选择的仓库改变
     */
    ChangeRep(value) {
      //修改任务名称
      this.cycle.task_name =
        this.taskType.find((item) => item.key === this.cycle.task_type).value +
        "[" +
        this.repList.find((item) => item.rep_key === this.cycle.rep_key)
          .rep_name +
        "]";
    },
    /**
     * 设置任务计划
     */
    ModalAddCrond() {
      if (this.cycle.task_type != 6) {
        //修改任务名称
        this.cycle.task_name =
          this.taskType.find((item) => item.key === this.cycle.task_type)
            .value +
          "[" +
          this.repList.find((item) => item.rep_key === this.cycle.rep_key)
            .rep_name +
          "]";
      }
      //显示对话框
      this.statusCrond = "add";
      this.titleModalCrond = "添加任务计划";
      this.modalCrond = true;
    },
    /**
     * 设置任务计划
     */
    SetCrond() {
      var that = this;
      that.loadingAddCrond = true;
      var data = {
        cycle: that.cycle,
      };
      that.$axios
        .post("/api.php?c=Crond&a=SetCrond&t=web", data)
        .then(function (response) {
          that.loadingAddCrond = false;
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
            that.modalCrond = false;
            that.GetCrondList();
          } else {
            that.$Message.error({ content: result.message, duration: 2 });
          }
        })
        .catch(function (error) {
          that.loadingAddCrond = false;
          console.log(error);
          that.$Message.error("出错了 请联系管理员！");
        });
    },
    /**
     * 查看任务计划日志
     */
    ModalViewCrondLog(crond_id) {
      this.modalViewCrondLog = true;
      this.GetCrondLog(crond_id);
    },
    GetCrondLog(crond_id) {
      var that = this;
      that.tempCrondLogCon = "";
      that.laodingGetLog = true;
      var data = {
        crond_id: crond_id,
      };
      that.$axios
        .post("/api.php?c=Crond&a=GetCrondLog&t=web", data)
        .then(function (response) {
          that.laodingGetLog = false;
          var result = response.data;
          if (result.status == 1) {
            that.tempCrondLogCon = result.data.log_con;
            that.tempCrondLogPath = result.data.log_path;
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
     * 编辑任务计划
     */
    ModalUpdCrond(index) {
      this.cycle = JSON.parse(JSON.stringify(this.tableDataCrond[index]));

      this.statusCrond = "upd";
      this.titleModalCrond = "编辑计划任务";
      this.modalCrond = true;
    },
    UpdCrond() {
      var that = this;
      that.loadingUpdCrond = true;
      var data = {
        cycle: that.cycle,
      };
      that.$axios
        .post("/api.php?c=Crond&a=UpdCrond&t=web", data)
        .then(function (response) {
          that.loadingUpdCrond = false;
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
            that.modalCrond = false;
            that.GetCrondList();
          } else {
            that.$Message.error({ content: result.message, duration: 2 });
          }
        })
        .catch(function (error) {
          that.loadingUpdCrond = false;
          console.log(error);
          that.$Message.error("出错了 请联系管理员！");
        });
    },
    /**
     * 删除任务计划
     */
    DelCrond(crond_id) {
      var that = this;
      that.$Modal.confirm({
        title: "删除任务计划",
        content: "确定要删除该记录吗？此操作不可逆！",
        onOk: () => {
          var data = {
            crond_id: crond_id,
          };
          that.$axios
            .post("/api.php?c=Crond&a=DelCrond&t=web", data)
            .then(function (response) {
              var result = response.data;
              if (result.status == 1) {
                that.$Message.success(result.message);
                that.GetCrondList();
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
    /**
     * 现在执行任务计划
     */
    CrondNow(crond_id) {
      var that = this;
      that.$Modal.confirm({
        title: "执行任务计划",
        content:
          "确定要立即执行该任务计划吗？该操作可用于测试任务计划配置的正确性！",
        onOk: () => {
          var data = {
            crond_id: crond_id,
          };
          that.$axios
            .post("/api.php?c=Crond&a=CrondNow&t=web", data)
            .then(function (response) {
              var result = response.data;
              if (result.status == 1) {
                that.$Message.success(result.message);
                that.GetCrondList();
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
    /**
     * 检查 crontab at 是否安装和启动
     */
    GetCronStatus() {
      var that = this;
      var data = {};
      that.$axios
        .post("/api.php?c=Crond&a=GetCronStatus&t=web", data)
        .then(function (response) {
          that.loadingAddCrond = false;
          var result = response.data;
          if (result.status == 1) {
          } else {
            that.tempCrondError = result.message;
            // that.$Message.error({ content: result.message, duration: 2 });
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