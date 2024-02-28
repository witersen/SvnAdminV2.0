<template>
  <div>
    <Card :bordered="false" :dis-hover="true">
      <Row style="margin-bottom: 15px">
        <Col
          type="flex"
          justify="space-between"
          :xs="21"
          :sm="20"
          :md="19"
          :lg="18"
        >
          <Button
            icon="ios-trash-outline"
            type="warning"
            ghost
            :loading="loadingClearLogs"
            @click="DelLogs"
            >{{ $t('logs.clearLogs') }}</Button
          >
          <download-excel
            style="display: inline-block"
            class="export-excel-wrapper"
            :data="tableDataLog"
            :fields="excelLogFields"
            :name="$t('logs.logName')"
          >
            <Button icon="ios-cloud-download-outline" type="success" ghost
              >{{ $t('logs.exportLogs') }}</Button
            >
          </download-excel>
        </Col>
        <Col :xs="3" :sm="4" :md="5" :lg="6">
          <Input
            v-model="searchKeywordLog"
            search
            enter-button
            :placeholder="$t('logs.searchLogs')"
            style="width: 100%"
            @on-search="SearchGetLogList"
        /></Col>
      </Row>
      <Table
        border
        :loading="loadingGetLogList"
        :columns="tableColumnLog"
        :data="tableDataLog"
        size="small"
      >
      </Table>
      <Card :bordered="false" :dis-hover="true">
        <Page
          v-if="totalLog != 0"
          :total="totalLog"
          :current="pageCurrentLog"
          :page-size="pageSizeLog"
          @on-page-size-change="LogPageSizeChange"
          @on-change="LogPageChange"
          size="small"
          show-sizer
        />
      </Card>
    </Card>
  </div>
</template>

<script>
import i18n from "@/i18n";
export default {
  data() {
    return {
      /**
       * 分页数据
       */
      //用户
      pageCurrentLog: 1,
      pageSizeLog: 10,
      totalLog: 0,

      /**
       * 搜索关键词
       */
      searchKeywordLog: "",

      /**
       * 加载
       */
      loadingGetLogList: true,
      //清空日志
      loadingClearLogs: false,

      tableDataLog: [],
    };
  },
  computed: {
       /**
       * 表格
       */
       excelLogFields() {
        return {
            操作人: "log_add_user_name",
            日志类型: "log_type_name",
            详细信息: "log_content",
            操作时间: "log_add_time",
        }
      },
      /**
       * 表格
       */
      //日志
      tableColumnLog() {
        return [
        {
          title: i18n.t("serial"),    //"序号",
          type: "index",
          fixed: "left",
          minWidth: 80,
        },
        {
          title: i18n.t("operator"),    //"操作人",
          key: "log_add_user_name",
          minWidth: 120,
        },
        {
          title: i18n.t("logs.logType"),    //"日志类型",
          key: "log_type_name",
          minWidth: 150,
        },
        {
          title: i18n.t("logs.content"),    //"详细信息",
          key: "log_content",
          tooltip: true,
          minWidth: 120,
        },
        {
          title: i18n.t("logs.addTime"),    //"操作时间",
          key: "log_add_time",
          minWidth: 150,
        },
      ]},
  },
  created() {},
  mounted() {
    this.GetLogList();
  },
  methods: {
    /**
     * 每页数量改变
     */
    LogPageSizeChange(value) {
      //设置每页条数
      this.pageSizeLog = value;
      this.GetLogList();
    },
    /**
     * 页码改变
     */
    LogPageChange(value) {
      //设置当前页数
      this.pageCurrentLog = value;
      this.GetLogList();
    },
    /**
     * 获取日志
     */
    SearchGetLogList() {
      // if (this.searchKeywordLog == "") {
      //   this.$Message.error("请输入搜索内容");
      //   return;
      // }
      this.GetLogList();
    },
    GetLogList() {
      var that = this;
      that.loadingGetLogList = true;
      that.tableDataLog = [];
      // that.totalLog = 0;
      var data = {
        pageSize: that.pageSizeLog,
        currentPage: that.pageCurrentLog,
        searchKeyword: that.searchKeywordLog,
      };
      that.$axios
        .post("api.php?c=Logs&a=GetLogList&t=web", data)
        .then(function (response) {
          that.loadingGetLogList = false;
          var result = response.data;
          if (result.status == 1) {
            // that.$Message.success(result.message);
            that.tableDataLog = result.data.data;
            that.totalLog = result.data.total;
          } else {
            that.$Message.error({ content: result.message, duration: 2 });
          }
        })
        .catch(function (error) {
          that.loadingGetLogList = false;
          console.log(error);
          that.$Message.error(i18n.t('errors.contactAdmin'));
        });
    },
    /**
     * 清空日志
     */
    DelLogs() {
      var that = this;
      that.$Modal.confirm({
        title: "清空日志",
        content: "确定要清空日志记录吗？此操作不可逆！",
        onOk: () => {
          that.loadingClearLogs = true;
          var data = {};
          that.$axios
            .post("api.php?c=Logs&a=DelLogs&t=web", data)
            .then(function (response) {
              that.loadingClearLogs = false;
              var result = response.data;
              if (result.status == 1) {
                that.$Message.success(result.message);
                that.GetLogList();
              } else {
                that.$Message.error({ content: result.message, duration: 2 });
              }
            })
            .catch(function (error) {
              that.loadingClearLogs = false;
              console.log(error);
              that.$Message.error(i18n.t('errors.contactAdmin'));
            });
        },
      });
    },
  },
};
</script>

<style >
</style>