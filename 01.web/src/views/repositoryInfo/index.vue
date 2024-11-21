<template>
  <div>
    <Card :bordered="false" :dis-hover="true">
      <!-- SVNserve服务非正常状态提示 -->
      <Alert
        v-if="formStatusSubversion.status == false"
        type="error"
        show-icon
        >{{ formStatusSubversion.info }}</Alert
      >
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
            icon="md-add"
            type="primary"
            ghost
            @click="ModalCreateRep"
            v-if="user_role_id == 1 || user_role_id == 3"
            >{{ $t("repositoryInfo.createRepo") }}</Button
          >
          <Tooltip
            max-width="250"
            :content="$t('repositoryInfo.syncRepListTip')"
            placement="bottom"
            :transfer="true"
            v-if="user_role_id == 1 || user_role_id == 3"
          >
            <Button
              icon="ios-sync"
              type="warning"
              ghost
              @click="GetRepList(true, true, false, false)"
              >{{ $t("repositoryInfo.syncRepList") }}</Button
            >
          </Tooltip>
          <Tooltip
            max-width="250"
            :content="$t('repositoryInfo.syncRepListInfoTip')"
            placement="bottom"
            :transfer="true"
            v-if="user_role_id == 1 || user_role_id == 3"
          >
            <Button
              icon="ios-sync"
              type="warning"
              ghost
              @click="GetRepList(true, true, true, true)"
              >{{ $t("repositoryInfo.syncRepListInfo") }}</Button
            >
          </Tooltip>
          <Tooltip
            max-width="250"
            :content="$t('repositoryInfo.syncUserRepListTip')"
            placement="bottom"
            :transfer="true"
            v-if="user_role_id == 2"
          >
            <Button
              icon="ios-sync"
              type="warning"
              ghost
              @click="GetSvnUserRepList(true)"
              >{{ $t("repositoryInfo.syncUserRepList") }}</Button
            >
          </Tooltip>
          <Tooltip
            max-width="450"
            :content="$t('repositoryInfo.checkAuthzTip')"
            placement="bottom"
            :transfer="true"
            v-if="user_role_id == 1 || user_role_id == 3"
          >
            <Button
              icon="ios-hammer-outline"
              type="error"
              ghost
              @click="CheckAuthz"
              >{{ $t("repositoryInfo.checkAuthz") }}</Button
            >
          </Tooltip>
          <!-- <Tooltip
            max-width="250"
            content="权限迁移"
            placement="bottom"
            :transfer="true"
          >
            <Button
              icon="ios-swap"
              type="error"
              ghost
              @click="GetRepList(true)"
              v-if="user_role_id == 1 || user_role_id == 3"
              >权限迁移</Button
            >
          </Tooltip> -->
        </Col>
        <Col :xs="3" :sm="4" :md="5" :lg="6">
          <Input
            search
            enter-button
            :placeholder="$t('repositoryInfo.searchRepByNameDesc')"
            @on-search="GetRepList()"
            v-model="searchKeywordRep"
            v-if="user_role_id == 1 || user_role_id == 3"
          />
          <Input
            search
            enter-button
            :placeholder="$t('repositoryInfo.searchUserRepByName')"
            @on-search="GetSvnUserRepList()"
            v-model="searchKeywordRep"
            v-if="user_role_id == 2"
          />
        </Col>
      </Row>
      <!-- 管理人员仓库列表 -->
      <Table
        v-if="user_role_id == 1 || user_role_id == 3"
        @on-sort-change="SortChangeRep"
        border
        :loading="loadingRep"
        :columns="tableColumnRep"
        :data="tableDataRep"
        size="small"
      >
        <template slot-scope="{ index }" slot="index">
          {{ pageSizeRep * (pageCurrentRep - 1) + index + 1 }}
        </template>
        <template slot-scope="{ row, index }" slot="rep_rev">
          {{ row.rep_rev
          }}<Icon
            type="ios-refresh"
            size="20"
            style="float: right"
            :color="row.loading_rep_rev ? '#ed4014' : '#808695'"
            @click="SyncRepRev(row.rep_name, index)"
          />
        </template>
        <template slot-scope="{ row, index }" slot="rep_size">
          {{ row.rep_size
          }}<Icon
            type="ios-refresh"
            size="20"
            style="float: right"
            :color="row.loading_rep_size ? '#ed4014' : '#808695'"
            @click="SyncRepSize(row.rep_name, index)"
          />
        </template>
        <template slot-scope="{ row, index }" slot="rep_note">
          <Input
            :border="false"
            v-model="tableDataRep[index].rep_note"
            @on-blur="UpdRepNote(index, row.rep_name)"
          />
        </template>
        <template slot-scope="{ row }" slot="repScan">
          <Button type="info" size="small" @click="ModalViewRep(row.rep_name)"
            >{{ $t('view') }}</Button
          >
        </template>
        <template slot-scope="{ row }" slot="repPri">
          <Button type="info" size="small" @click="ModalRepPri(row.rep_name)"
            >{{ $t('config') }}</Button
          >
        </template>
        <template slot-scope="{ row }" slot="repHooks">
          <Button type="info" size="small" @click="ModalRepHooks(row.rep_name)"
            >{{ $t('edit') }}</Button
          >
        </template>
        <template slot-scope="{ row }" slot="action">
          <Button
            type="success"
            size="small"
            @click="ModalRepAdvance(row.rep_name)"
            >{{ $t('advance') }}</Button
          >
          <Button
            type="warning"
            size="small"
            @click="ModalEditRepName(row.rep_name)"
            >{{ $t('modify') }}</Button
          >
          <Button type="error" size="small" @click="DelRep(row.rep_name)"
            >{{ $t('delete') }}</Button
          >
        </template>
      </Table>
      <!-- 用户仓库列表 -->
      <Table
        v-if="user_role_id == 2"
        @on-sort-change="SortChangeUserRep"
        border
        :loading="loadingUserRep"
        :columns="tableColumnUserRep"
        :data="tableDataUserRep"
        size="small"
      >
        <template slot-scope="{ index }" slot="index">
          {{ pageSizeUserRep * (pageCurrentUserRep - 1) + index + 1 }}
        </template>
        <template slot-scope="{ row }" slot="second_pri">
          <Button
            :disabled="!row.second_pri"
            type="info"
            size="small"
            @click="
              ModalRepPriUser(
                row.rep_name,
                row.svnn_user_pri_path_id,
                row.pri_path
              )
            "
            >{{ $t('config') }}</Button
          >
        </template>
        <template slot-scope="{ row }" slot="action">
          <Button
            type="info"
            size="small"
            @click="ModalViewUserRep(row.rep_name, row.pri_path)"
            >{{ $t('view') }}</Button
          >
          <Button
            type="info"
            size="small"
            v-if="enableCheckout == 'http'"
            @click="ModalViewUserRepRaw(row.raw_url)"
            >{{ $t('repositoryInfo.viewRaw') }}</Button
          >
        </template>
      </Table>
      <!-- 管理人员SVN仓库分页 -->
      <Card
        :bordered="false"
        :dis-hover="true"
        v-if="user_role_id == 1 || user_role_id == 3"
      >
        <Page
          v-if="totalRep != 0"
          :total="totalRep"
          :current="pageCurrentRep"
          :page-size="pageSizeRep"
          @on-page-size-change="PageSizeChangeRep"
          @on-change="PageChangeRep"
          size="small"
          show-sizer
        />
      </Card>
      <!-- 用户SVN仓库分页 -->
      <Card :bordered="false" :dis-hover="true" v-if="user_role_id == 2">
        <Page
          v-if="totalUserRep != 0"
          :total="totalUserRep"
          :current="pageCurrentUserRep"
          :page-size="pageSizeUserRep"
          @on-page-size-change="PageSizeChangeUserRep"
          @on-change="PageChangeUserRep"
          size="small"
          show-sizer
        />
      </Card>
    </Card>
    <!-- 对话框-新建SVN仓库 -->
    <Modal v-model="modalCreateRep" :draggable="true" :title="$t('repositoryInfo.createRepo')">
      <Form :model="formRepAdd" :label-width="80">
        <FormItem :label="$t('repositoryInfo.repoName')">
          <Input v-model="formRepAdd.rep_name"></Input>
        </FormItem>
        <FormItem>
          <Alert type="warning" show-icon
            >{{ $t('repositoryInfo.repoNameTip') }}</Alert
          >
        </FormItem>
        <FormItem :label="$t('note')">
          <Input v-model="formRepAdd.rep_note"></Input>
        </FormItem>
        <FormItem :label="$t('repositoryInfo.repoType')">
          <RadioGroup vertical v-model="formRepAdd.rep_type">
            <Radio label="1">
              <Icon type="social-apple"></Icon>
              <span>{{ $t('repositoryInfo.emptyRepo') }}</span>
            </Radio>
            <Radio label="2">
              <Icon type="social-android"></Icon>
              <span>{{ $t('repositoryInfo.standardRepo') }}</span>
            </Radio>
          </RadioGroup>
        </FormItem>
        <FormItem>
          <Button type="primary" @click="CreateRep" :loading="loadingCreateRep"
            >{{ $t('confirm') }}</Button
          >
        </FormItem>
      </Form>
      <div slot="footer">
        <Button type="primary" ghost @click="modalCreateRep = false"
          >{{ $t('cancel') }}</Button
        >
      </div>
    </Modal>
    <!-- 对话框-仓库浏览 -->
    <Modal v-model="modalViewRep" fullscreen :title="titleModalViewRep">
      <Row style="margin-bottom: 15px">
        <Col span="15">
          <Breadcrumb>
            <BreadcrumbItem
              v-for="(item, index) in breadRepPath.name"
              :key="index"
              @click.native="ClickBreadGetRepCon(breadRepPath.path[index])"
              >{{ item }}</BreadcrumbItem
            >
          </Breadcrumb>
        </Col>
        <Col span="1"> </Col>
        <Col span="8">
          <Tooltip
            style="width: 100%"
            max-width="450"
            :content="tempCheckout"
            placement="bottom"
          >
            <Input readonly v-model="tempCheckout">
              <Button slot="append" icon="md-copy" @click="CopyCheckout"
                >{{ $t('copy') }}</Button
              >
            </Input>
          </Tooltip>
        </Col>
      </Row>
      <Card :bordered="true" :dis-hover="true">
        <Table
          height="450"
          highlight-row
          :no-data-text="noDataTextRepCon"
          :border="false"
          :loading="loadingRepCon"
          :show-header="false"
          :columns="tableColumnRepCon"
          :data="tableDataRepCon"
          @on-row-click="ClickRowGetRepCon"
        >
          <template slot-scope="{ row }" slot="resourceType">
            <Icon
              v-if="row.resourceType == 1"
              size="20"
              type="ios-document-outline"
            />
            <Icon
              v-if="row.resourceType == 2"
              size="20"
              color="#65a0d5"
              type="ios-folder-open"
            />
          </template>
        </Table>
      </Card>
      <div slot="footer">
        <Button type="primary" ghost @click="modalViewRep = false">{{ $t('cancel') }}</Button>
      </div>
    </Modal>
    <!-- 对话框-仓库钩子 -->
    <Modal
      v-model="modalRepHooks"
      :title="titleModalRepHooks"
      class-name="hooks"
      :draggable="true"
    >
      <Alert type="info" show-icon
        >{{ $t('repositoryInfo.repoHooksAlert') }}</Alert
      >
      <Tabs type="card">
        <TabPane :label="$t('repositoryInfo.repoHooks')">
          <Card :bordered="false" :dis-hover="true" class="my-modal">
            <!-- <Scroll> -->
            <List>
              <Divider orientation="left" size="small">Commit</Divider>
              <ListItem>
                <ListItemMeta
                  description="Start-commit hook"
                  v-if="formRepHooks.start_commit.hasFile"
                />
                <ListItemMeta title="Start-commit hook" v-else />
                <template slot="action">
                  <li>
                    <span @click="ModalStudyRepHook('start_commit')">{{ $t('repositoryInfo.introduce') }}</span>
                  </li>
                  <li>
                    <span @click="ModalEditRepHook('start_commit')">{{ $t('edit') }}</span>
                  </li>
                  <li>
                    <span
                      @click="DelRepHook(formRepHooks.start_commit.fileName)"
                      >{{ $t('delete') }}</span
                    >
                  </li>
                </template>
              </ListItem>
              <ListItem>
                <ListItemMeta
                  description="Pre-commit hook"
                  v-if="formRepHooks.pre_commit.hasFile"
                />
                <ListItemMeta title="Pre-commit hook" v-else />
                <template slot="action">
                  <li>
                    <span @click="ModalStudyRepHook('pre_commit')">{{ $t('repositoryInfo.introduce') }}</span>
                  </li>
                  <li>
                    <span @click="ModalEditRepHook('pre_commit')">{{ $t('edit') }}</span>
                  </li>
                  <li>
                    <span @click="DelRepHook(formRepHooks.pre_commit.fileName)"
                      >{{ $t('delete') }}</span
                    >
                  </li>
                </template>
              </ListItem>
              <ListItem>
                <ListItemMeta
                  description="Post-commit hook"
                  v-if="formRepHooks.post_commit.hasFile"
                />
                <ListItemMeta title="Post-commit hook" v-else />
                <template slot="action">
                  <li>
                    <span @click="ModalStudyRepHook('post_commit')">{{ $t('repositoryInfo.introduce') }}</span>
                  </li>
                  <li>
                    <span @click="ModalEditRepHook('post_commit')">{{ $t('edit') }}</span>
                  </li>
                  <li>
                    <span @click="DelRepHook(formRepHooks.post_commit.fileName)"
                      >{{ $t('delete') }}</span
                    >
                  </li>
                </template>
              </ListItem>
              <Divider orientation="left" size="small">Locks</Divider>
              <ListItem>
                <ListItemMeta
                  description="Pre-lock hook"
                  v-if="formRepHooks.pre_lock.hasFile"
                />
                <ListItemMeta title="Pre-lock hook" v-else />
                <template slot="action">
                  <li>
                    <span @click="ModalStudyRepHook('pre_lock')">{{ $t('repositoryInfo.introduce') }}</span>
                  </li>
                  <li>
                    <span @click="ModalEditRepHook('pre_lock')">{{ $t('edit') }}</span>
                  </li>
                  <li>
                    <span @click="DelRepHook(formRepHooks.pre_lock.fileName)"
                      >{{ $t('delete') }}</span
                    >
                  </li>
                </template>
              </ListItem>
              <ListItem>
                <ListItemMeta
                  description="Post-lock hook"
                  v-if="formRepHooks.post_lock.hasFile"
                />
                <ListItemMeta title="Post-lock hook" v-else />
                <template slot="action">
                  <li>
                    <span @click="ModalStudyRepHook('post_lock')">{{ $t('repositoryInfo.introduce') }}</span>
                  </li>
                  <li>
                    <span @click="ModalEditRepHook('post_lock')">{{ $t('edit') }}</span>
                  </li>
                  <li>
                    <span @click="DelRepHook(formRepHooks.post_lock.fileName)"
                      >{{ $t('delete') }}</span
                    >
                  </li>
                </template>
              </ListItem>
              <ListItem>
                <ListItemMeta
                  description="Pre-unlock hook"
                  v-if="formRepHooks.pre_unlock.hasFile"
                />
                <ListItemMeta title="Pre-unlock hook" v-else />
                <template slot="action">
                  <li>
                    <span @click="ModalStudyRepHook('pre_unlock')">{{ $t('repositoryInfo.introduce') }}</span>
                  </li>
                  <li>
                    <span @click="ModalEditRepHook('pre_unlock')">{{ $t('edit') }}</span>
                  </li>
                  <li>
                    <span @click="DelRepHook(formRepHooks.pre_unlock.fileName)"
                      >{{ $t('delete') }}</span
                    >
                  </li>
                </template>
              </ListItem>
              <ListItem>
                <ListItemMeta
                  description="Post-unlock hook"
                  v-if="formRepHooks.post_unlock.hasFile"
                />
                <ListItemMeta title="Post-unlock hook" v-else />
                <template slot="action">
                  <li>
                    <span @click="ModalStudyRepHook('post_unlock')">{{ $t('repositoryInfo.introduce') }}</span>
                  </li>
                  <li>
                    <span @click="ModalEditRepHook('post_unlock')">{{ $t('edit') }}</span>
                  </li>
                  <li>
                    <span @click="DelRepHook(formRepHooks.post_unlock.fileName)"
                      >{{ $t('delete') }}</span
                    >
                  </li>
                </template>
              </ListItem>
              <Divider orientation="left" size="small"
                >Revision property change</Divider
              >
              <ListItem>
                <ListItemMeta
                  description="Pre-reversion property change hook"
                  v-if="formRepHooks.pre_revprop_change.hasFile"
                />
                <ListItemMeta
                  title="Pre-reversion property change hook"
                  v-else
                />
                <template slot="action">
                  <li>
                    <span @click="ModalStudyRepHook('pre_revprop_change')"
                      >{{ $t('repositoryInfo.introduce') }}</span
                    >
                  </li>
                  <li>
                    <span @click="ModalEditRepHook('pre_revprop_change')"
                      >{{ $t('edit') }}</span
                    >
                  </li>
                  <li>
                    <span
                      @click="
                        DelRepHook(formRepHooks.pre_revprop_change.fileName)
                      "
                      >{{ $t('delete') }}</span
                    >
                  </li>
                </template>
              </ListItem>
              <ListItem>
                <ListItemMeta
                  description="Post-reversion property change hook"
                  v-if="formRepHooks.post_revprop_change.hasFile"
                />
                <ListItemMeta
                  title="Post-reversion property change hook"
                  v-else
                />
                <template slot="action">
                  <li>
                    <span @click="ModalStudyRepHook('post_revprop_change')"
                      >{{ $t('repositoryInfo.introduce') }}</span
                    >
                  </li>
                  <li>
                    <span @click="ModalEditRepHook('post_revprop_change')"
                      >{{ $t('edit') }}</span
                    >
                  </li>
                  <li>
                    <span
                      @click="
                        DelRepHook(formRepHooks.post_revprop_change.fileName)
                      "
                      >{{ $t('delete') }}</span
                    >
                  </li>
                </template>
              </ListItem>
            </List>
            <!-- </Scroll> -->
          </Card>
          <Spin size="large" fix v-if="loadingGetRepHooks"></Spin>
        </TabPane>
        <TabPane :label="$t('repositoryInfo.recommendHooks')">
          <Alert
            >{{ $t('repositoryInfo.recommendHooksAlert1') }}<br /><br />
            {{ $t('repositoryInfo.recommendHooksAlert2') }}<br /><br />
            {{ $t('repositoryInfo.recommendHooksAlert3') }}<br />
            {{ $t('repositoryInfo.recommendHooksAlert4') }}<br />
            {{ $t('repositoryInfo.recommendHooksAlert5') }}<br />
            {{ $t('repositoryInfo.recommendHooksAlert6') }}<br />
          </Alert>
          <Scroll :height="200">
            <List :border="true">
              <ListItem v-for="(item, index) in recommendHooks" :key="index">
                <ListItemMeta
                  :title="item.hookName"
                  :description="item.hookDescription"
                />
                <template slot="action">
                  <li>
                    <span @click="ViewRecommendHook(item.hookName)">{{ $t('view') }}</span>
                  </li>
                </template>
              </ListItem>
            </List>
          </Scroll>
        </TabPane>
      </Tabs>
      <div slot="footer">
        <Button type="primary" ghost @click="modalRepHooks = false"
          >{{ $t('cancel') }}</Button
        >
      </div>
    </Modal>
    <!-- 对话框-钩子信息介绍 -->
    <Modal
      v-model="modalStudyRepHook"
      :draggable="true"
      :title="titleModalStudyRepHook"
    >
      <Input
        v-model="tempSelectRepHookTmpl"
        readonly
        :rows="15"
        show-word-limit
        type="textarea"
      />
      <div slot="footer">
        <Button type="primary" ghost @click="modalStudyRepHook = false"
          >{{ $t('cancel') }}</Button
        >
      </div>
    </Modal>
    <!-- 对话框-钩子文件编辑 -->
    <Modal
      v-model="modalEditRepHook"
      :draggable="true"
      :title="titleModalEditRepHook"
    >
      <Input
        v-model="tempSelectRepHookCon"
        :rows="15"
        show-word-limit
        type="textarea"
        :placeholder="$t('repositoryInfo.hookFilePlaceHolder')"
      />
      <div slot="footer">
        <Button type="primary" @click="UpdRepHook" :loading="loadingEditRepHook"
          >{{ $t('apply') }}</Button
        >
      </div>
    </Modal>
    <!-- 对话框-常用钩子 -->
    <Modal v-model="modalRecommendHook" :draggable="true" :title="$t('repositoryInfo.recommendHooks')">
      <Input
        v-model="tempSelectRepHookRecommend"
        readonly
        :rows="15"
        show-word-limit
        type="textarea"
      />
      <div slot="footer">
        <Button type="primary" ghost @click="modalRecommendHook = false"
          >{{ $t('cancel') }}</Button
        >
      </div>
    </Modal>
    <!-- 对话框-高级 -->
    <Modal
      v-model="modalRepAdvance"
      :draggable="true"
      :title="titleModalRepAdvance"
    >
      <Tabs type="card" v-model="curTabRepAdvance" @on-click="ClickTabAdvance">
        <TabPane :label="$t('repositoryInfo.repoAttribute')" name="attribute">
          <Table
            :show-header="false"
            :columns="tableColumnRepDetail"
            :data="tableDataRepDetail"
            :loading="loadingRepDetail"
            size="small"
            height="350"
          >
            <template slot-scope="{ index }" slot="copy">
              <Button
                icon="md-copy"
                type="text"
                @click="CopyRepDetail(index)"
              ></Button>
            </template>
            <template
              slot-scope="{ row }"
              slot="uuid"
              v-if="row.repKey == 'UUID' || row.repKey == 'uuid'"
            >
              <Button type="primary" size="small" @click="ModalSetUUID()"
                >{{ $t('reset') }}</Button
              >
            </template>
          </Table>
        </TabPane>
        <TabPane :label="$t('repositoryInfo.repoBackup')" name="backup">
          <Alert type="error" show-icon v-if="!file.on"
            >{{ $t('repositoryInfo.cannotUploadAlert') }}
          </Alert>
          <Row style="margin-bottom: 15px">
            <Col span="15">
              <Tooltip
                max-width="250"
                :content="$t('repositoryInfo.backupByCrondDump')"
                placement="bottom"
                :transfer="true"
              >
                <Button
                  type="primary"
                  ghost
                  icon="ios-cafe-outline"
                  :loading="loadingRepDump"
                  @click="SvnadminDump"
                  >{{ $t('repositoryInfo.backupNow') }}</Button
                >
              </Tooltip>
              <Button
                type="primary"
                ghost
                icon="ios-cloud-upload-outline"
                @click="ModalUploadBackup"
                >{{ $t('repositoryInfo.uploadBackup') }}</Button
              >
            </Col>
          </Row>
          <Table
            height="300"
            border
            :columns="tableColumnBackup"
            :data="tableDataBackup"
            size="small"
            :loading="loadingRepBackupList"
          >
            <template slot-scope="{ index, row }" slot="action">
              <Button
                type="success"
                size="small"
                :loading="loadingLoadBackup[index]"
                @click="SvnadminLoad(row.fileName, index)"
                >{{ $t('repositoryInfo.loadBackup') }}</Button
              >
              <Button
                type="success"
                size="small"
                @click="DownloadRepBackup(row.fileUrl)"
                >{{ $t('repositoryInfo.downloadBackup') }}</Button
              >
              <Button
                type="error"
                size="small"
                @click="DelRepBackup(row.fileName)"
                >{{ $t('delete') }}</Button
              >
            </template>
          </Table>
        </TabPane>
      </Tabs>
      <div slot="footer">
        <Button type="primary" ghost @click="modalRepAdvance = false"
          >{{ $t('cancel') }}</Button
        >
      </div>
    </Modal>
    <!-- 对话框-编辑仓库名称 -->
    <Modal
      v-model="modalEditRepName"
      :draggable="true"
      :title="titleModalEditRepName"
    >
      <Form :model="formRepEdit" :label-width="80">
        <FormItem :label="$t('repositoryInfo.repoName')">
          <Input v-model="formRepEdit.new_rep_name"></Input>
        </FormItem>
        <FormItem>
          <Button
            type="primary"
            :loading="loadingEditRepName"
            @click="UpdRepName"
            >{{ $t('confirm') }}</Button
          >
        </FormItem>
      </Form>
      <div slot="footer">
        <Button type="primary" ghost @click="modalEditRepName = false"
          >{{ $t('cancel') }}</Button
        >
      </div>
    </Modal>
    <!-- 对话框-重设仓库UUID -->
    <Modal v-model="modalSetUUID" :draggable="true" :title="$t('repositoryInfo.resetUUID')">
      <Form :label-width="80" @submit.native.prevent>
        <FormItem label="UUID">
          <Input
            v-model="tempRepUUID"
            :placeholder="$t('repositoryInfo.inputUUID')"
          ></Input>
        </FormItem>
        <FormItem>
          <Button type="primary" :loading="loadingSetUUID" @click="SetUUID"
            >{{ $t('confirm') }}</Button
          >
        </FormItem>
      </Form>
      <div slot="footer">
        <Button type="primary" ghost @click="modalSetUUID = false">{{ $t('cancel') }}</Button>
      </div>
    </Modal>
    <!-- 对话框-authz检测结果 -->
    <Modal v-model="modalValidateAuthz" :title="$t('repositoryInfo.authzCheckResult')">
      <Input
        v-model="tempmodalValidateAuthz"
        readonly
        :rows="15"
        show-word-limit
        type="textarea"
      />
      <div slot="footer">
        <Button type="primary" ghost @click="modalValidateAuthz = false"
          >{{ $t('cancel') }}</Button
        >
      </div>
    </Modal>
    <!-- 对话框-仓库导入错误 -->
    <Modal v-model="modalRepLoad" :draggable="true" :title="$t('repositoryInfo.repoLoadError')">
      <Input
        v-model="tempRepLoadError"
        readonly
        :rows="15"
        show-word-limit
        type="textarea"
      />
      <div slot="footer">
        <Button type="primary" ghost @click="modalRepLoad = false">{{ $t('cancel') }}</Button>
      </div>
    </Modal>
    <!-- 对话框-备份文件上传 -->
    <Modal
      v-model="modalRepUpload"
      :draggable="true"
      :title="$t('repositoryInfo.uploadBackupFile')"
      @on-visible-change="ChangeModalVisible"
    >
      <Form :label-width="80">
        <FormItem :label="$t('repositoryInfo.uploadFile')">
          <Button
            type="primary"
            icon="ios-cloud-upload-outline"
            ghost
            @click="ClickRepUpload"
            >{{ $t('repositoryInfo.chooseFile') }}</Button
          >
          <input
            type="file"
            id="myfile"
            name="myfile"
            accept=".dump"
            style="display: none"
          />
        </FormItem>
        <FormItem :label="$t('repositoryInfo.uploadProgress')">
          <Progress
            :percent="file.percent"
            :stroke-width="20"
            status="active"
          />
        </FormItem>
        <FormItem :label="$t('repositoryInfo.filename')"
          ><span style="color: #2d8cf0">{{ file.name }}</span>
        </FormItem>
        <FormItem :label="$t('repositoryInfo.filesize')">
          <span style="color: #2d8cf0">{{ file.size }}</span></FormItem
        >
        <FormItem :label="$t('repositoryInfo.uploadStatus')">
          <span style="color: red">{{ file.desc }}</span>
        </FormItem>
        <FormItem :label="$t('repositoryInfo.chunkSize')">
          <span style="color: #2d8cf0">{{ file.chunkSize }} MB</span>
        </FormItem>
        <FormItem :label="$t('repositoryInfo.timeleft')">
          <span style="color: #2d8cf0">{{ file.left }}</span></FormItem
        >
        <FormItem :label="$t('repositoryInfo.clearChunks')">
          <span style="color: #2d8cf0">{{
            file.deleteOnMerge == 1
              ? $t('repositoryInfo.deleteOnMerge')
              : $t('repositoryInfo.keepOnMerge')
          }}</span>
        </FormItem>
        <FormItem :label="$t('repositoryInfo.uploadControl')">
          <Button
            type="primary"
            ghost
            v-if="!file.stop"
            @click="file.stop = true"
            >{{ $t('repositoryInfo.pause') }}</Button
          >
          <span v-else style="color: red"
            >{{ $t('repositoryInfo.pauseTips') }}</span
          >
        </FormItem>
      </Form>
      <div slot="footer">
        <Button type="primary" ghost @click="ClickModalRepLoad">{{ $t('cancel') }}</Button>
      </div>
    </Modal>
    <!-- 对话框-仓库权限配置 -->
    <ModalRepPri
      :propCurrentRepName="currentRepName"
      :propCurrentRepPath="currentRepPath"
      :propModalRepPri="modalRepPri"
      :propChangeParentModalVisible="CloseModalRepPri"
      :propChangeParentCurrentRepPath="ChangeCurrentRepPath"
      :propSvnnUserPriPathId="svnn_user_pri_path_id"
    />
  </div>
