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
            v-if="user_role_id == 1"
            >新建SVN仓库</Button
          >
        </Col>
        <Col :xs="3" :sm="4" :md="5" :lg="6">
          <Input
            search
            enter-button
            placeholder="通过SVN仓库名、备注搜索..."
            @on-search="SearchGetRepList"
            v-model="searchKeywordRep"
            v-if="user_role_id == 1"
          />
          <Input
            search
            enter-button
            placeholder="通过SVN仓库名搜索..."
            @on-search="GetSvnUserRepList"
            v-model="searchKeywordRep"
            v-if="user_role_id == 2"
          />
        </Col>
      </Row>
      <!-- 管理人员仓库列表 -->
      <Table
        v-if="user_role_id == 1"
        @on-sort-change="SortChangeRep"
        border
        :loading="loadingRep"
        :columns="tableColumnRep"
        :data="tableDataRep"
        size="small"
      >
        <template slot-scope="{ row, index }" slot="rep_note">
          <Input
            :border="false"
            v-model="tableDataRep[index].rep_note"
            @on-blur="EditRepNote(index, row.rep_name)"
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
        <template slot-scope="{ row }" slot="action">
          <Button
            type="info"
            size="small"
            @click="ModalViewUserRep(row.rep_name, row.pri_path)"
            >浏览</Button
          >
        </template>
      </Table>
      <!-- 管理人员SVN仓库分页 -->
      <Card :bordered="false" :dis-hover="true" v-if="user_role_id == 1">
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
    <Modal v-model="modalCreateRep" title="新建SVN仓库">
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
        <Col span="16">
          <Breadcrumb>
            <BreadcrumbItem
              v-for="(item, index) in breadRepPath.name"
              :key="index"
              @click.native="ClickBreadGetRepCon(breadRepPath.path[index])"
              >{{ item }}</BreadcrumbItem
            >
          </Breadcrumb>
        </Col>
        <Col span="8">
          <Input readonly v-model="tempCheckout">
            <Button slot="append" icon="md-copy" @click="CopyCheckout"
              >复制</Button
            >
          </Input>
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
    <Modal v-model="modalRepDump" :title="titleModalRepBackup">
      <Row style="margin-bottom: 15px">
        <Col span="18">
          <Button
            type="primary"
            ghost
            @click="RepDump"
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
            @click="DownloadRepBackup(row.fileName)"
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
    <!-- 对话框-仓库权限 -->
    <Modal v-model="modalRepPri" :title="titleModalRepPri" fullscreen>
      <Row type="flex" justify="center" :gutter="16">
        <Col span="11">
          <Scroll :height="550">
            <Tree
              :data="treeRep"
              :load-data="LoadingRepTree"
              :render="RenderContent"
              @on-select-change="ChangeSelectTreeNode"
            ></Tree>
            <Spin size="large" fix v-if="loadingRepTree"></Spin>
          </Scroll>
        </Col>
        <Col span="11">
          <Card :bordered="true" :dis-hover="true" style="height: 550px">
            <Tabs type="card">
              <TabPane label="用户">
                <Form :label-width="60">
                  <FormItem label="信息">
                    <Table
                      highlight-row
                      border
                      :height="200"
                      size="small"
                      :columns="tableColumnRepPathUserPri"
                      :data="tableDataRepPathUserPri"
                      :loading="loadingRepPathUserPri"
                      @on-current-change="ChangeSelectRepUserPri"
                    ></Table>
                  </FormItem>
                  <FormItem label="操作">
                    <ButtonGroup>
                      <Button icon="ios-add" @click="ModalRepAllUser"></Button>
                      <Button
                        icon="ios-remove"
                        :loading="loadingDelRepPathUserPri"
                        @click="DelRepPathUserPri"
                      ></Button>
                    </ButtonGroup>
                  </FormItem>
                  <FormItem label="权限">
                    <RadioGroup
                      vertical
                      v-model="radioRepUserPri"
                      @on-change="ChangeRadioRepUserPri"
                    >
                      <Radio label="no">
                        <span>无权限</span>
                      </Radio>
                      <Radio label="r">
                        <span>只读</span>
                      </Radio>
                      <Radio label="rw">
                        <span>读写</span>
                      </Radio>
                    </RadioGroup>
                  </FormItem>
                </Form>
              </TabPane>
              <TabPane label="分组">
                <Form :label-width="60">
                  <FormItem label="信息">
                    <Table
                      highlight-row
                      border
                      :height="200"
                      size="small"
                      :columns="tableColumnRepPathGroupPri"
                      :data="tableDataRepPathGroupPri"
                      :loading="loadingRepPathGroupPri"
                      @on-current-change="ChangeSelectRepGroupPri"
                    ></Table>
                  </FormItem>
                  <FormItem label="操作">
                    <ButtonGroup>
                      <Button icon="ios-add" @click="ModalRepAllGroup"></Button>
                      <Button
                        icon="ios-remove"
                        @click="DelRepPathGroupPri"
                        :loading="loadingDelRepPathGroupPri"
                      ></Button>
                    </ButtonGroup>
                  </FormItem>
                  <FormItem label="权限">
                    <RadioGroup
                      vertical
                      v-model="radioRepGroupPri"
                      @on-change="ChangeRadioRepGroupPri"
                    >
                      <Radio label="no">
                        <span>无权限</span>
                      </Radio>
                      <Radio label="r">
                        <span>只读</span>
                      </Radio>
                      <Radio label="rw">
                        <span>读写</span>
                      </Radio>
                    </RadioGroup>
                  </FormItem>
                </Form>
              </TabPane>
            </Tabs>
          </Card>
        </Col>
      </Row>
      <div slot="footer">
        <Button type="primary" ghost @click="modalRepPri = false">取消</Button>
      </div>
    </Modal>
    <!-- 对话框-钩子配置 -->
    <Modal
      v-model="modalRepHooks"
      :title="titleModalRepHooks"
      class-name="hooks"
    >
      <Tabs type="card">
        <TabPane label="仓库钩子">
          <Card :bordered="false" :dis-hover="true" class="my-modal">
            <Scroll>
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
                      <span @click="ModalStudyRepHook('start_commit')"
                        >介绍</span
                      >
                    </li>
                    <li>
                      <span @click="ModalEditRepHook('start_commit')"
                        >编辑</span
                      >
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
                      <span
                        @click="DelRepHook(formRepHooks.pre_commit.fileName)"
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
                      <span @click="ModalStudyRepHook('post_commit')"
                        >介绍</span
                      >
                    </li>
                    <li>
                      <span @click="ModalEditRepHook('post_commit')">编辑</span>
                    </li>
                    <li>
                      <span
                        @click="DelRepHook(formRepHooks.post_commit.fileName)"
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
                      <span
                        @click="DelRepHook(formRepHooks.pre_unlock.fileName)"
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
                      <span @click="ModalStudyRepHook('post_unlock')"
                        >介绍</span
                      >
                    </li>
                    <li>
                      <span @click="ModalEditRepHook('post_unlock')">编辑</span>
                    </li>
                    <li>
                      <span
                        @click="DelRepHook(formRepHooks.post_unlock.fileName)"
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
            </Scroll>
          </Card>
          <Spin size="large" fix v-if="loadingGetRepHooks"></Spin>
        </TabPane>
        <TabPane label="常用钩子">
          <Scroll>
            <List :border="true">
              <ListItem>
                <ListItemMeta
                  title="Precommit hook"
                  description="This is description, this is description."
                />
                <template slot="action">
                  <li>
                    <span>查看</span>
                  </li>
                </template>
              </ListItem>
              <ListItem>
                <ListItemMeta
                  title="Precommit hook"
                  description="This is description, this is description."
                />
                <template slot="action">
                  <li>
                    <span>查看</span>
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
    <!-- 对话框-钩子模板内容查看 -->
    <Modal v-model="modalStudyRepHook" :title="titleModalStudyRepHook">
      <Input
        v-model="tempSelectRepHookTmpl"
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
    <!-- 对话框-钩子内容编辑 -->
    <Modal v-model="modalEditRepHook" :title="titleModalEditRepHook">
      <Input
        v-model="tempSelectRepHookCon"
        :rows="15"
        show-word-limit
        type="textarea"
        placeholder="具体介绍和语法可看钩子介绍"
      />
      <div slot="footer">
        <Button
          type="primary"
          @click="EditRepHook"
          :loading="loadingEditRepHook"
          >应用</Button
        >
      </div>
    </Modal>
    <!-- 对话框-高级 -->
    <Modal v-model="modalRepAdvance" :title="titleModalRepAdvance">
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
                @click="ImportRep"
                ghost
                >导入</Button
              >
            </FormItem>
          </Form>
        </TabPane>
      </Tabs>
      <div slot="footer">
        <Button type="primary" ghost @click="modalRepAdvance = false"
          >取消</Button
        >
      </div>
    </Modal>
    <!-- 对话框-编辑仓库名称 -->
    <Modal v-model="modalEditRepName" :title="titleModalEditRepName">
      <Form :model="formRepEdit" :label-width="80">
        <FormItem label="仓库名称">
          <Input v-model="formRepEdit.new_rep_name"></Input>
        </FormItem>
        <FormItem>
          <Button
            type="primary"
            :loading="loadingEditRepName"
            @click="EditRepName"
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
    <!-- 对话框-选择SVN用户 -->
    <Modal
      v-model="modalRepAllUser"
      title="选择SVN用户（添加的用户权限都会被重置为rw）"
      @on-ok="AddRepPathUserPri"
      :loading="loadingAddRepPathUserPri"
    >
      <Table
        :height="350"
        size="small"
        highlight-row
        :show-header="false"
        :columns="tableColumnAllUser"
        :data="tableDataAllUser"
        :loading="loadingAllUserList"
        @on-row-click="ClickRowAddRepPathUser"
      >
        <template slot-scope="{ row }" slot="disabled">
          <Tag color="blue" v-if="row.disabled == 0">正常</Tag>
          <Tag color="red" v-else>禁用</Tag>
        </template>
      </Table>
    </Modal>
    <!-- 对话框-选择SVN分组 -->
    <Modal
      v-model="modalRepAllGroup"
      title="选择SVN分组（添加的用户权限都会被重置为rw）"
      @on-ok="AddRepPathGroupPri"
      :loading="loadingAddRepPathGroupPri"
    >
      <Table
        height="350"
        highlight-row
        :show-header="false"
        :columns="tableColumnAllGroup"
        :data="tableDataAllGroup"
        :loading="loadingAllGroupList"
        @on-row-click="ClickRowAddRepPathGroup"
      ></Table>
    </Modal>
  </div>
