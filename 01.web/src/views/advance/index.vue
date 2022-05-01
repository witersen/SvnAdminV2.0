<template>
  <div>
    <Card :bordered="false" :dis-hover="true">
      <Tabs v-model="currentAdvanceTab" @on-click="SetCurrentAdvanceTab">
        <TabPane label="Subversion" name="1">
          <Card :bordered="false" :dis-hover="true" style="width: 620px">
            <Form :label-width="140">
              <FormItem label="Subversion">
                <Row>
                  <Col span="12">
                    <span>{{ formSvn.version }}</span>
                  </Col>
                  <Col span="1"> </Col>
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
                  <Col span="6"> </Col>
                </Row>
              </FormItem>
              <FormItem label="运行状态">
                <Row>
                  <Col span="12">
                    <span style="color: #ff9900" v-if="formSvn.installed == 0"
                      >未安装</span
                    >
                    <span style="color: #f90" v-if="formSvn.installed == 1"
                      >未启动</span
                    >
                    <span style="color: #19be6b" v-if="formSvn.installed == 2"
                      >运行中</span
                    >
                    <span style="color: #ed4014" v-if="formSvn.installed == -1"
                      >未知</span
                    >
                  </Col>
                  <Col span="1"> </Col>
                  <Col span="6">
                    <Button
                      :loading="loadingSvnserveStart"
                      type="success"
                      v-if="formSvn.installed == 1"
                      @click="Start"
                      >启动</Button
                    >
                    <Button
                      :loading="loadingSvnserveStop"
                      type="warning"
                      v-if="formSvn.installed == 2"
                      @click="Stop"
                      >停止</Button
                    >
                  </Col>
                </Row>
              </FormItem>
              <FormItem label="svnserve绑定端口">
                <Row>
                  <Col span="12">
                    <InputNumber
                      :min="1"
                      v-model="tempBindPort"
                      @on-change="ChangeEditPort"
                    ></InputNumber>
                  </Col>
                  <Col span="1"> </Col>
                  <Col span="6">
                    <Button
                      type="warning"
                      @click="EditPort"
                      :disabled="disabledEditPort"
                      :loading="loadingEditPort"
                      >修改</Button
                    >
                  </Col>
                </Row>
              </FormItem>
              <FormItem label="svnserve绑定主机名">
                <Row>
                  <Col span="12">
                    <Input
                      v-model="tempBindHost"
                      @on-change="ChangeEditHost"
                      placeholder="默认地址：0.0.0.0"
                    />
                  </Col>
                  <Col span="1"> </Col>
                  <Col span="6">
                    <Tooltip
                      :transfer="true"
                      max-width="350"
                      content="请注意，如果您的机器为公网服务器且非弹性IP，则可能会绑定失败。原因与云服务器厂商分配公网IP给服务器的方式有关。如果绑定失败，建议配置使用管理系统主机名代替检出地址。"
                    >
                      <Button
                        type="warning"
                        @click="EditHost"
                        :disabled="disabledEditHost"
                        :loading="loadingEditHost"
                        >修改</Button
                      >
                    </Tooltip>
                  </Col>
                </Row>
              </FormItem>
              <FormItem label="管理系统主机名">
                <Row>
                  <Col span="12">
                    <Input
                      v-model="tempManageHost"
                      @on-change="ChangeEditManageHost"
                      placeholder="默认地址：127.0.0.1"
                    />
                  </Col>
                  <Col span="1"> </Col>
                  <Col span="6">
                    <Button
                      type="warning"
                      @click="EditManageHost"
                      :disabled="disabledEditManageHost"
                      :loading="loadingEditManageHost"
                      >修改</Button
                    >
                  </Col>
                </Row>
              </FormItem>
              <FormItem label="检出地址选用">
                <Row>
                  <Col span="12">
                    <RadioGroup
                      v-model="formSvn.enable"
                      @on-change="EditEnable"
                    >
                      <Radio label="bindHost">svnserve绑定主机名</Radio>
                      <Radio label="manageHost">管理系统主机名</Radio>
                    </RadioGroup>
                  </Col>
                  <Col span="6"> </Col>
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
                <Row>
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
                <Row>
                  <Col span="12">
                    <Input value="2.4.0"></Input>
                  </Col>
                </Row>
              </FormItem>
              <FormItem label="SMTP端口">
                <Row>
                  <Col span="12">
                    <Input value="2.4.0"></Input>
                  </Col>
                </Row>
              </FormItem>
              <FormItem label="SMTP用户名">
                <Row>
                  <Col span="12">
                    <Input value="2.4.0"></Input>
                  </Col>
                </Row>
              </FormItem>
              <FormItem label="SMTP密码">
                <Row>
                  <Col span="12">
                    <Input value="2.4.0"></Input>
                  </Col>
                </Row>
              </FormItem>
              <FormItem label="发件人邮箱">
                <Row>
                  <Col span="12">
                    <Input value="2.4.0"></Input>
                  </Col>
                </Row>
              </FormItem>
              <FormItem label="测试接收邮箱">
                <Row>
                  <Col span="12">
                    <Input value="2.4.0"></Input>
                  </Col>
                  <Col span="6">
                    <Button type="success">发送测试邮件</Button>
                  </Col>
                </Row>
              </FormItem>
              <FormItem label="默认启用状态">
                <Row>
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
                <Row>
                  <Col span="12">
                    <Switch>
                      <Icon type="md-checkmark" slot="open"></Icon>
                      <Icon type="md-close" slot="close"></Icon>
                    </Switch>
                  </Col>
                </Row>
              </FormItem>
              <FormItem label="用户密码修改">
                <Row>
                  <Col span="12">
                    <Switch>
                      <Icon type="md-checkmark" slot="open"></Icon>
                      <Icon type="md-close" slot="close"></Icon>
                    </Switch>
                  </Col>
                </Row>
              </FormItem>
              <FormItem label="任务计划执行失败">
                <Row>
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
                <Row>
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
                <Row>
                  <Col span="12">
                    <span>5.4+</span>
                  </Col>
                </Row>
              </FormItem>
              <FormItem label="支持PHP-CLI版本">
                <Row>
                  <Col span="12">
                    <span>5.4+</span>
                  </Col>
                </Row>
              </FormItem>
              <FormItem label="支持数据库">
                <Row>
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
       * 临变量
       */
      //svnserve绑定端口
      tempBindPort: 0,
      //svnserve绑定主机名
      tempBindHost: "",
      //管理系统主机名称
      tempManageHost: "",

      /**
       * 控制修改状态
       */
      disabledEditPort: true,
      disabledEditHost: true,
      disabledEditManageHost: true,

      /**
       * tab
       */
      currentAdvanceTab: "1",

      /**
       * 加载
       */
      //启动svnserve
      loadingSvnserveStart: false,
      //停止svnserve
      loadingSvnserveStop: false,
      //更换绑定地址
      loadingEditHost: false,
      //更换绑定主机
      loadingEditPort: false,
      //更换管理系统地址
      loadingEditManageHost: false,

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
    };
  },
  computed: {},
  created() {},
  mounted() {
    if (!sessionStorage.currentAdvanceTab) {
      sessionStorage.setItem("currentAdvanceTab", "1");
    } else {
      this.currentAdvanceTab = sessionStorage.currentAdvanceTab;
    }
    this.GetDetail();
    this.GetConfig();
  },
  methods: {
    /**
     * 设置选中的标签
     */
    SetCurrentAdvanceTab(name) {
      sessionStorage.setItem("currentAdvanceTab", name);
      this.currentAdvanceTab = name;
    },
    /**
     * 获取subversion的详细信息
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
            //为临时变量赋值
            that.tempBindPort = result.data.bindPort;
            that.tempBindHost = result.data.bindHost;
            that.tempManageHost = result.data.manageHost;
            //初始化禁用按钮
            that.disabledEditPort = true;
            that.disabledEditHost = true;
            that.disabledEditManageHost = true;
          } else {
            that.$Message.error(result.message);
          }
        })
        .catch(function (error) {
          console.log(error);
          that.$Message.error("出错了 请联系管理员！");
        });
    },
    /**
     * 修改端口的值 触发重新计算按钮的禁用状态
     */
    ChangeEditPort(value) {
      if (this.tempBindPort == this.formSvn.bindPort) {
        this.disabledEditPort = true;
      } else {
        this.disabledEditPort = false;
      }
    },
    /**
     * 修改地址的值 触发重新计算按钮的禁用状态
     */
    ChangeEditHost(event) {
      if (this.tempBindHost == this.formSvn.bindHost) {
        this.disabledEditHost = true;
      } else {
        this.disabledEditHost = false;
      }
    },
    /**
     * 修改管理系统主机名的值 触发重新计算按钮的禁用状态
     */
    ChangeEditManageHost(event) {
      if (this.tempManageHost == this.formSvn.manageHost) {
        this.disabledEditManageHost = true;
      } else {
        this.disabledEditManageHost = false;
      }
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
          that.$Message.error("出错了 请联系管理员！");
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
    Start() {
      var that = this;
      that.$Modal.confirm({
        title: "以daomen方式启动svnserve服务",
        content: "确定要启动svnserve服务吗吗？",
        onOk: () => {
          that.loadingSvnserveStart = true;
          var data = {};
          that.$axios
            .post("/api.php?c=subversion&a=Start&t=web", data)
            .then(function (response) {
              that.loadingSvnserveStart = false;
              var result = response.data;
              if (result.status == 1) {
                that.$Message.success(result.message);
                that.GetDetail();
              } else {
                that.$Message.error(result.message);
              }
            })
            .catch(function (error) {
              that.loadingSvnserveStart = false;
              console.log(error);
              that.$Message.error("出错了 请联系管理员！");
            });
        },
      });
    },
    /**
     * 停止SVN
     */
    Stop() {
      var that = this;
      that.$Modal.confirm({
        title: "停止svnserve服务",
        content: "确定要停止svnserve服务吗？",
        onOk: () => {
          that.loadingSvnserveStop = true;
          var data = {};
          that.$axios
            .post("/api.php?c=subversion&a=Stop&t=web", data)
            .then(function (response) {
              that.loadingSvnserveStop = false;
              var result = response.data;
              if (result.status == 1) {
                that.$Message.success(result.message);
                that.GetDetail();
              } else {
                that.$Message.error(result.message);
              }
            })
            .catch(function (error) {
              that.loadingSvnserveStop = false;
              console.log(error);
              that.$Message.error("出错了 请联系管理员！");
            });
        },
      });
    },
    /**
     * 修改svnserve的绑定端口
     */
    EditPort() {
      var that = this;
      that.$Modal.confirm({
        title: "更换svnserve服务绑定端口",
        content:
          "确定要更换svnserve服务绑定端口吗？此操作会使svnserve服务停止并重新启动！",
        onOk: () => {
          that.loadingEditPort = true;
          var data = {
            bindPort: that.tempBindPort,
          };
          that.$axios
            .post("/api.php?c=subversion&a=EditPort&t=web", data)
            .then(function (response) {
              that.loadingEditPort = false;
              var result = response.data;
              if (result.status == 1) {
                that.$Message.success(result.message);
                that.GetDetail();
              } else {
                that.GetDetail();
                that.$Message.error(result.message);
              }
            })
            .catch(function (error) {
              that.loadingEditPort = false;
              console.log(error);
              that.$Message.error("出错了 请联系管理员！");
            });
        },
      });
    },
    /**
     * 修改svnserve的绑定主机
     */
    EditHost() {
      var that = this;
      that.$Modal.confirm({
        title: "更换svnserve服务绑定主机",
        content:
          "确定要更换svnserve服务绑定主机吗？此操作会使svnserve服务停止并重新启动！",
        onOk: () => {
          that.loadingEditHost = true;
          var data = {
            bindHost: that.tempBindHost,
          };
          that.$axios
            .post("/api.php?c=subversion&a=EditHost&t=web", data)
            .then(function (response) {
              that.loadingEditHost = false;
              var result = response.data;
              if (result.status == 1) {
                that.$Message.success(result.message);
                that.GetDetail();
              } else {
                that.GetDetail();
                that.$Message.error(result.message);
              }
            })
            .catch(function (error) {
              that.loadingEditHost = false;
              console.log(error);
              that.$Message.error("出错了 请联系管理员！");
            });
        },
      });
    },
    /**
     * 修改管理系统主机名
     */
    EditManageHost() {
      var that = this;
      that.$Modal.confirm({
        title: "更换管理系统主机名",
        content:
          "确定要更换管理系统主机名吗？此操作不会影响svnserve服务的状态！",
        onOk: () => {
          that.loadingEditManageHost = true;
          var data = {
            manageHost: that.tempManageHost,
          };
          that.$axios
            .post("/api.php?c=subversion&a=EditManageHost&t=web", data)
            .then(function (response) {
              that.loadingEditManageHost = false;
              var result = response.data;
              if (result.status == 1) {
                that.$Message.success(result.message);
                that.GetDetail();
              } else {
                that.GetDetail();
                that.$Message.error(result.message);
              }
            })
            .catch(function (error) {
              that.loadingEditManageHost = false;
              console.log(error);
              that.$Message.error("出错了 请联系管理员！");
            });
        },
      });
    },
    /**
     * 修改检出地址
     */
    EditEnable(value) {
      var that = this;
      var data = {
        enable: value,
      };
      that.$axios
        .post("/api.php?c=subversion&a=EditEnable&t=web", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
            that.GetDetail();
          } else {
            that.GetDetail();
            that.$Message.error(result.message);
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