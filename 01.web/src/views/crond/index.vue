<template>
  <div>
    <Card :bordered="false" :dis-hover="true">
      <!-- crond 服务非正常状态提示 -->
      <Alert v-if="tempCrondError != ''" type="error" show-icon>{{
        tempCrondError
      }}</Alert>
      <Alert v-else show-icon>{{ $t('crond.plsCheckCrondAtd') }}</Alert>
      <Row style="margin-bottom: 15px">
        <Col
          type="flex"
          justify="space-between"
          :xs="21"
          :sm="20"
          :md="19"
          :lg="18"
        >
          <!-- <Tooltip
            :transfer="true"
            max-width="350"
            content="此功能需要系统中安装 crontab 和 at 服务"
          > -->
          <Button icon="md-add" type="primary" ghost @click="ModalAddCrond"
            >{{ $t('crond.addCrond') }}</Button
          >
          <!-- </Tooltip> -->
        </Col>
        <Col :xs="3" :sm="4" :md="5" :lg="6">
          <Input
            v-model="searchKeywordCrond"
            search
            enter-button
            :placeholder="$t('crond.searchByNameAndDesc')"
            style="width: 100%"
            @on-search="GetCrontabList"
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
            @on-change="(value) => UpdCrontabStatus(value, row.crond_id)"
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
            >{{ $t('crond.noNotice') }}</Tag
          >
          <Tag
            color="purple"
            style="width: 90px; text-align: center"
            v-if="row.notice.length == 1 && row.notice.indexOf('success') != -1"
            >{{ $t('crond.successNotice') }}</Tag
          >
          <Tag
            color="magenta"
            style="width: 90px; text-align: center"
            v-if="row.notice.length == 1 && row.notice.indexOf('fail') != -1"
            >{{ $t('crond.failureNotice') }}</Tag
          >
          <Tag
            color="blue"
            style="width: 90px; text-align: center"
            v-if="
              row.notice.indexOf('fail') != -1 &&
              row.notice.indexOf('success') != -1
            "
            >{{ $t('crond.allNotice') }}</Tag
          >
        </template>
        <template slot-scope="{ row, index }" slot="action">
          <Button
            type="info"
            size="small"
            @click="ModalViewCrondLog(row.crond_id)"
            >{{ $t('crond.viewLog') }}</Button
          >
          <Button type="warning" size="small" @click="ModalUpdCrond(index)"
            >{{ $t('edit') }}</Button
          >
          <Button type="error" size="small" @click="DelCrontab(row.crond_id)"
            >{{ $t('delete') }}</Button
          >
          <Tooltip
            :transfer="true"
            placement="left"
            max-width="200"
            :content="$t('crond.tipCheckByTrigger')"
          >
            <Button
              type="error"
              size="small"
              @click="TriggerCrontab(row.crond_id)"
              >{{ $t('crond.trigger') }}</Button
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
    <Modal v-model="modalCrond" :draggable="true" :title="titleModalCrond">
      <Form :model="cycle" :label-width="80">
        <FormItem :label="$t('crond.type')">
          <Select
            :disabled="statusCrond == 'upd'"
            style="width: 250px"
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
        <FormItem :label="$t('crond.name')">
          <Input
            v-model="cycle.task_name"
            :readonly="[1, 2, 3, 4, 5, 7, 8, 9].indexOf(cycle.task_type) != -1"
          ></Input>
        </FormItem>
        <FormItem :label="$t('crond.cycleType')">
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
            :formatter="(value) => `${$t('crond.monthDay', [value])}`"
            :parser="(value) => value.replace($t('crond.monthDay'), '')"
            v-model="cycle.day"
            v-if="['month'].indexOf(cycle.cycle_type) != -1"
          ></InputNumber>
          <!-- 天 -->
          <InputNumber
            :min="1"
            :max="31"
            :formatter="(value) => `${value + $t('crond.dayDay')}`"
            :parser="(value) => value.replace($t('crond.dayDay'), '')"
            v-model="cycle.day"
            v-if="['day_n'].indexOf(cycle.cycle_type) != -1"
          ></InputNumber>
          <!-- 小时 -->
          <InputNumber
            :min="0"
            :max="23"
            :formatter="(value) => `${value + $t('crond.hourHour')}`"
            :parser="(value) => value.replace($t('crond.hourHour'), '')"
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
            :formatter="(value) => `${value + $t('crond.minuteMinute')}`"
            :parser="(value) => value.replace($t('crond.minuteMinute'), '')"
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
          :label="$t('crond.changeRepo')"
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
              >{{ index == 0 ? $t('crond.allRepos') : rep.rep_name }}</Option
            >
          </Select>
        </FormItem>
        <FormItem
          :label="$t('crond.notice')"
          v-if="[1, 2, 3, 4, 5, 6, 7, 8, 9].indexOf(cycle.task_type) != -1"
        >
          <CheckboxGroup v-model="cycle.notice">
            <Checkbox label="success">{{ $t('crond.noticeSuccess') }}</Checkbox>
            <Checkbox label="fail">{{ $t('crond.noticeFailure') }}</Checkbox>
          </CheckboxGroup>
        </FormItem>
        <FormItem
          :label="$t('crond.saveCount')"
          v-if="[1, 2, 3, 4].indexOf(cycle.task_type) != -1"
        >
          <InputNumber :min="1" v-model="cycle.save_count"></InputNumber>
        </FormItem>
        <FormItem :label="$t('crond.scriptContent')" v-if="[6].indexOf(cycle.task_type) != -1">
          <Input
            v-model="cycle.shell"
            :rows="8"
            show-word-limit
            type="textarea"
            :placeholder="$t('crond.inputScriptContent')"
          />
        </FormItem>
        <FormItem>
          <Button
            v-if="statusCrond == 'add'"
            type="primary"
            @click="CreateCrontab()"
            :loading="loadingAddCrond"
            >{{ $t('add') }}</Button
          >
          <Button
            v-else
            type="primary"
            @click="UpdCrontab"
            :loading="loadingUpdCrond"
            >{{ $t('confirm') }}</Button
          >
        </FormItem>
      </Form>
      <div slot="footer">
        <Button type="primary" ghost @click="modalCrond = false">{{ $t('cancel') }}</Button>
      </div>
    </Modal>
    <Modal
      v-model="modalViewCrondLog"
      :draggable="true"
      :title="$t('crond.viewCrondLog')"
    >
      <Input
        readonly
        type="textarea"
        v-model="tempCrondLogCon"
        show-word-limit
        :rows="15"
      >
      </Input>
      <br /><br />{{ $t('crond.logFile') }}: {{ tempCrondLogPath }}
      <Spin fix v-if="laodingGetLog"></Spin>
      <div slot="footer">
        <Button type="primary" ghost @click="modalViewCrondLog = false"
          >{{ $t('cancel') }}</Button
        >
      </div>
    </Modal>
  </div>
