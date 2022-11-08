<template>
  <div>
    <Card :bordered="false" :dis-hover="true">
      <Tabs v-model="currentAdvanceTab" @on-click="SetCurrentAdvanceTab">
        <TabPane label="Subversion" name="1">
          <Card :bordered="false" :dis-hover="true" style="width: 620px">
            <Form :label-width="100" label-position="left">
              <FormItem label="Subversion">
                <Row>
                  <Col span="12">
                    <span>{{ formSvn.version }}</span>
                  </Col>
                  <Col span="1"> </Col>
                  <Col span="6">
                    <Tooltip
                      :transfer="true"
                      max-width="360"
                      content="可在命令行模式下执行 server/insta.php 进行Subversion安装和初始化等操作"
                    >
                      <Button type="info">说明</Button>
                    </Tooltip>
                  </Col>
                  <Col span="6"> </Col>
                </Row>
              </FormItem>
              <FormItem label="运行状态">
                <Row>
                  <Col span="12">
                    <Tooltip
                      :transfer="true"
                      max-width="300"
                      content="运行状态通过pid文件和pid数值进行判断-如有误判请检查svnserve程序的启动方式"
                    >
                      <span style="color: #f90" v-if="!formSvn.status"
                        >未启动</span
                      >
                      <span style="color: #19be6b" v-if="formSvn.status"
                        >运行中</span
                      >
                    </Tooltip>
                  </Col>
                  <Col span="1"> </Col>
                  <Col span="6">
                    <Button
                      :loading="loadingSvnserveStart"
                      type="success"
                      v-if="!formSvn.status"
                      @click="Start"
                      >启动</Button
                    >
                    <Button
                      :loading="loadingSvnserveStop"
                      type="warning"
                      v-if="formSvn.status"
                      @click="Stop"
                      >停止</Button
                    >
                  </Col>
                </Row>
              </FormItem>
              <FormItem label="绑定端口">
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
              <FormItem label="绑定主机">
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
                      content="请注意，此值默认为 0.0.0.0 ，是 svnserve 服务器的实际的默认的绑定地址。如果无特殊原因无需修改此默认值。如果你要更换为公网IP地址，且你的机器为公网服务器且非弹性IP，则可能会绑定失败。原因与云服务器厂商分配公网IP给服务器的方式有关。"
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
              <FormItem label="自定义主机">
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
                    <Tooltip
                      :transfer="true"
                      max-width="350"
                      content="请注意，此值不影响 svnserve 服务器的正常运行，只是一个管理员自定义的主机地址字符串。如果将下方的检出地址切换为自定义主机，用户进行仓库浏览的时候，复制的检出地址将会以此值为前缀"
                    >
                      <Button
                        type="warning"
                        @click="EditManageHost"
                        :disabled="disabledEditManageHost"
                        :loading="loadingEditManageHost"
                        >修改</Button
                      >
                    </Tooltip>
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
                      <Radio label="bindHost">绑定主机</Radio>
                      <Radio label="manageHost">自定义主机</Radio>
                    </RadioGroup>
                  </Col>
                  <Col span="6"> </Col>
                </Row>
              </FormItem>
            </Form>
          </Card>
        </TabPane>
        <TabPane label="路径信息" name="2">
          <Card :bordered="false" :dis-hover="true" style="width: 620px">
            <Alert
              >可在命令行模式下执行 server/insta.php 进行目录更换操作
            </Alert>
            <Form :label-width="160" label-position="left">
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
          <Card :bordered="false" :dis-hover="true" style="width: 620px">
            <Form :label-width="120" label-position="left">
              <FormItem label="SMTP主机">
                <Row>
                  <Col span="12">
                    <Input v-model="formMailSmtp.host"></Input>
                  </Col>
                </Row>
              </FormItem>
              <FormItem label="加密">
                <Row>
                  <Col span="12">
                    <RadioGroup
                      v-model="formMailSmtp.encryption"
                      @on-change="ChangeEncryption"
                    >
                      <Radio label="none">
                        <span>无</span>
                      </Radio>
                      <Radio label="SSL">
                        <span>SSL</span>
                      </Radio>
                      <Radio label="TLS">
                        <span>TLS</span>
                      </Radio>
                    </RadioGroup>
                  </Col>
                  <Col span="1"> </Col>
                  <Col span="6">
                    <Tooltip
                      :transfer="true"
                      max-width="360"
                      content="对于大多数服务器，建议使用TLS。 如果您的SMTP提供商同时提供SSL和TLS选项，我们建议您使用TLS。"
                    >
                      <Button type="info">说明</Button>
                    </Tooltip>
                  </Col>
                </Row>
              </FormItem>
              <FormItem label="SMTP端口">
                <Row>
                  <Col span="12">
                    <InputNumber
                      :min="1"
                      v-model="formMailSmtp.port"
                    ></InputNumber>
                  </Col>
                  <Col span="1"> </Col>
                </Row>
              </FormItem>
              <FormItem label="自动TLS" v-if="formMailSmtp.encryption != 'TLS'">
                <Row>
                  <Col span="12">
                    <Switch v-model="formMailSmtp.autotls">
                      <Icon type="md-checkmark" slot="open"></Icon>
                      <Icon type="md-close" slot="close"></Icon>
                    </Switch>
                  </Col>
                  <Col span="1"> </Col>
                  <Col span="6">
                    <Tooltip
                      :transfer="true"
                      max-width="360"
                      content="默认情况下，如果服务器支持TLS加密，则会自动使用TLS加密（推荐）。在某些情况下，由于服务器配置错误可能会导致问题，则需要将其禁用。"
                    >
                      <Button type="info">说明</Button>
                    </Tooltip>
                  </Col>
                </Row>
              </FormItem>
              <FormItem label="认证">
                <Row>
                  <Col span="12">
                    <Switch v-model="formMailSmtp.auth">
                      <Icon type="md-checkmark" slot="open"></Icon>
                      <Icon type="md-close" slot="close"></Icon>
                    </Switch>
                  </Col>
                </Row>
              </FormItem>
              <FormItem label="SMTP用户名" v-if="formMailSmtp.auth">
                <Row>
                  <Col span="12">
                    <Input v-model="formMailSmtp.user"></Input>
                  </Col>
                  <Col span="1"> </Col>
                  <Col span="6">
                    <Tooltip
                      :transfer="true"
                      max-width="360"
                      content="如果使用QQ邮件服务，请注意对于@qq.com的邮件地址，仅输入@前面的部分，对于@vip.qq.com的邮件地址，可能需填入完整的地址"
                    >
                      <Button type="info">说明</Button>
                    </Tooltip>
                  </Col>
                </Row>
              </FormItem>
              <FormItem label="SMTP密码" v-if="formMailSmtp.auth">
                <Row>
                  <Col span="12">
                    <Input
                      v-model="formMailSmtp.pass"
                      type="password"
                      password
                    ></Input>
                  </Col>
                  <Col span="1"> </Col>
                </Row>
              </FormItem>
              <FormItem label="发件人邮箱">
                <Row>
                  <Col span="12">
                    <Input
                      v-model="formMailSmtp.from.address"
                      placeholder="默认与用户名相同，需要为邮件格式"
                    ></Input>
                  </Col>
                  <Col span="1"> </Col>
                </Row>
              </FormItem>
              <FormItem label="收件人邮箱">
                <Row>
                  <Col span="12">
                    <Tag
                      closable
                      v-for="item in formMailSmtp.to"
                      :key="item.address"
                      @on-close="CloseTagToEmail"
                      :name="item.address"
                      >{{ item.address }}</Tag
                    >
                    <Button
                      icon="ios-add"
                      type="dashed"
                      size="small"
                      @click="ModalAddToEmail"
                      >添加</Button
                    >
                  </Col>
                  <Col span="1"> </Col>
                  <Col span="6">
                    <Tooltip
                      :transfer="true"
                      max-width="360"
                      content="收件人邮箱只有在触发消息推送选项且邮件服务启用的条件下才会收到邮件"
                    >
                      <Button type="info">说明</Button>
                    </Tooltip>
                  </Col>
                </Row>
              </FormItem>
              <FormItem label="测试邮箱">
                <Row>
                  <Col span="12">
                    <Input
                      v-model="formMailSmtp.test"
                      placeholder="测试邮箱不会被保存"
                    ></Input>
                  </Col>
                  <Col span="1"> </Col>
                  <Col span="6">
                    <Tooltip
                      :transfer="true"
                      max-width="360"
                      content="发送测试邮件会使用当前表单填写的配置信息而不是已经保存过的配置信息。全局默认的发送超时时间为10s，如有需要请自行修改。"
                    >
                      <Button
                        type="success"
                        @click="TestMail"
                        :loading="loadingSendTest"
                        >发送</Button
                      >
                    </Tooltip>
                  </Col>
                </Row>
              </FormItem>
              <FormItem label="发邮超时时间">
                <InputNumber
                  :min="1"
                  v-model="formMailSmtp.timeout"
                ></InputNumber>
              </FormItem>
              <FormItem label="启用状态">
                <Row>
                  <Col span="12">
                    <Switch v-model="formMailSmtp.status">
                      <Icon type="md-checkmark" slot="open"></Icon>
                      <Icon type="md-close" slot="close"></Icon>
                    </Switch>
                  </Col>
                </Row>
              </FormItem>
              <FormItem>
                <Button
                  type="primary"
                  @click="UpdMail"
                  :loading="loadingEditEmail"
                  >保存</Button
                >
              </FormItem>
            </Form>
          </Card>
        </TabPane>
        <TabPane label="消息推送" name="4">
          <Card :bordered="false" :dis-hover="true" style="width: 600px">
            <Alert
              >由于邮件发送没有使用异步任务<br /><br />
              因此开启了邮件推送模块的响应时间会有相应延迟<br /><br />
              如，用户点击登录 ~ 登录成功跳转的响应时间 = 正常处理时间 +
              邮件发送时间</Alert
            >
            <Form :label-width="140">
              <FormItem
                :label="item.note"
                v-for="(item, index) in listPush"
                :key="index"
              >
                <Row>
                  <Col span="12">
                    <Switch v-model="listPush[index].enable">
                      <Icon type="md-checkmark" slot="open"></Icon>
                      <Icon type="md-close" slot="close"></Icon>
                    </Switch>
                  </Col>
                </Row>
              </FormItem>
              <FormItem>
                <Button
                  type="primary"
                  :loading="loadingEditPush"
                  @click="UpdPush"
                  >保存</Button
                >
              </FormItem>
            </Form>
          </Card>
        </TabPane>
        <TabPane label="安全配置" name="5">
          <Card :bordered="false" :dis-hover="true" style="width: 600px">
            <Form :label-width="140">
              <FormItem
                :label="item.note"
                v-for="(item, index) in listSafe"
                :key="index"
              >
                <Row>
                  <Col span="12">
                    <Switch v-model="listSafe[index].enable">
                      <Icon type="md-checkmark" slot="open"></Icon>
                      <Icon type="md-close" slot="close"></Icon>
                    </Switch>
                  </Col>
                </Row>
              </FormItem>
              <FormItem>
                <Button
                  type="primary"
                  :loading="loadingEditSafe"
                  @click="UpdSafeConfig"
                  >保存</Button
                >
              </FormItem>
            </Form>
          </Card>
        </TabPane>
        <TabPane label="自助检测" name="6" v-if="false">
          <Card :bordered="false" :dis-hover="true" style="width: 620px">
            <Alert
              >不经意的配置可能会导致 authz 配置文件失效<br /><br />
              如 svnserve 1.10 版本中为空分组授权会导致配置失效等<br /><br />
              因此可通过此工具在线检测 authz 配置文件<br /><br />
              此功能依赖 svnauthz-validate</Alert
            >
            <Form :label-width="100" label-position="left">
              <FormItem label="authz文件">
                <Row>
                  <Col span="12">
                    <span>/home/svnadmin/authz</span>
                  </Col>
                  <Col span="1"> </Col>
                  <Col span="6">
                    <Button type="info">查看</Button>
                  </Col>
                  <Col span="6"> </Col>
                </Row>
              </FormItem>
              <FormItem label="passwd文件">
                <Row>
                  <Col span="12">
                    <span>/home/svnadmin/authz</span>
                  </Col>
                  <Col span="1"> </Col>
                  <Col span="6">
                    <Button type="info">查看</Button>
                  </Col>
                  <Col span="6"> </Col>
                </Row>
              </FormItem>
            </Form>
          </Card>
        </TabPane>
        <TabPane :label="labelUpd" name="7">
          <Card :bordered="false" :dis-hover="true" style="width: 600px">
            <Form :label-width="140">
              <FormItem label="当前版本">
                <Badge> {{ version.current_verson }} </Badge>
              </FormItem>
              <FormItem label="支持PHP版本">
                <Row>
                  <Col span="12">
                    <span>{{ version.php_version }}</span>
                  </Col>
                </Row>
              </FormItem>
              <FormItem label="支持数据库">
                <Row>
                  <Col span="12">
                    <span>{{ version.database }}</span>
                  </Col>
                </Row>
              </FormItem>
              <FormItem label="开源地址">
                <Row>
                  <Badge>
                    <a :href="version.github" target="_blank">GitHub</a>
                  </Badge>
                </Row>
                <Row>
                  <Badge>
                    <a :href="version.gitee" target="_blank">Gitee</a>
                  </Badge>
                </Row>
              </FormItem>
              <FormItem>
                <!-- <Tooltip
                  max-width="300"
                  content="此操作是通过读取位于GitHub和Gitee公开仓库(witersen/update)的配置文件进行软件更新检测 所以需要软件所在主机能够访问外网"
                  placement="top"
                  transfer
                > -->
                <Button
                  type="primary"
                  :loading="loadingCheckUpdate"
                  @click="CheckUpdate()"
                  >检测更新</Button
                >
                <!-- </Tooltip> -->
              </FormItem>
            </Form>
          </Card>
        </TabPane>
      </Tabs>
    </Card>
    <Modal
      v-model="modalSofawareUpdateGet"
      :draggable="true"
      title="最新版本信息"
    >
      <Scroll>
        <Form ref="formUpdate" :model="formUpdate" :label-width="90">
          <FormItem label="最新版本">
            <Badge dot>
              {{ formUpdate.version }}
            </Badge>
          </FormItem>
          <FormItem label="修复内容">
            <ul style="list-style: none">
              <li v-for="(item, index) in formUpdate.fixd.con" :key="index">
                <span> [{{ item.title }}] {{ item.content }} </span>
              </li>
            </ul>
          </FormItem>
          <FormItem label="新增内容">
            <ul style="list-style: none">
              <li v-for="(item, index) in formUpdate.add.con" :key="index">
                <span> [{{ item.title }}] {{ item.content }} </span>
              </li>
            </ul>
          </FormItem>
          <FormItem label="移除内容">
            <ul style="list-style: none">
              <li v-for="(item, index) in formUpdate.remove.con" :key="index">
                <span> [{{ item.title }}] {{ item.content }} </span>
              </li>
            </ul>
          </FormItem>
          <FormItem label="完整程序包">
            <ul style="list-style: none">
              <li
                v-for="(item, index) in formUpdate.release.download"
                :key="index"
              >
                [{{ index + 1 }}] {{ item.nodeName }}节点
                <ul style="list-style: none">
                  <li>
                    <a :href="item.url" target="_blank">下载</a>
                  </li>
                </ul>
              </li>
            </ul>
          </FormItem>
          <FormItem label="升级程序包">
            <ul style="list-style: none">
              <li
                v-for="(item1, index1) in formUpdate.update.download"
                :key="index1"
              >
                [{{ index1 + 1 }}] {{ item1.nodeName }}节点
                <ul style="list-style: none">
                  <li v-for="(item2, index2) in item1.packages" :key="index2">
                    <a :href="item2.url" target="_blank"
                      >{{ item2.for.source }} -> {{ item2.for.dest }}</a
                    >
                  </li>
                </ul>
              </li>
            </ul>
          </FormItem>
          <FormItem label="升级步骤">
            <ul style="list-style: none">
              <li v-for="(item, index) in formUpdate.update.step" :key="index">
                <span> [{{ item.title }}] {{ item.content }} </span>
              </li>
            </ul>
          </FormItem>
        </Form>
      </Scroll>
    </Modal>
    <Modal
      v-model="modalAddToEmail"
      :draggable="true"
      title="添加收件人邮箱"
      @on-ok="AddToEmail"
    >
      <Form @submit.native.prevent>
        <FormItem>
          <Input type="text" v-model="tempToEmail"> </Input>
        </FormItem>
      </Form>
    </Modal>
  </div>