</template>

<script>
import { WritableStream } from "web-streams-polyfill/ponyfill";
export default {
  data() {
    return {
      /**
       * 权限相关
       */
      token: sessionStorage.token,
      user_role_id: sessionStorage.user_role_id,

      //仓库目录树
      treeRep: [],

      /**
       * 对话框
       */
      //新建SVN仓库
      modalCreateRep: false,
      //浏览仓库
      modalViewRep: false,
      //仓库备份
      modalRepDump: false,
      //仓库权限
      modalRepPri: false,
      //仓库钩子配置
      modalRepHooks: false,
      //高级
      modalRepAdvance: false,
      //编辑仓库信息
      modalEditRepName: false,
      //SVN仓库所有用户
      modalRepAllUser: false,
      //SVN仓库所有分组
      modalRepAllGroup: false,
      //编辑仓库钩子内容
      modalEditRepHook: false,
      //查看钩子模板内容
      modalStudyRepHook: false,

      /**
       * 排序数据
       */
      sortName: "rep_name",
      sortType: "asc",

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
      //仓库目录树
      loadingRepTree: true,
      //某个仓库路径的用户权限
      loadingRepPathUserPri: true,
      //为路径添加用户授权
      loadingAddRepPathUserPri: true,
      //去除路径的用户权限
      loadingDelRepPathUserPri: false,
      //某个仓库路径的分组权限
      loadingRepPathGroupPri: true,
      //为仓库路径添加分组权限
      loadingAddRepPathGroupPri: true,
      //删除仓库路径的分组权限
      loadingDelRepPathGroupPri: false,
      //全部的SVN用户列表
      loadingAllUserList: true,
      //全部的SVN分组列表
      loadingAllGroupList: true,
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

      /**
       * 临时变量
       */
      //临时选中的仓库名称
      currentRepName: "",
      //展开目录树时选中的仓库路径
      currentRepTreePath: "/",
      //点击目录树查看权限时的仓库路径
      currentRepTreePriPath: "/",
      //检出路径
      tempCheckout: "",
      //单选 仓库路径的用户权限列表
      radioRepUserPri: "",
      //单选 仓库路径的分组权限列表
      radioRepGroupPri: "",
      //仓库路径的用户权限列表 当前选中的用户以及下标
      currentRepPriUser: "",
      currentRepPriUserIndex: -1,
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

      /**
       * 对话框标题
       */
      //浏览仓库内容
      titleModalViewRep: "",
      //仓库备份
      titleModalRepBackup: "",
      //配置仓库权限
      titleModalRepPri: "",
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

      /**
       * 表格
       */
      //所有仓库
      tableColumnRep: [
        {
          title: "序号",
          type: "index",
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
          minWidth: 120,
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
          type: "index",
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
        },
        {
          title: "信息",
          key: "repValue",
          tooltip: true,
        },
        {
          title: "复制",
          slot: "copy",
          width: 60,
        },
      ],
      tableDataRepDetail: [],
      //仓库的所有用户
      tableColumnAllUser: [
        {
          title: "用户名",
          key: "userName",
        },
        {
          title: "启用状态",
          slot: "disabled",
        },
      ],
      tableDataAllUser: [],
      //仓库的所有分组
      tableColumnAllGroup: [
        {
          title: "分组名",
          key: "groupName",
        },
      ],
      tableDataAllGroup: [],
    };
  },
  computed: {},
  created() {},
  mounted() {
    // abort so it dose not look stuck
    // window.onunload = () => {
    //   console.log('test-start');
    //   console.log(window.WritableStream);
    //   console.log(writer);
    //   console.log('test-end');

    //   writableStream.abort();
    //   // also possible to call abosrt on the writer you got from `getWriter()`
    //   writer.abort();
    // };
    this.GetStatus();
    if (this.user_role_id == 1) {
      this.GetRepList();
    } else if (this.user_role_id == 2) {
      this.GetSvnUserRepList();
    }
  },
  methods: {
    /**
     * 获取svnserve运行状态
     */
    GetStatus() {
      var that = this;
      var data = {};
      that.$axios
        .post("/api.php?c=Svn&a=GetStatus&t=web", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
          } else {
            that.formStatusSubversion.status = false;
            that.formStatusSubversion.info = result.message;
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
          that.modalCreateRep = false;
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
            that.GetRepList();
          } else {
            that.$Message.error(result.message);
          }
        })
        .catch(function (error) {
          that.loadingCreateRep = false;
          console.log(error);
          that.$Message.error("出错了 请联系管理员！");
        });
    },

    /**
     * 获取仓库列表
     */
    SearchGetRepList() {
      // if (this.searchKeywordRep == "") {
      //   this.$Message.error("请输入搜索内容");
      //   return;
      // }
      this.GetRepList();
    },
    GetRepList() {
      var that = this;
      that.loadingRep = true;
      that.tableDataRep = [];
      // that.totalRep = 0;
      var data = {
        pageSize: that.pageSizeRep,
        currentPage: that.pageCurrentRep,
        searchKeyword: that.searchKeywordRep,
        sortName: that.sortName,
        sortType: that.sortType,
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
            that.$Message.error(result.message);
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
      this.sortName = value.key;
      if (value.order == "desc" || value.order == "asc") {
        this.sortType = value.order;
      }
      this.GetRepList();
    },

    /**
     * 获取用户仓库列表
     */
    GetSvnUserRepList() {
      var that = this;
      that.loadingUserRep = true;
      that.tableDataUserRep = [];
      that.totalUserRep = 0;
      var data = {
        pageSize: that.pageSizeUserRep,
        currentPage: that.pageCurrentUserRep,
        searchKeyword: that.searchKeywordRep,
        sortType: that.sortType,
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
          } else {
            that.$Message.error(result.message);
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
      this.sortName = value.key;
      if (value.order == "desc" || value.order == "asc") {
        this.sortType = value.order;
      }
      this.GetSvnUserRepList();
    },

    /**
     * 编辑仓库备注信息
     */
    EditRepNote(index, rep_name) {
      var that = this;
      var data = {
        rep_name: rep_name,
        rep_note: that.tableDataRep[index].rep_note,
      };
      that.$axios
        .post("/api.php?c=Svnrep&a=EditRepNote&t=web", data)
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
      that.currentRepTreePath = "/";
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
      that.currentRepTreePath = pri_path;
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
            that.currentRepTreePath;
        }
      });
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
          .post("/api.php?c=Svn&a=GetCheckout&t=web", data)
          .then(function (response) {
            var result = response.data;
            if (result.status == 1) {
              that.checkInfo = result.data;
            } else {
              that.loadingRepCon = false;
              that.$Message.error(result.message);
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
        path: that.currentRepTreePath,
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
              that.currentRepTreePath;
          } else {
            that.$Message.error(result.message);
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
        path: that.currentRepTreePath,
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
              that.currentRepTreePath;
          } else {
            that.$Message.error(result.message);
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
        this.currentRepTreePath = this.tableDataRepCon[index].fullPath;
        if (this.user_role_id == 1) {
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
      this.currentRepTreePath = fullPath;
      if (this.user_role_id == 1) {
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
            that.$Message.error(result.message);
          }
        })
        .catch(function (error) {
          that.loadingRepBackupList = false;
          console.log(error);
          that.$Message.error("出错了 请联系管理员！");
        });
    },
    RepDump() {
      var that = this;
      that.loadingRepDump = true;
      var data = {
        rep_name: that.currentRepName,
      };
      that.$axios
        .post("/api.php?c=Svnrep&a=RepDump&t=web", data)
        .then(function (response) {
          that.loadingRepDump = false;
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
            that.GetBackupList();
          } else {
            that.$Message.error(result.message);
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
    DownloadRepBackup(fileName) {
      var that = this;
      const streamSaver = require("../../libs/streamsaver/StreamSaver");
      fetch("/api.php?c=Svnrep&a=DownloadRepBackup&t=web", {
        headers: {
          "Content-Type": "application/json",
          token: that.token,
        },
        method: "POST",
        body: JSON.stringify({
          fileName: fileName,
        }),
      }).then((response) => {
        // These code section is adapted from an example of the StreamSaver.js
        // https://jimmywarting.github.io/StreamSaver.js/examples/fetch.html

        // If the WritableStream is not available (Firefox, Safari), take it from the ponyfill
        if (!window.WritableStream) {
          streamSaver.WritableStream = WritableStream;
          window.WritableStream = WritableStream;
        }

        const fileStream = streamSaver.createWriteStream(fileName, {
          size: response.headers.get("content-length"),
        });
        const readableStream = response.body;

        // More optimized
        if (readableStream.pipeTo) {
          return readableStream.pipeTo(fileStream);
        }

        window.writer = fileStream.getWriter();

        const reader = response.body.getReader();
        const pump = () =>
          reader
            .read()
            .then((res) =>
              res.done ? writer.close() : writer.write(res.value).then(pump)
            );

        pump();
      });
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
                that.$Message.error(result.message);
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
      var that = this;
      //通过按钮点击浏览 初始化路径和仓库名称
      that.currentRepTreePath = "/";
      that.currentRepTreePriPath = "/";
      that.currentRepName = rep_name;
      //设置标题
      that.titleModalRepPri = "仓库权限 - " + rep_name;
      //显示对话框
      that.modalRepPri = true;
      //显示加载动画
      that.loadingRepTree = true;
      //清空数据
      that.treeRep = [];
      //请求目录树
      that.GetRepTree().then(function (response) {
        that.loadingRepTree = false;
        var result = response.data;
        if (result.status == 1) {
          that.treeRep = result.data;
        } else {
          that.$Message.error(result.message);
        }
      });
      //获取仓库根路径的用户权限列表
      that.GetRepPathUserPri();
      //获取仓库根路径的分组权限列表
      that.GetRepPathGroupPri();
    },
    /**
     * 获取目录树
     */
    GetRepTree() {
      var that = this;
      var data = {
        rep_name: that.currentRepName,
        path: that.currentRepTreePath,
      };
      return new Promise(function (resolve, reject) {
        that.$axios
          .post("/api.php?c=Svnrep&a=GetRepTree&t=web", data)
          .then(function (response) {
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
     * 渲染目录树 给文件夹和文件设置对应的图标
     */
    RenderContent(h, { root, node, data }) {
      return h("span", [
        h("Icon", {
          props: {
            type:
              data.resourceType == "1"
                ? "ios-document-outline"
                : "ios-folder-open",
          },
          style: {
            marginRight: "8px",
          },
        }),
        h("span", data.title),
      ]);
    },
    /**
     * 点击目录树节点触发
     */
    ChangeSelectTreeNode(selectArray, currentItem) {
      this.currentRepTreePriPath = currentItem.fullPath;
      this.GetRepPathUserPri();
      this.GetRepPathGroupPri();
    },
    /**
     * 异步加载目录下的内容
     */
    LoadingRepTree(item, callback) {
      var that = this;
      var data = [];
      that.currentRepTreePath = item.fullPath;
      that.GetRepTree().then(function (response) {
        var result = response.data;
        if (result.status == 1) {
          data = result.data;
          if (data.length > 0) {
            if (data[0].fullPath != "/") {
              callback(data);
            } else {
              callback([]);
              //根目录下没有内容时 直接覆盖掉
              that.treeRep = [
                {
                  resourceType: 2,
                  title: that.currentRepName + "/",
                  fullPath: "/",
                },
              ];
            }
          } else {
            callback([]);
          }
        } else {
          that.$Message.error(result.message);
          callback(data);
        }
      });
    },

    /**
     * 获取某个仓库路径的用户权限列表
     */
    GetRepPathUserPri() {
      var that = this;
      //清空上次表格数据
      that.tableDataRepPathUserPri = [];
      //清空选中的用户数据
      that.currentRepPriUser = "";
      that.currentRepPriUserIndex = -1;
      //开始加载动画
      that.loadingRepPathUserPri = true;
      var data = {
        rep_name: that.currentRepName,
        path: that.currentRepTreePriPath,
      };
      that.$axios
        .post("/api.php?c=Svnrep&a=GetRepPathUserPri&t=web", data)
        .then(function (response) {
          that.loadingRepPathUserPri = false;
          var result = response.data;
          if (result.status == 1) {
            that.tableDataRepPathUserPri = result.data;
          } else {
            that.$Message.error(result.message);
          }
        })
        .catch(function (error) {
          that.loadingRepPathUserPri = false;
          console.log(error);
          that.$Message.error("出错了 请联系管理员！");
        });
    },
    /**
     * 点击仓库路径的用户权限列表的某行
     */
    ChangeSelectRepUserPri(currentRow, oldCurrentRow) {
      //将当前选中的用户和下标进行同步
      this.currentRepPriUser = currentRow.userName;
      this.currentRepPriUserIndex = currentRow.index;
      //将当前选中行的权限同步到下方的单选
      this.radioRepUserPri = currentRow.userPri;
    },
    /**
     * 单选组合 为选中的用户更换权限
     */
    ChangeRadioRepUserPri(value) {
      //如果没有选中用户则做出提示
      if (this.currentRepPriUser == "") {
        this.$Message.error("未选择用户");
      } else {
        this.tableDataRepPathUserPri[this.currentRepPriUserIndex].userPri =
          value;

        //发起请求
        this.EditRepPathUserPri();
      }
    },
    /**
     * 单选按钮 删除某个仓库路径的用户权限
     */
    DelRepPathUserPri() {
      var that = this;
      //如果没有选中用户则做出提示
      if (that.currentRepPriUser == "") {
        that.$Message.error("未选择用户");
        return;
      }
      that.loadingDelRepPathUserPri = true;
      var data = {
        rep_name: that.currentRepName,
        path: that.currentRepTreePriPath,
        user: that.currentRepPriUser,
      };
      that.$axios
        .post("/api.php?c=Svnrep&a=DelRepPathUserPri&t=web", data)
        .then(function (response) {
          that.loadingDelRepPathUserPri = false;
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
            that.GetRepPathUserPri();
          } else {
            that.$Message.error(result.message);
          }
        })
        .catch(function (error) {
          that.loadingDelRepPathUserPri = false;
          console.log(error);
          that.$Message.error("出错了 请联系管理员！");
        });
    },
    /**
     * 单选按钮 修改某个仓库路径的用户权限
     */
    EditRepPathUserPri() {
      var that = this;
      //如果没有选中用户则做出提示
      if (that.currentRepPriUser == "") {
        that.$Message.error("未选择用户");
        return;
      }
      var data = {
        rep_name: that.currentRepName,
        path: that.currentRepTreePriPath,
        pri: that.tableDataRepPathUserPri[that.currentRepPriUserIndex].userPri,
        user: that.tableDataRepPathUserPri[that.currentRepPriUserIndex]
          .userName,
      };
      that.$axios
        .post("/api.php?c=Svnrep&a=EditRepPathUserPri&t=web", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
            that.GetRepPathUserPri();
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
     * 增加某个仓库路径的用户权限
     */
    AddRepPathUserPri() {
      var that = this;
      var data = {
        rep_name: that.currentRepName,
        path: that.currentRepTreePriPath,
        pri: "rw",
        user: that.currentRepPriAddUser,
      };
      that.$axios
        .post("/api.php?c=Svnrep&a=AddRepPathUserPri&t=web", data)
        .then(function (response) {
          that.modalRepAllUser = false;
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
            that.GetRepPathUserPri();
          } else {
            that.$Message.error(result.message);
          }
        })
        .catch(function (error) {
          that.modalRepAllUser = false;
          console.log(error);
          that.$Message.error("出错了 请联系管理员！");
        });
    },
    /**
     * 获取所有的SVN用户列表
     */
    ModalRepAllUser() {
      this.modalRepAllUser = true;
      //获取所有SVN用户列表
      this.GetAllUserList();
    },
    /**
     * 获取所有的SVN用户列表
     */
    GetAllUserList() {
      var that = this;
      //清空上次数据
      that.tableDataAllUser = [];
      //开始加载动画
      that.loadingAllUserList = true;
      var data = {};
      that.$axios
        .post("/api.php?c=Svnuser&a=GetAllUserList&t=web", data)
        .then(function (response) {
          that.loadingAllUserList = false;
          var result = response.data;
          if (result.status == 1) {
            that.tableDataAllUser = result.data;
          } else {
            that.$Message.error(result.message);
          }
        })
        .catch(function (error) {
          that.loadingAllUserList = false;
          console.log(error);
          that.$Message.error("出错了 请联系管理员！");
        });
    },
    /**
     * 选中用户添加权限
     */
    ClickRowAddRepPathUser(currentRow, oldCurrentRow) {
      this.currentRepPriAddUser = currentRow.userName;
    },

    /**
     * 获取某个仓库路径的分组权限列表
     */
    GetRepPathGroupPri() {
      var that = this;
      //清空上次表格数据
      that.tableDataRepPathGroupPri = [];
      //清空选中的分组名称数据
      that.currentRepPriGroup = "";
      that.currentRepPriGroupIndex = -1;
      //开始加载动画
      that.loadingRepPathGroupPri = true;
      var data = {
        rep_name: that.currentRepName,
        path: that.currentRepTreePriPath,
      };
      that.$axios
        .post("/api.php?c=Svnrep&a=GetRepPathGroupPri&t=web", data)
        .then(function (response) {
          that.loadingRepPathGroupPri = false;
          var result = response.data;
          if (result.status == 1) {
            that.tableDataRepPathGroupPri = result.data;
          } else {
            that.$Message.error(result.message);
          }
        })
        .catch(function (error) {
          that.loadingRepPathGroupPri = false;
          console.log(error);
          that.$Message.error("出错了 请联系管理员！");
        });
    },
    /**
     * 点击仓库路径的分组权限列表的某行
     */
    ChangeSelectRepGroupPri(currentRow, oldCurrentRow) {
      //将当前选中的分组和下标进行同步
      this.currentRepPriGroup = currentRow.groupName;
      this.currentRepPriGroupIndex = currentRow.index;
      //将当前选中行的权限同步到下方的单选
      if (currentRow.groupPri == "") {
        this.radioRepGroupPri = "no";
      } else {
        this.radioRepGroupPri = currentRow.groupPri;
      }
    },
    /**
     * 单选组合 为选中的分组更换权限
     */
    ChangeRadioRepGroupPri(value) {
      //如果没有选中分组则做出提示
      if (this.currentRepPriGroup == "") {
        this.$Message.error("未选择分组");
      } else {
        this.tableDataRepPathGroupPri[this.currentRepPriGroupIndex].groupPri =
          value;

        //发起请求
        this.EditRepPathGroupPri();
      }
    },
    /**
     * 单选按钮 删除某个仓库路径的分组权限
     */
    DelRepPathGroupPri() {
      var that = this;
      //如果没有选中用户则做出提示
      if (that.currentRepPriGroup == "") {
        that.$Message.error("未选择分组");
        return;
      }
      that.loadingDelRepPathGroupPri = true;
      var data = {
        rep_name: that.currentRepName,
        path: that.currentRepTreePriPath,
        group: that.currentRepPriGroup,
      };
      that.$axios
        .post("/api.php?c=Svnrep&a=DelRepPathGroupPri&t=web", data)
        .then(function (response) {
          that.loadingDelRepPathGroupPri = false;
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
            that.GetRepPathGroupPri();
          } else {
            that.$Message.error(result.message);
          }
        })
        .catch(function (error) {
          that.loadingDelRepPathGroupPri = false;
          console.log(error);
          that.$Message.error("出错了 请联系管理员！");
        });
    },
    /**
     * 单选按钮 修改某个仓库路径的分组权限
     */
    EditRepPathGroupPri() {
      var that = this;
      //如果没有选中分组则做出提示
      if (that.currentRepPriGroup == "") {
        that.$Message.error("未选择分组");
        return;
      }
      var data = {
        rep_name: that.currentRepName,
        path: that.currentRepTreePriPath,
        pri: that.tableDataRepPathGroupPri[that.currentRepPriGroupIndex]
          .groupPri,
        group:
          that.tableDataRepPathGroupPri[that.currentRepPriGroupIndex].groupName,
      };
      that.$axios
        .post("/api.php?c=Svnrep&a=EditRepPathGroupPri&t=web", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
            that.GetRepPathGroupPri();
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
     * 增加某个仓库路径的分组权限
     */
    AddRepPathGroupPri() {
      var that = this;
      var data = {
        rep_name: that.currentRepName,
        path: that.currentRepTreePriPath,
        pri: "rw",
        group: that.currentRepPriAddGroup,
      };
      that.$axios
        .post("/api.php?c=Svnrep&a=AddRepPathGroupPri&t=web", data)
        .then(function (response) {
          that.modalRepAllGroup = false;
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
            that.GetRepPathGroupPri();
          } else {
            that.$Message.error(result.message);
          }
        })
        .catch(function (error) {
          that.modalRepAllGroup = false;
          console.log(error);
          that.$Message.error("出错了 请联系管理员！");
        });
    },
    /**
     * 获取所有的SVN分组列表
     */
    ModalRepAllGroup() {
      this.modalRepAllGroup = true;
      //获取所有SVN分组列表
      this.GetAllGroupList();
    },
    /**
     * 获取所有的SVN分组列表
     */
    GetAllGroupList() {
      var that = this;
      //清空上次数据
      that.tableDataAllGroup = [];
      //开始加载动画
      that.loadingAllGroupList = true;
      var data = {};
      that.$axios
        .post("/api.php?c=Svngroup&a=GetAllGroupList&t=web", data)
        .then(function (response) {
          that.loadingAllGroupList = false;
          var result = response.data;
          if (result.status == 1) {
            that.tableDataAllGroup = result.data;
          } else {
            that.$Message.error(result.message);
          }
        })
        .catch(function (error) {
          that.loadingAllGroupList = false;
          console.log(error);
          that.$Message.error("出错了 请联系管理员！");
        });
    },
    /**
     * 选中分组添加权限
     */
    ClickRowAddRepPathGroup(currentRow, oldCurrentRow) {
      this.currentRepPriAddGroup = currentRow.groupName;
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
      //请求数据
      this.GetRepHooks();
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
            that.$Message.error(result.message);
          }
        })
        .catch(function (error) {
          that.loadingGetRepHooks = false;
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
            that.$Message.error(result.message);
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
    EditRepHook() {
      var that = this;
      that.loadingEditRepHook = true;
      var data = {
        rep_name: that.currentRepName,
        fileName: that.tempSelectRepHook,
        content: that.tempSelectRepHookCon,
      };
      that.$axios
        .post("/api.php?c=Svnrep&a=EditRepHook&t=web", data)
        .then(function (response) {
          that.loadingEditRepHook = false;
          var result = response.data;
          if (result.status == 1) {
            that.modalEditRepHook = false;
            that.$Message.success(result.message);
            that.GetRepHooks();
          } else {
            that.$Message.error(result.message);
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
            that.$Message.error(result.message);
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
            that.$Message.error(result.message);
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
        this.$Message.error(result.message);
      }
    },
    /**
     * 选中文件进行导入
     */
    ClickRowUploadBackup(currentRow, oldCurrentRow) {
      //当前选中的文件
      this.formUploadBackup.fileName = currentRow.fileName;
    },
    ImportRep() {
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
        .post("/api.php?c=Svnrep&a=ImportRep&t=web", data)
        .then(function (response) {
          that.loadingImportBackup = false;
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
            that.formUploadBackup.errorInfo = result.data;
          } else {
            that.$Message.error(result.message);
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
    EditRepName() {
      var that = this;
      that.loadingEditRepName = true;
      var data = {
        old_rep_name: that.formRepEdit.old_rep_name,
        new_rep_name: that.formRepEdit.new_rep_name,
      };
      that.$axios
        .post("/api.php?c=Svnrep&a=EditRepName&t=web", data)
        .then(function (response) {
          that.loadingEditRepName = false;
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
            that.modalEditRepName = false;
            that.GetRepList();
          } else {
            that.$Message.error(result.message);
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
        title: "删除仓库 - " + rep_name,
        content:
          "确定要删除该仓库吗？<br/>该操作不可逆！<br/>如果该仓库有正在进行的网络传输，可能会删除失败，请注意提示信息！",
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
                that.$Message.error(result.message);
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
// .hooks {
//   .ivu-modal-body {
//     padding: 0px 16px 0px 16px;
//   }
// }
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