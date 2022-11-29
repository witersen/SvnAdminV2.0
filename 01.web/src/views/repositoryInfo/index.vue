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
            >新建仓库</Button
          >
          <Tooltip
            max-width="250"
            content="同步才可获取最新仓库列表"
            placement="bottom"
            :transfer="true"
          >
            <Button
              icon="ios-sync"
              type="warning"
              ghost
              @click="GetRepList(true)"
              v-if="user_role_id == 1 || user_role_id == 3"
              >同步列表</Button
            >
          </Tooltip>
          <Tooltip
            max-width="250"
            content="同步才可获取最新权限列表"
            placement="bottom"
            :transfer="true"
          >
            <Button
              icon="ios-sync"
              type="warning"
              ghost
              @click="GetSvnUserRepList(true)"
              v-if="user_role_id == 2"
              >同步列表</Button
            >
          </Tooltip>
          <Tooltip
            max-width="450"
            content="不经意的配置可能会导致 authz 配置文件失效
如 svnserve 1.10 版本中为空分组授权仓库可能会导致配置失效等
配置文件失效会导致用户端无法检出、浏览等正常操作
因此可通过此工具在线检测 authz 配置文件有无问题
此功能依赖 svnauthz-validate"
            placement="bottom"
            :transfer="true"
          >
            <Button
              icon="ios-hammer-outline"
              type="error"
              ghost
              @click="CheckAuthz"
              v-if="user_role_id == 1 || user_role_id == 3"
              >authz检测</Button
            >
          </Tooltip>
        </Col>
        <Col :xs="3" :sm="4" :md="5" :lg="6">
          <Input
            search
            enter-button
            placeholder="通过SVN仓库名、备注搜索..."
            @on-search="GetRepList()"
            v-model="searchKeywordRep"
            v-if="user_role_id == 1 || user_role_id == 3"
          />
          <Input
            search
            enter-button
            placeholder="通过SVN仓库名搜索..."
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
        <template slot-scope="{ row, index }" slot="rep_note">
          <Input
            :border="false"
            v-model="tableDataRep[index].rep_note"
            @on-blur="UpdRepNote(index, row.rep_name)"
          />
        </template>
        <template slot-scope="{ row }" slot="repScan">
          <Button type="info" size="small" @click="ModalViewRep(row.rep_name)"
            >浏览</Button
          >
        </template>
        <template slot-scope="{ row }" slot="repBackup">
          <Button type="info" size="small" @click="ModalRepDump(row.rep_name)"
            >管理</Button
          >
        </template>
        <template slot-scope="{ row }" slot="repPri">
          <Button type="info" size="small" @click="ModalRepPri(row.rep_name)"
            >配置</Button
          >
        </template>
        <template slot-scope="{ row }" slot="repHooks">
          <Button type="info" size="small" @click="ModalRepHooks(row.rep_name)"
            >编辑</Button
          >
        </template>
        <template slot-scope="{ row }" slot="action">
          <Button
            type="success"
            size="small"
            @click="ModalRepAdvance(row.rep_name)"
            >高级</Button
          >
          <Button
            type="warning"
            size="small"
            @click="ModalEditRepName(row.rep_name)"
            >修改</Button
          >
          <Button type="error" size="small" @click="DelRep(row.rep_name)"
            >删除</Button
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
            >配置</Button
          >
        </template>
        <template slot-scope="{ row }" slot="action">
          <Button
            type="info"
            size="small"
            @click="ModalViewUserRep(row.rep_name, row.pri_path)"
            >浏览</Button
          >
          <Button
            type="info"
            size="small"
            v-if="enableCheckout == 'http'"
            @click="ModalViewUserRepRaw(row.raw_url)"
            >原生浏览</Button
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
    <Modal v-model="modalCreateRep" :draggable="true" title="新建SVN仓库">
      <Form :model="formRepAdd" :label-width="80">
        <FormItem label="仓库名称">
          <Input v-model="formRepAdd.rep_name"></Input>
        </FormItem>
        <FormItem>
          <Alert type="warning" show-icon
            >仓库名称只能包含中文、字母、数字、破折号、下划线、点，不能以点开头或结尾</Alert
          >
        </FormItem>
        <FormItem label="备注信息">
          <Input v-model="formRepAdd.rep_note"></Input>
        </FormItem>
        <FormItem label="仓库类型">
          <RadioGroup vertical v-model="formRepAdd.rep_type">
            <Radio label="1">
              <Icon type="social-apple"></Icon>
              <span>空仓库</span>
            </Radio>
            <Radio label="2">
              <Icon type="social-android"></Icon>
              <span>指定结构的仓库(包含 "trunk" "branches" "tags" 文件夹)</span>
            </Radio>
          </RadioGroup>
        </FormItem>
        <FormItem>
          <Button type="primary" @click="CreateRep" :loading="loadingCreateRep"
            >确定</Button
          >
        </FormItem>
      </Form>
      <div slot="footer">
        <Button type="primary" ghost @click="modalCreateRep = false"
          >取消</Button
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
                >复制</Button
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
        <Button type="primary" ghost @click="modalViewRep = false">取消</Button>
      </div>
    </Modal>
    <!-- 对话框-备份仓库 -->
    <Modal
      v-model="modalRepDump"
      :draggable="true"
      :title="titleModalRepBackup"
    >
      <Row style="margin-bottom: 15px">
        <Col span="18">
          <Button
            type="primary"
            ghost
            @click="SvnadminDump"
            :loading="loadingRepDump"
            >备份(dump)</Button
          >
        </Col>
      </Row>
      <Table
        height="200"
        border
        :columns="tableColumnBackup2"
        :data="tableDataBackup"
        size="small"
        :loading="loadingRepBackupList"
      >
        <template slot-scope="{ row }" slot="action">
          <Button
            type="success"
            size="small"
            @click="DownloadRepBackup(row.fileUrl)"
            >下载</Button
          >
          <Button type="error" size="small" @click="DelRepBackup(row.fileName)"
            >删除</Button
          >
        </template>
      </Table>
      <div slot="footer">
        <Button type="primary" ghost @click="modalRepDump = false">取消</Button>
      </div>
    </Modal>
    <!-- 对话框-仓库钩子 -->
    <Modal
      v-model="modalRepHooks"
      :title="titleModalRepHooks"
      class-name="hooks"
      :draggable="true"
    >
      <Tabs type="card">
        <TabPane label="仓库钩子">
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
                    <span @click="ModalStudyRepHook('start_commit')">介绍</span>
                  </li>
                  <li>
                    <span @click="ModalEditRepHook('start_commit')">编辑</span>
                  </li>
                  <li>
                    <span
                      @click="DelRepHook(formRepHooks.start_commit.fileName)"
                      >移除</span
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
                    <span @click="ModalStudyRepHook('pre_commit')">介绍</span>
                  </li>
                  <li>
                    <span @click="ModalEditRepHook('pre_commit')">编辑</span>
                  </li>
                  <li>
                    <span @click="DelRepHook(formRepHooks.pre_commit.fileName)"
                      >移除</span
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
                    <span @click="ModalStudyRepHook('post_commit')">介绍</span>
                  </li>
                  <li>
                    <span @click="ModalEditRepHook('post_commit')">编辑</span>
                  </li>
                  <li>
                    <span @click="DelRepHook(formRepHooks.post_commit.fileName)"
                      >移除</span
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
                    <span @click="ModalStudyRepHook('pre_lock')">介绍</span>
                  </li>
                  <li>
                    <span @click="ModalEditRepHook('pre_lock')">编辑</span>
                  </li>
                  <li>
                    <span @click="DelRepHook(formRepHooks.pre_lock.fileName)"
                      >移除</span
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
                    <span @click="ModalStudyRepHook('post_lock')">介绍</span>
                  </li>
                  <li>
                    <span @click="ModalEditRepHook('post_lock')">编辑</span>
                  </li>
                  <li>
                    <span @click="DelRepHook(formRepHooks.post_lock.fileName)"
                      >移除</span
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
                    <span @click="ModalStudyRepHook('pre_unlock')">介绍</span>
                  </li>
                  <li>
                    <span @click="ModalEditRepHook('pre_unlock')">编辑</span>
                  </li>
                  <li>
                    <span @click="DelRepHook(formRepHooks.pre_unlock.fileName)"
                      >移除</span
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
                    <span @click="ModalStudyRepHook('post_unlock')">介绍</span>
                  </li>
                  <li>
                    <span @click="ModalEditRepHook('post_unlock')">编辑</span>
                  </li>
                  <li>
                    <span @click="DelRepHook(formRepHooks.post_unlock.fileName)"
                      >移除</span
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
                      >介绍</span
                    >
                  </li>
                  <li>
                    <span @click="ModalEditRepHook('pre_revprop_change')"
                      >编辑</span
                    >
                  </li>
                  <li>
                    <span
                      @click="
                        DelRepHook(formRepHooks.pre_revprop_change.fileName)
                      "
                      >移除</span
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
                      >介绍</span
                    >
                  </li>
                  <li>
                    <span @click="ModalEditRepHook('post_revprop_change')"
                      >编辑</span
                    >
                  </li>
                  <li>
                    <span
                      @click="
                        DelRepHook(formRepHooks.post_revprop_change.fileName)
                      "
                      >移除</span
                    >
                  </li>
                </template>
              </ListItem>
            </List>
            <!-- </Scroll> -->
          </Card>
          <Spin size="large" fix v-if="loadingGetRepHooks"></Spin>
        </TabPane>
        <TabPane label="常用钩子">
          <Alert
            >如需将自己常用的钩子显示在此处<br /><br />
            以新增 pre-commit 功能为例，操作步骤如下：<br /><br />
            1、在 /home/svnadmin/hooks/ 目录下创建任意名称的文件夹<br />
            2、创建文件 hookDescription 并写入此钩子的主要功能描述<br />
            3、创建文件 hookName 并写入钩子的类型 pre-commit<br />
            4、创建文件 pre-commit 并写入钩子内容<br />
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
                    <span @click="ViewRecommendHook(item.hookName)">查看</span>
                  </li>
                </template>
              </ListItem>
            </List>
          </Scroll>
        </TabPane>
      </Tabs>
      <div slot="footer">
        <Button type="primary" ghost @click="modalRepHooks = false"
          >取消</Button
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
          >取消</Button
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
        placeholder="具体介绍和语法可看钩子介绍"
      />
      <div slot="footer">
        <Button type="primary" @click="UpdRepHook" :loading="loadingEditRepHook"
          >应用</Button
        >
      </div>
    </Modal>
    <!-- 对话框-常用钩子 -->
    <Modal v-model="modalRecommendHook" :draggable="true" title="常用钩子">
      <Input
        v-model="tempSelectRepHookRecommend"
        readonly
        :rows="15"
        show-word-limit
        type="textarea"
      />
      <div slot="footer">
        <Button type="primary" ghost @click="modalRecommendHook = false"
          >取消</Button
        >
      </div>
    </Modal>
    <!-- 对话框-高级 -->
    <Modal
      v-model="modalRepAdvance"
      :draggable="true"
      :title="titleModalRepAdvance"
    >
      <Tabs type="card">
        <TabPane label="属性">
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
                >重设</Button
              >
            </template>
          </Table>
        </TabPane>
        <TabPane label="恢复">
          <Alert>可以将通过svnadmin dump方式生成的备份文件导入到当前仓库</Alert>
          <Form :model="formRepImport" :label-width="100">
            <FormItem label="备份文件位置">
              <RadioGroup
                vertical
                @on-change="ChangeRadioUploadType"
                v-model="formUploadBackup.selectType"
              >
                <Radio label="1">
                  <span>从本地上传</span>
                </Radio>
                <Alert type="warning" show-icon
                  >1、大文件建议通过FTP等方式上传<br />
                  2、PHP上传限制参数如下：<br /><br />
                  file_uploads：{{
                    uploadLimit.file_uploads == true ? "开启" : "关闭"
                  }}<br />
                  upload_max_filesize：{{ uploadLimit.upload_max_filesize
                  }}<br />
                  post_max_size：{{ uploadLimit.post_max_size }}<br /><br />
                  3、还要注意web服务器的限制<br /><br />
                  如Nginx需考虑 client_max_body_size 等参数
                </Alert>
                <Radio label="2">
                  <span>从服务器选择</span>
                </Radio>
              </RadioGroup>
            </FormItem>
            <FormItem v-if="formUploadBackup.selectType == '1'">
              <Upload
                multiple
                :on-success="UploadSuccess"
                :before-upload="BeforeUpload"
                action="/api.php?c=Svnrep&a=UploadBackup&t=web"
                name="file"
                :headers="{ token: token }"
              >
                <Button
                  icon="ios-cloud-upload-outline"
                  :loading="loadingUploadBackup"
                  >上传文件</Button
                >
              </Upload>
            </FormItem>
            <FormItem
              label="备份文件夹"
              v-if="formUploadBackup.selectType == '2'"
            >
              <Table
                height="200"
                border
                highlight-row
                :loading="loadingRepBackupList"
                :columns="tableColumnBackup1"
                :data="tableDataBackup"
                size="small"
                @on-row-click="ClickRowUploadBackup"
              ></Table>
            </FormItem>
            <FormItem label="已选择" v-if="formUploadBackup.selectType == '2'">
              <Input readonly v-model="formUploadBackup.fileName"></Input>
            </FormItem>
            <FormItem
              label="执行结果"
              v-if="formUploadBackup.selectType == '2'"
            >
              <Input
                readonly
                type="textarea"
                :rows="4"
                placeholder="如果导入失败 错误信息会显示在此处"
                v-model="formUploadBackup.errorInfo"
              ></Input>
            </FormItem>
            <FormItem v-if="formUploadBackup.selectType == '2'">
              <Alert type="warning" show-icon
                >不了解svnadmin
                dump指令的用户建议将备份文件只导入到空仓库而不是已经包含修订版本的非空仓库</Alert
              >
              <Button
                type="primary"
                :loading="loadingImportBackup"
                @click="SvnadminLoad"
                ghost
                >导入</Button
              >
            </FormItem>
          </Form>
        </TabPane>
        <!-- <TabPane label="从仓库导出权限">
          <Alert
            >即：将本仓库原有配置的权限导入到本系统<br /><br />
            由于本系统采用多仓库对应一个配置文件的形式，而不是传统的一个仓库对应一个配置文件的形式<br /><br />
            因此您将之前的仓库通过本系统管理后，原有仓库的人员和分组权限信息不再生效，但是他们依然存在于配置文件中<br /><br />
            因此您可通过识别功能将原来的人员和分组信息识别并导入到本系统以使原仓库的角色配置信息依然生效<br /><br />
            此处只支持识别 authz 和 未加密的 passwd 文件
          </Alert>
        </TabPane> -->
        <!-- <TabPane label="向仓库导入权限">
          <Alert>
            即：将本系统配置的角色和分组信息单独写入到本仓库中<br /><br />
            由于本系统采用多仓库对应一个配置文件的形式，而不是传统的一个仓库对应一个配置文件的形式<br /><br />
            因此本操作适合于您不再使用本系统管理仓库并想回归一个仓库对应一个配置文件的形式
            此处支持将内容写入到 authz 和 passwd 文件
          </Alert>
        </TabPane> -->
      </Tabs>
      <div slot="footer">
        <Button type="primary" ghost @click="modalRepAdvance = false"
          >取消</Button
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
        <FormItem label="仓库名称">
          <Input v-model="formRepEdit.new_rep_name"></Input>
        </FormItem>
        <FormItem>
          <Button
            type="primary"
            :loading="loadingEditRepName"
            @click="UpdRepName"
            >确定</Button
          >
        </FormItem>
      </Form>
      <div slot="footer">
        <Button type="primary" ghost @click="modalEditRepName = false"
          >取消</Button
        >
      </div>
    </Modal>
    <!-- 对话框-重设仓库UUID -->
    <Modal v-model="modalSetUUID" :draggable="true" title="重设仓库UUID">
      <Form :label-width="80" @submit.native.prevent>
        <FormItem label="UUID">
          <Input
            v-model="tempRepUUID"
            placeholder="不填写则自动生成全新UUID"
          ></Input>
        </FormItem>
        <FormItem>
          <Button type="primary" :loading="loadingSetUUID" @click="SetUUID"
            >确定</Button
          >
        </FormItem>
      </Form>
      <div slot="footer">
        <Button type="primary" ghost @click="modalSetUUID = false">取消</Button>
      </div>
    </Modal>
    <!-- 对话框-authz检测结果 -->
    <Modal v-model="modalValidateAuthz" title="authz检测结果">
      <Input
        v-model="tempmodalValidateAuthz"
        readonly
        :rows="15"
        show-word-limit
        type="textarea"
      />
      <div slot="footer">
        <Button type="primary" ghost @click="modalValidateAuthz = false"
          >取消</Button
        >
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
      //仓库备份
      modalRepDump: false,
      //仓库钩子配置
      modalRepHooks: false,
      //高级
      modalRepAdvance: false,
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
       * 表格无数据提示
       */
      noDataTextRepCon: "暂无数据",

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
      loadingImportBackup: false,
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
      //仓库备份
      titleModalRepBackup: "",
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
      //上传限制
      uploadLimit: {
        file_uploads: true,
        upload_max_filesize: 0,
        post_max_size: 0,
      },
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
      //导入仓库
      formRepImport: {},
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
      //上传状态
      formUploadBackup: {
        selectType: "1",
        fileName: "",
        errorInfo: "",
      },

      /**
       * 浏览仓库面包屑
       */
      breadRepPath: [],

      //常用钩子列表
      recommendHooks: [],

      /**
       * 表格
       */
      //所有仓库
      tableColumnRep: [
        {
          title: "序号",
          slot: "index",
          fixed: "left",
          minWidth: 80,
        },
        {
          title: "仓库名",
          key: "rep_name",
          tooltip: true,
          sortable: "custom",
          minWidth: 120,
        },
        {
          title: "版本数",
          key: "rep_rev",
          sortable: "custom",
          minWidth: 90,
        },
        {
          title: "体积",
          key: "rep_size",
          sortable: "custom",
          minWidth: 120,
        },
        {
          title: "备注信息",
          slot: "rep_note",
          minWidth: 120,
        },
        {
          title: "仓库内容",
          slot: "repScan",
          minWidth: 120,
        },
        {
          title: "仓库备份",
          slot: "repBackup",
          minWidth: 120,
        },
        {
          title: "仓库权限",
          slot: "repPri",
          minWidth: 120,
        },
        {
          title: "仓库钩子",
          slot: "repHooks",
          width: 120,
        },
        {
          title: "其它",
          slot: "action",
          width: 180,
          // fixed:"right"
        },
      ],
      tableDataRep: [],
      //SVN用户仓库
      tableColumnUserRep: [
        {
          title: "序号",
          slot: "index",
          fixed: "left",
          minWidth: 80,
        },
        {
          title: "仓库名",
          key: "rep_name",
          tooltip: true,
          sortable: "custom",
          minWidth: 120,
        },
        {
          title: "路径/文件",
          tooltip: true,
          key: "pri_path",
          minWidth: 120,
        },
        {
          title: "权限",
          key: "rep_pri",
          minWidth: 120,
        },
        {
          title: "二次授权",
          slot: "second_pri",
          minWidth: 120,
        },
        {
          title: "其它",
          slot: "action",
          width: 180,
          // fixed:"right"
        },
      ],
      tableDataUserRep: [],
      //仓库内容浏览
      tableColumnRepCon: [
        {
          title: "类型",
          slot: "resourceType",
          width: 60,
        },
        {
          title: "文件",
          key: "resourceName",
          tooltip: true,
        },
        {
          title: "体积",
          key: "fileSize",
          tooltip: true,
        },
        {
          title: "作者",
          key: "revAuthor",
          tooltip: true,
        },
        {
          title: "版本",
          key: "revNum",
          tooltip: true,
        },
        {
          title: "日期",
          key: "revTime",
          tooltip: true,
          width: 350,
        },
        {
          title: "日志",
          key: "revLog",
          tooltip: true,
        },
      ],
      tableDataRepCon: [],
      //备份文件夹
      //导入仓库文件浏览用
      tableColumnBackup1: [
        {
          title: "文件名",
          key: "fileName",
          tooltip: true,
        },
        {
          title: "文件大小",
          key: "fileSize",
          tooltip: true,
        },
        {
          title: "修改时间",
          key: "fileEditTime",
          tooltip: true,
        },
      ],
      //仓库备份管理用
      tableColumnBackup2: [
        {
          title: "文件名",
          key: "fileName",
          tooltip: true,
        },
        {
          title: "大小",
          key: "fileSize",
          tooltip: true,
        },
        {
          title: "修改时间",
          key: "fileEditTime",
          tooltip: true,
        },
        {
          title: "其它",
          slot: "action",
          width: 130,
        },
      ],
      tableDataBackup: [],
      //某节点的用户权限
      tableColumnRepPathUserPri: [
        {
          title: "用户名",
          key: "userName",
        },
        {
          title: "权限",
          key: "userPri",
        },
      ],
      tableDataRepPathUserPri: [],
      //某节点的分组权限
      tableColumnRepPathGroupPri: [
        {
          title: "分组名",
          key: "groupName",
        },
        {
          title: "权限",
          key: "groupPri",
        },
      ],
      tableDataRepPathGroupPri: [],

      //仓库的详细信息 uuid等
      tableColumnRepDetail: [
        {
          title: "属性",
          key: "repKey",
          tooltip: true,
          fixed: "left",
          width: 170,
          // width:80
        },
        {
          title: "信息",
          key: "repValue",
          tooltip: true,
          width: 170,
        },
        {
          title: "复制",
          slot: "copy",
          width: 60,
        },
        {
          title: "重设",
          slot: "uuid",
        },
      ],
      tableDataRepDetail: [],
    };
  },
  components: {
    ModalRepPri,
  },
  computed: {},
  created() {},
  mounted() {
    this.GetSvnserveStatus();
    if (this.user_role_id == 1 || this.user_role_id == 3) {
      this.GetRepList();
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
     * 获取svnserve运行状态
     */
    GetSvnserveStatus() {
      var that = this;
      var data = {};
      that.$axios
        .post("/api.php?c=Svnrep&a=GetSvnserveStatus&t=web", data)
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
          that.$Message.error("出错了 请联系管理员！");
        });
    },

    /**
     * 检测 authz 文件状态
     */
    CheckAuthz() {
      var that = this;
      var data = {};
      that.$axios
        .post("/api.php?c=Svnrep&a=CheckAuthz&t=web", data)
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
          that.$Message.error("出错了 请联系管理员！");
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
        .post("/api.php?c=Svnrep&a=CreateRep&t=web", data)
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
          that.$Message.error("出错了 请联系管理员！");
        });
    },

    GetRepList(sync = false, page = true) {
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
      };
      that.$axios
        .post("/api.php?c=Svnrep&a=GetRepList&t=web", data)
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
          that.$Message.error("出错了 请联系管理员！");
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
      this.sortNameGetRepList = value.key;
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
        .post("/api.php?c=Svnrep&a=GetSvnUserRepList&t=web", data)
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
          that.$Message.error("出错了 请联系管理员！");
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
        .post("/api.php?c=Svnrep&a=UpdRepNote&t=web", data)
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
          that.$Message.error("出错了 请联系管理员！");
        });
    },

    /**
     * 管理人员浏览仓库
     */
    ModalViewRep(rep_name) {
      var that = this;
      //还原表格为空提示内容
      that.noDataTextRepCon = "暂无数据";
      //通过按钮点击浏览 初始化路径和仓库名称
      that.currentRepPath = "/";
      that.currentRepName = rep_name;
      //设置标题
      that.titleModalViewRep = "仓库内容 - " + rep_name;
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
      that.noDataTextRepCon = "暂无数据";
      //通过按钮点击浏览 初始化路径和仓库名称
      that.currentRepPath = pri_path;
      that.currentRepName = rep_name;
      //设置标题
      that.titleModalViewRep = "仓库内容 - " + rep_name;
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
            "由于svnserve服务未启动，SVN用户只能复制检出地址而不能进行仓库内容浏览";
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
          .post("/api.php?c=Svnrep&a=GetCheckout&t=web", data)
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
            that.$Message.error("出错了 请联系管理员！");
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
        .post("/api.php?c=Svnrep&a=GetRepCon&t=web", data)
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
          that.$Message.error("出错了 请联系管理员！");
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
        .post("/api.php?c=Svnrep&a=GetUserRepCon&t=web", data)
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
          that.$Message.error("出错了 请联系管理员！");
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
          that.$Message.success("复制成功");
        },
        function (e) {
          that.$Message.error("复制失败，请手动复制");
        }
      );
    },

    /**
     * 备份仓库
     */
    ModalRepDump(rep_name) {
      //设置标题
      this.titleModalRepBackup = "仓库备份 - " + rep_name;
      //显示对话框
      this.modalRepDump = true;
      //设置当前选中的仓库名
      this.currentRepName = rep_name;
      //请求数据
      this.GetBackupList();
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
        .post("/api.php?c=Svnrep&a=GetBackupList&t=web", data)
        .then(function (response) {
          that.loadingRepBackupList = false;
          var result = response.data;
          if (result.status == 1) {
            that.tableDataBackup = result.data;
          } else {
            that.$Message.error({ content: result.message, duration: 2 });
          }
        })
        .catch(function (error) {
          that.loadingRepBackupList = false;
          console.log(error);
          that.$Message.error("出错了 请联系管理员！");
        });
    },
    SvnadminDump() {
      var that = this;
      that.loadingRepDump = true;
      var data = {
        rep_name: that.currentRepName,
      };
      that.$axios
        .post("/api.php?c=Svnrep&a=SvnadminDump&t=web", data)
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
          that.$Message.error("出错了 请联系管理员！");
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
        title: "删除文件",
        content: "确定要删除该文件吗？<br/>该操作不可逆！",
        onOk: () => {
          var data = {
            fileName: fileName,
          };
          that.$axios
            .post("/api.php?c=Svnrep&a=DelRepBackup&t=web", data)
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
              that.$Message.error("出错了 请联系管理员！");
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
      this.titleModalRepHooks = "仓库钩子 - " + rep_name;
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
        .post("/api.php?c=Svnrep&a=GetRepHooks&t=web", data)
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
          that.$Message.error("出错了 请联系管理员！");
        });
    },
    /**
     * 获取推荐钩子
     */
    GetRecommendHooks() {
      var that = this;
      var data = {};
      that.$axios
        .post("/api.php?c=Svnrep&a=GetRecommendHooks&t=web", data)
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
          that.$Message.error("出错了 请联系管理员！");
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
        .post("/api.php?c=Svnrep&a=DelRepHook&t=web", data)
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
          that.$Message.error("出错了 请联系管理员！");
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
        .post("/api.php?c=Svnrep&a=UpdRepHook&t=web", data)
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
          that.$Message.error("出错了 请联系管理员！");
        });
    },
    /**
     * 高级
     */
    ModalRepAdvance(rep_name) {
      //设置当前仓库名称
      this.currentRepName = rep_name;
      //设置标题
      this.titleModalRepAdvance = "高级 - " + rep_name;
      //重置
      this.formUploadBackup.selectType = "1";
      this.formUploadBackup.fileName = "";
      this.formUploadBackup.errorInfo = "";
      //显示对话框
      this.modalRepAdvance = true;
      //请求数据
      this.GetRepDetail();
      //获取上传限制
      this.GetUploadSize();
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
        .post("/api.php?c=Svnrep&a=GetRepDetail&t=web", data)
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
          that.$Message.error("出错了 请联系管理员！");
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
          that.$Message.success("复制成功");
        },
        function (e) {
          that.$Message.error("复制失败，请手动复制");
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
        .post("/api.php?c=Svnrep&a=SetUUID&t=web", data)
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
          that.$Message.error("出错了 请联系管理员！");
        });
    },
    /**
     * 单选按钮 选择导入
     */
    ChangeRadioUploadType(value) {
      this.formUploadBackup.selectType = value;
      if (value == "1") {
        //获取php上传限制信息
        this.GetUploadSize();
      } else if (value == "2") {
        this.GetBackupList();
      }
    },
    //获取上传限制
    GetUploadSize() {
      var that = this;
      var data = {};
      that.$axios
        .post("/api.php?c=Svnrep&a=GetUploadLimit&t=web", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.uploadLimit = result.data;
          } else {
            that.$Message.error({ content: result.message, duration: 2 });
          }
        })
        .catch(function (error) {
          console.log(error);
          that.$Message.error("出错了 请联系管理员！");
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
    },
    /**
     * 选中文件进行导入
     */
    ClickRowUploadBackup(currentRow, oldCurrentRow) {
      //当前选中的文件
      this.formUploadBackup.fileName = currentRow.fileName;
    },
    SvnadminLoad() {
      var that = this;
      if (that.formUploadBackup.fileName == "") {
        that.$Message.error("请先选择文件");
        return;
      }
      that.loadingImportBackup = true;
      var data = {
        rep_name: that.currentRepName,
        fileName: that.formUploadBackup.fileName,
      };
      that.$axios
        .post("/api.php?c=Svnrep&a=SvnadminLoad&t=web", data)
        .then(function (response) {
          that.loadingImportBackup = false;
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
            that.formUploadBackup.errorInfo = result.data;
          } else {
            that.$Message.error({ content: result.message, duration: 2 });
            that.formUploadBackup.errorInfo = result.data;
          }
        })
        .catch(function (error) {
          that.loadingImportBackup = false;
          console.log(error);
          that.$Message.error("出错了 请联系管理员！");
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
      this.titleModalEditRepName = "修改仓库名称 - " + rep_name;
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
        .post("/api.php?c=Svnrep&a=UpdRepName&t=web", data)
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
          that.$Message.error("出错了 请联系管理员！");
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
                        innerHTML: "删除仓库 - " + rep_name,
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
                          "删除仓库 - " + rep_name
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
                      "确定要删除该仓库吗？<br/>该操作不可逆！<br/>如果该仓库有正在进行的网络传输，可能会删除失败，请注意提示信息！",
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
            .post("/api.php?c=Svnrep&a=DelRep&t=web", data)
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
              that.$Message.error("出错了 请联系管理员！");
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