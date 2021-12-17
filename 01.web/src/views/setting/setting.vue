<style lang="less">
.text-wrapper {
  white-space: pre-wrap;
}
</style>
<template>
  <Card :bordered="false" :dis-hover="true">
    <Tabs value="tab1">
      <TabPane label="基础配置" name="tab1">
        <Row>
          <Col span="8">
            <Card :bordered="true" :dis-hover="true">
              <p slot="title">服务器配置信息</p>
              <Form ref="formConfig" :model="formConfig" :label-width="140">
                <FormItem label="版本库父目录">
                  <Input v-model="formConfig.svn_repository_path" disabled />
                </FormItem>
                <FormItem label="SVN配置文件">
                  <Input v-model="formConfig.svnserve" disabled />
                </FormItem>
                <FormItem label="用户文件(passwd)">
                  <Input v-model="formConfig.passwd" disabled />
                </FormItem>
                <FormItem label="权限文件(authz)">
                  <Input v-model="formConfig.authz" disabled />
                </FormItem>
                <FormItem label="备份目录">
                  <Input v-model="formConfig.backup_path" disabled />
                </FormItem>
                <FormItem label="日志目录">
                  <Input v-model="formConfig.logs" disabled />
                </FormItem>
              </Form>
            </Card>
          </Col>
          <Col span="8" offset="1">
            <Card :bordered="true" :dis-hover="true">
              <p slot="title">管理系统配置信息</p>
              <Form ref="formConfig" :model="formConfig" :label-width="140">
                <FormItem label="服务器域名">
                  <Input v-model="formConfig.server_domain" />
                </FormItem>
                <FormItem label="服务器IP">
                  <Input v-model="formConfig.server_ip" />
                </FormItem>
                <FormItem label="加密密钥">
                  <Input
                    v-model="formConfig.token"
                    placeholder="如果是初次安装 请修改打乱该token"
                  />
                </FormItem>
                <FormItem label="消息通知服务">
                  <Switch
                    true-color="#13ce66"
                    false-color="#ff4949"
                    v-model="formConfig.all_mail_status"
                  />
                </FormItem>
                <FormItem>
                  <Tooltip
                    max-width="300"
                    :content="toolTipSave"
                    placement="top"
                    transfer
                  >
                    <Button type="primary" @click="SetBasicSetting()"
                      >保存</Button
                    >
                  </Tooltip>
                </FormItem>
              </Form>
            </Card>
          </Col>
        </Row>
      </TabPane>
      <TabPane label="服务管理" name="tab2">
        <Row>
          <Col span="8">
            <Card dis-hover style="height: 460px">
              <p slot="title">SVN服务</p>
              <p>服务状态</p>
              <br />
              <Button :type="svnserve.type" ghost>{{ svnserve.status }}</Button
              ><br /><br />
              <p>服务端口</p>
              <br />
              <Button type="info" ghost>{{ svnserve.port }}</Button
              ><br /><br />
              <p>状态管理</p>
              <br />
              <ButtonGroup>
                <Button type="info" @click="SetSvnserveStatus('startSvn')"
                  >开启服务</Button
                >
                <Button type="warning" @click="SetSvnserveStatus('restartSvn')"
                  >重启服务</Button
                >
                <Button type="error" @click="SetSvnserveStatus('stopSvn')"
                  >关闭服务</Button
                > </ButtonGroup
              ><br /><br />
              <p>安装卸载</p>
              <br />
              <ButtonGroup>
                <Tooltip
                  max-width="300"
                  :content="toolTipInstall"
                  placement="top"
                  transfer
                >
                  <Button
                    type="info"
                    @click="Install"
                    :loading="svnserveLoading.installSvn"
                    >安装服务</Button
                  >
                </Tooltip>
                <Tooltip
                  max-width="300"
                  :content="toolTipRepaire"
                  placement="top"
                  transfer
                  disabled
                >
                  <Button
                    type="warning"
                    disabled
                    @click="Repaire"
                    :loading="svnserveLoading.repaireSvn"
                    >修复异常</Button
                  >
                </Tooltip>
                <Tooltip
                  max-width="300"
                  :content="toolTipUnInstall"
                  placement="top"
                  transfer
                >
                  <Button
                    type="error"
                    @click="UnInstall"
                    :loading="svnserveLoading.unInstallSvn"
                    >卸载服务</Button
                  >
                </Tooltip>
              </ButtonGroup>
            </Card>
          </Col>
          <Col span="8" offset="1">
            <Card dis-hover style="height: 460px">
              <p slot="title">防火墙服务</p>
              <p>服务状态</p>
              <br />
              <Button :type="firewallStatus.type" ghost>{{
                firewallStatus.status
              }}</Button
              ><br /><br />
              <p>状态管理</p>
              <br />
              <ButtonGroup>
                <Button
                  type="info"
                  @click="SetFirewallStatus('startFirewall')"
                  :loading="firewallLoading.startFirewall"
                  >开启服务</Button
                >
                <Button
                  type="warning"
                  @click="SetFirewallStatus('restartFirewall')"
                  :loading="firewallLoading.restartFirewall"
                  >重启服务</Button
                >
                <Button
                  type="error"
                  @click="SetFirewallStatus('stopFirewall')"
                  :loading="firewallLoading.stopFirewall"
                  >关闭服务</Button
                > </ButtonGroup
              ><br /><br />
              <p>快捷放行</p>
              <br />
              <template>
                HTTP(TCP/80)
                <Switch
                  disabled
                  true-color="#13ce66"
                  false-color="#ff4949"
                  v-model="firewallPort.http"
                  @on-change="SetFirewallPolicy('80', 'http')"
                  :loading="firewallPortLoading.http"
                /><br /><br />
                SVN(TCP/3690)
                <Switch
                  true-color="#13ce66"
                  false-color="#ff4949"
                  v-model="firewallPort.svn"
                  @on-change="SetFirewallPolicy('3690', 'svn')"
                  :loading="firewallPortLoading.svn"
                /><br /><br />
                HTTPS(TCP/443)
                <Switch
                  true-color="#13ce66"
                  false-color="#ff4949"
                  v-model="firewallPort.https"
                  @on-change="SetFirewallPolicy('443', 'https')"
                  :loading="firewallPortLoading.https"
                />
              </template>
            </Card>
          </Col>
        </Row>
      </TabPane>
      <TabPane label="消息通知" name="tab3">
        <Row>
          <Col span="8">
            <Card dis-hover style="height: 540px">
              <p slot="title">邮件服务器</p>
              <Form ref="formEmail" :model="formEmail" :label-width="110">
                <FormItem label="SMTP主机">
                  <Input v-model="formEmail.smtp_host" />
                </FormItem>
                <FormItem label="SMTP端口">
                  <InputNumber
                    :max="99999"
                    :min="0"
                    v-model="formEmail.smtp_port"
                  ></InputNumber>
                </FormItem>
                <FormItem label="SMTP用户名">
                  <Input v-model="formEmail.smtp_user" />
                </FormItem>
                <FormItem label="SMTP密码">
                  <Input v-model="formEmail.smtp_password" />
                </FormItem>
                <FormItem label="发送邮箱">
                  <Input v-model="formEmail.smtp_send_email" />
                </FormItem>
                <FormItem label="测试邮箱">
                  <Input v-model="formEmail.smtp_test_email" />
                </FormItem>
                <FormItem>
                  <Button type="primary" @click="SendTestMail()"
                    >发送测试邮件</Button
                  >
                </FormItem>
                <FormItem>
                  <Tooltip
                    max-width="300"
                    :content="toolTipSave"
                    placement="top"
                    transfer
                  >
                    <Button type="primary" @click="SetMailInfo()">保存</Button>
                  </Tooltip>
                </FormItem>
              </Form>
            </Card>
          </Col>
        </Row>
      </TabPane>
      <TabPane label="管理员信息" name="tab5">
        <Row>
          <Col span="8">
            <Card :bordered="true" :dis-hover="true">
              <p slot="title">账户密码</p>
              <Form
                ref="formAdminInfo"
                :model="formAdminInfo"
                :label-width="110"
              >
                <FormItem label="管理员账号">
                  <Input v-model="formAdminInfo.manageUser" disabled />
                </FormItem>
                <FormItem label="管理员密码">
                  <Input v-model="formAdminInfo.managePass" />
                </FormItem>
                <FormItem label="管理员邮箱">
                  <Input v-model="formAdminInfo.manageEmail" />
                </FormItem>
                <FormItem>
                  <Tooltip
                    max-width="300"
                    :content="toolTipSave"
                    placement="top"
                    transfer
                  >
                    <Button type="primary" @click="SetManageSetting()"
                      >保存</Button
                    >
                  </Tooltip>
                </FormItem>
              </Form>
            </Card>
          </Col>
        </Row>
      </TabPane>
      <TabPane label="系统更新" name="tab4">
        <Row>
          <Col span="8">
            <Card dis-hover style="height: 320px">
              <p slot="title">当前版本信息</p>
              <Form
                ref="formSoftwareInfo"
                :model="formSoftwareInfo"
                :label-width="110"
              >
                <FormItem label="当前版本">
                  <Badge>
                    {{ formSoftwareInfo.current_verson }}
                  </Badge>
                </FormItem>
                <FormItem label="作者主页">
                  <Badge>
                    <a :href="formSoftwareInfo.author" target="_blank">{{
                      formSoftwareInfo.author
                    }}</a>
                  </Badge>
                </FormItem>
                <FormItem label="开源地址">
                  <Row>
                    <Badge>
                      <a :href="formSoftwareInfo.github" target="_blank"
                        >GitHub</a
                      >
                    </Badge>
                  </Row>
                  <Row>
                    <Badge>
                      <a :href="formSoftwareInfo.gitee" target="_blank"
                        >Gitee</a
                      >
                    </Badge>
                  </Row>
                </FormItem>
                <FormItem>
                  <Tooltip
                    max-width="300"
                    :content="toolTipUpdate"
                    placement="top"
                    transfer
                  >
                    <Button type="primary" @click="CheckUpdate()"
                      >检测更新</Button
                    >
                  </Tooltip>
                </FormItem>
              </Form>
            </Card>
          </Col>
        </Row>
      </TabPane>
    </Tabs>
    <Modal v-model="modalSofawareUpdateGet" title="最新版本信息">
      <Form ref="formSoftwareNew" :model="formSoftwareNew" :label-width="90">
        <FormItem label="最新版本">
          <Badge dot>
            {{ formSoftwareNew.latestVersion }}
          </Badge>
        </FormItem>
        <FormItem label="升级类型">
          <Badge>
            {{ formSoftwareNew.updateStep }}
          </Badge>
        </FormItem>
        <FormItem label="修复bug">
          <i-input
            v-html="formSoftwareNew.fixedContent"
            type="textarea"
            autosize
          ></i-input>
        </FormItem>
        <FormItem label="新增功能">
          <i-input
            v-html="formSoftwareNew.newContent"
            type="textarea"
            autosize
          ></i-input>
        </FormItem>
      </Form>
    </Modal>
  </Card>
