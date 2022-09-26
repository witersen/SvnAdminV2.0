<template>
  <div>
    <Card :bordered="false" :dis-hover="true">
      <Row type="flex" justify="end">
        <Col span="18">
          <Button icon="md-add" type="primary" ghost @click="ModalAddCrond"
            >添加任务计划</Button
          >
        </Col>
        <Col span="6">
          <Input
            search
            enter-button
            placeholder="通过xxx信息搜索..."
            style="margin-bottom: 15px; width: 315px"
        /></Col>
      </Row>
      <Table
        border
        :columns="tableColumnCrond"
        :data="tableDataCrond"
        size="small"
      >
        <template slot-scope="{ row }" slot="repStatus">
          <Switch v-model="row.repStatus">
            <Icon type="md-checkmark" slot="open"></Icon>
            <Icon type="md-close" slot="close"></Icon>
          </Switch>
        </template>
        <template slot-scope="{ row }" slot="test">
          <Button type="success" size="small">执行</Button>
        </template>
        <template slot-scope="{ row }" slot="log">
          <Button type="info" size="small" @click="ModalViewCrondLog()"
            >查看</Button
          >
        </template>
        <template slot-scope="{ row }" slot="action">
          <Button type="warning" size="small" @click="ModalEditCrond()"
            >编辑</Button
          >
          <Button type="error" size="small" @click="DelRep()">删除</Button>
        </template>
      </Table>
      <Card :bordered="false" :dis-hover="true">
        <Page :total="40" size="small" show-sizer />
      </Card>
    </Card>
    <Modal v-model="modalAddCrond" title="添加任务计划" @on-ok="AddCrond">
      <Form :model="formAddCrond" :label-width="80">
        <FormItem label="任务类型">
          <Select style="width: 200px">
            <Option
              v-for="item in crondTypeList"
              :value="item.key"
              :key="item.key"
              >{{ item.value }}</Option
            >
          </Select>
        </FormItem>
        <FormItem label="任务名称">
          <Input></Input>
        </FormItem>
        <FormItem label="执行周期">
          <InputNumber
            :min="1"
            :max="31"
            :formatter="(value) => `${value}天`"
            :parser="(value) => value.replace('天', '')"
          ></InputNumber>
          <InputNumber
            :min="0"
            :max="23"
            :formatter="(value) => `${value}小时`"
            :parser="(value) => value.replace('小时', '')"
          ></InputNumber>
          <InputNumber
            :min="0"
            :max="59"
            :formatter="(value) => `${value}分钟`"
            :parser="(value) => value.replace('分钟', '')"
          ></InputNumber>
        </FormItem>
      </Form>
    </Modal>
    <Modal v-model="modalEditCrond" title="编辑任务计划信息">
      <Form :model="formEditCrond" :label-width="80">
        <FormItem label="任务类型">
          <Select disabled style="width: 200px">
            <Option
              v-for="item in crondTypeList"
              :value="item.key"
              :key="item.key"
              >{{ item.value }}</Option
            >
          </Select>
        </FormItem>
        <FormItem label="任务名称">
          <Input disabled></Input>
        </FormItem>
        <FormItem label="执行周期">
          <InputNumber
            :min="1"
            :max="31"
            :formatter="(value) => `${value}天`"
            :parser="(value) => value.replace('天', '')"
          ></InputNumber>
          <InputNumber
            :min="0"
            :max="23"
            :formatter="(value) => `${value}小时`"
            :parser="(value) => value.replace('小时', '')"
          ></InputNumber>
          <InputNumber
            :min="0"
            :max="59"
            :formatter="(value) => `${value}分钟`"
            :parser="(value) => value.replace('分钟', '')"
          ></InputNumber>
        </FormItem>
      </Form>
    </Modal>
    <Modal v-model="modalViewCrondLog" title="查看任务计划日志">
      <Input readonly type="textarea" :rows="15" />
    </Modal>
  </div>
</template>

<script>
export default {
  data() {
    return {
      /**
       * 下拉
       */
      crondTypeList: [
        {
          key: "1",
          value: "仓库备份",
        },
        {
          key: "2",
          value: "仓库检查",
        },
      ],
      /**
       * 对话框
       */
      //新建任务计划
      modalAddCrond: false,
      //查看任务计划日志
      modalViewCrondLog: false,
      //编辑任务计划
      modalEditCrond: false,
      /**
       * 表单
       */
      //新建任务计划
      formAddCrond: {},
      //编辑任务计划
      formEditCrond: {},
      /**
       * 表格
       */
      //任务计划信息
      tableColumnCrond: [
        {
          title: "序号",
          type: "index",
        },
        {
          title: "任务类型",
          key: "repName",
          tooltip: true,
          sortable: true,
        },
        {
          title: "任务名称",
          key: "repName",
          tooltip: true,
          sortable: true,
        },
        {
          title: "执行周期",
          key: "repRemarks",
        },
        {
          title: "启用状态",
          slot: "repStatus",
          sortable: true,
        },
        {
          title: "立即执行",
          slot: "test",
          // width: 180,
        },
        {
          title: "执行日志",
          slot: "log",
          // width: 180,
        },
        {
          title: "其它",
          slot: "action",
          // width: 180,
        },
      ],
      tableDataCrond: [
        {
          repName: "xxxxxxxxxxxxxxxxxxxxxxxxxx",
          repRev: 12,
          repSize: 128,
          repStatus: 0,
        },
      ],

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
  mounted() {},
  methods: {
    /**
     * 添加任务计划
     */
    ModalAddCrond() {
      this.modalAddCrond = true;
    },
    AddCrond() {},
    /**
     * 查看任务计划日志
     */
    ModalViewCrondLog() {
      this.modalViewCrondLog = true;
    },
    /**
     * 编辑任务计划
     */
    ModalEditCrond(index, repName) {
      this.modalEditCrond = true;
    },
    EditCrond() {},
    /**
     * 删除任务计划
     */
    DelRep(index, repName) {
      this.$Modal.confirm({
        title: "删除任务计划",
        content: "确定要删除该记录吗？此操作不可逆！",
        onOk: () => {},
      });
    },
  },
};
</script>

<style >
</style>