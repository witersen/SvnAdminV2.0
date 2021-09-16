<template>
  <Card :bordered="false" :dis-hover="true">
    <Tabs value="tab1">
      <TabPane label="基础配置" name="tab1">
        <Row>
          <Col span="8">
            <Card :bordered="true" :dis-hover="true">
              <p slot="title">服务器信息</p>
              <Form ref="formConfig" :model="formConfig" :label-width="110">
                <FormItem label="服务器IP">
                  <Input v-model="formConfig.server_ip" />
                </FormItem>
                <FormItem label="服务器域名">
                  <Input v-model="formConfig.server_domain" />
                </FormItem>
                <FormItem label="版本库父文件夹">
                  <Input v-model="formConfig.svn_repository_path" />
                </FormItem>
                <FormItem label="备份目录">
                  <Input v-model="formConfig.backup_path" />
                </FormItem>
                <FormItem label="消息通知服务">
                  <Switch
                    true-color="#13ce66"
                    false-color="#ff4949"
                    v-model="formConfig.all_mail_status"
                  />
                </FormItem>
                <FormItem>
                  <Button type="primary" @click="SetBasicSetting()"
                    >保存</Button
                  >
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
                <Button
                  type="info"
                  @click="Install"
                  :loading="svnserveLoading.installSvn"
                  >安装服务</Button
                >
                <Button
                  type="warning"
                  @click="Repaire"
                  :loading="svnserveLoading.repaireSvn"
                  >修复异常</Button
                >
                <Button
                  type="error"
                  @click="UnInstall"
                  :loading="svnserveLoading.unInstallSvn"
                  >卸载服务</Button
                >
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
            <Card dis-hover style="height: 470px">
              <p slot="title">邮件服务器</p>
              <Form ref="formEmail" :model="formEmail" :label-width="110">
                <Row>
                  <Col span="17">
                    <FormItem label="SMTP主机">
                      <Input v-model="formEmail.smtp_host" />
                    </FormItem>
                  </Col>
                </Row>
                <Row>
                  <Col span="17">
                    <FormItem label="SMTP端口">
                      <InputNumber
                        :max="99999"
                        :min="0"
                        v-model="formEmail.smtp_port"
                      ></InputNumber>
                    </FormItem>
                  </Col>
                </Row>
                <Row>
                  <Col span="17">
                    <FormItem label="SMTP用户名">
                      <Input v-model="formEmail.smtp_user" />
                    </FormItem>
                  </Col>
                </Row>
                <Row>
                  <Col span="17">
                    <FormItem label="SMTP密码">
                      <Input v-model="formEmail.smtp_password" />
                    </FormItem>
                  </Col>
                </Row>
                <Row>
                  <Col span="17">
                    <FormItem label="发送邮箱">
                      <Input v-model="formEmail.smtp_send_email" />
                    </FormItem>
                  </Col>
                </Row>
                <Row>
                  <Col span="17">
                    <FormItem label="测试邮箱">
                      <Input v-model="formEmail.smtp_test_email" />
                    </FormItem>
                  </Col>
                </Row>
                <Row>
                  <Col span="9">
                    <FormItem>
                      <Button type="primary" @click="SendTestMail()"
                        >发送测试邮件</Button
                      >
                    </FormItem>
                  </Col>
                  <Col span="9">
                    <FormItem>
                      <Button type="primary" @click="SetMailInfo()"
                        >保存</Button
                      >
                    </FormItem>
                  </Col>
                </Row>
              </Form>
            </Card>
          </Col>
          <!-- <Col span="8" offset="1">
            <Card dis-hover style="height: 470px">
              <p slot="title">短信网关</p>
              <Form ref="formMessage" :model="formMessage" :label-width="130">
                <Row>
                  <Col span="18">
                    <FormItem label="短信服务名称">
                      <Input v-model="formMessage.api_name" />
                    </FormItem>
                  </Col>
                </Row>
                <Row>
                  <Col span="18">
                    <FormItem label="AccessKey Id">
                      <Input v-model="formMessage.accesskey_id" />
                    </FormItem>
                  </Col>
                </Row>
                <Row>
                  <Col span="18">
                    <FormItem label="AccessKey Secret">
                      <Input v-model="formMessage.accesskey_secret" />
                    </FormItem>
                  </Col>
                </Row>
                <Row>
                  <Col span="18">
                    <FormItem label="模版CODE">
                      <Input v-model="formMessage.model_code" />
                    </FormItem>
                  </Col>
                </Row>
                <Row>
                  <Col span="18">
                    <FormItem label="签名名称">
                      <Input v-model="formMessage.signature" />
                    </FormItem>
                  </Col>
                </Row>
                <Row>
                  <Col span="18">
                    <FormItem label="测试手机号码">
                      <Input v-model="formMessage.test_phone" />
                    </FormItem>
                  </Col>
                </Row>
                <Row>
                  <Col span="9">
                    <FormItem>
                      <Button type="primary">发送测试短信</Button>
                    </FormItem>
                  </Col>
                  <Col span="9">
                    <FormItem>
                      <Button type="primary">保存</Button>
                    </FormItem>
                  </Col>
                </Row>
              </Form>
            </Card>
          </Col> -->
        </Row>
      </TabPane>
      <TabPane label="计划任务" name="tab4">
        <Row>
          <Col span="8">
            <Card :bordered="true" :dis-hover="true">
              <p slot="title">添加计划任务</p>
              <Form
                ref="formCrontab.backup_type"
                :model="formCrontab.backup_type"
                :label-width="90"
              >
                <FormItem label="任务类型">
                  <Select v-model="formCrontab.backup_type.select">
                    <Option
                      v-for="item in formCrontab.backup_type.list"
                      :value="item.value"
                      :key="item.value"
                      >{{ item.label }}</Option
                    >
                  </Select>
                </FormItem>
                <FormItem label="执行周期">
                  <Row>
                    <Col span="5">
                      <Select v-model="formCrontab.cycle_type.select">
                        <Option
                          v-for="item in formCrontab.cycle_type.list"
                          :value="item.value"
                          :key="item.value"
                          >{{ item.label }}</Option
                        >
                      </Select>
                    </Col>
                    <Col
                      span="5"
                      offset="1"
                      v-if="formCrontab.cycle_type.select == 'weekly'"
                    >
                      <Select v-model="formCrontab.week.select">
                        <Option
                          v-for="item in formCrontab.week.list"
                          :value="item.value"
                          :key="item.value"
                          >{{ item.label }}</Option
                        >
                      </Select>
                    </Col>
                    <Col
                      span="5"
                      offset="1"
                      v-if="formCrontab.cycle_type.select != 'hourly'"
                    >
                      <InputNumber
                        :min="0"
                        :max="23"
                        v-model="formCrontab.hours"
                        :formatter="(value) => `${value}小时`"
                        :parser="(value) => value.replace('小时', '')"
                      ></InputNumber>
                    </Col>
                    <Col span="5" offset="1">
                      <InputNumber
                        :min="0"
                        :max="59"
                        v-model="formCrontab.minutes"
                        :formatter="(value) => `${value}分钟`"
                        :parser="(value) => value.replace('分钟', '')"
                      ></InputNumber>
                    </Col>
                  </Row>
                </FormItem>
                <FormItem label="选择仓库">
                  <Select v-model="formCrontab.repository.select">
                    <Option
                      v-for="item in formCrontab.repository.list"
                      :value="item.value"
                      :key="item.value"
                      >{{ item.label }}</Option
                    >
                  </Select>
                </FormItem>
                <FormItem label="保留最新">
                  <InputNumber
                    :min="1"
                    :max="1000"
                    v-model="formCrontab.crontab_count"
                    :formatter="(value) => `${value}份`"
                    :parser="(value) => value.replace('份', '')"
                  ></InputNumber>
                </FormItem>
                <FormItem>
                  <Button type="primary" @click="AddCrontab">添加任务</Button>
                </FormItem>
              </Form>
            </Card>
          </Col>
        </Row>
        <br />
        <Card :bordered="true" :dis-hover="true">
          <p slot="title">计划任务列表</p>
          <Table :columns="crontab_column" :data="crontab_data">
            <template slot-scope="{ index }" slot="action">
              <Button type="error" size="small" @click="DeleteCrontab(index)"
                >删除</Button
              >
            </template>
          </Table>
          <Card :bordered="false" :dis-hover="true">
            <Page
              v-if="content_total != 0"
              :total="content_total"
              :page-size="page_size"
              @on-change="pageChange"
            />
          </Card>
        </Card>
      </TabPane>
      <!-- <TabPane label="系统安全" name="tab5">系统安全</TabPane>
      <TabPane label="扩展服务" name="tab6">扩展服务</TabPane> -->
    </Tabs>
  </Card>