</template>

<script>
export default {
  data() {
    return {
      //render的系统更新标点
      labelUpd: (h) => {
        return h("div", [
          h("span", "系统更新"),
          h("Badge", {
            props: {
              //通过此状态设置有无更新
              dot: sessionStorage.hasUpdate == 1 ? true : false,
              offset: [-12, -10],
            },
          }),
        ]);
      },
      /**
       * 临时变量
       */
      //svnserve绑定端口
      tempBindPort: 0,
      //svnserve绑定主机名
      tempBindHost: "",
      //管理系统主机名称
      tempManageHost: "",
      //测试邮箱
      tempTestEmail: "",
      //添加收件人邮箱
      tempToEmail: "",

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
       * 版本信息
       */
      version: {
        current_verson: "2.3.4",
        php_version: "5.5+",
        database: "MYSQL、SQLite",
        github: "https://github.com/witersen/SvnAdminV2.0",
        gitee: "https://gitee.com/witersen/SvnAdminV2.0",
      },

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
      //发送测试邮件
      loadingSendTest: false,
      //保存邮件配置信息
      loadingEditEmail: false,
      //保存推送配置信息
      loadingEditPush: false,
      //保存安全配置选项
      loadingEditSafe: false,
      //检测更新
      loadingCheckUpdate: false,

      /**
       * subversion信息
       */
      formSvn: {
        version: "",
        bindPort: "",
        bindHost: "",
        manageHost: "",
        enable: "",
        svnserveLog: "",
      },

      /**
       * list
       */
      //配置文件
      configList: [],
      //消息推送配置
      listPush: [],
      //安全配置选项
      listSafe: [],

      /**
       * 对话框
       */
      modalSofawareUpdateGet: false,
      modalAddToEmail: false,

      /**
       * 表单
       */
      //邮件服务
      formMailSmtp: {
        host: "",
        auth: "",
        user: "",
        pass: "",
        encryption: "",
        autotls: true,
        port: 0,
        test: "",
        from: {
          address: "",
          name: "",
        },
        status: false,
        to: [],
        timeout: 0,
      },
      //新版本信息
      formUpdate: {
        version: "",
        fixd: {
          con: [],
        },
        add: {
          con: [],
        },
        remove: {
          con: [],
        },
        release: {
          download: [],
        },
        update: {
          step: [],
          download: [],
        },
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
    this.GetMailInfo();
    this.GetPushInfo();
    this.GetSafeInfo();
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
        .post("/api.php?c=Svn&a=GetDetail&t=web", data)
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
            that.$Message.error({ content: result.message, duration: 2 });
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
     * 获取邮件配置信息
     */
    GetMailInfo() {
      var that = this;
      var data = {};
      that.$axios
        .post("/api.php?c=Mail&a=GetMailInfo&t=web", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.formMailSmtp = result.data;
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
     * 更换加密选择 触发
     */
    ChangeEncryption(value) {
      //自动推荐默认端口
      if (value == "none") {
        this.formMailSmtp.port = 25;
      } else if (value == "SSL") {
        this.formMailSmtp.port = 465;
      } else if (value == "TLS") {
        this.formMailSmtp.port = 587;
      }
    },
    /**
     * 修改邮件配置信息
     */
    UpdMail() {
      var that = this;
      that.loadingEditEmail = true;
      var data = {
        host: that.formMailSmtp.host,
        auth: that.formMailSmtp.auth,
        user: that.formMailSmtp.user,
        pass: that.formMailSmtp.pass,
        encryption: that.formMailSmtp.encryption,
        autotls: that.formMailSmtp.autotls,
        port: that.formMailSmtp.port,
        from: that.formMailSmtp.from,
        status: that.formMailSmtp.status,
        to: that.formMailSmtp.to,
        timeout: that.formMailSmtp.timeout,
      };
      that.$axios
        .post("/api.php?c=Mail&a=UpdMail&t=web", data)
        .then(function (response) {
          that.loadingEditEmail = false;
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
            that.GetMailInfo();
          } else {
            that.$Message.error({ content: result.message, duration: 2 });
          }
        })
        .catch(function (error) {
          that.loadingEditEmail = false;
          console.log(error);
          that.$Message.error("出错了 请联系管理员！");
        });
    },
    /**
     * 发送测试邮件
     */
    TestMail() {
      var that = this;
      that.loadingSendTest = true;
      var data = {
        host: that.formMailSmtp.host,
        auth: that.formMailSmtp.auth,
        user: that.formMailSmtp.user,
        pass: that.formMailSmtp.pass,
        encryption: that.formMailSmtp.encryption,
        autotls: that.formMailSmtp.autotls,
        port: that.formMailSmtp.port,
        test: that.formMailSmtp.test,
        from: that.formMailSmtp.from,
        timeout: that.formMailSmtp.timeout,
      };
      that.$axios
        .post("/api.php?c=Mail&a=TestMail&t=web", data)
        .then(function (response) {
          that.loadingSendTest = false;
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
          } else {
            that.$Message.error({ content: result.message, duration: 2 });
          }
        })
        .catch(function (error) {
          that.loadingSendTest = false;
          console.log(error);
          that.$Message.error("出错了 请联系管理员！");
        });
    },
    /**
     * 获取配置文件信息
     */
    GetConfig() {
      var that = this;
      var data = {};
      that.$axios
        .post("/api.php?c=Svn&a=GetConfig&t=web", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.configList = result.data;
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
     * 获取消息推送配置
     */
    GetPushInfo() {
      var that = this;
      var data = {};
      that.$axios
        .post("/api.php?c=Mail&a=GetPushInfo&t=web", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.listPush = result.data;
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
     * 获取安全配置选项
     */
    GetSafeInfo() {
      var that = this;
      var data = {};
      that.$axios
        .post("/api.php?c=Safe&a=GetSafeInfo&t=web", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.listSafe = result.data;
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
     * 删除收件人邮箱
     */
    CloseTagToEmail(event, name) {
      this.formMailSmtp.to = this.formMailSmtp.to.filter(
        (item) => item.address != name
      );
    },
    /**
     * 添加收件人邮箱
     */
    ModalAddToEmail() {
      this.modalAddToEmail = true;
      this.tempToEmail = "";
    },
    AddToEmail() {
      //检查为空输入
      if (this.tempToEmail == "") {
        this.$Message.error("输入不能为空");
        return;
      }
      //检查重复输入
      var temp = this.formMailSmtp.to.filter(
        (item) => item.address != this.tempToEmail
      );
      if (temp.length != this.formMailSmtp.to.length) {
        this.$Message.error("邮件已存在");
        return;
      }
      //插入
      this.formMailSmtp.to.push({
        address: this.tempToEmail,
        name: "",
      });
    },
    /**
     * 修改信息
     */
    UpdPush() {
      var that = this;
      that.loadingEditPush = true;
      var data = {
        listPush: that.listPush,
      };
      that.$axios
        .post("/api.php?c=Mail&a=UpdPush&t=web", data)
        .then(function (response) {
          that.loadingEditPush = false;
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
            that.GetPushInfo();
          } else {
            that.$Message.error({ content: result.message, duration: 2 });
          }
        })
        .catch(function (error) {
          that.loadingEditPush = false;
          console.log(error);
          that.$Message.error("出错了 请联系管理员！");
        });
    },
    /**
     * 保存安全配置选项
     */
    UpdSafeConfig() {
      var that = this;
      that.loadingEditSafe = true;
      var data = {
        listSafe: that.listSafe,
      };
      that.$axios
        .post("/api.php?c=Safe&a=UpdSafeConfig&t=web", data)
        .then(function (response) {
          that.loadingEditSafe = false;
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
            that.GetSafeInfo();
          } else {
            that.$Message.error({ content: result.message, duration: 2 });
          }
        })
        .catch(function (error) {
          that.loadingEditSafe = false;
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
            .post("/api.php?c=Svn&a=Start&t=web", data)
            .then(function (response) {
              that.loadingSvnserveStart = false;
              var result = response.data;
              if (result.status == 1) {
                that.$Message.success(result.message);
                that.GetDetail();
              } else {
                that.$Message.error({ content: result.message, duration: 2 });
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
            .post("/api.php?c=Svn&a=Stop&t=web", data)
            .then(function (response) {
              that.loadingSvnserveStop = false;
              var result = response.data;
              if (result.status == 1) {
                that.$Message.success(result.message);
                that.GetDetail();
              } else {
                that.$Message.error({ content: result.message, duration: 2 });
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
            .post("/api.php?c=Svn&a=EditPort&t=web", data)
            .then(function (response) {
              that.loadingEditPort = false;
              var result = response.data;
              if (result.status == 1) {
                that.$Message.success(result.message);
                that.GetDetail();
              } else {
                that.GetDetail();
                that.$Message.error({ content: result.message, duration: 2 });
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
            .post("/api.php?c=Svn&a=EditHost&t=web", data)
            .then(function (response) {
              that.loadingEditHost = false;
              var result = response.data;
              if (result.status == 1) {
                that.$Message.success(result.message);
                that.GetDetail();
              } else {
                that.GetDetail();
                that.$Message.error({ content: result.message, duration: 2 });
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
            .post("/api.php?c=Svn&a=EditManageHost&t=web", data)
            .then(function (response) {
              that.loadingEditManageHost = false;
              var result = response.data;
              if (result.status == 1) {
                that.$Message.success(result.message);
                that.GetDetail();
              } else {
                that.GetDetail();
                that.$Message.error({ content: result.message, duration: 2 });
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
        .post("/api.php?c=Svn&a=EditEnable&t=web", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
            that.GetDetail();
          } else {
            that.GetDetail();
            that.$Message.error({ content: result.message, duration: 2 });
          }
        })
        .catch(function (error) {
          console.log(error);
          that.$Message.error("出错了 请联系管理员！");
        });
    },
    //检测更新
    CheckUpdate() {
      var that = this;
      that.loadingCheckUpdate = true;
      var data = {};
      that.$axios
        .post("/api.php?c=Update&a=CheckUpdate&t=web", data)
        .then(function (response) {
          that.loadingCheckUpdate = false;
          var result = response.data;
          if (result.status == 1) {
            if (result.data != "") {
              that.formUpdate = result.data;
              that.modalSofawareUpdateGet = true;
            } else {
              that.$Message.success(result.message);
            }
          } else {
            that.$Message.error({ content: result.message, duration: 2 });
          }
        })
        .catch(function (error) {
          that.loadingCheckUpdate = false;
          console.log(error);
        });
    },
  },
};
</script>

<style >
</style>