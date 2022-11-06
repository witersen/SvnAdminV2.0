<template>
  <div>
    <!-- 对话框-仓库权限 -->
    <Modal
      v-model="modalRepPri"
      :title="titleModalRepPri"
      @on-visible-change="ChangeModalVisible"
      fullscreen
    >
      <Row type="flex" justify="center" :gutter="16">
        <Col span="11">
          <Scroll :height="550">
            <Tree
              :data="dataTreeRep"
              :load-data="ExpandRepTree"
              :render="renderContent"
              @on-select-change="ChangeSelectTreeNode"
            ></Tree>
            <Spin size="large" fix v-if="loadingRepTree"></Spin>
          </Scroll>
        </Col>
        <Col span="11">
          <Tooltip
            style="width: 100%"
            max-width="450"
            :content="currentRepPath"
            placement="bottom"
          >
            <Input v-model="currentRepPath">
              <span slot="prepend">当前路径:</span>
            </Input>
          </Tooltip>
          <Card
            :bordered="true"
            :dis-hover="true"
            style="height: 500px; margin-top: 18px"
          >
            <Button
              icon="md-add"
              type="primary"
              ghost
              @click="modalSvnObject = true"
              >路径授权</Button
            >
            <Table
              border
              :height="410"
              size="small"
              :loading="loadingRepPathAllPri"
              :columns="tableColumnRepPathAllPri"
              :data="tableDataRepPathAllPri"
              style="margin-top: 20px"
            >
              <template slot-scope="{ row }" slot="objectType">
                <Tag
                  color="blue"
                  v-if="row.objectType == 'user'"
                  style="width: 90px; text-align: center"
                  >SVN用户</Tag
                >
                <Tag
                  color="geekblue"
                  v-if="row.objectType == 'group'"
                  style="width: 90px; text-align: center"
                  >SVN分组</Tag
                >
                <Tag
                  color="purple"
                  v-if="row.objectType == 'aliase'"
                  style="width: 90px; text-align: center"
                  >SVN别名</Tag
                >
                <Tag
                  color="red"
                  v-if="row.objectType == '*'"
                  style="width: 90px; text-align: center"
                  >所有人</Tag
                >
                <Tag
                  color="magenta"
                  v-if="row.objectType == '$authenticated'"
                  style="width: 90px; text-align: center"
                  >所有已认证者</Tag
                >
                <Tag
                  color="volcano"
                  v-if="row.objectType == '$anonymous'"
                  style="width: 90px; text-align: center"
                  >所有匿名者</Tag
                >
              </template>
              <template slot-scope="{ row }" slot="objectPri">
                <RadioGroup
                  v-model="row.objectPri"
                  type="button"
                  size="small"
                  button-style="solid"
                  @on-change="
                    (objectPri) =>
                      ClickRepPathPri(
                        row.objectType,
                        row.invert,
                        row.objectName,
                        objectPri
                      )
                  "
                >
                  <Radio label="rw">读写</Radio>
                  <Radio label="r">只读</Radio>
                  <Radio label="no">禁止</Radio>
                </RadioGroup>
              </template>
              <template slot-scope="{ row }" slot="invert">
                <Switch
                  v-if="row.objectType != '*'"
                  v-model="row.invert"
                  @on-change="
                    (invert) =>
                      ClickRepPathPri(
                        row.objectType,
                        invert,
                        row.objectName,
                        row.objectPri
                      )
                  "
                >
                  <Icon type="md-checkmark" slot="open"></Icon>
                  <Icon type="md-close" slot="close"></Icon>
                </Switch>
              </template>
              <template slot-scope="{ row }" slot="action">
                <Button
                  type="error"
                  size="small"
                  @click="DelRepPathPri(row.objectType, row.objectName)"
                  >删除</Button
                >
              </template>
            </Table>
          </Card>
        </Col>
      </Row>
      <div slot="footer">
        <Button type="primary" ghost @click="CloseModalRepPri">取消</Button>
      </div>
    </Modal>
    <!-- SVN对象列表组件 -->
    <ModalSvnObject
      :propModalSvnObject="modalSvnObject"
      :propChangeParentModalObject="CloseModalObject"
      :propSendParentObject="AddRepPathPri"
      :propSvnnUserPriPathId="svnn_user_pri_path_id"
    />
  </div>
</template>

<script>
//SVN对象列表组件
import ModalSvnObject from "./modalSvnObject.vue";

