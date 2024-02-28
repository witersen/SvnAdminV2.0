<template>
  <div>
    <Card :bordered="false" :dis-hover="true">
      <Tabs v-model="curTabSettingAdvance" @on-click="SetCurrentAdvanceTab">
        <TabPane :label="$t('setting.serverConfig')" name="1">
          <Card :bordered="false" :dis-hover="true" style="width: 620px">
            <Alert>{{ $t('setting.serverConfigDesc') }}</Alert>
            <Form :label-width="120" label-position="left">
              <FormItem :label="$t('setting.serverNameIp')">
                <Row>
                  <Col span="12">
                    <Input
                      v-model="formDockerHost.docker_host"
                      placeholder="localhost"
                    />
                  </Col>
                  <Col span="1"> </Col>
                  <Col span="6">
                    <Tooltip
                      :transfer="true"
                      max-width="350"
                      :content="$t('setting.serverNameIpTip')"
                    >
                      <Button type="info">{{ $t('setting.info') }}</Button>
                    </Tooltip>
                  </Col>
                </Row>
              </FormItem>
              <FormItem :label="$t('setting.hostSvnPort')">
                <Row>
                  <Col span="12">
                    <InputNumber
                      :min="1"
                      v-model="formDockerHost.docker_svn_port"
                    ></InputNumber>
                  </Col>
                  <Col span="1"> </Col>
                  <Col span="6">
                    <Tooltip
                      :transfer="true"
                      max-width="350"
                      :content="$t('setting.hostPortTip')"
                    >
                      <Button type="info">{{ $t('setting.info') }}</Button>
                    </Tooltip>
                  </Col>
                </Row>
              </FormItem>
              <FormItem :label="$t('setting.hostWebPort')">
                <Row>
                  <Col span="12">
                    <InputNumber
                      :min="1"
                      v-model="formDockerHost.docker_http_port"
                    ></InputNumber>
                  </Col>
                  <Col span="1"> </Col>
                  <Col span="6">
                    <Tooltip
                      :transfer="true"
                      max-width="350"
                      :content="$t('setting.hostPortTip')"
                    >
                      <Button type="info">{{ $t('setting.info') }}</Button>
                    </Tooltip>
                  </Col>
                </Row>
              </FormItem>
              <FormItem>
                <Button
                  type="primary"
                  @click="UpdDockerHostInfo"
                  :loading="loadingUpdDockerHostInfo"
                  >{{ $t('save') }}</Button
                >
              </FormItem>
            </Form>
          </Card>
        </TabPane>
        <TabPane :label="$t('setting.pathInfo')" name="2">
          <Card :bordered="false" :dis-hover="true" style="width: 620px">
            <Alert
              >{{ $t('setting.pathInfoDesc') }}
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
        <TabPane :label="$t('setting.checkoutBySvnProtocol')" name="3">
          <Card :bordered="false" :dis-hover="true" style="width: 620px">
            <h2 style="margin: 0 0 30px 0">{{ $t('setting.protocolStatus') }}</h2>
            <Form :label-width="100" label-position="left">
              <FormItem :label="$t('setting.protocolStatus')">
                <Row>
                  <Col span="12">
                    <span style="color: #f90" v-if="!formSvn.enable"
                      >{{ $t('setting.disable') }}</span
                    >
                    <span style="color: #19be6b" v-if="formSvn.enable"
                      >{{ $t('setting.enabled') }}</span
                    >
                  </Col>
                  <Col span="1"> </Col>
                  <Col span="6">
                    <Tooltip
                      :transfer="true"
                      max-width="360"
                      :content="$t('setting.protocolStatusTip')"
                    >
                      <Button
                        :loading="loadingUpdSvnEnable"
                        type="success"
                        v-if="!formSvn.enable"
                        @click="UpdSvnEnable"
                        >{{ $t('setting.enable') }}</Button
                      >
                    </Tooltip>
                  </Col>
                </Row>
              </FormItem>
            </Form>
            <h2 style="margin: 30px 0 30px 0">{{ $t('setting.svnserveInfo') }}</h2>
            <Form :label-width="100" label-position="left">
              <FormItem label="svnserve">
                <Row>
                  <Col span="12">
                    <span>{{ formSvn.version }}</span>
                  </Col>
                  <Col span="1"> </Col>
                  <Col span="6">
                    <Tooltip
                      :transfer="true"
                      max-width="360"
                      :content="$t('setting.svnserveTip')"
                    >
                      <Button type="info">{{ $t('setting.info') }}</Button>
                    </Tooltip>
                  </Col>
                  <Col span="6"> </Col>
                </Row>
              </FormItem>
              <FormItem :label="$t('setting.runningStatus')">
                <Row>
                  <Col span="12">
                    <Tooltip
                      :transfer="true"
                      max-width="300"
                      :content="$t('setting.runningStatusTip')"
                    >
                      <span style="color: #f90" v-if="!formSvn.status"
                        >{{ $t('setting.notStart') }}</span
                      >
                      <span style="color: #19be6b" v-if="formSvn.status"
                        >{{ $t('setting.running') }}</span
                      >
                    </Tooltip>
                  </Col>
                  <Col span="1"> </Col>
                  <Col span="6">
                    <Button
                      :loading="loadingSvnserveStart"
                      type="success"
                      v-if="!formSvn.status"
                      @click="UpdSvnserveStatusStart"
                      >{{ $t('setting.start') }}</Button
                    >
                    <Button
                      :loading="loadingSvnserveStop"
                      type="warning"
                      v-if="formSvn.status"
                      @click="UpdSvnserveStatusStop"
                      >{{ $t('setting.stop') }}</Button
                    >
                  </Col>
                </Row>
              </FormItem>
              <FormItem :label="$t('setting.listeningPort')">
                <Row>
                  <Col span="12">
                    <InputNumber
                      :min="1"
                      v-model="tempSvnservePort"
                      @on-change="ChangeUpdSvnservePort"
                    ></InputNumber>
                  </Col>
                  <Col span="1"> </Col>
                  <Col span="6">
                    <Tooltip
                      :transfer="true"
                      max-width="360"
                      :content="$t('setting.listeningPortTip')"
                    >
                      <Button
                        type="warning"
                        @click="UpdSvnservePort"
                        :disabled="disableUpdSvnservePort"
                        :loading="loadingUpdSvnservePort"
                        >{{ $t('modify') }}</Button
                      >
                    </Tooltip>
                  </Col>
                </Row>
              </FormItem>
              <FormItem :label="$t('setting.listeningAddress')">
                <Row>
                  <Col span="12">
                    <Input
                      v-model="tempSvnserveHost"
                      @on-change="ChangeUpdSvnserveHost"
                      placeholder="0.0.0.0"
                    />
                  </Col>
                  <Col span="1"> </Col>
                  <Col span="6">
                    <Tooltip
                      :transfer="true"
                      max-width="350"
                      :content="$t('setting.listeningAddressTip')"
                    >
                      <Button
                        type="warning"
                        @click="UpdSvnserveHost"
                        :disabled="disableUpdSvnserveHost"
                        :loading="loadingUpdSvnserveHost"
                        >{{ $t('modify') }}</Button
                      >
                    </Tooltip>
                  </Col>
                </Row>
              </FormItem>
              <FormItem :label="$t('setting.passwordDb')">
                <Row>
                  <Col span="12">
                    <span>{{ formSvn.password_db }}</span>
                  </Col>
                  <Col span="1"> </Col>
                  <Col span="6">
                    <Tooltip
                      :transfer="true"
                      max-width="360"
                      :content="$t('setting.passwordDbTip')"
                    >
                      <Button type="info">{{ $t('setting.info') }}</Button>
                    </Tooltip>
                  </Col>
                  <Col span="6"> </Col>
                </Row>
              </FormItem>
            </Form>
            <h2 style="margin: 30px 0 30px 0">{{ $t('setting.saslauthdService') }}</h2>
            <Form :label-width="100" label-position="left">
              <FormItem label="saslauthd">
                <Row>
                  <Col span="12">
                    <span>{{ formSvn.sasl.version }}</span>
                  </Col>
                  <Col span="1"> </Col>
                  <Col span="6">
                    <Tooltip
                      :transfer="true"
                      max-width="360"
                      :content="$t('setting.saslauthdServiceTip')"
                    >
                      <Button type="info">{{ $t('setting.info') }}</Button>
                    </Tooltip>
                  </Col>
                  <Col span="6"> </Col>
                </Row>
              </FormItem>
              <FormItem :label="$t('setting.supportInfo')">
                <Row>
                  <Col span="12">
                    <span>{{ formSvn.sasl.mechanisms }}</span>
                  </Col>
                  <Col span="1"> </Col>
                  <Col span="6"> </Col>
                  <Col span="6"> </Col>
                </Row>
              </FormItem>
              <FormItem :label="$t('setting.runningStatus')">
                <Row>
                  <Col span="12">
                    <Tooltip
                      :transfer="true"
                      max-width="300"
                      :content="$t('setting.runningStatusTip')"
                    >
                      <span style="color: #f90" v-if="!formSvn.sasl.status"
                        >{{ $t('setting.notStart') }}</span
                      >
                      <span style="color: #19be6b" v-if="formSvn.sasl.status"
                        >{{ $t('setting.running') }}</span
                      >
                    </Tooltip>
                  </Col>
                  <Col span="1"> </Col>
                  <Col span="6">
                    <Button
                      :loading="loadingUpdSaslStatusStart"
                      type="success"
                      v-if="!formSvn.sasl.status"
                      @click="UpdSaslStatusStart"
                      >{{ $t('setting.start') }}</Button
                    >
                    <Button
                      :loading="loadingUpdSaslStatusStop"
                      type="warning"
                      v-if="formSvn.sasl.status"
                      @click="UpdSaslStatusStop"
                      >{{ $t('setting.stop') }}</Button
                    >
                  </Col>
                </Row>
              </FormItem>
            </Form>
            <h2 style="margin: 30px 0 30px 0">{{ $t('setting.userSource') }}</h2>
            <Form :label-width="120" label-position="left">
              <FormItem :label="$t('setting.svnUserSource')">
                <Row>
                  <Col span="12">
                    <Select
                      v-model="formSvn.user_source"
                      style="width: 200px"
                      @on-change="ChangeSvnUsersource"
                    >
                      <Option value="passwd">{{ $t('setting.passwdFile') }}</Option>
                      <Option value="ldap">ldap</Option>
                    </Select>
                  </Col>
                  <Col span="6"> </Col>
                </Row>
              </FormItem>
              <FormItem :label="$t('setting.svnGroupSource')">
                <Row>
                  <Col span="12">
                    <Select v-model="formSvn.group_source" style="width: 200px">
                      <Option value="authz">{{ $t('setting.authzFile') }}</Option>
                      <Option
                        value="ldap"
                        :disabled="formSvn.user_source == 'passwd'"
                        >ldap</Option
                      >
                    </Select>
                  </Col>
                  <Col span="1"> </Col>
                  <Col span="6">
                    <Tooltip
                      v-if="
                        formSvn.user_source == 'ldap' ||
                        formSvn.group_source == 'ldap'
                      "
                      :transfer="true"
                      max-width="250"
                      :content="$t('setting.ldapSourceTip')"
                    >
                      <Button type="info">{{ $t('setting.info') }}</Button>
                    </Tooltip>
                  </Col>
                </Row>
              </FormItem>
            </Form>
            <Form :label-width="120" label-position="left">
              <!-- LDAP 服务器 -->
              <span
                v-if="
                  formSvn.user_source == 'ldap' ||
                  formSvn.group_source == 'ldap'
                "
              >
                <Divider>{{ $t('setting.ldapServer') }}</Divider>
                <FormItem label="$t('setting.ldapServerAddress')">
                  <Row>
                    <Col span="12">
                      <Input
                        v-model="formSvn.ldap.ldap_host"
                        placeholder="ldap://127.0.0.1/"
                      ></Input>
                    </Col>
                  </Row>
                </FormItem>
                <FormItem :label="$t('setting.ldapPort')">
                  <Row>
                    <Col span="12">
                      <InputNumber
                        :min="1"
                        v-model="formSvn.ldap.ldap_port"
                      ></InputNumber>
                    </Col>
                  </Row>
                </FormItem>
                <FormItem :label="$t('setting.ldapVersion')">
                  <Row>
                    <Col span="12">
                      <InputNumber
                        :min="2"
                        :max="3"
                        v-model="formSvn.ldap.ldap_version"
                      ></InputNumber>
                    </Col>
                  </Row>
                </FormItem>
                <FormItem label="Bind DN">
                  <Row>
                    <Col span="12">
                      <Input v-model="formSvn.ldap.ldap_bind_dn"></Input>
                    </Col>
                    <Col span="1"> </Col>
                    <Col span="6">
                      <Tooltip
                        :transfer="true"
                        max-width="250"
                        :content="$t('setting.ldapBindDnTip')"
                      >
                        <Button type="info">{{ $t('setting.info') }}</Button>
                      </Tooltip>
                    </Col>
                  </Row>
                </FormItem>
                <FormItem label="Bind password">
                  <Row>
                    <Col span="12">
                      <Input
                        v-model="formSvn.ldap.ldap_bind_password"
                        type="password"
                        password
                      ></Input>
                    </Col>
                    <Col span="1"> </Col>
                    <Col span="6">
                      <Button
                        type="success"
                        @click="LdapTest('svn', 'connection')"
                        :loading="loadingLdapTestConnection"
                        >{{ $t('setting.ldapTest') }}</Button
                      >
                    </Col>
                  </Row>
                </FormItem>
              </span>
              <!-- LDAP 用户 -->
              <span v-if="formSvn.user_source == 'ldap'">
                <Divider>{{ $t('setting.ldapUser') }}</Divider>
                <FormItem label="Base DN">
                  <Row>
                    <Col span="12">
                      <Input v-model="formSvn.ldap.user_base_dn"></Input>
                    </Col>
                    <Col span="1"> </Col>
                    <Col span="6">
                      <Tooltip
                        :transfer="true"
                        max-width="250"
                        :content="$t('setting.ldapBaseDnTip')"
                      >
                        <Button type="info">{{ $t('setting.info') }}</Button>
                      </Tooltip>
                    </Col>
                  </Row>
                </FormItem>
                <FormItem label="Search filter">
                  <Row>
                    <Col span="12">
                      <Input v-model="formSvn.ldap.user_search_filter"></Input>
                    </Col>
                    <Col span="1"> </Col>
                    <Col span="6">
                      <Tooltip
                        :transfer="true"
                        max-width="250"
                        content="如:  (&(objectClass=person)(objectClass=user))"
                      >
                        <Button type="info">{{ $t('setting.info') }}</Button>
                      </Tooltip>
                    </Col>
                  </Row>
                </FormItem>
                <FormItem label="Attributes">
                  <Row>
                    <Col span="12">
                      <Input v-model="formSvn.ldap.user_attributes"></Input>
                    </Col>
                    <Col span="1"> </Col>
                    <Col span="8">
                      <Row>
                        <Col span="11">
                          <Tooltip
                            :transfer="true"
                            max-width="250"
                            :content="$t('setting.ldapAttributesTip')"
                          >
                            <Button type="info">{{ $t('setting.info') }}</Button>
                          </Tooltip>
                        </Col>
                        <Col span="2"> </Col>
                        <Col span="11">
                          <Button
                            type="success"
                            @click="LdapTest('svn', 'user')"
                            :loading="loadingLdapTestUser"
                            >{{ $t('setting.ldapTest') }}</Button
                          >
                        </Col>
                      </Row>
                    </Col>
                  </Row>
                </FormItem>
              </span>
              <!-- LDAP 分组 -->
              <span v-if="formSvn.group_source == 'ldap'">
                <Divider>{{ $t('setting.ldapGroup') }}</Divider>
                <FormItem label="Base DN">
                  <Row>
                    <Col span="12">
                      <Input v-model="formSvn.ldap.group_base_dn"></Input>
                    </Col>
                    <Col span="1"> </Col>
                    <Col span="6">
                      <Tooltip
                        :transfer="true"
                        max-width="250"
                        :content="$t('setting.ldapGroupBaseDnTip')"
                      >
                        <Button type="info">{{ $t('setting.info') }}</Button>
                      </Tooltip>
                    </Col>
                  </Row>
                </FormItem>
                <FormItem label="Search filter">
                  <Row>
                    <Col span="12">
                      <Input v-model="formSvn.ldap.group_search_filter"></Input>
                    </Col>
                    <Col span="1"> </Col>
                    <Col span="6">
                      <Tooltip
                        :transfer="true"
                        max-width="250"
                        :content="$t('setting.ldapGroupSearchFilterTip')"
                      >
                        <Button type="info">{{ $t('setting.info') }}</Button>
                      </Tooltip>
                    </Col>
                  </Row>
                </FormItem>
                <FormItem label="Attributes">
                  <Row>
                    <Col span="12">
                      <Input v-model="formSvn.ldap.group_attributes"></Input>
                    </Col>
                    <Col span="1"> </Col>
                    <Col span="6">
                      <Tooltip
                        :transfer="true"
                        max-width="250"
                        :content="$t('setting.ldapGroupAttributesTip')"
                      >
                        <Button type="info">{{ $t('setting.info') }}</Button>
                      </Tooltip>
                    </Col>
                  </Row>
                </FormItem>
                <FormItem label="Groups to user attribute">
                  <Row>
                    <Col span="12">
                      <Input
                        v-model="formSvn.ldap.groups_to_user_attribute"
                      ></Input>
                    </Col>
                    <Col span="1"> </Col>
                    <Col span="6">
                      <Tooltip
                        :transfer="true"
                        max-width="250"
                        :content="$t('setting.ldapGroupsToUserAttributeTip')"
                      >
                        <Button type="info">{{ $t('setting.info') }}</Button>
                      </Tooltip>
                    </Col>
                  </Row>
                </FormItem>
                <FormItem label="Groups to user attribute value">
                  <Row>
                    <Col span="12">
                      <Input
                        v-model="formSvn.ldap.groups_to_user_attribute_value"
                      ></Input>
                    </Col>
                    <Col span="1"> </Col>
                    <Col span="8">
                      <Row>
                        <Col span="11">
                          <Tooltip
                            :transfer="true"
                            max-width="250"
                            :content="$t('setting.ldapGroupsToUserAttributeValueTip')"
                          >
                            <Button type="info">{{ $t('setting.info') }}</Button>
                          </Tooltip>
                        </Col>
                        <Col span="2"> </Col>
                        <Col span="11">
                          <Button
                            type="success"
                            @click="LdapTest('svn', 'group')"
                            :loading="loadingLdapTestGroup"
                            >{{ $t('setting.ldapTest') }}</Button
                          >
                        </Col>
                      </Row>
                    </Col>
                  </Row>
                </FormItem>
              </span>
              <!-- 保存 -->
              <FormItem>
                <Button
                  type="primary"
                  @click="UpdSvnUsersource"
                  :loading="loadingUpdSvnUsersource"
                  >{{ $t('save') }}</Button
                >
              </FormItem>
            </Form>
          </Card>
        </TabPane>
        <TabPane :label="$t('setting.checkoutByHttpProtocol')" name="4">
          <Card :bordered="false" :dis-hover="true" style="width: 620px">
            <h2 style="margin: 0 0 30px 0">{{ $t('setting.protocolStatus') }}</h2>
            <Form :label-width="100" label-position="left">
              <FormItem :label="$t('setting.protocolStatus')">
                <Row>
                  <Col span="12">
                    <span style="color: #f90" v-if="!formHttp.enable"
                      >{{ $t('setting.disable') }}</span
                    >
                    <span style="color: #19be6b" v-if="formHttp.enable"
                      >{{ $t('setting.enabled') }}</span
                    >
                  </Col>
                  <Col span="1"> </Col>
                  <Col span="6">
                    <Tooltip
                      :transfer="true"
                      max-width="360"
                      :content="$t('setting.httpProtocolTip')"
                    >
                      <Button
                        :loading="loadingUpdSubversionEnable"
                        type="success"
                        v-if="!formHttp.enable"
                        @click="UpdSubversionEnable"
                        >{{ $t('setting.enable') }}</Button
                      >
                    </Tooltip>
                  </Col>
                </Row>
              </FormItem>
            </Form>
            <h2 style="margin: 30px 0 30px 0">{{ $t('setting.apacheServiceInfo') }}</h2>
            <Form :label-width="100" label-position="left">
              <FormItem label="apache">
                <Row>
                  <Col span="12">
                    <span>{{ formHttp.version }}</span>
                  </Col>
                  <Col span="1"> </Col>
                  <Col span="6">
                    <Tooltip
                      :transfer="true"
                      max-width="360"
                      :content="$t('setting.apacheServiceInfoTip')"
                    >
                      <Button type="info">{{ $t('setting.info') }}</Button>
                    </Tooltip>
                  </Col>
                  <Col span="6"> </Col>
                </Row>
              </FormItem>
              <FormItem :label="$t('setting.modulesInfo')">
                <Row>
                  <Col span="12">
                    <span>{{ formHttp.modules }}</span>
                  </Col>
                  <Col span="1"> </Col>
                  <Col span="6"> </Col>
                  <Col span="6"> </Col>
                </Row>
              </FormItem>
              <FormItem :label="$t('setting.modulesPathInfo')">
                <Row>
                  <Col span="12">
                    <span>{{ formHttp.modules_path }}</span>
                  </Col>
                  <Col span="1"> </Col>
                  <Col span="6"> </Col>
                  <Col span="6"> </Col>
                </Row>
              </FormItem>
              <FormItem :label="$t('setting.listeningPort')">
                <Row>
                  <Col span="12">
                    <InputNumber
                      :min="1"
                      v-model="tempHttpPort"
                      @on-change="ChangeUpdHttpPort"
                    ></InputNumber>
                  </Col>
                  <Col span="1"> </Col>
                  <Col span="6">
                    <Tooltip
                      :transfer="true"
                      max-width="350"
                      :content="$t('setting.apacheListeningPortTip')"
                    >
                      <Button
                        type="warning"
                        @click="UpdHttpPort"
                        :disabled="disableUpdHttpPort"
                        :loading="loadingUpdHttpPort"
                        >{{ $t('modify') }}</Button
                      >
                    </Tooltip>
                  </Col>
                </Row>
              </FormItem>
              <FormItem :label="$t('setting.httpRepoPrefix')">
                <Row>
                  <Col span="12">
                    <Input
                      v-model="tempHttpPrefix"
                      @on-change="ChangeUpdHttpPrefix"
                      placeholder="/svn"
                    />
                  </Col>
                  <Col span="1"> </Col>
                  <Col span="6">
                    <Tooltip
                      :transfer="true"
                      max-width="350"
                      :content="$t('setting.httpRepoPrefixTip')"
                    >
                      <Button
                        type="warning"
                        @click="UpdHttpPrefix"
                        :disabled="disableUpdHttpPrefix"
                        :loading="loadingUpdHttpPrefix"
                        >{{ $t('modify') }}</Button
                      >
                    </Tooltip>
                  </Col>
                </Row>
              </FormItem>
              <FormItem :label="$t('setting.passwordDb')">
                <Row>
                  <Col span="12">
                    <span>{{ formHttp.password_db }}</span>
                  </Col>
                  <Col span="1"> </Col>
                  <Col span="6">
                    <Tooltip
                      :transfer="true"
                      max-width="360"
                      :content="$t('setting.httpPasswordDbTip')"
                    >
                      <Button type="info">{{ $t('setting.info') }}</Button>
                    </Tooltip>
                  </Col>
                  <Col span="6"> </Col>
                </Row>
              </FormItem>
            </Form>
            <h2 style="margin: 30px 0 30px 0">{{ $t('setting.userSource') }}</h2>
            <Form :label-width="120" label-position="left">
              <FormItem :label="$t('setting.svnUserSource')">
                <Row>
                  <Col span="12">
                    <Select
                      v-model="formHttp.user_source"
                      style="width: 200px"
                      @on-change="ChangeHttpUsersource"
                    >
                      <Option value="httpPasswd">{{ $t('setting.httpPasswdFile') }}</Option>
                      <Option value="ldap">ldap</Option>
                    </Select>
                  </Col>
                  <Col span="6"> </Col>
                </Row>
              </FormItem>
              <FormItem :label="$t('setting.svnGroupSource')">
                <Row>
                  <Col span="12">
                    <Select
                      v-model="formHttp.group_source"
                      style="width: 200px"
                    >
                      <Option value="authz">{{ $t('setting.authzFile') }}</Option>
                      <Option
                        value="ldap"
                        :disabled="formHttp.user_source == 'httpPasswd'"
                        >ldap</Option
                      >
                    </Select>
                  </Col>
                  <Col span="1"> </Col>
                  <Col span="6">
                    <Tooltip
                      v-if="
                        formHttp.user_source == 'ldap' ||
                        formHttp.group_source == 'ldap'
                      "
                      :transfer="true"
                      max-width="250"
                      :content="$t('setting.ldapSourceTip')"
                    >
                      <Button type="info">{{ $t('setting.info') }}</Button>
                    </Tooltip>
                  </Col>
                </Row>
              </FormItem>
            </Form>
            <Form :label-width="120" label-position="left">
              <!-- LDAP 服务器 -->
              <span
                v-if="
                  formSvn.user_source == 'ldap' ||
                  formSvn.group_source == 'ldap'
                "
              >
                <Divider>{{ $t('setting.ldapServer') }}</Divider>
                <FormItem label="$t('setting.ldapServerAddress')">
                  <Row>
                    <Col span="12">
                      <Input
                        v-model="formSvn.ldap.ldap_host"
                        placeholder="ldap://127.0.0.1/"
                      ></Input>
                    </Col>
                  </Row>
                </FormItem>
                <FormItem :label="$t('setting.ldapPort')">
                  <Row>
                    <Col span="12">
                      <InputNumber
                        :min="1"
                        v-model="formSvn.ldap.ldap_port"
                      ></InputNumber>
                    </Col>
                  </Row>
                </FormItem>
                <FormItem :label="$t('setting.ldapVersion')">
                  <Row>
                    <Col span="12">
                      <InputNumber
                        :min="2"
                        :max="3"
                        v-model="formSvn.ldap.ldap_version"
                      ></InputNumber>
                    </Col>
                  </Row>
                </FormItem>
                <FormItem label="Bind DN">
                  <Row>
                    <Col span="12">
                      <Input v-model="formSvn.ldap.ldap_bind_dn"></Input>
                    </Col>
                    <Col span="1"> </Col>
                    <Col span="6">
                      <Tooltip
                        :transfer="true"
                        max-width="250"
                        :content="$t('setting.ldapBindDnTip')"
                      >
                        <Button type="info">{{ $t('setting.info') }}</Button>
                      </Tooltip>
                    </Col>
                  </Row>
                </FormItem>
                <FormItem label="Bind password">
                  <Row>
                    <Col span="12">
                      <Input
                        v-model="formSvn.ldap.ldap_bind_password"
                        type="password"
                        password
                      ></Input>
                    </Col>
                    <Col span="1"> </Col>
                    <Col span="6">
                      <Button
                        type="success"
                        @click="LdapTest('svn', 'connection')"
                        :loading="loadingLdapTestConnection"
                        >{{ $t('setting.ldapTest') }}</Button
                      >
                    </Col>
                  </Row>
                </FormItem>
              </span>
              <!-- LDAP 用户 -->
              <span v-if="formSvn.user_source == 'ldap'">
                <Divider>{{ $t('setting.ldapUser') }}</Divider>
                <FormItem label="Base DN">
                  <Row>
                    <Col span="12">
                      <Input v-model="formSvn.ldap.user_base_dn"></Input>
                    </Col>
                    <Col span="1"> </Col>
                    <Col span="6">
                      <Tooltip
                        :transfer="true"
                        max-width="250"
                        :content="$t('setting.ldapBaseDnTip')"
                      >
                        <Button type="info">{{ $t('setting.info') }}</Button>
                      </Tooltip>
                    </Col>
                  </Row>
                </FormItem>
                <FormItem label="Search filter">
                  <Row>
                    <Col span="12">
                      <Input v-model="formSvn.ldap.user_search_filter"></Input>
                    </Col>
                    <Col span="1"> </Col>
                    <Col span="6">
                      <Tooltip
                        :transfer="true"
                        max-width="250"
                        content="如:  (&(objectClass=person)(objectClass=user))"
                      >
                        <Button type="info">{{ $t('setting.info') }}</Button>
                      </Tooltip>
                    </Col>
                  </Row>
                </FormItem>
                <FormItem label="Attributes">
                  <Row>
                    <Col span="12">
                      <Input v-model="formSvn.ldap.user_attributes"></Input>
                    </Col>
                    <Col span="1"> </Col>
                    <Col span="8">
                      <Row>
                        <Col span="11">
                          <Tooltip
                            :transfer="true"
                            max-width="250"
                            :content="$t('setting.ldapAttributesTip')"
                          >
                            <Button type="info">{{ $t('setting.info') }}</Button>
                          </Tooltip>
                        </Col>
                        <Col span="2"> </Col>
                        <Col span="11">
                          <Button
                            type="success"
                            @click="LdapTest('svn', 'user')"
                            :loading="loadingLdapTestUser"
                            >{{ $t('setting.ldapTest') }}</Button
                          >
                        </Col>
                      </Row>
                    </Col>
                  </Row>
                </FormItem>
              </span>
              <!-- LDAP 分组 -->
              <span v-if="formSvn.group_source == 'ldap'">
                <Divider>{{ $t('setting.ldapGroup') }}</Divider>
                <FormItem label="Base DN">
                  <Row>
                    <Col span="12">
                      <Input v-model="formSvn.ldap.group_base_dn"></Input>
                    </Col>
                    <Col span="1"> </Col>
                    <Col span="6">
                      <Tooltip
                        :transfer="true"
                        max-width="250"
                        :content="$t('setting.ldapGroupBaseDnTip')"
                      >
                        <Button type="info">{{ $t('setting.info') }}</Button>
                      </Tooltip>
                    </Col>
                  </Row>
                </FormItem>
                <FormItem label="Search filter">
                  <Row>
                    <Col span="12">
                      <Input v-model="formSvn.ldap.group_search_filter"></Input>
                    </Col>
                    <Col span="1"> </Col>
                    <Col span="6">
                      <Tooltip
                        :transfer="true"
                        max-width="250"
                        :content="$t('setting.ldapGroupSearchFilterTip')"
                      >
                        <Button type="info">{{ $t('setting.info') }}</Button>
                      </Tooltip>
                    </Col>
                  </Row>
                </FormItem>
                <FormItem label="Attributes">
                  <Row>
                    <Col span="12">
                      <Input v-model="formSvn.ldap.group_attributes"></Input>
                    </Col>
                    <Col span="1"> </Col>
                    <Col span="6">
                      <Tooltip
                        :transfer="true"
                        max-width="250"
                        :content="$t('setting.ldapGroupAttributesTip')"
                      >
                        <Button type="info">{{ $t('setting.info') }}</Button>
                      </Tooltip>
                    </Col>
                  </Row>
                </FormItem>
                <FormItem label="Groups to user attribute">
                  <Row>
                    <Col span="12">
                      <Input
                        v-model="formSvn.ldap.groups_to_user_attribute"
                      ></Input>
                    </Col>
                    <Col span="1"> </Col>
                    <Col span="6">
                      <Tooltip
                        :transfer="true"
                        max-width="250"
                        :content="$t('setting.ldapGroupsToUserAttributeTip')"
                      >
                        <Button type="info">{{ $t('setting.info') }}</Button>
                      </Tooltip>
                    </Col>
                  </Row>
                </FormItem>
                <FormItem label="Groups to user attribute value">
                  <Row>
                    <Col span="12">
                      <Input
                        v-model="formSvn.ldap.groups_to_user_attribute_value"
                      ></Input>
                    </Col>
                    <Col span="1"> </Col>
                    <Col span="8">
                      <Row>
                        <Col span="11">
                          <Tooltip
                            :transfer="true"
                            max-width="250"
                            :content="$t('setting.ldapGroupsToUserAttributeValueTip')"
                          >
                            <Button type="info">{{ $t('setting.info') }}</Button>
                          </Tooltip>
                        </Col>
                        <Col span="2"> </Col>
                        <Col span="11">
                          <Button
                            type="success"
                            @click="LdapTest('svn', 'group')"
                            :loading="loadingLdapTestGroup"
                            >{{ $t('setting.ldapTest') }}</Button
                          >
                        </Col>
                      </Row>
                    </Col>
                  </Row>
                </FormItem>
              </span>
              <!-- 保存 -->
              <FormItem>
                <Button
                  type="primary"
                  @click="UpdSvnUsersource"
                  :loading="loadingUpdSvnUsersource"
                  >{{ $t('save') }}</Button
                >
              </FormItem>
            </Form>
          </Card>
        </TabPane>
        <TabPane :label="$t('setting.emailSetting')" name="5">
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
                      <Button type="info">{{ $t('setting.info') }}</Button>
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
                      <Button type="info">{{ $t('setting.info') }}</Button>
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
                      <Button type="info">{{ $t('setting.info') }}</Button>
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
                      <Button type="info">{{ $t('setting.info') }}</Button>
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
                        @click="SendMailTest"
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
                  @click="UpdMailInfo"
                  :loading="loadingEditEmail"
                  >保存</Button
                >
              </FormItem>
            </Form>
          </Card>
        </TabPane>
        <TabPane label="消息推送" name="6">
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
                  @click="UpdPushInfo"
                  >保存</Button
                >
              </FormItem>
            </Form>
          </Card>
        </TabPane>
        <TabPane label="安全配置" name="7">
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
                  @click="UpdSafeInfo"
                  >保存</Button
                >
              </FormItem>
            </Form>
          </Card>
        </TabPane>
        <TabPane :label="labelUpd" name="8">
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
                <Button
                  type="primary"
                  :loading="loadingCheckUpdate"
                  @click="CheckUpdate()"
                  >检测更新</Button
                >
              </FormItem>
            </Form>
          </Card>
        </TabPane>
      </Tabs>
    </Card>
    <!-- 对话框-更新信息 -->
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
    <!-- 对话框-添加收件人邮箱 -->
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
    <!-- 对话框-ldap用户/分组过滤结果 -->
    <Modal
      v-model="modalLdapUsersGroups"
      :draggable="true"
      :title="titleLdapUsersGroups"
    >
      <Input
        v-model="tempLdapUsersGroups"
        readonly
        :rows="15"
        show-word-limit
        type="textarea"
      />
      <div slot="footer">
        <Button type="primary" ghost @click="modalLdapUsersGroups = false"
          >{{ $t('cancel') }}</Button
        >
      </div>
    </Modal>
  </div>
