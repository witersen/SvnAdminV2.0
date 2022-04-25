<template>
  <div>
    <Card :bordered="false" :dis-hover="true">
      <Tabs value="1">
        <TabPane label="Subversion" name="1">
          <Card :bordered="false" :dis-hover="true" style="width: 620px">
            <Form :label-width="140">
              <FormItem label="Subversion">
                <Row :gutter="16">
                  <Col span="12">
                    <span>{{ formSvn.version }}</span>
                  </Col>
                  <Col span="6">
                    <Button
                      type="error"
                      v-if="formSvn.installed == 1 || formSvn.installed == 2"
                      @click="UnInstall"
                      >卸载</Button
                    >
                    <Button
                      type="primary"
                      v-if="formSvn.installed == 0"
                      @click="Install"
                      >安装</Button
                    >
                  </Col>
                </Row>
              </FormItem>
              <FormItem label="运行状态">
                <Row :gutter="16">
                  <Col span="12">
                    <span style="color: #ff9900" v-if="formSvn.installed == 0"
                      >未安装</span
                    >
                    <span style="color: #2db7f5" v-if="formSvn.installed == 1"
                      >未启动</span
                    >
                    <span style="color: #19be6b" v-if="formSvn.installed == 2"
                      >运行中</span
                    >
                    <span style="color: #ed4014" v-if="formSvn.installed == -1"
                      >未知</span
                    >
                  </Col>
                  <Col span="6">
                    <Button
                      type="success"
                      v-if="formSvn.installed == 1"
                      @click="Start"
                      >启动</Button
                    >
                    <Button
                      type="success"
                      v-if="formSvn.installed == 2"
                      @click="Stop"
                      >停止</Button
                    >
                  </Col>
                </Row>
              </FormItem>
              <FormItem label="svnserve绑定端口">
                <Row :gutter="16">
                  <Col span="12">
                    <span>{{ formSvn.bindPort }}</span>
                  </Col>
                  <Col span="6">
                    <Button type="warning" @click="ModalEditPort">修改</Button>
                  </Col>
                </Row>
              </FormItem>
              <FormItem label="svnserve绑定主机名">
                <Row :gutter="16">
                  <Col span="12">
                    <span>{{ formSvn.bindHost }}</span>
                  </Col>
                  <Col span="6">
                    <Tooltip
                      :transfer="true"
                      max-width="350"
                      content="请注意，如果您的机器为公网服务器且非弹性IP，则可能会绑定失败。原因与云服务器厂商分配公网IP给服务器的方式有关。如果绑定失败，建议配置使用管理系统主机名代替检出地址。"
                    >
                      <Button type="warning" @click="ModalEditHost"
                        >修改</Button
                      >
                    </Tooltip>
                  </Col>
                </Row>
              </FormItem>
              <FormItem label="管理系统主机名">
                <Row :gutter="16">
                  <Col span="12">
                    <span>{{ formSvn.manageHost }}</span>
                  </Col>
                  <Col span="6">
                    <Button type="warning" @click="ModalEditManageHost"
                      >修改</Button
                    >
                  </Col>
                </Row>
              </FormItem>
              <FormItem label="检出地址选用">
                <Row :gutter="16">
                  <Col span="12">
                    <RadioGroup
                      v-model="formSvn.enable"
                      @on-change="EditEnable"
                    >
                      <Radio :label="formSvn.bindHost"></Radio>
                      <Radio :label="formSvn.manageHost"></Radio>
                    </RadioGroup>
                  </Col>
                  <Col span="6"> </Col>
                </Row>
              </FormItem>
              <FormItem label="svnserve运行日志">
                <Row :gutter="16">
                  <Col span="12">
                    <span>{{ formSvn.svnserveLog }}</span>
                  </Col>
                  <Col span="6">
                    <Button type="success" @click="ViewSvnserveLog"
                      >查看</Button
                    >
                  </Col>
                </Row>
              </FormItem>
            </Form>
          </Card>
        </TabPane>
        <TabPane label="配置文件" name="2">
          <Card :bordered="false" :dis-hover="true" style="width: 600px">
            <Form :label-width="160">
              <FormItem
                :label="item.key"
                v-for="(item, index) in configList"
                :key="index"
              >
                <Row :gutter="16">
                  <Col span="12">
                    <span>{{ item.value }}</span>
                  </Col>
                </Row>
              </FormItem>
            </Form>
          </Card>
        </TabPane>
        <TabPane label="邮件服务" name="3">
          <Card :bordered="false" :dis-hover="true" style="width: 600px">
            <Form :label-width="140">
              <FormItem label="SMTP主机">
                <Row :gutter="16">
                  <Col span="12">
                    <Input value="2.4.0"></Input>
                  </Col>
                </Row>
              </FormItem>
              <FormItem label="SMTP端口">
                <Row :gutter="16">
                  <Col span="12">
                    <Input value="2.4.0"></Input>
                  </Col>
                </Row>
              </FormItem>
              <FormItem label="SMTP用户名">
                <Row :gutter="16">
                  <Col span="12">
                    <Input value="2.4.0"></Input>
                  </Col>
                </Row>
              </FormItem>
              <FormItem label="SMTP密码">
                <Row :gutter="16">
                  <Col span="12">
                    <Input value="2.4.0"></Input>
                  </Col>
                </Row>
              </FormItem>
              <FormItem label="发件人邮箱">
                <Row :gutter="16">
                  <Col span="12">
                    <Input value="2.4.0"></Input>
                  </Col>
                </Row>
              </FormItem>
              <FormItem label="测试接收邮箱">
                <Row :gutter="16">
                  <Col span="12">
                    <Input value="2.4.0"></Input>
                  </Col>
                  <Col span="6">
                    <Button type="success">发送测试邮件</Button>
                  </Col>
                </Row>
              </FormItem>
              <FormItem label="默认启用状态">
                <Row :gutter="16">
                  <Col span="12">
                    <Switch>
                      <Icon type="md-checkmark" slot="open"></Icon>
                      <Icon type="md-close" slot="close"></Icon>
                    </Switch>
                  </Col>
                </Row>
              </FormItem>

              <FormItem>
                <Button type="primary">保存</Button>
              </FormItem>
            </Form>
          </Card>
        </TabPane>
        <TabPane label="消息推送" name="4">
          <Card :bordered="false" :dis-hover="true" style="width: 600px">
            <Form :label-width="140">
              <FormItem label="用户登录">
                <Row :gutter="16">
                  <Col span="12">
                    <Switch>
                      <Icon type="md-checkmark" slot="open"></Icon>
                      <Icon type="md-close" slot="close"></Icon>
                    </Switch>
                  </Col>
                </Row>
              </FormItem>
              <FormItem label="用户密码修改">
                <Row :gutter="16">
                  <Col span="12">
                    <Switch>
                      <Icon type="md-checkmark" slot="open"></Icon>
                      <Icon type="md-close" slot="close"></Icon>
                    </Switch>
                  </Col>
                </Row>
              </FormItem>
              <FormItem label="任务计划执行失败">
                <Row :gutter="16">
                  <Col span="12">
                    <Switch>
                      <Icon type="md-checkmark" slot="open"></Icon>
                      <Icon type="md-close" slot="close"></Icon>
                    </Switch>
                  </Col>
                </Row>
              </FormItem>
              <FormItem>
                <Button type="primary">保存</Button>
              </FormItem>
            </Form>
          </Card>
        </TabPane>
        <TabPane label="系统安全" name="5">
          <Card :bordered="false" :dis-hover="true" style="width: 600px">
            <Form :label-width="140">
              <FormItem label="token">
                1、本系统使用token进行鉴权和登录状态保持<br />
                2、密钥 + 算法 = token<br />
                3、密钥泄露会导致token被伪造从而登录本系统<br />
                4、定期重置密钥可以增加系统的安全性<br />
                5、重置密钥后所有管理系统在线用户会被下线<br />
              </FormItem>
              <FormItem label="密钥">
                <Row :gutter="16">
                  <Col span="12">
                    <Input type="password" readonly value="2.4.0"></Input>
                  </Col>
                  <Col span="6"> <Button type="primary">重置</Button></Col>
                </Row>
              </FormItem>
            </Form>
          </Card>
        </TabPane>
        <TabPane label="系统更新" name="6">
          <Card :bordered="false" :dis-hover="true" style="width: 600px">
            <Form :label-width="140">
              <FormItem label="当前版本">
                <Badge> v2.3 </Badge>
              </FormItem>
              <FormItem label="支持PHP-FPM版本">
                <Row :gutter="16">
                  <Col span="12">
                    <span>5.4+</span>
                  </Col>
                </Row>
              </FormItem>
              <FormItem label="支持PHP-CLI版本">
                <Row :gutter="16">
                  <Col span="12">
                    <span>5.4+</span>
                  </Col>
                </Row>
              </FormItem>
              <FormItem label="支持数据库">
                <Row :gutter="16">
                  <Col span="12">
                    <span>MySQL、sqlite</span>
                  </Col>
                </Row>
              </FormItem>
              <FormItem label="开源地址">
                <Row>
                  <Badge>
                    <a href="" target="_blank">GitHub</a>
                  </Badge>
                </Row>
                <Row>
                  <Badge>
                    <a href="" target="_blank">Gitee</a>
                  </Badge>
                </Row>
              </FormItem>
              <FormItem>
                <Tooltip max-width="300" content="111" placement="top" transfer>
                  <Button type="primary" @click="CheckUpdate()"
                    >检测更新</Button
                  >
                </Tooltip>
              </FormItem>
            </Form>
          </Card>
        </TabPane>
      </Tabs>
    </Card>
  </div>