</template>
<script>
export default {
  data() {
    return {
      current: 1, //当前在第几页
      page_size: 10, //每一页有几条数据
      content_total: 20, //总共有多少条数据
      formCrontab: {
        backup_type: {
          select: "dump",
          list: [
            {
              value: "dump",
              label: "仓库备份-Dump-修订版本较多的情况下备份和恢复较慢",
            },
            {
              value: "hotcopy",
              label: "仓库备份-Hotcopy-备份和恢复较快但不节约空间",
            },
          ],
        },
        cycle_type: {
          select: "weekly",
          list: [
            {
              value: "weekly",
              label: "每周",
            },
            {
              value: "daily",
              label: "每天",
            },
            {
              value: "hourly",
              label: "每小时",
            },
          ],
        },
        week: {
          select: "1",
          list: [
            {
              value: "1",
              label: "周一",
            },
            {
              value: "2",
              label: "周二",
            },
            {
              value: "3",
              label: "周三",
            },
            {
              value: "4",
              label: "周四",
            },
            {
              value: "5",
              label: "周五",
            },
            {
              value: "6",
              label: "周六",
            },
            {
              value: "7",
              label: "周天",
            },
          ],
        },
        repository: {
          select: "",
          list: [
            {
              value: "1",
              label: "11",
            },
          ],
        },
        hours: 1,
        minutes: 30,
        crontab_count: 3,
      },
      crontab_column: [
        {
          title: "任务名称",
          key: "crontab_name",
        },
        {
          title: "周期",
          key: "crontab_cycle",
        },
        {
          title: "执行时间",
          key: "crontab_time",
        },
        {
          title: "保存数量",
          key: "crontab_count",
        },
        {
          title: "操作",
          slot: "action",
          width: 300,
          align: "center",
        },
      ],
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
      that.GetCrontabList();
    },
    DeleteCrontab(index) {
      var that = this;
      var data = {
        sign: that.crontab_data[index]["sign"],
      };
      that.$Modal.confirm({
        title: "警告",
        content: "确定要删除该记录吗？",
        loading: true,
        onOk: () => {
          that.$axios
            .post("/api.php?c=crontab&a=DeleteCrontab", data)
            .then(function (response) {
              var result = response.data;
              if (result.status == 1) {
                that.$Message.success(result.message);
                that.$Modal.remove();
                that.GetCrontabList();
              } else {
                that.$Message.error(result.message);
                that.$Modal.remove();
              }
            })
            .catch(function (error) {
              console.log(error);
            });
        },
        onCancel: () => {},
      });
    },
    AddCrontab() {
      var that = this;
      var data = {
        backup_type: that.formCrontab.backup_type.select, // dump hotcopy
        cycle_type: that.formCrontab.cycle_type.select, // weekly daily hourly
        week: that.formCrontab.week.select, // 1 2 3 4 5 6 7
        hour: that.formCrontab.hours, // 0-24
        minute: that.formCrontab.minutes, // 0-60
        repository_name: that.formCrontab.repository.select, // repository_name
        crontab_count: that.formCrontab.crontab_count, // 1-1000
      };
      that.$axios
        .post("/api.php?c=crontab&a=AddCrontab", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
            that.GetCrontabList();
          } else {
            that.$Message.error(result.message);
          }
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    GetCrontabList() {
      var that = this;
      var data = {
        pageSize: that.page_size,
        currentPage: that.current,
      };
      that.$axios
        .post("/api.php?c=crontab&a=GetCrontabList", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.crontab_data = result.data;
            that.content_total = result.total;
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
        server_ip: that.formConfig.server_ip,
        server_domain: that.formConfig.server_domain,
        svn_repository_path: that.formConfig.svn_repository_path,
        backup_path: that.formConfig.backup_path,
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
        content: "此操作将会删除所有仓库和账户信息，确认继续吗？",
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
        content:
          "此操作将会清除所有普通用户的仓库权限，并尝试重新修复一些数据库表，确认继续吗？",
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
        content: "此操作将会删除所有仓库和账户信息，确认继续吗？",
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
    GetAllRepositoryList() {
      var that = this;
      var data = {};
      that.$axios
        .post("/api.php?c=svnserve&a=GetAllRepositoryList", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.formCrontab.repository.list = result.data;
          } else {
            that.$Message.error(result.message);
          }
        })
        .catch(function (error) {
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
    that.GetAllRepositoryList();
    that.GetCrontabList();
  },
};
</script>