</template>

<script>
import i18n from "@/i18n";
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
      tempSvnservePort: 0,
      //svnserve绑定主机名
      tempSvnserveHost: "",
      //管理系统主机名称
      tempHost: "",
      //apache端口
      tempPort: 0,
      //apache访问仓库前缀
      tempHttpPrefix: "",
      //测试邮箱
      tempTestEmail: "",
      //添加收件人邮箱
      tempToEmail: "",
      //ldap用户/分组过滤结果
      tempLdapUsersGroups: "",
      //apache显示端口
      tempHttpPort: 0,

      /**
       * 定时器
       */
      timer: null,

      /**
       * 控制修改状态
       */
      //修改svnserve监听端口
      disableUpdSvnservePort: true,
      //修改svnserve监听地址
      disableUpdSvnserveHost: true,
      //修改主机名
      disableUpdHost: true,
      //修改端口
      disableUpdPort: true,
      //修改apache访问仓库前缀
      disableUpdHttpPrefix: true,
      //修改http显示端口
      disableUpdHttpPort: false,

      /**
       * 标题
       */
      titleLdapUsersGroups: "",

      /**
       * tab
       */
      curTabSettingAdvance: "1",

      /**
       * 版本信息
       */
      version: {
        current_verson: "2.5.7",
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
      loadingUpdSvnserveHost: false,
      //更换绑定主机
      loadingUpdSvnservePort: false,
      //修改主机地址
      loadingUpdDockerHostInfo: false,
      //修改apache端口
      loadingUpdPort: false,
      //修改apache访问仓库前缀
      loadingUpdHttpPrefix: false,
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
      //测试连接ldap服务器
      loadingLdapTestConnection: false,
      loadingLdapTestUser: false,
      loadingLdapTestGroup: false,
      //更新ldap配置
      loadingUpdLdapInfo: false,
      //sasl
      loadingUpdSaslStatusStart: false,
      loadingUpdSaslStatusStop: false,
      //启用 svn 协议检出
      loadingUpdSvnEnable: false,
      //启用 http 协议检出
      loadingUpdSubversionEnable: false,

      //更新svn用户源
      loadingUpdSvnUsersource: false,
      //更新http用户源
      loadingUpdHttpUsersource: false,

      //修改http显示端口
      loadingUpdHttpPort: false,

      /**
       * svnserve信息
       */
      formSvn: {
        version: "",
        status: false,
        listen_port: "",
        listen_host: "",
        svnserve_log: "",
        password_db: "",
        enable: false,

        //数据源
        user_source: "",
        group_source: "",

        //sasl
        sasl: {
          version: "",
          mechanisms: "",
          status: false,
        },

        ldap: {
          //ldap服务器
          ldap_host: "",
          ldap_port: 389,
          ldap_version: 3,
          ldap_bind_dn: "",
          ldap_bind_password: "",

          //用户相关
          user_base_dn: "",
          user_search_filter: "",
          user_attributes: "",

          //分组相关
          group_base_dn: "",
          group_search_filter: "",
          group_attributes: "",
          groups_to_user_attribute: "",
          groups_to_user_attribute_value: "",
        },
      },

      //主机信息
      formDockerHost: {
        docker_host: "",
        docker_svn_port: 0,
        docker_http_port: 0,
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
      //ldap用户/分组过滤结果
      modalLdapUsersGroups: false,

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
      //sasl
      formSasl: {
        version: "",
        mechanisms: "",
        status: false,
      },
      //apache
      formHttp: {
        enable: false,

        version: "",
        modules: "",
        port: 0,
        prefix: "",
        modules_path: "",
        password_db: "",

        //数据源
        user_source: "",
        group_source: "",

        ldap: {
          //ldap服务器
          ldap_host: "",
          ldap_port: 389,
          ldap_version: 3,
          ldap_bind_dn: "",
          ldap_bind_password: "",

          //用户相关
          user_base_dn: "",
          user_search_filter: "",
          user_attributes: "",

          //分组相关
          group_base_dn: "",
          group_search_filter: "",
          group_attributes: "",
          groups_to_user_attribute: "",
          groups_to_user_attribute_value: "",
        },
      },
    };
  },
  computed: {},
  created() {},
  mounted() {
    if (!sessionStorage.curTabSettingAdvance) {
      sessionStorage.setItem("curTabSettingAdvance", "1");
    } else {
      this.curTabSettingAdvance = sessionStorage.curTabSettingAdvance;
    }
    this.GetDcokerHostInfo();
    this.GetSvnInfo();
    this.GetApacheInfo();
    this.GetDirInfo();
    this.GetMailInfo();
    this.GetMailPushInfo();
    this.GetSafeInfo();
  },
  methods: {
    /**
     * 设置选中的标签
     */
    SetCurrentAdvanceTab(name) {
      sessionStorage.setItem("curTabSettingAdvance", name);
      this.curTabSettingAdvance = name;
    },
    /**
     * 获取 svnserve 的详细信息
     */
    GetSvnInfo() {
      var that = this;
      var data = {};
      that.$axios
        .post("api.php?c=Setting&a=GetSvnInfo&t=web", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.formSvn = result.data;
            //为临时变量赋值
            that.tempSvnservePort = result.data.listen_port;
            that.tempSvnserveHost = result.data.listen_host;
            //初始化禁用按钮
            that.disableUpdSvnservePort = true;
            that.disableUpdSvnserveHost = true;
            if (that.timer) {
              that.$Message.success(result.message);
              clearInterval(that.timer);
            }
          } else {
            that.$Message.error({ content: result.message, duration: 2 });
          }
        })
        .catch(function (error) {
          console.log(error);
          that.$Message.error(i18n.t('errors.contactAdmin'));
        });
    },
    ChangeUpdSvnservePort(value) {
      this.disableUpdSvnservePort =
        this.tempSvnservePort == this.formSvn.listen_port;
    },
    ChangeUpdSvnserveHost(event) {
      this.disableUpdSvnserveHost =
        this.tempSvnserveHost == this.formSvn.listen_host;
    },
    ChangeUpdHttpPort(value) {
      this.disableUpdHttpPort = this.tempHttpPort == this.formHttp.port;
    },
    ChangeUpdHttpPrefix(event) {
      this.disableUpdHttpPrefix = this.tempHttpPrefix == this.formHttp.prefix;
    },
    /**
     * 获取邮件配置信息
     */
    GetMailInfo() {
      var that = this;
      var data = {};
      that.$axios
        .post("api.php?c=Setting&a=GetMailInfo&t=web", data)
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
          that.$Message.error(i18n.t('errors.contactAdmin'));
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
    UpdMailInfo() {
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
        .post("api.php?c=Setting&a=UpdMailInfo&t=web", data)
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
          that.$Message.error(i18n.t('errors.contactAdmin'));
        });
    },
    /**
     * 发送测试邮件
     */
    SendMailTest() {
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
        .post("api.php?c=Setting&a=SendMailTest&t=web", data)
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
          that.$Message.error(i18n.t('errors.contactAdmin'));
        });
    },
    /**
     * 获取配置文件信息
     */
    GetDirInfo() {
      var that = this;
      var data = {};
      that.$axios
        .post("api.php?c=Setting&a=GetDirInfo&t=web", data)
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
          that.$Message.error(i18n.t('errors.contactAdmin'));
        });
    },
    /**
     * 获取消息推送配置
     */
    GetMailPushInfo() {
      var that = this;
      var data = {};
      that.$axios
        .post("api.php?c=Setting&a=GetMailPushInfo&t=web", data)
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
          that.$Message.error(i18n.t('errors.contactAdmin'));
        });
    },
    /**
     * 获取安全配置选项
     */
    GetSafeInfo() {
      var that = this;
      var data = {};
      that.$axios
        .post("api.php?c=Setting&a=GetSafeInfo&t=web", data)
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
          that.$Message.error(i18n.t('errors.contactAdmin'));
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
    UpdPushInfo() {
      var that = this;
      that.loadingEditPush = true;
      var data = {
        listPush: that.listPush,
      };
      that.$axios
        .post("api.php?c=Setting&a=UpdPushInfo&t=web", data)
        .then(function (response) {
          that.loadingEditPush = false;
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
            that.GetMailPushInfo();
          } else {
            that.$Message.error({ content: result.message, duration: 2 });
          }
        })
        .catch(function (error) {
          that.loadingEditPush = false;
          console.log(error);
          that.$Message.error(i18n.t('errors.contactAdmin'));
        });
    },
    /**
     * 保存安全配置选项
     */
    UpdSafeInfo() {
      var that = this;
      that.loadingEditSafe = true;
      var data = {
        listSafe: that.listSafe,
      };
      that.$axios
        .post("api.php?c=Setting&a=UpdSafeInfo&t=web", data)
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
          that.$Message.error(i18n.t('errors.contactAdmin'));
        });
    },
    /**
     * 启动SVN
     */
    UpdSvnserveStatusStart() {
      var that = this;
      that.$Modal.confirm({
        title: "以daomen方式启动svnserve服务",
        content: "确定要启动svnserve服务吗吗？",
        onOk: () => {
          that.loadingSvnserveStart = true;
          var data = {};
          that.$axios
            .post("api.php?c=Setting&a=UpdSvnserveStatusStart&t=web", data)
            .then(function (response) {
              that.loadingSvnserveStart = false;
              var result = response.data;
              if (result.status == 1) {
                that.$Message.success(result.message);
                that.GetSvnInfo();
              } else {
                that.$Message.error({ content: result.message, duration: 2 });
              }
            })
            .catch(function (error) {
              that.loadingSvnserveStart = false;
              console.log(error);
              that.$Message.error(i18n.t('errors.contactAdmin'));
            });
        },
      });
    },
    /**
     * 停止SVN
     */
    UpdSvnserveStatusStop() {
      var that = this;
      that.$Modal.confirm({
        title: "停止svnserve服务",
        content: "确定要停止svnserve服务吗？",
        onOk: () => {
          that.loadingSvnserveStop = true;
          var data = {};
          that.$axios
            .post("api.php?c=Setting&a=UpdSvnserveStatusStop&t=web", data)
            .then(function (response) {
              that.loadingSvnserveStop = false;
              var result = response.data;
              if (result.status == 1) {
                that.$Message.success(result.message);
                that.GetSvnInfo();
              } else {
                that.$Message.error({ content: result.message, duration: 2 });
              }
            })
            .catch(function (error) {
              that.loadingSvnserveStop = false;
              console.log(error);
              that.$Message.error(i18n.t('errors.contactAdmin'));
            });
        },
      });
    },
    /**
     * 修改 svnserve 的绑定端口
     */
    UpdSvnservePort() {
      var that = this;
      that.$Modal.confirm({
        title: "更换svnserve服务绑定端口",
        content:
          "确定要更换svnserve服务绑定端口吗？此操作会使svnserve服务停止并重新启动！",
        onOk: () => {
          that.loadingUpdSvnservePort = true;
          var data = {
            listen_port: that.tempSvnservePort,
          };
          that.$axios
            .post("api.php?c=Setting&a=UpdSvnservePort&t=web", data)
            .then(function (response) {
              that.loadingUpdSvnservePort = false;
              var result = response.data;
              if (result.status == 1) {
                that.$Message.success(result.message);
                that.GetSvnInfo();
              } else {
                that.GetSvnInfo();
                that.$Message.error({ content: result.message, duration: 2 });
              }
            })
            .catch(function (error) {
              that.loadingUpdSvnservePort = false;
              console.log(error);
              that.$Message.error(i18n.t('errors.contactAdmin'));
            });
        },
      });
    },
    /**
     * 修改 svnserve 的绑定主机
     */
    UpdSvnserveHost() {
      var that = this;
      that.$Modal.confirm({
        title: "更换svnserve服务绑定主机",
        content:
          "确定要更换svnserve服务绑定主机吗？此操作会使svnserve服务停止并重新启动！",
        onOk: () => {
          that.loadingUpdSvnserveHost = true;
          var data = {
            listen_host: that.tempSvnserveHost,
          };
          that.$axios
            .post("api.php?c=Setting&a=UpdSvnserveHost&t=web", data)
            .then(function (response) {
              that.loadingUpdSvnserveHost = false;
              var result = response.data;
              if (result.status == 1) {
                that.$Message.success(result.message);
                that.GetSvnInfo();
              } else {
                that.GetSvnInfo();
                that.$Message.error({ content: result.message, duration: 2 });
              }
            })
            .catch(function (error) {
              that.loadingUpdSvnserveHost = false;
              console.log(error);
              that.$Message.error(i18n.t('errors.contactAdmin'));
            });
        },
      });
    },
    //获取主机配置
    GetDcokerHostInfo() {
      var that = this;
      var data = {};
      that.$axios
        .post("api.php?c=Setting&a=GetDcokerHostInfo&t=web", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.formDockerHost = result.data;
          } else {
            that.$Message.error({ content: result.message, duration: 2 });
          }
        })
        .catch(function (error) {
          console.log(error);
          that.$Message.error(i18n.t('errors.contactAdmin'));
        });
    },
    //修改主机配置
    UpdDockerHostInfo() {
      var that = this;
      that.loadingUpdDockerHostInfo = true;
      var data = {
        dockerHost: that.formDockerHost,
      };
      that.$axios
        .post("api.php?c=Setting&a=UpdDockerHostInfo&t=web", data)
        .then(function (response) {
          that.loadingUpdDockerHostInfo = false;
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
            that.GetDcokerHostInfo();
          } else {
            that.$Message.error({ content: result.message, duration: 2 });
          }
        })
        .catch(function (error) {
          that.loadingUpdDockerHostInfo = false;
          console.log(error);
          that.$Message.error(i18n.t('errors.contactAdmin'));
        });
    },
    /**
     * 检测更新
     */
    CheckUpdate() {
      var that = this;
      that.loadingCheckUpdate = true;
      var data = {};
      that.$axios
        .post("api.php?c=Setting&a=CheckUpdate&t=web", data)
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
    /**
     * 测试连接ldap服务器
     */
    LdapTest(source, type) {
      var that = this;
      if (type == "connection") {
        that.loadingLdapTestConnection = true;
      } else if (type == "user") {
        that.loadingLdapTestUser = true;
      } else if (type == "group") {
        that.loadingLdapTestGroup = true;
      }
      var data = {
        type: type,
        data_source: source == "svn" ? that.formSvn.ldap : that.formHttp.ldap,
      };
      that.$axios
        .post("api.php?c=Setting&a=LdapTest&t=web", data)
        .then(function (response) {
          var result = response.data;
          if (type == "connection") {
            that.loadingLdapTestConnection = false;
          } else if (type == "user") {
            that.loadingLdapTestUser = false;
          } else if (type == "group") {
            that.loadingLdapTestGroup = false;
          }
          if (result.status == 1) {
            if (type == "connection") {
              that.$Message.success(result.message);
            } else if (type == "user") {
              that.titleLdapUsersGroups =
                "LDAP用户共 " +
                result.data.count +
                " 个：成功 " +
                result.data.success +
                " 个，失败 " +
                result.data.fail +
                " 个";
              that.tempLdapUsersGroups = result.data.users;
              that.modalLdapUsersGroups = true;
            } else if (type == "group") {
              that.titleLdapUsersGroups =
                "LDAP分组共 " +
                result.data.count +
                " 个：成功 " +
                result.data.success +
                " 个，失败 " +
                result.data.fail +
                " 个";
              that.tempLdapUsersGroups = result.data.groups;
              that.modalLdapUsersGroups = true;
            }
          } else {
            that.$Message.error({ content: result.message, duration: 2 });
          }
        })
        .catch(function (error) {
          if (type == "connection") {
            that.loadingLdapTestConnection = false;
          } else if (type == "user") {
            that.loadingLdapTestUser = false;
          } else if (type == "group") {
            that.loadingLdapTestGroup = false;
          }
          console.log(error);
          that.$Message.error(i18n.t('errors.contactAdmin'));
        });
    },
    //保存svnserve配置
    UpdSvnUsersource() {
      var that = this;
      that.$Modal.confirm({
        title: "警告",
        content:
          "如果为切换到ldap服务器，请仔细阅读以下内容后做出选择:<br/>1、此操作会将数据库中的SVN用户信息清空,后续手动同步时会将ldap用户写入数据库。<br/>2、接入ldap不会修改本系统中的passwd文件。<br/>3、如果设置了分组来源为ldap，此操作会将数据库中的SVN分组信息清空但是不立刻清空authz的分组信息。后续手动同步时自动清空authz的分组信息之后进行到authz文件中分组和数据库的同步。<br/>4、此操作不会清理被清理分组和用户之前已配置的仓库路径权限",
        onOk: () => {
          that.loadingUpdSvnUsersource = true;
          var data = {
            data_source: that.formSvn,
          };
          that.$axios
            .post("api.php?c=Setting&a=UpdSvnUsersource&t=web", data)
            .then(function (response) {
              var result = response.data;
              that.loadingUpdSvnUsersource = false;
              if (result.status == 1) {
                that.$Message.success(result.message);
                that.GetSvnInfo();
              } else {
                that.$Message.error({ content: result.message, duration: 2 });
              }
            })
            .catch(function (error) {
              that.loadingUpdSvnUsersource = false;
              console.log(error);
              that.$Message.error(i18n.t('errors.contactAdmin'));
            });
        },
      });
    },
    //保存htp配置
    UpdHttpUsersource() {
      var that = this;
      that.$Modal.confirm({
        title: "警告",
        content:
          "如果为切换到ldap服务器，请仔细阅读以下内容后做出选择:<br/>1、此操作会将数据库中的SVN用户信息清空,后续手动同步时会将ldap用户写入数据库。<br/>2、接入ldap不会修改本系统中的passwd文件。<br/>3、如果设置了分组来源为ldap，此操作会将数据库中的SVN分组信息清空但是不立刻清空authz的分组信息。后续手动同步时自动清空authz的分组信息之后进行到authz文件中分组和数据库的同步。<br/>4、此操作不会清理被清理分组和用户之前已配置的仓库路径权限",
        onOk: () => {
          that.loadingUpdHttpUsersource = true;
          var data = {
            data_source: that.formHttp,
          };
          that.$axios
            .post("api.php?c=Setting&a=UpdHttpUsersource&t=web", data)
            .then(function (response) {
              var result = response.data;
              that.loadingUpdHttpUsersource = false;
              if (result.status == 1) {
                that.$Message.success(result.message);
                that.GetApacheInfo();
              } else {
                that.$Message.error({ content: result.message, duration: 2 });
              }
            })
            .catch(function (error) {
              that.loadingUpdHttpUsersource = false;
              console.log(error);
              that.$Message.error(i18n.t('errors.contactAdmin'));
            });
        },
      });
    },
    //svn用户来源下拉
    ChangeSvnUsersource(value) {
      if (value == "passwd") {
        this.formSvn.group_source = "authz";
      }
    },
    //http用户来源下拉
    ChangeHttpUsersource(value) {
      if (value == "httpPasswd") {
        this.formHttp.group_source = "authz";
      }
    },
    /**
     * 启动sasl
     */
    UpdSaslStatusStart() {
      var that = this;
      that.$Modal.confirm({
        title: "以daomen方式启动saslauthd服务",
        content: "确定要启动saslauthd服务吗吗？",
        onOk: () => {
          that.loadingUpdSaslStatusStart = true;
          var data = {};
          that.$axios
            .post("api.php?c=Setting&a=UpdSaslStatusStart&t=web", data)
            .then(function (response) {
              that.loadingUpdSaslStatusStart = false;
              var result = response.data;
              if (result.status == 1) {
                that.$Message.success(result.message);
                that.GetSvnInfo();
              } else {
                that.$Message.error({ content: result.message, duration: 2 });
              }
            })
            .catch(function (error) {
              that.loadingUpdSaslStatusStart = false;
              console.log(error);
              that.$Message.error(i18n.t('errors.contactAdmin'));
            });
        },
      });
    },
    /**
     * 停止sasl
     */
    UpdSaslStatusStop() {
      var that = this;
      that.$Modal.confirm({
        title: "停止saslauthd服务",
        content: "确定要停止saslauthd服务吗？",
        onOk: () => {
          that.loadingUpdSaslStatusStop = true;
          var data = {};
          that.$axios
            .post("api.php?c=Setting&a=UpdSaslStatusStop&t=web", data)
            .then(function (response) {
              that.loadingUpdSaslStatusStop = false;
              var result = response.data;
              if (result.status == 1) {
                that.$Message.success(result.message);
                that.GetSvnInfo();
              } else {
                that.$Message.error({ content: result.message, duration: 2 });
              }
            })
            .catch(function (error) {
              that.loadingUpdSaslStatusStop = false;
              console.log(error);
              that.$Message.error(i18n.t('errors.contactAdmin'));
            });
        },
      });
    },
    /**
     * 获取 apache 服务器信息
     */
    GetApacheInfo() {
      var that = this;
      var data = {};
      that.$axios
        .post("api.php?c=Setting&a=GetApacheInfo&t=web", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.formHttp = result.data;

            that.tempHttpPrefix = result.data.prefix;
            that.disableUpdHttpPrefix = true;

            that.tempHttpPort = result.data.port;
            that.disableUpdHttpPort = true;
            if (that.timer) {
              that.$Message.success(result.message);
              clearInterval(that.timer);
            }
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
     * 启用 http 协议检出
     */
    UpdSubversionEnable() {
      var that = this;
      that.$Modal.confirm({
        title: "警告",
        content:
          "启用 http 协议检出将会使用另外的用户密码文件、会清空数据库中目前的用户信息、会停止 svn 协议检出。是否继续？",
        onOk: () => {
          that.loadingUpdSubversionEnable = true;
          var data = {};
          that.$axios
            .post("api.php?c=Setting&a=UpdSubversionEnable&t=web", data)
            .then(function (response) {
              var result = response.data;
              that.loadingUpdSubversionEnable = false;
              if (result.status == 1) {
                that.$Message.success(result.message);
                that.GetApacheInfo();
                that.GetSvnInfo();
              } else {
                that.$Message.error({ content: result.message, duration: 2 });
              }
            })
            .catch(function (error) {
              that.loadingUpdSubversionEnable = false;
              console.log(error);
              that.timer = window.setInterval(() => {
                setTimeout(function () {
                  that.GetApacheInfo();
                  that.GetSvnInfo();
                }, 0);
              }, 1000);
              that.$Message.success("等待httpd服务重启");
            });
        },
      });
    },
    /**
     * 启用 svn 协议检出
     */
    UpdSvnEnable() {
      var that = this;
      that.$Modal.confirm({
        title: "警告",
        content:
          "启用 svn 协议检出将会使用另外的用户密码文件、会清空数据库中目前的用户信息、会停止 http 协议检出。是否继续？",
        onOk: () => {
          that.loadingUpdSvnEnable = true;
          var data = {};
          that.$axios
            .post("api.php?c=Setting&a=UpdSvnEnable&t=web", data)
            .then(function (response) {
              var result = response.data;
              that.loadingUpdSvnEnable = false;
              if (result.status == 1) {
                that.$Message.success(result.message);
                that.GetApacheInfo();
                that.GetSvnInfo();
              } else {
                that.$Message.error({ content: result.message, duration: 2 });
              }
            })
            .catch(function (error) {
              that.loadingUpdSvnEnable = false;
              console.log(error);
              that.timer = window.setInterval(() => {
                setTimeout(function () {
                  that.GetApacheInfo();
                  that.GetSvnInfo();
                }, 0);
              }, 1000);
              that.$Message.success("等待httpd服务重启");
            });
        },
      });
    },
    //修改http端口
    UpdPort() {
      var that = this;
      that.loadingUpdPort = true;
      var data = {
        port: that.tempPort,
      };
      that.$axios
        .post("api.php?c=Setting&a=UpdPort&t=web", data)
        .then(function (response) {
          var result = response.data;
          that.loadingUpdPort = false;
          if (result.status == 1) {
            that.$Message.success(result.message);
            that.GetDcokerHostInfo();
          } else {
            that.$Message.error({ content: result.message, duration: 2 });
          }
        })
        .catch(function (error) {
          that.loadingUpdPort = false;
          console.log(error);
          that.$Message.error(i18n.t('errors.contactAdmin'));
        });
    },
    //修改http显示端口
    UpdHttpPort() {
      var that = this;
      that.loadingUpdHttpPort = true;
      var data = {
        port: that.tempHttpPort,
      };
      that.$axios
        .post("api.php?c=Setting&a=UpdHttpPort&t=web", data)
        .then(function (response) {
          that.loadingUpdHttpPort = false;
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
            that.GetApacheInfo();
          } else {
            that.$Message.error({ content: result.message, duration: 2 });
          }
        })
        .catch(function (error) {
          that.loadingUpdHttpPort = false;
          console.log(error);
          that.$Message.error(i18n.t('errors.contactAdmin'));
        });
    },
    //修改http访问仓库前缀
    UpdHttpPrefix() {
      var that = this;
      that.$Modal.confirm({
        title: "警告",
        content: "此操作将会重启httpd服务。是否继续？",
        onOk: () => {
          that.loadingUpdHttpPrefix = true;
          var data = {
            prefix: that.tempHttpPrefix,
          };
          that.$axios
            .post("api.php?c=Setting&a=UpdHttpPrefix&t=web", data)
            .then(function (response) {
              var result = response.data;
              that.loadingUpdHttpPrefix = false;
              if (result.status == 1) {
                that.$Message.success(result.message);
                that.GetApacheInfo();
              } else {
                that.$Message.error({ content: result.message, duration: 2 });
              }
            })
            .catch(function (error) {
              that.loadingUpdHttpPrefix = false;
              console.log(error);
              that.timer = window.setInterval(() => {
                setTimeout(function () {
                  that.GetApacheInfo();
                  that.GetSvnInfo();
                }, 0);
              }, 1000);
              that.$Message.success("等待httpd服务重启");
            });
        },
      });
    },
  },
};
</script>

<style >
</style>