</template>

<script>
export default {
  data() {
    return {
      /**
       * subversion信息
       */
      formSvn: {
        version: "",
        installed: null,
        bindPort: "",
        bindHost: "",
        manageHost: "",
        enable: "",
        svnserveLog: "",
      },
      /**
       *
       */
      configList: [],
      /**
       * 对话框
       */
      //新建仓库
      modalAddRep: false,
      //编辑仓库信息
      modalEditRepName: false,
      /**
       * 表单
       */
      //新建仓库
      formRepAdd: {},
      //编辑仓库
      formRepEdit: {
        repNameOld: "",
        repNameNew: "",
      },
      /**
       * 表格
       */
      //仓库信息
      tableRepColumn: [
        {
          title: "序号",
          type: "index",
        },
        {
          title: "用户名",
          key: "repName",
          tooltip: true,
          sortable: true,
        },
        {
          title: "密码",
          key: "repRev",
          tooltip: true,
        },
        {
          title: "启用状态",
          slot: "repStatus",
          sortable: true,
        },
        {
          title: "过期时间",
          key: "repRemarks",
          sortable: true,
        },
        {
          title: "备注信息",
          key: "repRemarks",
        },
        {
          title: "其它",
          slot: "action",
          width: 180,
        },
      ],
      tableRepData: [
        {
          repName: "xxxxxxxxxxxxxxxxxxxxxxxxxx",
          repRev: 12,
          repSize: 128,
          repStatus: 0,
        },
      ],
    };
  },
  computed: {},
  created() {},
  mounted() {
    this.GetDetail();
    this.GetConfig();
  },
  methods: {
    /**
     * 获取版本信息
     */
    GetDetail() {
      var that = this;
      var data = {};
      that.$axios
        .post("/api.php?c=subversion&a=GetDetail&t=web", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.formSvn = result.data;
          } else {
            that.$Message.error(result.message);
          }
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    /**
     * 获取配置文件信息
     */
    GetConfig() {
      var that = this;
      var data = {};
      that.$axios
        .post("/api.php?c=subversion&a=GetConfig&t=web", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.configList = result.data;
          } else {
            that.$Message.error(result.message);
          }
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    /**
     * 安装SVN
     */
    Install() {},
    /**
     * 卸载SVN
     */
    UnInstall() {},
    /**
     * 启动SVN
     */
    Start() {},
    /**
     * 停止SVN
     */
    Stop() {},
    /**
     * 修改svnserve的绑定端口
     */
    ModalEditPort() {},
    EditPort() {},
    /**
     * 修改svnserve的绑定主机
     */
    ModalEditHost() {},
    EditHost() {},
    /**
     * 修改管理系统主机名
     */
    ModalEditManageHost() {},
    EditManageHost() {},
    /**
     * 修改检出地址
     */
    EditEnable(value) {},
    /**
     * 查看svnserve运行日志
     */
    ViewSvnserveLog() {},

    /**
     * 添加仓库
     */
    ModalAddRep() {
      this.modalAddRep = true;
    },
    AddRep() {},
    /**
     * 编辑仓库名称
     */
    ModalEditRepName(index, repName) {
      this.modalEditRepName = true;
    },
    EditRepName() {},
    /**
     * 删除仓库
     *
     */
    DelRep(index, repName) {
      this.$Modal.confirm({
        title: "删除SVN用户-xxxxx用户",
        content: "确定要删除该用户吗？<br/>该操作不可逆！",
        onOk: () => {},
      });
    },
  },
};
</script>

<style >
</style>