</template>

<script>
import i18n from '@/i18n'
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
      sortNameGetCrondList: "crond_id",
      sortTypeGetCrondList: "asc",

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
      
      tableDataCrond: [],

      
      tableDataCrondLog: [
        {
          time: "xxx",
          content: "xxxxxxxxxxxx",
          tooltip: true,
        },
      ],
    };
  },
  computed: {
      //任务计划类型
      taskType(){
        return [
        {
          key: 1,
          value: i18n.t('crond.dumpFull')   //"仓库备份[dump-全量]",
        },
        {
          key: 2,
          value: i18n.t('crond.dumpDeltas')   //"仓库备份[dump-增量-deltas]",
        },
        // {
        //   key: 3,
        //   value: i18n.t('crond.hotcopyFull')   //"仓库备份[hotcopy-全量]",
        // },
        // {
        //   key: 4,
        //   value: i18n.t('crond.hotcopyDeltas')   //"仓库备份[hotcopy-增量]",
        // },
        {
          key: 5,
          value: i18n.t('crond.checkRepo')   //"仓库检查",
        },
        {
          key: 6,
          value: i18n.t('crond.shellScript')   //"shell脚本",
        },
        {
          key: 7,
          value: i18n.t('crond.syncSvnUser')   //"同步SVN用户",
        },
        {
          key: 8,
          value: i18n.t('crond.syncSvnGroup')   //"同步SVN分组",
        },
        {
          key: 9,
          value: i18n.t('crond.syncSvnRepo')   //"同步SVN仓库",
        },
      ]},
      //周期类型
      cycleType() {
        return [
        {
          name: i18n.t('crond.minute'),   //"每分钟",
          value: "minute",
        },
        {
          name: i18n.t('crond.minute_n'),   //"每隔N分钟",
          value: "minute_n",
        },
        {
          name: i18n.t('crond.hour'),   //"每小时",
          value: "hour",
        },
        {
          name: i18n.t('crond.hour_n'),   //"每隔N小时",
          value: "hour_n",
        },
        {
          name: i18n.t('crond.day'),   //"每天",
          value: "day",
        },
        {
          name: i18n.t('crond.day_n'),   //"每隔N天",
          value: "day_n",
        },
        {
          name: i18n.t('crond.week'),   //"每周",
          value: "week",
        },
        {
          name: i18n.t('crond.month'),   //"每月",
          value: "month",
        },
      ]},
      //周一到周日
      weekList() {
        return [
        {
          name: i18n.t('crond.Monday'),   //"周一",
          value: 1,
        },
        {
          name: i18n.t('crond.Tuesday'),   //"周二",
          value: 2,
        },
        {
          name: i18n.t('crond.Wednesday'),   //"周三",
          value: 3,
        },
        {
          name: i18n.t('crond.Thursday'),   //"周四",
          value: 4,
        },
        {
          name: i18n.t('crond.Friday'),   //"周五",
          value: 5,
        },
        {
          name: i18n.t('crond.Saturday'),   //"周六",
          value: 6,
        },
        {
          name: i18n.t('crond.Sunday'),   //"周日",
          value: 0,
        },
      ]},
      //任务计划信息
      tableColumnCrond() {
        return [
        {
          title: i18n.t('serial'),   //"序号",
          type: "index",
          fixed: "left",
          minWidth: 80,
        },
        {
          title: i18n.t('crond.name'),   //"任务名称",
          key: "task_name",
          tooltip: true,
          width: 220,
          minWidth: 220,
        },
        {
          title: i18n.t('crond.cycleDesc'),   //"执行周期描述",
          tooltip: true,
          key: "cycle_desc",
          width: 200,
          minWidth: 200,
        },
        {
          title: i18n.t('crond.notice'),   //"消息通知",
          slot: "notice",
          minWidth: 120,
        },
        {
          title: i18n.t('status'),   //"启用状态",
          key: "status",
          slot: "status",
          sortable: true,
          minWidth: 120,
        },
        {
          title: i18n.t('crond.saveCount'),   //"保存数量",
          key: "save_count",
          width: 110,
          minWidth: 110,
        },
        {
          title: i18n.t('crond.lastExecTime'),   //"上次执行时间",
          key: "last_exec_time",
          tooltip: true,
          // width: 180,
          minWidth: 140,
        },
        {
          title: i18n.t('others'),   //"其它",
          slot: "action",
          width: 240,
        },
      ]},
      //任务计划日志
      tableColumnCrondLog() {
        return [
        {
          title: i18n.t('crond.time'),   //"时间",
          key: "time",
        },
        {
          title: i18n.t('crond.content'),   //"内容",
          key: "content",
        },
      ]},
  },
  created() {},
  mounted() {
    this.GetCronStatus();
    this.GetCrontabList();
    this.GetRepList();
  },
  methods: {
    /**
     * 每页数量改变
     */
    CrondPageSizeChange(value) {
      //设置每页条数
      this.pageSizeCrond = value;
      this.GetCrontabList();
    },
    /**
     * 页码改变
     */
    CrondPageChange(value) {
      //设置当前页数
      this.pageCurrentCrond = value;
      this.GetCrontabList();
    },
    /**
     * 排序
     */
    SortChangeCrond(value) {
      this.sortNameGetCrondList = value.key;
      if (value.order == "desc" || value.order == "asc") {
        this.sortTypeGetCrondList = value.order;
      }
      this.GetCrontabList();
    },
    /**
     * 启用或禁用任务计划
     */
    UpdCrontabStatus(status, crond_id) {
      var that = this;
      var data = {
        crond_id: crond_id,
        status: status,
      };
      that.$axios
        .post("api.php?c=Crond&a=UpdCrontabStatus&t=web", data)
        .then(function (response) {
          var result = response.data;
          that.GetCrontabList();
          if (result.status == 1) {
            that.$Message.success(result.message);
          } else {
            that.$Message.error({ content: result.message, duration: 2 });
          }
        })
        .catch(function (error) {
          console.log(error);
          that.$Message.error(i18n.t('errors.contactAdmin'));
        });
    },
    /**
     * 获取特殊结构的下拉列表
     */
    GetRepList() {
      var that = this;
      var data = {};
      that.$axios
        .post("api.php?c=Crond&a=GetRepList&t=web", data)
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
          that.$Message.error(i18n.t('errors.contactAdmin'));
        });
    },
    /**
     * 获取任务计划列表
     */
    GetCrontabList() {
      var that = this;
      that.loadingGetCrondList = true;
      that.tableDataCrond = [];
      // that.totalUser = 0;
      var data = {
        pageSize: that.pageSizeCrond,
        currentPage: that.pageCurrentCrond,
        searchKeyword: that.searchKeywordCrond,
        sortName: that.sortNameGetCrondList,
        sortType: that.sortTypeGetCrondList,
      };
      that.$axios
        .post("api.php?c=Crond&a=GetCrontabList&t=web", data)
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
          that.$Message.error(i18n.t('errors.contactAdmin'));
        });
    },
    /**
     * 任务类型改变
     */
    ChangeCrondType(value) {
        var t_rep_name = this.repList.find((item) => item.rep_key === this.cycle.rep_key).rep_name;
        if (parseInt(this.cycle.rep_key) == -1) {
            t_rep_name = i18n.t('crond.allRepos');
        }
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
            t_rep_name
             +
            "]";
          break;
        case 6:
          //修改任务名称
          this.cycle.task_name = "";
          break;
        case 7:
        case 8:
        case 9:
          //修改任务名称
          this.cycle.task_name = this.taskType.find(
            (item) => item.key === this.cycle.task_type
          ).value;
          break;
      }
    },
    /**
     * 选择的仓库改变
     */
    ChangeRep(value) {
        var t_rep_name = this.repList.find((item) => item.rep_key === this.cycle.rep_key).rep_name;
        if (parseInt(this.cycle.rep_key) == -1) {
            t_rep_name = i18n.t('crond.allRepos');
        }
      //修改任务名称
      this.cycle.task_name =
        this.taskType.find((item) => item.key === this.cycle.task_type).value +
        "[" +
        t_rep_name
        +
        "]";
        console.log("2 ")
        console.log(parseInt(this.cycle.rep_key) === -1);
    },
    /**
     * 设置任务计划
     */
    ModalAddCrond() {
      if (this.cycle.task_type != 6) {
        var t_rep_name = this.repList.find((item) => item.rep_key === this.cycle.rep_key).rep_name;
        if (parseInt(this.cycle.rep_key) == -1) {
            t_rep_name = i18n.t('crond.allRepos');
        }
        //修改任务名称
        this.cycle.task_name =
          this.taskType.find((item) => item.key === this.cycle.task_type)
            .value +
          "[" +
          t_rep_name
          +
          "]";
      }
      //显示对话框
      this.statusCrond = "add";
      this.titleModalCrond = i18n.t('crond.addCrond');
      this.modalCrond = true;
    },
    /**
     * 设置任务计划
     */
    CreateCrontab() {
      var that = this;
      that.loadingAddCrond = true;
      var data = {
        cycle: that.cycle,
      };
      that.$axios
        .post("api.php?c=Crond&a=CreateCrontab&t=web", data)
        .then(function (response) {
          that.loadingAddCrond = false;
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
            that.modalCrond = false;
            that.GetCrontabList();
          } else {
            that.$Message.error({ content: result.message, duration: 2 });
          }
        })
        .catch(function (error) {
          that.loadingAddCrond = false;
          console.log(error);
          that.$Message.error(i18n.t('errors.contactAdmin'));
        });
    },
    /**
     * 查看任务计划日志
     */
    ModalViewCrondLog(crond_id) {
      this.modalViewCrondLog = true;
      this.GetCrontabLog(crond_id);
    },
    GetCrontabLog(crond_id) {
      var that = this;
      that.tempCrondLogCon = "";
      that.laodingGetLog = true;
      var data = {
        crond_id: crond_id,
      };
      that.$axios
        .post("api.php?c=Crond&a=GetCrontabLog&t=web", data)
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
          that.$Message.error(i18n.t('errors.contactAdmin'));
        });
    },
    /**
     * 编辑任务计划
     */
    ModalUpdCrond(index) {
      this.cycle = JSON.parse(JSON.stringify(this.tableDataCrond[index]));

      this.statusCrond = "upd";
      this.titleModalCrond = i18n.t('crond.editCrond'); //"编辑计划任务";
      this.modalCrond = true;
    },
    UpdCrontab() {
      var that = this;
      that.loadingUpdCrond = true;
      var data = {
        cycle: that.cycle,
      };
      that.$axios
        .post("api.php?c=Crond&a=UpdCrontab&t=web", data)
        .then(function (response) {
          that.loadingUpdCrond = false;
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
            that.modalCrond = false;
            that.GetCrontabList();
          } else {
            that.$Message.error({ content: result.message, duration: 2 });
          }
        })
        .catch(function (error) {
          that.loadingUpdCrond = false;
          console.log(error);
          that.$Message.error(i18n.t('errors.contactAdmin'));
        });
    },
    /**
     * 删除任务计划
     */
    DelCrontab(crond_id) {
      var that = this;
      that.$Modal.confirm({
        title: i18n.t('crond.deleteCrond'), //"删除任务计划",
        content: i18n.t('crond.confirmDelCrond'), //"确定要删除该记录吗？此操作不可逆！",
        onOk: () => {
          var data = {
            crond_id: crond_id,
          };
          that.$axios
            .post("api.php?c=Crond&a=DelCrontab&t=web", data)
            .then(function (response) {
              var result = response.data;
              if (result.status == 1) {
                that.$Message.success(result.message);
                that.GetCrontabList();
              } else {
                that.$Message.error({ content: result.message, duration: 2 });
              }
            })
            .catch(function (error) {
              console.log(error);
              that.$Message.error(i18n.t('errors.contactAdmin'));
            });
        },
      });
    },
    /**
     * 现在执行任务计划
     */
    TriggerCrontab(crond_id) {
      var that = this;
      that.$Modal.confirm({
        title: i18n.t('crond.triggerCrond'), //"执行任务计划",
        content:
          i18n.t('crond.confirmTriggerCrond'), //"确定要立即执行该任务计划吗？该操作可用于测试任务计划配置的正确性！",
        onOk: () => {
          var data = {
            crond_id: crond_id,
          };
          that.$axios
            .post("api.php?c=Crond&a=TriggerCrontab&t=web", data)
            .then(function (response) {
              var result = response.data;
              if (result.status == 1) {
                that.$Message.success(result.message);
                that.GetCrontabList();
              } else {
                that.$Message.error({ content: result.message, duration: 2 });
              }
            })
            .catch(function (error) {
              console.log(error);
              that.$Message.error(i18n.t('errors.contactAdmin'));
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
        .post("api.php?c=Crond&a=GetCronStatus&t=web", data)
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
          that.$Message.error(i18n.t('errors.contactAdmin'));
        });
    },
  },
};
</script>

<style >
</style>