</template>
<script>
export default {
  data() {
    return {
      current: 1, //当前在第几页
      page_size: 10, //每一页有几条数据
      content_total: 20, //总共有多少条数据
      toolTipRepaire: "",
      toolTipInstall:
        "此操作会使用yum install方式进行Subversion服务的安装和相关的配置文件修改 请务必通过本方式安装 请确保软件所在主机能够访问外网",
      toolTipUnInstall:
        "此操作会通过yum remove方式卸载Subversion服务 不会删除用户的SVN存储库和密码与权限文件 但还是建议操作前先进行数据备份",
      modalSofawareUpdateGet: false,
      toolTipUpdate:
        "此操作是通过读取位于GitHub和Gitee公开仓库(witersen/update)的配置文件进行软件更新检测 所以需要软件所在主机能够访问外网",
      toolTipSave:
        "由于配置信息是通过PHP配置文件进行管理读写 如果遇到设置不生效的情况 请检查 web 用户(可能是apache等) 对 $path/config 文件及下属文件的读写权限 您可以直接重设777权限 或联系开发者解决",
      //当前版本信息
      formSoftwareInfo: {
        current_verson: "",
        github: "",
        gitee: "",
        author: "",
      },
      //新版本信息
      formSoftwareNew: {
        newContent: "",
        latestVersion: "",
        fixedContent: "",
        updateType: "",
        updateStep: "",
      },
      //管理员信息
      formAdminInfo: {
        manageUser: "",
        managePass: "",
        manageEmail: "",
      },
      crontab_data: [],
      formMessage: {
        api_name: "",
        accesskey_id: "",
        accesskey_secret: "",
        model_code: "",
        signature: "",
        test_phone: "",
      },
      formEmail: {
        smtp_host: "",
        smtp_port: 25,
        smtp_user: "",
        smtp_password: "",
        smtp_send_email: "",
        smtp_test_email: "",
      },
      formConfig: {
        // server_ip: "127.0.0.1",
        // server_domain: "localhost",
        // svn_repository_path: "/www/svn",
        // backup_path: "/backup",
        // all_mail_status: 1,
      },
      svnserve: {
        // status: "已停止",
        // port: "3690",
        // type: "error",
      },
      firewallStatus: {
        // status: "运行中",
        // type: "success",
      },
      firewallPort: {
        svn: false,
        http: false,
        https: false,
      },
      svnserveLoading: {
        installSvn: false,
        repaireSvn: false,
        unInstallSvn: false,
      },
      firewallLoading: {
        startFirewall: false,
        restartFirewall: false,
        stopFirewall: false,
      },
      firewallPortLoading: {
        svn: false,
        http: false,
        https: false,
      },
    };
  },
  methods: {
    pageChange(value) {
      var that = this;
      that.current = value;
    },
    GetVersionInfo() {
      var that = this;
      var data = {};
      that.$axios
        .post("/api.php?c=update&a=GetVersionInfo", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.formSoftwareInfo = result.data;
          } else {
            that.$Message.error(result.message);
          }
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    CheckUpdate() {
      var that = this;
      var data = {};
      that.$axios
        .post("/api.php?c=update&a=CheckUpdate", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            if (result.data != null) {
              that.formSoftwareNew = result.data;
              that.modalSofawareUpdateGet = true;
            } else {
              that.$Message.success(result.message);
            }
          } else {
            that.$Message.error(result.message);
          }
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    GetMailInfo() {
      var that = this;
      var data = {};
      that.$axios
        .post("/api.php?c=mail&a=GetMailInfo", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            // that.$Message.success(result.message);
            that.formEmail = result.data;
          } else {
            that.$Message.error(result.message);
          }
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    SetMailInfo() {
      var that = this;
      var data = {
        host: that.formEmail.smtp_host,
        port: that.formEmail.smtp_port,
        username: that.formEmail.smtp_user,
        password: that.formEmail.smtp_password,
        from: that.formEmail.smtp_send_email,
      };
      that.$axios
        .post("/api.php?c=mail&a=SetMailInfo", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
          } else {
            that.$Message.error(result.message);
          }
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    SendTestMail() {
      var that = this;
      var data = {
        host: that.formEmail.smtp_host,
        port: that.formEmail.smtp_port,
        username: that.formEmail.smtp_user,
        password: that.formEmail.smtp_password,
        from: that.formEmail.smtp_send_email,
        to: that.formEmail.smtp_test_email,
      };
      that.$axios
        .post("/api.php?c=mail&a=SendTestMail", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
          } else {
            that.$Message.error(result.message);
          }
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    SetBasicSetting() {
      var that = this;
      var data = {
        token: that.formConfig.token,
        server_ip: that.formConfig.server_ip,
        server_domain: that.formConfig.server_domain,
        all_mail_status: that.formConfig.all_mail_status,
      };
      that.$axios
        .post("/api.php?c=config&a=SetBasicSetting", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
          } else {
            that.$Message.error(result.message);
          }
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    SetManageSetting() {
      var that = this;
      var data = {
        manageUser: that.formAdminInfo.manageUser,
        managePass: that.formAdminInfo.managePass,
        manageEmail: that.formAdminInfo.manageEmail,
      };
      that.$axios
        .post("/api.php?c=config&a=SetManageSetting", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
          } else {
            that.$Message.error(result.message);
          }
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    GetManageSetting() {
      var that = this;
      var data = {};
      that.$axios
        .post("/api.php?c=config&a=GetManageSetting", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.formAdminInfo = result.data;
          } else {
            that.$Message.error(result.message);
          }
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    GetBasicSetting() {
      var that = this;
      var data = {};
      that.$axios
        .post("/api.php?c=config&a=GetBasicSetting", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            // that.$Message.success(result.message);
            that.formConfig = result.data;
          } else {
            that.$Message.error(result.message);
          }
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    GetSvnserveStatus() {
      var that = this;
      var data = {};
      that.$axios
        .post("/api.php?c=svnserve&a=GetSvnserveStatus", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            //that.$Message.success(result.message);
            that.svnserve = result.data;
          } else {
            that.$Message.error(result.message);
          }
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    SetSvnserveStatus(action) {
      var that = this;
      var data = {
        action: action,
      };
      that.$axios
        .post("/api.php?c=svnserve&a=SetSvnserveStatus", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
            that.GetSvnserveStatus();
          } else {
            that.$Message.error(result.message);
          }
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    Install() {
      var that = this;
      var data = {};
      that.$Modal.confirm({
        title: "警告",
        content: "此操作将会安装Subversion服务，确认继续吗？",
        onOk: () => {
          that.svnserveLoading.installSvn = true;
          that.$axios
            .post("/api.php?c=svnserve&a=Install", data)
            .then(function (response) {
              var result = response.data;
              if (result.status == 1) {
                that.$Message.success(result.message);
                that.GetSvnserveStatus();
                that.svnserveLoading.installSvn = false;
                that.GetFirewallStatus();
              } else {
                that.svnserveLoading.installSvn = false;
                that.$Message.error(result.message);
              }
            })
            .catch(function (error) {
              that.svnserveLoading.installSvn = false;
              console.log(error);
            });
        },
        onCancel: () => {},
      });
    },
    Repaire() {
      var that = this;
      var data = {};
      that.$Modal.confirm({
        title: "警告",
        content: "此操作将会尝试修复一些问题，确认继续吗？",
        onOk: () => {
          that.svnserveLoading.repaireSvn = true;
          that.$axios
            .post("/api.php?c=svnserve&a=Repaire", data)
            .then(function (response) {
              var result = response.data;
              if (result.status == 1) {
                that.$Message.success(result.message);
                that.GetSvnserveStatus();
                that.svnserveLoading.repaireSvn = false;
              } else {
                that.svnserveLoading.repaireSvn = false;
                that.$Message.error(result.message);
              }
            })
            .catch(function (error) {
              that.svnserveLoading.repaireSvn = false;
              console.log(error);
            });
        },
        onCancel: () => {},
      });
    },
    UnInstall() {
      var that = this;
      var data = {};
      that.$Modal.confirm({
        title: "警告",
        content: "此操作将会卸载Subversion服务，确认继续吗？",
        onOk: () => {
          that.svnserveLoading.unInstallSvn = true;
          that.$axios
            .post("/api.php?c=svnserve&a=UnInstall", data)
            .then(function (response) {
              var result = response.data;
              if (result.status == 1) {
                that.$Message.success(result.message);
                that.GetSvnserveStatus();
                that.svnserveLoading.unInstallSvn = false;
              } else {
                that.svnserveLoading.unInstallSvn = false;
                that.$Message.error(result.message);
              }
            })
            .catch(function (error) {
              that.svnserveLoading.unInstallSvn = false;
              console.log(error);
            });
        },
        onCancel: () => {},
      });
    },
    GetFirewallStatus() {
      var that = this;
      var data = {};
      that.$axios
        .post("/api.php?c=firewall&a=GetFirewallStatus", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            //that.$Message.success(result.message);
            that.firewallStatus = result.data;
          } else {
            that.$Message.error(result.message);
          }
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    SetFirewallStatus(action) {
      var that = this;
      that.firewallLoading[`${action}`] = true;
      var data = {
        action: action,
      };
      that.$axios
        .post("/api.php?c=firewall&a=SetFirewallStatus", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
            that.firewallLoading[`${action}`] = false;
            that.GetFirewallStatus();
            that.GetFirewallPolicy();
          } else {
            that.firewallLoading[`${action}`] = false;
            that.$Message.error(result.message);
          }
        })
        .catch(function (error) {
          that.firewallLoading[`${action}`] = false;
          console.log(error);
        });
    },
    SetFirewallPolicy(port, serve) {
      var that = this;
      that.firewallPortLoading.http = true;
      that.firewallPortLoading.https = true;
      that.firewallPortLoading.svn = true;
      var data = {
        port: port,
        type: that.firewallPort[`${serve}`],
      };
      that.$axios
        .post("/api.php?c=firewall&a=SetFirewallPolicy", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);

            that.GetFirewallPolicy();
            that.GetFirewallStatus();
          } else {
            that.firewallPortLoading.http = false;
            that.firewallPortLoading.https = false;
            that.firewallPortLoading.svn = false;

            that.$Message.error(result.message);
          }
        })
        .catch(function (error) {
          that.GetFirewallPolicy();
          that.GetFirewallStatus();

          console.log(error);
        });
    },
    GetFirewallPolicy() {
      var that = this;
      that.firewallPortLoading.http = true;
      that.firewallPortLoading.https = true;
      that.firewallPortLoading.svn = true;
      var data = {};
      that.$axios
        .post("/api.php?c=firewall&a=GetFirewallPolicy", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.firewallPortLoading.http = false;
            that.firewallPortLoading.https = false;
            that.firewallPortLoading.svn = false;

            //that.$Message.success(result.message);
            that.firewallPort = result.data;
          } else {
            that.firewallPortLoading.http = false;
            that.firewallPortLoading.https = false;
            that.firewallPortLoading.svn = false;

            that.$Message.error(result.message);
          }
        })
        .catch(function (error) {
          that.firewallPortLoading.http = false;
          that.firewallPortLoading.https = false;
          that.firewallPortLoading.svn = false;

          console.log(error);
        });
    },
  },
  mounted() {
    var that = this;
    that.GetSvnserveStatus();
    that.GetFirewallStatus();
    that.GetFirewallPolicy();
    that.GetBasicSetting();
    that.GetMailInfo();
    that.GetVersionInfo();
    that.GetManageSetting();
  },
};
</script>