export default {
  props: {
    //父组件控制子组件显示
    propModalRepPri: {
      type: Boolean,
      default: false,
    },
    propCurrentRepPath: {
      type: String,
      default: "",
    },
    propCurrentRepName: {
      type: String,
      default: "",
    },
    propTitleModalRepPri: {
      type: String,
      default: "",
    },
    //向父组件发送对话框状态变量
    propChangeParentModalVisible: {
      type: Function,
    },
    propSvnnUserPriPathId: {
      type: Number,
      default: -1,
    },
  },
  data() {
    return {
      /**
       * 对话框
       */
      //仓库权限对话框
      modalRepPri: this.propModalRepPri,
      //SVN对象列表组件
      modalSvnObject: false,

      //仓库目录树
      dataTreeRep: [],

      /**
       * 加载
       */
      //仓库目录树
      loadingRepTree: true,
      //某个仓库路径的所有对象的权限列表
      loadingRepPathAllPri: true,

      /**
       * 临时变量
       */
      //当前仓库路径
      currentRepPath: this.propCurrentRepPath,
      //当前仓库名称
      currentRepName: this.propCurrentRepName,
      //当前权限路径id
      svnn_user_pri_path_id: this.propSvnnUserPriPathId,

      /**
       * 对话框标题
       */
      //配置仓库权限
      titleModalRepPri: "仓库权限",

      /**
       * 表格
       */
      //某节点的权限信息
      tableDataRepPathAllPri: [],
      tableColumnRepPathAllPri: [
        {
          title: "授权类型",
          slot: "objectType",
          width: 125,
        },
        {
          title: "对象名称",
          key: "objectName",
          tooltip: true,
          width: 115,
        },
        {
          title: "读写权限",
          slot: "objectPri",
          width: 200,
        },
        {
          slot: "invert",
          width: 100,
          renderHeader(h, params) {
            return h(
              "tooltip",
              {
                props: {
                  transfer: true,
                  placement: "left",
                  "max-width": "400",
                },
              },
              [
                h("span", [
                  h("span", "权限反转"),
                  h("Icon", {
                    props: {
                      type: "ios-help-circle-outline",
                      size: "15",
                    },
                    class: { iconClass: true },
                  }),
                ]),
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
                          color: "#479af1",
                          fontSize: "15px",
                        },
                      },
                      "不熟练的用户请慎用此功能！"
                    ),
                    h("p", " "),
                    h("p", "从 Subversion 1.5 开始"),
                    h("p", "$authenticated 表示所有已认证的用户"),
                    h("p", "$anonymous 表示所有未认证的用户"),
                    h(
                      "p",
                      "~ 即权限反转表示排除某些用户 如在用户名、别名、用户组、认证类别前加上 ~ 表示将访问权限授予给与规则不匹配的用户"
                    ),
                    h("p", " "),
                    h("p", "如："),
                    h("p", "[calendar:/projects/calendar]"),
                    h("p", "$anonymous = r"),
                    h("p", "$authenticated = rw"),
                    h("p", " "),
                    h(
                      "p",
                      "虽然下面的配置容易让人产生困惑,，但它和上面的例子是等效的："
                    ),
                    h("p", " "),
                    h("p", "[calendar:/projects/calendar]"),
                    h("p", "~$authenticated = r"),
                    h("p", "~$anonymous = rw"),
                    h("p", " "),
                    h("p", "下面是一个更恰当的使用 ~ 的例子："),
                    h("p", " "),
                    h("p", "[groups]"),
                    h("p", "# calc 项目的开发人员信息"),
                    h("p", "calc-developers = &harry, &sally, &joe"),
                    h("p", " "),
                    h("p", "# calc 项目的管理人员信息"),
                    h("p", "calc-owners = &hewlett, &packard"),
                    h("p", " "),
                    h("p", "# calc 项目的所有参与人信息"),
                    h("p", "calc = @calc-developers, @calc-owners"),
                    h("p", " "),
                    h("p", "# 所有的 calc 项目参与成员有该项目的读权限"),
                    h("p", "[calc:/projects/calc]"),
                    h("p", "@calc = rw"),
                    h("p", " "),
                    h("p", "# 只有项目管理员有 calc 项目的发行版标签操作权限"),
                    h("p", "[calc:/projects/calc/tags]"),
                    h("p", "~@calc-owners = r"),
                  ]
                ),
              ]
            );
          },
        },
        {
          title: "操作",
          slot: "action",
        },
      ],
    };
  },
  components: {
    ModalSvnObject,
  },
  computed: {},
  created() {},
  mounted() {},
  watch: {
    //监控有序
    //仓库路径
    propCurrentRepPath: function (value) {
      this.currentRepPath = value;
    },
    //仓库名称
    propCurrentRepName: function (value) {
      this.currentRepName = value;
    },
    //SVN用户权限路径id
    propSvnnUserPriPathId: function (value) {
      this.svnn_user_pri_path_id = value;
    },
    //对话框状态
    propModalRepPri: function (value) {
      var that = this;
      that.modalRepPri = value;
      if (value) {
        //标题
        that.titleModalRepPri = "仓库权限 - " + that.currentRepName;
        //显示加载动画
        that.loadingRepTree = true;
        //清空数据
        that.dataTreeRep = [];
        //请求目录树
        that.GetRepTree().then(function (response) {
          that.loadingRepTree = false;
          var result = response.data;
          if (result.status == 1) {
            that.dataTreeRep = result.data;
          } else {
            that.$Message.error({ content: result.message, duration: 2 });
          }
        });
        //获取仓库根路径的所有对象的权限列表
        that.GetRepPathAllPri();
      }
    },
  },
  methods: {
    /**
     * ModalSvnObject 子组件传递变量给父组件
     */
    CloseModalObject() {
      this.modalSvnObject = false;
    },
    /**
     * 本组件 关闭对话框触发事件
     */
    CloseModalRepPri() {
      //本组件内对话框状态
      this.modalRepPri = false;
      //将对话框状态从本组件内传递给父组件
      this.propChangeParentModalVisible();
    },
    /**
     * 本组件 Modal右上角叉号触发父组件修改变量状态
     */
    ChangeModalVisible(value) {
      if (!value) {
        //本组件对话框右上角的叉号被触发也会将对话框关闭状态从本组件内传递给父组件
        this.propChangeParentModalVisible();
      }
    },
    /**
     * 渲染目录树 给文件夹和文件设置对应的图标
     */
    renderContent(h, { root, node, data }) {
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
     * 获取目录树
     */
    GetRepTree() {
      var that = this;
      var data = {
        rep_name: that.currentRepName,
        path: that.currentRepPath,
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
     * 目录树展开触发
     * 异步加载目录下的内容
     */
    ExpandRepTree(item, callback) {
      var that = this;
      var data = [];
      that.currentRepPath = item.fullPath;
      that.GetRepPathAllPri();
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
              that.dataTreeRep = [
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
          that.$Message.error({ content: result.message, duration: 2 });
          callback(data);
        }
      });
    },
    /**
     * 点击目录树节点触发
     * 获取节点的权限
     */
    ChangeSelectTreeNode(selectArray, currentItem) {
      this.currentRepPath = currentItem.fullPath;
      this.GetRepPathAllPri();
    },
    /**
     * 获取某个仓库路径的所有对象的权限列表
     */
    GetRepPathAllPri() {
      var that = this;
      //清空上次表格数据
      that.tableDataRepPathAllPri = [];
      //开始加载动画
      that.loadingRepPathAllPri = true;
      var data = {
        rep_name: that.currentRepName,
        path: that.currentRepPath,
        svnn_user_pri_path_id: that.svnn_user_pri_path_id,
      };
      that.$axios
        .post("/api.php?c=Svnrep&a=GetRepPathAllPri&t=web", data)
        .then(function (response) {
          that.loadingRepPathAllPri = false;
          var result = response.data;
          if (result.status == 1) {
            that.tableDataRepPathAllPri = result.data;
          } else {
            that.$Message.error({ content: result.message, duration: 2 });
          }
        })
        .catch(function (error) {
          that.loadingRepPathAllPri = false;
          console.log(error);
          that.$Message.error("出错了 请联系管理员！");
        });
    },
    /**
     * 为某仓库路径下增加权限
     */
    AddRepPathPri(objectType, objectName) {
      var that = this;
      var data = {
        rep_name: that.currentRepName,
        path: that.currentRepPath,
        objectType: objectType,
        objectPri: "rw",
        objectName: objectName,
        svnn_user_pri_path_id: that.svnn_user_pri_path_id,
      };
      that.$axios
        .post("/api.php?c=Svnrep&a=AddRepPathPri&t=web", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.modalSvnObject = false;
            that.$Message.success(result.message);
            that.GetRepPathAllPri();
          } else {
            that.$Message.error({ content: result.message, duration: 2 });
          }
        })
        .catch(function (error) {
          that.modalSvnObject = false;
          console.log(error);
          that.$Message.error("出错了 请联系管理员！");
        });
    },
    /**
     * 修改某个仓库下的权限
     */
    ClickRepPathPri(objectType, invert, objectName, objectPri) {
      var that = this;
      var data = {
        rep_name: that.currentRepName,
        path: that.currentRepPath,
        objectType: objectType,
        invert: invert,
        objectName: objectName,
        objectPri: objectPri,
        svnn_user_pri_path_id: that.svnn_user_pri_path_id,
      };
      that.$axios
        .post("/api.php?c=Svnrep&a=EditRepPathPri&t=web", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
            that.GetRepPathAllPri();
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
     * 删除某个仓库下的权限
     */
    DelRepPathPri(objectType, objectName) {
      var that = this;
      var data = {
        rep_name: that.currentRepName,
        path: that.currentRepPath,
        objectType: objectType,
        objectName: objectName,
        svnn_user_pri_path_id: that.svnn_user_pri_path_id,
      };
      that.$axios
        .post("/api.php?c=Svnrep&a=DelRepPathPri&t=web", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
            that.GetRepPathAllPri();
          } else {
            that.$Message.error({ content: result.message, duration: 2 });
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

<style>
</style>