</template>

<script>
//SVN对象列表组件
import ModalRepPri from "@/components/modalRepPri.vue";

import SparkMD5 from "spark-md5";

import i18n from "@/i18n";

export default {
  data() {
    return {
      /**
       * 权限相关
       */
      token: sessionStorage.token,
      user_role_id: sessionStorage.user_role_id,

      /**
       * 当前启用协议
       */
      enableCheckout: "passwd",

      /**
       * 对话框
       */
      //新建SVN仓库
      modalCreateRep: false,
      //浏览仓库
      modalViewRep: false,
      //仓库钩子配置
      modalRepHooks: false,
      //高级
      modalRepAdvance: false,
      //仓库导入错误
      modalRepLoad: false,
      //仓库备份上传
      modalRepUpload: false,
      //编辑仓库信息
      modalEditRepName: false,
      //编辑仓库钩子内容
      modalEditRepHook: false,
      //查看钩子模板内容
      modalStudyRepHook: false,
      //常看常用钩子内容
      modalRecommendHook: false,
      //重设仓库UUID
      modalSetUUID: false,
      //显示authz检测结果
      modalValidateAuthz: false,
      //仓库权限
      modalRepPri: false,

      /**
       * 排序数据
       */
      //获取仓库列表
      sortNameGetRepList: "rep_name",
      sortTypeGetRepList: "asc",

      //获取SVN用户有权限的仓库路径列表
      sortNameGetSvnUserRepList: "",
      sortTypeGetSvnUserRepList: "asc",

      /**
       * 分页数据
       */
      //所有仓库
      pageCurrentRep: 1,
      pageSizeRep: 20,
      totalRep: 0,
      //用户仓库
      pageCurrentUserRep: 1,
      pageSizeUserRep: 10,
      totalUserRep: 0,

      /**
       * 搜索关键词
       */
      searchKeywordRep: "",

      /**
       * 加载
       */
      //所有仓库列表
      loadingRep: true,
      //创建仓库
      loadingCreateRep: false,
      //用户仓库列表
      loadingUserRep: true,
      //仓库内容列表
      loadingRepCon: true,

      //去除路径的用户权限
      loadingDelRepPathUserPri: false,
      //删除仓库路径的分组权限
      loadingDelRepPathGroupPri: false,
      //获取仓库的详细信息
      loadingRepDetail: true,
      //获取仓库的备份文件夹文件内容
      loadingRepBackupList: true,
      //备份仓库按钮
      loadingRepDump: false,
      //上传备份文件
      loadingUploadBackup: false,
      //导入备份文件
      loadingLoadBackup: [],
      //修改仓库名称
      loadingEditRepName: false,
      //获取仓库钩子信息
      loadingGetRepHooks: true,
      //编辑仓库内容
      loadingEditRepHook: false,
      //重设仓库UUID
      loadingSetUUID: false,

      /**
       * 临时变量
       */
      //临时选中的仓库名称
      currentRepName: "",
      //当前仓库路径
      currentRepPath: "",
      //选中的id
      svnn_user_pri_path_id: -1,
      //仓库导入错误信息
      tempRepLoadError: "",
      //高级选项tab
      curTabRepAdvance: "attribute",
      //检出路径
      tempCheckout: "",
      //单选 仓库路径的用户权限列表
      radioRepUserPri: "",
      //单选 仓库路径的分组权限列表
      radioRepGroupPri: "",
      //仓库路径的分组权限列表 当前选中的分组以及下标
      currentRepPriGroup: "",
      currentRepPriGroupIndex: -1,
      //仓库路径的权限新增用户列表 当前选中的用户
      currentRepPriAddUser: "",
      //仓库路径的权限新增分组列表 当前选中的分组
      currentRepPriAddGroup: "",
      //仓库钩子名称
      tempSelectRepHook: "",
      //仓库钩子内容
      tempSelectRepHookCon: "",
      //钩子模板内容
      tempSelectRepHookTmpl: "",
      //常用钩子内容查看
      tempSelectRepHookRecommend: "",
      //仓库重设UUID
      tempRepUUID: "",
      //authz检测结果
      tempmodalValidateAuthz: "",
      //当前选中的分组名
      currentSelectGroupName: "",

      /**
       * 对话框标题
       */
      //浏览仓库内容
      titleModalViewRep: "",
      //仓库钩子
      titleModalRepHooks: "",
      //编辑仓库名称
      titleModalEditRepName: "",
      //高级
      titleModalRepAdvance: "",
      //钩子文件编辑
      titleModalEditRepHook: "",
      //钩子文件模板
      titleModalStudyRepHook: "",

      /**
       * 表单
       */
      //新建SVN仓库
      formRepAdd: {
        rep_name: "",
        rep_note: "",
        rep_type: "1",
      },
      //编辑仓库
      formRepEdit: {
        old_rep_name: "",
        new_rep_name: "",
      },
      //钩子结构
      formRepHooks: {
        start_commit: { fileName: "", hasFile: false, con: "", tmpl: "" },
        pre_commit: { fileName: "", hasFile: false, con: "", tmpl: "" },
        post_commit: { fileName: "", hasFile: false, con: "", tmpl: "" },
        pre_lock: { fileName: "", hasFile: false, con: "", tmpl: "" },
        post_lock: { fileName: "", hasFile: false, con: "", tmpl: "" },
        pre_unlock: { fileName: "", hasFile: false, con: "", tmpl: "" },
        post_unlock: { fileName: "", hasFile: false, con: "", tmpl: "" },
        pre_revprop_change: { fileName: "", hasFile: false, con: "", tmpl: "" },
        post_revprop_change: {
          fileName: "",
          hasFile: false,
          con: "",
          tmpl: "",
        },
      },
      //页头提示信息
      formStatusSubversion: {
        status: true,
        info: "",
      },
      //目录浏览的检出地址
      checkInfo: {
        protocal: "",
        prefix: "",
      },
      //文件上传相关
      file: {
        //文件上传功能开启状态
        on: true,
        // 分片上传大小 MB
        chunkSize: 1,
        // 分片总数
        chunkCount: 0,
        // 分片合并后删除分片
        deleteOnMerge: 1,
        //文件上传进度条
        current: 0,
        total: 0,
        percent: 0,
        //文件名称
        name: "",
        //上传状态
        desc: "",
        //文件体积
        size: "",
        //文件md5
        md5: "",
        //预估时间
        left: "",
        //是否停止上传
        stop: false,
      },

      /**
       * 浏览仓库面包屑
       */
      breadRepPath: [],

      //常用钩子列表
      recommendHooks: [],

      tableDataRep: [],
      
      tableDataUserRep: [],
      
      tableDataRepCon: [],
      
      tableDataBackup: [],
      
      tableDataRepPathUserPri: [],
      
      tableDataRepPathGroupPri: [],

      tableDataRepDetail: [],
    };
  },
  components: {
    ModalRepPri,
  },
  computed: {
      /**
       * 表格无数据提示
       */
       noDataTextRepCon() { 
        return i18n.t('noDataNow'); //"暂无数据",
      },
      /**
       * 表格
       */
      //所有仓库
      tableColumnRep() {
        return [
        {
          title: i18n.t('serial'),   //"序号",
          slot: "index",
          fixed: "left",
          minWidth: 80,
        },
        {
          title: i18n.t('repositoryInfo.repoName'),   //"仓库名",
          key: "rep_name",
          tooltip: true,
          sortable: "custom",
          minWidth: 120,
        },
        {
          title: i18n.t('repositoryInfo.repoRev'),   //"版本数",
          slot: "rep_rev",
          sortable: "custom",
          minWidth: 90,
        },
        {
          title: i18n.t('repositoryInfo.repoSize'),   //"体积",
          slot: "rep_size",
          sortable: "custom",
          minWidth: 120,
        },
        {
          title: i18n.t('note'),   //"备注信息",
          slot: "rep_note",
          minWidth: 120,
        },
        {
          title: i18n.t('repositoryInfo.repoScan'),   //"仓库内容",
          slot: "repScan",
          minWidth: 120,
        },
        {
          title: i18n.t('repositoryInfo.repoPri'),   //"仓库权限",
          slot: "repPri",
          minWidth: 120,
        },
        {
          title: i18n.t('repositoryInfo.repoHooks'),   //"仓库钩子",
          slot: "repHooks",
          width: 120,
        },
        {
          title: i18n.t('others'),   //"其它",
          slot: "action",
          width: 180,
          // fixed:"right"
        },
      ]},
      //SVN用户仓库
      tableColumnUserRep() {
        return [
        {
          title: i18n.t('serial'),   //"序号",
          slot: "index",
          fixed: "left",
          minWidth: 80,
        },
        {
          title: i18n.t('repositoryInfo.repoName'),   //"仓库名",
          key: "rep_name",
          tooltip: true,
          sortable: "custom",
          minWidth: 120,
        },
        {
          title: i18n.t('repositoryInfo.pathFile'),   //"路径/文件",
          tooltip: true,
          key: "pri_path",
          minWidth: 120,
        },
        {
          title: i18n.t('repositoryInfo.repoPri'),   //"权限",
          key: "rep_pri",
          minWidth: 120,
        },
        {
          title: i18n.t('repositoryInfo.secondPri'),   //"二次授权",
          slot: "second_pri",
          minWidth: 120,
        },
        {
          title: i18n.t('others'),   //"其它",
          slot: "action",
          width: 180,
          // fixed:"right"
        },
      ]},
      //仓库内容浏览
      tableColumnRepCon() {
        return [
        {
          title: i18n.t('repositoryInfo.resourceType'),   //"类型",
          slot: "resourceType",
          width: 60,
        },
        {
          title: i18n.t('repositoryInfo.resourceName'),   //"文件",
          key: "resourceName",
          tooltip: true,
        },
        {
          title: i18n.t('repositoryInfo.filesize'),   //"体积",
          key: "fileSize",
          tooltip: true,
        },
        {
          title: i18n.t('repositoryInfo.revAuthor'),   //"作者",
          key: "revAuthor",
          tooltip: true,
        },
        {
          title: i18n.t('repositoryInfo.revNum'),   //"版本",
          key: "revNum",
          tooltip: true,
        },
        {
          title: i18n.t('repositoryInfo.revTime'),   //"日期",
          key: "revTime",
          tooltip: true,
          width: 350,
        },
        {
          title: i18n.t('repositoryInfo.revLog'),   //"日志",
          key: "revLog",
          tooltip: true,
        },
      ]},
      //备份文件
      tableColumnBackup() {
        return [
        {
          title: i18n.t('repositoryInfo.filename'),   //"文件名",
          key: "fileName",
          tooltip: true,
        },
        {
          title: i18n.t('repositoryInfo.filesize'),   //"大小",
          key: "fileSize",
          tooltip: true,
        },
        {
          title: i18n.t('repositoryInfo.fileEditTime'),   //"修改时间",
          key: "fileEditTime",
          tooltip: true,
        },
        {
          title: i18n.t('others'),   //"其它",
          slot: "action",
          width: 200,
        },
      ]},
      //某节点的用户权限
      tableColumnRepPathUserPri() {
        return [
        {
          title: i18n.t('username'),   //"用户名",
          key: "userName",
        },
        {
          title: i18n.t('repositoryInfo.userPri'),   //"权限",
          key: "userPri",
        },
      ]},
      //某节点的分组权限
      tableColumnRepPathGroupPri() {
        return [
        {
          title: i18n.t('repositoryInfo.groupName'),   //"分组名",
          key: "groupName",
        },
        {
          title: i18n.t('repositoryInfo.groupPri'),   //"权限",
          key: "groupPri",
        },
      ]},
      //仓库的详细信息 uuid等
      tableColumnRepDetail() {
        return [
        {
          title: i18n.t('repositoryInfo.repoAttribute'),   //"属性",
          key: "repKey",
          tooltip: true,
          fixed: "left",
          width: 170,
          // width:80
        },
        {
          title: i18n.t('repositoryInfo.repoInfo'),   //"信息",
          key: "repValue",
          tooltip: true,
          width: 170,
        },
        {
          title: i18n.t('copy'),   //"复制",
          slot: "copy",
          width: 60,
        },
        {
          title: i18n.t('reset'),   //"重设",
          slot: "uuid",
        },
      ]},
  },
  created() {},
  mounted() {
    this.GetSvnserveStatus();
    if (this.user_role_id == 1 || this.user_role_id == 3) {
      this.GetRepList();

      //高级选项
      if (!sessionStorage.curTabRepAdvance) {
        sessionStorage.setItem("curTabRepAdvance", "attribute");
      } else {
        this.curTabRepAdvance = sessionStorage.curTabRepAdvance;
      }
    } else if (this.user_role_id == 2) {
      this.GetSvnUserRepList();
    }
  },
  methods: {
    /**
     * 子组件 modalRepPri 传递变量给父组件
     */
    CloseModalRepPri() {
      this.modalRepPri = false;
    },
    /**
     * 子组件 modalRepPri 传递变量给父组件
     */
    ChangeCurrentRepPath(value) {
      this.currentRepPath = value;
    },

    /**
     * 秒单位格式化
     */
    FormatTime(seconds) {
      let h = parseInt((seconds / 60 / 60) % 24);
      h = h < 10 ? "0" + h : h;
      let m = parseInt((seconds / 60) % 60);
      m = m < 10 ? "0" + m : m;
      let s = parseInt(seconds % 60);
      s = s < 10 ? "0" + s : s;
      // 作为返回值返回
      return [h, m, s];
    },
    /**
     * 文件体积单位格式化
     */
    FormatFileSize(fileSize) {
      if (fileSize < 1024) {
        return fileSize + "B";
      } else if (fileSize < 1024 * 1024) {
        var temp = fileSize / 1024;
        temp = temp.toFixed(2);
        return temp + "KB";
      } else if (fileSize < 1024 * 1024 * 1024) {
        var temp = fileSize / (1024 * 1024);
        temp = temp.toFixed(2);
        return temp + "MB";
      } else {
        var temp = fileSize / (1024 * 1024 * 1024);
        temp = temp.toFixed(2);
        return temp + "GB";
      }
    },

    /**
     * 退出文件上传对话框
     */
    ChangeModalVisible(value) {
      if (!value) {
        this.modalRepUpload = false;
        this.file.stop = true;
      }
    },
    ClickModalRepLoad() {
      this.modalRepUpload = false;
      this.file.stop = true;
    },

    //高级选项切换
    ClickTabAdvance(name) {
      sessionStorage.setItem("curTabRepAdvance", name);

      switch (name) {
        case "attribute":
          this.GetRepDetail();
          break;
        case "backup":
          this.GetUploadInfo();
          this.GetBackupList();
          break;
        default:
          break;
      }
    },

    /**
     * 获取svnserve运行状态
     */
    GetSvnserveStatus() {
      var that = this;
      var data = {};
      that.$axios
        .post("api.php?c=Svnrep&a=GetSvnserveStatus&t=web", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            if (!result.data) {
              that.formStatusSubversion.status = result.data;
              that.formStatusSubversion.info = result.message;
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
     * 检测 authz 文件状态
     */
    CheckAuthz() {
      var that = this;
      var data = {};
      that.$axios
        .post("api.php?c=Svnrep&a=CheckAuthz&t=web", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
          } else if (result.status == 2) {
            that.$Message.error({ content: result.message, duration: 2 });
            that.modalValidateAuthz = true;
            that.tempmodalValidateAuthz = result.data;
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
     * 添加仓库
     */
    ModalCreateRep() {
      this.modalCreateRep = true;
    },
    CreateRep() {
      var that = this;
      that.loadingCreateRep = true;
      var data = {
        rep_name: that.formRepAdd.rep_name,
        rep_note: that.formRepAdd.rep_note,
        rep_type: that.formRepAdd.rep_type,
      };
      that.$axios
        .post("api.php?c=Svnrep&a=CreateRep&t=web", data)
        .then(function (response) {
          that.loadingCreateRep = false;
          var result = response.data;
          if (result.status == 1) {
            that.modalCreateRep = false;
            that.$Message.success(result.message);
            that.GetRepList();
          } else {
            that.$Message.error({ content: result.message, duration: 2 });
          }
        })
        .catch(function (error) {
          that.loadingCreateRep = false;
          console.log(error);
          that.$Message.error(i18n.t('errors.contactAdmin'));
        });
    },

    GetRepList(sync = false, page = true, sync_size = false, sync_rev = false) {
      var that = this;
      that.loadingRep = true;
      that.tableDataRep = [];
      // that.totalRep = 0;
      var data = {
        pageSize: that.pageSizeRep,
        currentPage: that.pageCurrentRep,
        searchKeyword: that.searchKeywordRep,
        sortName: that.sortNameGetRepList,
        sortType: that.sortTypeGetRepList,
        sync: sync,
        page: page,
        sync_size: sync_size,
        sync_rev: sync_rev,
      };
      that.$axios
        .post("api.php?c=Svnrep&a=GetRepList&t=web", data)
        .then(function (response) {
          that.loadingRep = false;
          var result = response.data;
          if (result.status == 1) {
            // that.$Message.success(result.message);
            that.tableDataRep = result.data.data;
            that.totalRep = result.data.total;
          } else {
            that.$Message.error({ content: result.message, duration: 2 });
          }
        })
        .catch(function (error) {
          that.loadingRep = false;
          console.log(error);
          that.$Message.error(i18n.t('errors.contactAdmin'));
        });
    },
    SyncRepSize(rep_name, index) {
      var that = this;
      that.tableDataRep[index].loading_rep_size = true;
      var data = {
        rep_name: rep_name,
      };
      that.$axios
        .post("api.php?c=Svnrep&a=SyncRepSize&t=web", data)
        .then(function (response) {
          that.loadingRep = false;
          that.tableDataRep[index].loading_rep_size = false;
          var result = response.data;
          if (result.status == 1) {
            // that.$Message.success(result.message);
            that.GetRepList();
          } else {
            that.$Message.error({ content: result.message, duration: 2 });
          }
        })
        .catch(function (error) {
          that.tableDataRep[index].loading_rep_size = false;
          console.log(error);
          that.$Message.error(i18n.t('errors.contactAdmin'));
        });
    },
    SyncRepRev(rep_name, index) {
      var that = this;
      that.tableDataRep[index].loading_rep_rev = true;
      var data = {
        rep_name: rep_name,
      };
      that.$axios
        .post("api.php?c=Svnrep&a=SyncRepRev&t=web", data)
        .then(function (response) {
          that.loadingRep = false;
          that.tableDataRep[index].loading_rep_rev = false;
          var result = response.data;
          if (result.status == 1) {
            // that.$Message.success(result.message);
            that.GetRepList();
          } else {
            that.$Message.error({ content: result.message, duration: 2 });
          }
        })
        .catch(function (error) {
          that.tableDataRep[index].loading_rep_rev = false;
          console.log(error);
          that.$Message.error(i18n.t('errors.contactAdmin'));
        });
    },
    /**
     * 所有仓库列表页码改变
     */
    PageChangeRep(value) {
      //设置当前页数
      this.pageCurrentRep = value;
      this.GetRepList();
    },
    /**
     * 所有仓库列表每页数量改变
     */
    PageSizeChangeRep(value) {
      //设置每页条数
      this.pageSizeRep = value;
      this.GetRepList();
    },
    /**
     * 所有仓库排序
     */
    SortChangeRep(value) {
      var sortNameGetRepList;
      try {
        sortNameGetRepList = value.key;
      } catch (error) {
        if (error instanceof TypeError) {
          sortNameGetRepList = value.slot;
        } else {
          throw error;
        }
      }
      // this.sortNameGetRepList =
      //   value.key !== undefined ? value.key : value.slot;
      if (value.order == "desc" || value.order == "asc") {
        this.sortTypeGetRepList = value.order;
      }
      this.GetRepList();
    },

    /**
     * 获取用户仓库列表
     */
    GetSvnUserRepList(sync = false, page = true) {
      var that = this;
      that.loadingUserRep = true;
      that.tableDataUserRep = [];
      that.totalUserRep = 0;
      var data = {
        pageSize: that.pageSizeUserRep,
        currentPage: that.pageCurrentUserRep,
        searchKeyword: that.searchKeywordRep,
        sortType: that.sortTypeGetSvnUserRepList,
        sync: sync,
        page: page,
      };
      that.$axios
        .post("api.php?c=Svnrep&a=GetSvnUserRepList&t=web", data)
        .then(function (response) {
          that.loadingUserRep = false;
          var result = response.data;
          if (result.status == 1) {
            // that.$Message.success(result.message);
            that.tableDataUserRep = result.data.data;
            that.totalUserRep = result.data.total;
            that.enableCheckout = result.data.enableCheckout;
          } else {
            that.$Message.error({ content: result.message, duration: 2 });
          }
        })
        .catch(function (error) {
          that.loadingUserRep = false;
          console.log(error);
          that.$Message.error(i18n.t('errors.contactAdmin'));
        });
    },
    /**
     * 用户仓库列表页码改变
     */
    PageChangeUserRep(value) {
      //设置当前页数
      this.pageCurrentUserRep = value;
      this.GetSvnUserRepList();
    },
    /**
     * 用户仓库每页数量改变
     */
    PageSizeChangeUserRep(value) {
      //设置每页条数
      this.pageSizeUserRep = value;
      this.GetSvnUserRepList();
    },
    /**
     * 用户仓库排序
     */
    SortChangeUserRep(value) {
      if (value.order == "desc" || value.order == "asc") {
        this.sortTypeGetSvnUserRepList = value.order;
      }
      this.GetSvnUserRepList();
    },

    /**
     * 编辑仓库备注信息
     */
    UpdRepNote(index, rep_name) {
      var that = this;
      var data = {
        rep_name: rep_name,
        rep_note: that.tableDataRep[index].rep_note,
      };
      that.$axios
        .post("api.php?c=Svnrep&a=UpdRepNote&t=web", data)
        .then(function (response) {
          var result = response.data;
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
     * 管理人员浏览仓库
     */
    ModalViewRep(rep_name) {
      var that = this;
      //还原表格为空提示内容
      that.noDataTextRepCon = i18n.t('noDataNow'); //"暂无数据";
      //通过按钮点击浏览 初始化路径和仓库名称
      that.currentRepPath = "/";
      that.currentRepName = rep_name;
      //设置标题
      that.titleModalViewRep = i18n.t('repositoryInfo.repoScan') + " - " + rep_name;
      //显示对话框
      that.modalViewRep = true;
      //请求检出地址信息
      that.GetCheckout().then(function (response) {
        //在检出地址的成功回调中开始请求仓库内容
        that.GetRepCon();
      });
    },
    /**
     * 用户浏览仓库
     */
    ModalViewUserRep(rep_name, pri_path) {
      var that = this;
      //还原表格为空提示内容
      that.noDataTextRepCon = i18n.t('noDataNow'); //"暂无数据";
      //通过按钮点击浏览 初始化路径和仓库名称
      that.currentRepPath = pri_path;
      that.currentRepName = rep_name;
      //设置标题
      that.titleModalViewRep = i18n.t('repositoryInfo.repoScan') + " - " + rep_name;
      //显示对话框
      that.modalViewRep = true;
      //请求检出地址信息
      that.GetCheckout().then(function (response) {
        //在检出地址的成功回调中开始请求仓库内容
        if (that.formStatusSubversion.status == true) {
          that.GetUserRepCon();
        } else {
          that.loadingRepCon = false;
          //设置表格提示信息
          that.noDataTextRepCon =
            i18n.t('repositoryInfo.noDataTextRepCon');
          //更新检出地址
          that.tempCheckout =
            that.checkInfo.protocal +
            that.checkInfo.prefix +
            "/" +
            that.currentRepName +
            that.currentRepPath;
        }
      });
    },
    /**
     * 用户通过http提供的能力直接浏览
     */
    ModalViewUserRepRaw(row_url) {
      window.open(row_url, "_blank");
    },
    /**
     * 获取检出地址前缀
     */
    GetCheckout() {
      var that = this;
      //清空之前的检出地址
      that.tempCheckout = "";
      //清空之前的表格内容
      that.tableDataRepCon = [];
      //清空之前的面包屑
      that.breadRepPath = [];
      //重置加载动画
      that.loadingRepCon = true;
      var data = {};
      return new Promise(function (resolve, reject) {
        that.$axios
          .post("api.php?c=Svnrep&a=GetCheckout&t=web", data)
          .then(function (response) {
            var result = response.data;
            if (result.status == 1) {
              that.checkInfo = result.data;
            } else {
              that.loadingRepCon = false;
              that.$Message.error({ content: result.message, duration: 2 });
            }
            resolve(response);
          })
          .catch(function (error) {
            console.log(error);
            that.$Message.error(i18n.t('errors.contactAdmin'));
            reject(error);
          });
      });
    },
    /**
     * 获取仓库内容
     */
    GetRepCon() {
      var that = this;
      that.loadingRepCon = true;
      var data = {
        rep_name: that.currentRepName,
        path: that.currentRepPath,
      };
      that.$axios
        .post("api.php?c=Svnrep&a=GetRepCon&t=web", data)
        .then(function (response) {
          that.loadingRepCon = false;
          var result = response.data;
          if (result.status == 1) {
            that.tableDataRepCon = result.data.data;
            that.breadRepPath = result.data.bread;
            //更新检出地址
            that.tempCheckout =
              that.checkInfo.protocal +
              that.checkInfo.prefix +
              "/" +
              that.currentRepName +
              that.currentRepPath;
          } else {
            that.$Message.error({ content: result.message, duration: 2 });
          }
        })
        .catch(function (error) {
          that.loadingRepCon = false;
          console.log(error);
          that.$Message.error(i18n.t('errors.contactAdmin'));
        });
    },
    /**
     * 获取用户仓库内容
     */
    GetUserRepCon() {
      var that = this;
      that.loadingRepCon = true;
      var data = {
        rep_name: that.currentRepName,
        path: that.currentRepPath,
      };
      that.$axios
        .post("api.php?c=Svnrep&a=GetUserRepCon&t=web", data)
        .then(function (response) {
          that.loadingRepCon = false;
          var result = response.data;
          if (result.status == 1) {
            that.tableDataRepCon = result.data.data;
            that.breadRepPath = result.data.bread;
            //更新检出地址
            that.tempCheckout =
              that.checkInfo.protocal +
              that.checkInfo.prefix +
              "/" +
              that.currentRepName +
              that.currentRepPath;
          } else {
            that.$Message.error({ content: result.message, duration: 2 });
          }
        })
        .catch(function (error) {
          that.loadingRepCon = false;
          console.log(error);
          that.$Message.error(i18n.t('errors.contactAdmin'));
        });
    },
    /**
     * 点击某行获取仓库路径内容
     */
    ClickRowGetRepCon(row, index) {
      if (this.tableDataRepCon[index].resourceType == "2") {
        this.currentRepPath = this.tableDataRepCon[index].fullPath;
        if (this.user_role_id == 1 || this.user_role_id == 3) {
          this.GetRepCon();
        } else if (this.user_role_id == 2) {
          this.GetUserRepCon();
        }
      }
    },
    /**
     * 点击面包屑获取仓库路径内容
     */
    ClickBreadGetRepCon(fullPath) {
      this.currentRepPath = fullPath;
      if (this.user_role_id == 1 || this.user_role_id == 3) {
        this.GetRepCon();
      } else if (this.user_role_id == 2) {
        this.GetUserRepCon();
      }
    },
    /**
     * 复制检出地址
     */
    CopyCheckout() {
      var that = this;
      that.$copyText(that.tempCheckout).then(
        function (e) {
          that.$Message.success(i18n.t('repositoryInfo.copySuccess'));
        },
        function (e) {
          that.$Message.error(i18n.t('repositoryInfo.copyFailed'));
        }
      );
    },
    /**
     * 获取备份文件夹下的文件列表
     */
    GetBackupList() {
      var that = this;
      that.loadingRepBackupList = true;
      that.tableDataBackup = [];
      var data = {};
      that.$axios
        .post("api.php?c=Svnrep&a=GetBackupList&t=web", data)
        .then(function (response) {
          that.loadingRepBackupList = false;
          var result = response.data;
          if (result.status == 1) {
            that.tableDataBackup = result.data;
            for (var i = 0; i < result.data.length; i++) {
              that.loadingLoadBackup[i] = false;
            }
          } else {
            that.$Message.error({ content: result.message, duration: 2 });
          }
        })
        .catch(function (error) {
          that.loadingRepBackupList = false;
          console.log(error);
          that.$Message.error(i18n.t('errors.contactAdmin'));
        });
    },
    //立即备份
    SvnadminDump() {
      var that = this;
      that.loadingRepDump = true;
      var data = {
        rep_name: that.currentRepName,
      };
      that.$axios
        .post("api.php?c=Svnrep&a=SvnadminDump&t=web", data)
        .then(function (response) {
          that.loadingRepDump = false;
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
            that.GetBackupList();
          } else {
            that.$Message.error({ content: result.message, duration: 2 });
          }
        })
        .catch(function (error) {
          that.loadingRepDump = false;
          console.log(error);
          that.$Message.error(i18n.t('errors.contactAdmin'));
        });
    },
    //点击按钮 触发隐藏 input 的 click 事件
    ClickRepUpload() {
      let myfile = document.getElementById("myfile");
      myfile.click();
    },
    //文件上传
    ModalUploadBackup() {
      //重置进度条
      this.file.percent = 0;
      this.file.current = 0;
      //重置允许上传
      this.file.stop = false;

      this.modalRepUpload = true;
      myfile.onchange = () => {
        //重置进度条
        this.file.percent = 0;
        this.file.current = 0;
        //重置允许上传
        this.file.stop = false;

        let myfile = document.getElementById("myfile");
        let file = myfile.files[0];
        this.ChangeUpload(file);
      };
    },
    //文件上传统筹
    async ChangeUpload(file) {
      var that = this;
      //文件大小
      const fileSize = file.size;
      //单个分片大小 1MB
      const chunkSize = 1024 * 1024 * 1;
      //分片数量
      const chunkCount = Math.ceil(fileSize / chunkSize);
      //总进度 = 分片 + 上传合并
      that.file.total = chunkCount * 2;
      // 分片总数
      that.file.chunkCount = chunkCount;
      //文件体积
      that.file.size = that.FormatFileSize(fileSize);
      //文件名
      that.file.name = file.name;
      //获取文件md5
      const md5 = await that.GetFileMd5(file, chunkSize);
      //循环调用上传
      for (var i = 0; i < chunkCount; i++) {
        //分片开始位置
        let start = i * chunkSize;
        //分片结束位置
        let end = Math.min(fileSize, start + chunkSize);
        let _chunkFile = file.slice(start, end);
        let formdata = new FormData();
        formdata.append("file", _chunkFile);
        formdata.append("md5", md5);
        formdata.append("filename", file.name);
        formdata.append("numBlobTotal", chunkCount);
        formdata.append("numBlobCurrent", i + 1);
        formdata.append("deleteOnMerge", that.file.deleteOnMerge);

        if (!that.file.stop) {
          // 通过await实现顺序上传
          await that
            .UploadBackup(formdata)
            .then(function (response) {
              var result = response.data;
              if (result.status == 1) {
                if (result.data.completeCount == that.file.total / 2 - 1) {
                  that.file.desc = i18n.t('repositoryInfo.mergingChunks');
                } else if (result.data.completeCount == that.file.total / 2) {
                  that.file.desc = i18n.t('repositoryInfo.mergeSuccess');
                } else {
                  // that.file.desc = "分片上传中";
                  that.file.desc = `${that.file.chunkCount + i18n.t('repositoryInfo.chunksUploading')}`;
                }
                if (result.data.complete) {
                  //进度条百分比
                  that.file.percent = 100;
                  //剩余时间
                  var formateTime = that.FormatTime(0);
                  that.file.left = `${formateTime[0]}${i18n.t('repositoryInfo.hours')}${formateTime[1]}${i18n.t('repositoryInfo.minutes')}${formateTime[2]}${i18n.t('repositoryInfo.seconds')}`;
                  that.$Message.success(result.message);
                  that.GetBackupList();
                  that.file.stop = true;
                } else {
                  //进度条百分比
                  that.file.current++;
                  that.file.percent = Math.trunc(
                    (that.file.current / that.file.total) * 100
                  );
                  //剩余时间
                  var formateTime = that.FormatTime(
                    that.file.total - that.file.current
                  );
                  that.file.left = `${formateTime[0]}${i18n.t('repositoryInfo.hours')}${formateTime[1]}${i18n.t('repositoryInfo.minutes')}${formateTime[2]}${i18n.t('repositoryInfo.seconds')}`;
                }
              } else {
                that.file.stop = true;
                that.$Message.error({
                  content: result.message,
                  duration: 2,
                });
              }
            })
            .catch(function (error) {
              that.file.stop = true;
              console.log(error);
              that.$Message.error(i18n.t('errors.contactAdmin'));
            });
        }

        if (that.file.stop) {
          break;
        }
      }
    },
    //分片上传接口
    UploadBackup(data) {
      var that = this;
      var config = {
        headers: { "Content-Type": "multipart/form-data" },
      };
      return new Promise(function (resolve, reject) {
        that.$axios
          .post("api.php?c=Svnrep&a=UploadBackup&t=web", data, config)
          .then(function (response) {
            resolve(response);
          })
          .catch(function (error) {
            reject(error);
          });
      });
    },
    //计算md5
    GetFileMd5(file, chunkSize) {
      let that = this;
      return new Promise((resolve, reject) => {
        let blobSlice =
          File.prototype.slice ||
          File.prototype.mozSlice ||
          File.prototype.webkitSlice;
        let chunks = Math.ceil(file.size / chunkSize);
        let currentChunk = 0;
        let spark = new SparkMD5.ArrayBuffer();
        let fileReader = new FileReader();

        fileReader.onload = function (e) {
          spark.append(e.target.result);
          currentChunk++;
          //开关控制
          if (that.file.stop) {
            return;
          }
          if (currentChunk < chunks) {
            loadNext();
          } else {
            // 返回十六进制结果
            let md5 = spark.end();
            resolve(md5);
          }
        };

        fileReader.onerror = function (e) {
          reject(e);
        };

        function loadNext() {
          let start = currentChunk * chunkSize;
          let end = start + chunkSize;
          end > file.size && (end = file.size);
          fileReader.readAsArrayBuffer(blobSlice.call(file, start, end));

          //进度条百分比
          that.file.current++;
          that.file.percent = Math.trunc(
            (that.file.current / that.file.total) * 100
          );
          //剩余时间
          var formateTime = that.FormatTime(
            that.file.total - that.file.current
          );
          that.file.left = `${formateTime[0]}${i18n.t('repositoryInfo.hours')}${formateTime[1]}${i18n.t('repositoryInfo.minutes')}${formateTime[2]}${i18n.t('repositoryInfo.seconds')}`;
          //当前状态
          that.file.desc = `${that.file.chunkCount + i18n.t('repositoryInfo.chunksMd5Calculating')}`;
        }

        loadNext();
      });
    },
    /**
     * 下载备份文件
     */
    DownloadRepBackup(fileUrl) {
      window.open(fileUrl, "_blank");
    },
    /**
     * 删除备份文件
     */
    DelRepBackup(fileName) {
      var that = this;
      that.$Modal.confirm({
        title: i18n.t('repositoryInfo.deleteFile'),
        content: i18n.t('repositoryInfo.deleteFileConfirm'),
        onOk: () => {
          var data = {
            fileName: fileName,
          };
          that.$axios
            .post("api.php?c=Svnrep&a=DelRepBackup&t=web", data)
            .then(function (response) {
              var result = response.data;
              if (result.status == 1) {
                that.$Message.success(result.message);
                that.GetBackupList();
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
     * 仓库权限
     */
    ModalRepPri(rep_name) {
      //通过按钮点击浏览 初始化路径和仓库名称
      this.currentRepPath = "/";
      this.currentRepName = rep_name;
      //显示对话框
      this.modalRepPri = true;
    },

    /**
     * SVN用户配置仓库权限
     */
    ModalRepPriUser(rep_name, svnn_user_pri_path_id, pri_path) {
      //通过按钮点击浏览 初始化路径和仓库名称
      this.currentRepPath = pri_path;
      this.currentRepName = rep_name;
      //SVN用户权限路径id
      this.svnn_user_pri_path_id = svnn_user_pri_path_id;
      //显示对话框
      this.modalRepPri = true;
    },

    /**
     * 仓库钩子
     */
    ModalRepHooks(rep_name) {
      //设置标题
      this.titleModalRepHooks = i18n.t('repositoryInfo.repoHooks') + " - " + rep_name;
      //显示对话框
      this.modalRepHooks = true;
      //设置当前选中仓库
      this.currentRepName = rep_name;
      //请求仓库钩子数据
      this.GetRepHooks();
      //请求常用钩子列表
      this.GetRecommendHooks();
    },
    /**
     * 获取仓库的钩子和对应的内容列表
     */
    GetRepHooks() {
      var that = this;
      that.loadingGetRepHooks = true;
      var data = {
        rep_name: that.currentRepName,
      };
      that.$axios
        .post("api.php?c=Svnrep&a=GetRepHooks&t=web", data)
        .then(function (response) {
          that.loadingGetRepHooks = false;
          var result = response.data;
          if (result.status == 1) {
            that.formRepHooks = result.data;
          } else {
            that.$Message.error({ content: result.message, duration: 2 });
          }
        })
        .catch(function (error) {
          that.loadingGetRepHooks = false;
          console.log(error);
          that.$Message.error(i18n.t('errors.contactAdmin'));
        });
    },
    /**
     * 获取推荐钩子
     */
    GetRecommendHooks() {
      var that = this;
      var data = {};
      that.$axios
        .post("api.php?c=Svnrep&a=GetRecommendHooks&t=web", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.recommendHooks = result.data;
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
     * 移除仓库钩子
     */
    DelRepHook(fileName) {
      var that = this;
      that.loadingGetRepHooks = true;
      var data = {
        rep_name: that.currentRepName,
        fileName: fileName,
      };
      that.$axios
        .post("api.php?c=Svnrep&a=DelRepHook&t=web", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
            that.GetRepHooks();
          } else {
            that.loadingGetRepHooks = false;
            that.$Message.error({ content: result.message, duration: 2 });
          }
        })
        .catch(function (error) {
          that.loadingGetRepHooks = false;
          console.log(error);
          that.$Message.error(i18n.t('errors.contactAdmin'));
        });
    },
    /**
     * 查看钩子模板内容
     */
    ModalStudyRepHook(key) {
      //设置当前选中的钩子文件名称
      this.tempSelectRepHook = this.formRepHooks[key].fileName;
      //设置当前选中的钩子文件模板内容到输入框
      this.tempSelectRepHookTmpl = this.formRepHooks[key].tmpl;
      //设置标题
      this.titleModalStudyRepHook =
        "钩子信息介绍 - " + this.formRepHooks[key].fileName;
      // 展示输入框
      this.modalStudyRepHook = true;
    },
    /**
     * 修改仓库的钩子内容
     */
    ModalEditRepHook(key) {
      //设置当前选中的钩子文件名称
      this.tempSelectRepHook = this.formRepHooks[key].fileName;
      //设置当前选中的钩子文件内容到输入框
      this.tempSelectRepHookCon = this.formRepHooks[key].con;
      //设置标题
      this.titleModalEditRepHook =
        "钩子文件编辑 - " + this.formRepHooks[key].fileName;
      // 展示输入框
      this.modalEditRepHook = true;
    },
    /**
     * 查看推荐仓库钩子内容
     */
    ViewRecommendHook(hookName) {
      var temp = this.recommendHooks.filter(
        (item) => (item.hookName = hookName)
      );
      //设置当前选中的内容到输入框
      this.tempSelectRepHookRecommend = temp[0].hookContent;
      // 展示输入框
      this.modalRecommendHook = true;
    },
    UpdRepHook() {
      var that = this;
      that.loadingEditRepHook = true;
      var data = {
        rep_name: that.currentRepName,
        fileName: that.tempSelectRepHook,
        content: that.tempSelectRepHookCon,
      };
      that.$axios
        .post("api.php?c=Svnrep&a=UpdRepHook&t=web", data)
        .then(function (response) {
          that.loadingEditRepHook = false;
          var result = response.data;
          if (result.status == 1) {
            that.modalEditRepHook = false;
            that.$Message.success(result.message);
            that.GetRepHooks();
          } else {
            that.$Message.error({ content: result.message, duration: 2 });
          }
        })
        .catch(function (error) {
          that.loadingEditRepHook = false;
          console.log(error);
          that.$Message.error(i18n.t('errors.contactAdmin'));
        });
    },
    /**
     * 高级
     */
    ModalRepAdvance(rep_name) {
      //设置当前仓库名称
      this.currentRepName = rep_name;
      //设置标题
      this.titleModalRepAdvance = i18n.t('advance') + " - " + rep_name;
      //显示对话框
      this.modalRepAdvance = true;
      this.ClickTabAdvance(sessionStorage.curTabRepAdvance);
    },
    /**
     * 获取仓库的属性内容（key-vlaue的形式）
     */
    GetRepDetail() {
      var that = this;
      that.loadingRepDetail = true;
      var data = {
        rep_name: that.currentRepName,
      };
      that.$axios
        .post("api.php?c=Svnrep&a=GetRepDetail&t=web", data)
        .then(function (response) {
          that.loadingRepDetail = false;
          var result = response.data;
          if (result.status == 1) {
            that.tableDataRepDetail = result.data;
          } else {
            that.$Message.error({ content: result.message, duration: 2 });
          }
        })
        .catch(function (error) {
          that.loadingRepDetail = false;
          console.log(error);
          that.$Message.error(i18n.t('errors.contactAdmin'));
        });
    },
    /**
     * 复制仓库属性
     */
    CopyRepDetail(index) {
      var that = this;
      var copyContent =
        that.tableDataRepDetail[index].repKey +
        ":" +
        that.tableDataRepDetail[index].repValue;
      that.$copyText(copyContent).then(
        function (e) {
          that.$Message.success(i18n.t("repositoryInfo.copySuccess"));
        },
        function (e) {
          that.$Message.error(i18n.t("repositoryInfo.copyFailed"));
        }
      );
    },
    /**
     * 重设仓库UUID
     */
    ModalSetUUID() {
      //清空
      this.tempRepUUID = "";
      //对话框
      this.modalSetUUID = true;
    },
    SetUUID() {
      var that = this;
      that.loadingSetUUID = true;
      var data = {
        rep_name: that.currentRepName,
        uuid: that.tempRepUUID,
      };
      that.$axios
        .post("api.php?c=Svnrep&a=SetUUID&t=web", data)
        .then(function (response) {
          that.loadingSetUUID = false;
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
            that.GetRepDetail();
            that.modalSetUUID = false;
          } else {
            that.$Message.error({ content: result.message, duration: 2 });
          }
        })
        .catch(function (error) {
          that.loadingSetUUID = false;
          console.log(error);
          that.$Message.error(i18n.t('errors.contactAdmin'));
        });
    },
    //获取php文件上传相关参数
    GetUploadInfo() {
      var that = this;
      var data = {};
      that.$axios
        .post("api.php?c=Svnrep&a=GetUploadInfo&t=web", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.file.on = result.data.upload;
            that.file.chunkSize = result.data.chunkSize;
            that.file.deleteOnMerge = result.data.deleteOnMerge;
          } else {
            that.$Message.error({ content: result.message, duration: 2 });
          }
        })
        .catch(function (error) {
          console.log(error);
          that.$Message.error(i18n.t('errors.contactAdmin'));
        });
    },
    //上传前
    BeforeUpload() {
      this.loadingUploadBackup = true;
      return true;
    },
    //上传成功
    UploadSuccess(res, file, fileList) {
      this.loadingUploadBackup = false;
      var result = res;
      if (result.status == 1) {
        this.$Message.success(result.message);
      } else {
        this.$Message.error({ content: result.message, duration: 2 });
      }
      this.GetBackupList();
    },
    SvnadminLoad(fileName, index) {
      var that = this;
      that.loadingLoadBackup[index] = true;
      that.loadingLoadBackup = JSON.parse(
        JSON.stringify(that.loadingLoadBackup)
      );
      var data = {
        rep_name: that.currentRepName,
        fileName: fileName,
      };
      that.$axios
        .post("api.php?c=Svnrep&a=SvnadminLoad&t=web", data)
        .then(function (response) {
          that.loadingLoadBackup[index] = false;
          that.loadingLoadBackup = JSON.parse(
            JSON.stringify(that.loadingLoadBackup)
          );
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
          } else {
            that.$Message.error({ content: result.message, duration: 2 });
            that.modalRepLoad = true;
            that.tempRepLoadError = result.data;
          }
        })
        .catch(function (error) {
          that.loadingLoadBackup[index] = true;
          that.loadingLoadBackup = JSON.parse(
            JSON.stringify(that.loadingLoadBackup)
          );
          console.log(error);
          that.$Message.error(i18n.t('errors.contactAdmin'));
        });
    },

    /**
     * 编辑仓库名称
     */
    ModalEditRepName(rep_name) {
      //备份旧名称
      this.formRepEdit.old_rep_name = JSON.parse(JSON.stringify(rep_name));
      //设置新名称
      this.formRepEdit.new_rep_name = JSON.parse(JSON.stringify(rep_name));
      //配置标题
      this.titleModalEditRepName = i18n.t('repositoryInfo.modifyRepoName') + " - " + rep_name;
      //显示对话框
      this.modalEditRepName = true;
    },
    UpdRepName() {
      var that = this;
      that.loadingEditRepName = true;
      var data = {
        old_rep_name: that.formRepEdit.old_rep_name,
        new_rep_name: that.formRepEdit.new_rep_name,
      };
      that.$axios
        .post("api.php?c=Svnrep&a=UpdRepName&t=web", data)
        .then(function (response) {
          that.loadingEditRepName = false;
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
            that.modalEditRepName = false;
            that.GetRepList();
          } else {
            that.$Message.error({ content: result.message, duration: 2 });
          }
        })
        .catch(function (error) {
          that.loadingEditRepName = false;
          console.log(error);
          that.$Message.error(i18n.t('errors.contactAdmin'));
        });
    },

    /**
     * 删除仓库
     */
    DelRep(rep_name) {
      var that = this;
      that.$Modal.confirm({
        // title: "删除仓库 - " + rep_name,
        // content:
        //   "确定要删除该仓库吗？<br/>该操作不可逆！<br/>如果该仓库有正在进行的网络传输，可能会删除失败，请注意提示信息！",
        render: (h) => {
          return h("div", [
            h(
              "div",
              {
                class: { "modal-title": true },
                style: {
                  display: "flex",
                  height: "42px",
                  alignItems: "center",
                },
              },
              [
                h("Icon", {
                  props: {
                    type: "ios-help-circle",
                  },
                  style: {
                    width: "28px",
                    height: "28px",
                    fontSize: "28px",
                    color: "#f90",
                  },
                }),
                h(
                  "tooltip",
                  {
                    props: {
                      transfer: true,
                      placement: "bottom",
                      "max-width": "400",
                    },
                  },
                  [
                    h("span", {
                      style: {
                        marginLeft: "12px",
                        fontSize: "16px",
                        color: "#17233d",
                        fontWeight: 500,
                        whiteSpace: "nowrap",
                        overflow: "hidden",
                        textOverflow: "ellipsis",
                        width: "285px",
                        display: "inline-block",
                      },
                      domProps: {
                        innerHTML: i18n.t('repositoryInfo.deleteRepo') + " - " + rep_name,
                      },
                    }),
                    h(
                      "div",
                      {
                        slot: "content",
                        style: {
                          fontSize: "10px",
                        },
                      },
                      [
                        h(
                          "p",
                          {
                            style: {
                              fontSize: "15px",
                            },
                          },
                          i18n.t('repositoryInfo.deleteRepo') + " - " + rep_name
                        ),
                      ]
                    ),
                  ]
                ),
              ]
            ),
            h(
              "div",
              {
                class: { "modal-content": true },
                style: { paddingLeft: "40px" },
              },
              [
                h("p", {
                  style: { marginBottom: "15px" },
                  domProps: {
                    innerHTML:
                      i18n.t('repositoryInfo.deleteRepoConfirm'),
                  },
                }),
              ]
            ),
          ]);
        },
        onOk: () => {
          var data = {
            rep_name: rep_name,
          };
          that.$axios
            .post("api.php?c=Svnrep&a=DelRep&t=web", data)
            .then(function (response) {
              var result = response.data;
              if (result.status == 1) {
                that.$Message.success(result.message);
                that.GetRepList();
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
  },
};
</script>

<style lang="less">
.my-modal {
  // 卡片
  .ivu-card-body {
    padding: 0px 16px 0px 16px;
  }

  // 分割线
  .ivu-divider-inner-text {
    // color: #2db7f5;
    color: #5cadff;
  }
  // 列表
  .ivu-list-split .ivu-list-item {
    border-bottom: 0px;
  }
  .ivu-list-item {
    padding: 2px 0px;
  }
  //列表选项颜色
  .ivu-list-item-meta-title {
    color: #515a6e;
  }
  .ivu-list-item-meta-description {
    // color: #2db7f5;
    color: #ff9900;
  }
  //编辑和移除按钮
  span {
    color: #515a6e;
  }
}
</style>

<style>
</style>