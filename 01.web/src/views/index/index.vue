<template>
  <div>
    <Card
      :bordered="false"
      :dis-hover="true"
      style="margin-bottom: 10px"
      v-if="display.part1"
    >
      <p slot="title">
        <Icon type="md-bulb" />
        <Tooltip
          max-width="500"
          placement="bottom"
          :transfer="true"
          :content="systemBrif.os"
        >
          <span
            style="
              white-space: nowrap;
              overflow: hidden;
              text-overflow: ellipsis;
              width: 450px;
              display: inline-block;
            "
          >
            {{ systemBrif.os }}
          </span>
        </Tooltip>
      </p>
      <div>
        <Row>
          <Col span="4">
            <div class="statusTop">负载状态</div>
            <Tooltip placement="bottom" max-width="200">
              <Circle
                :percent="statusInfo.load.percent"
                dashboard
                :size="100"
                :stroke-color="statusInfo.load.color"
                class="statusCircle"
              >
                <span class="demo-circle-inner" style="font-size: 24px"
                  >{{ statusInfo.load.percent }}%</span
                >
              </Circle>
              <div slot="content">
                <p>最近1分钟平均负载：{{ statusInfo.load.cpuLoad1Min }}</p>
                <p>最近5分钟平均负载：{{ statusInfo.load.cpuLoad5Min }}</p>
                <p>最近15分钟平均负载：{{ statusInfo.load.cpuLoad15Min }}</p>
              </div>
            </Tooltip>
            <div class="statusBottom">{{ statusInfo.load.title }}</div>
          </Col>
          <Col span="4">
            <div class="statusTop">CPU使用率</div>
            <Tooltip placement="bottom" max-width="200">
              <Circle
                :percent="statusInfo.cpu.percent"
                dashboard
                :size="100"
                :stroke-color="statusInfo.cpu.color"
                class="statusCircle"
              >
                <span class="demo-circle-inner" style="font-size: 24px"
                  >{{ statusInfo.cpu.percent }}%</span
                >
              </Circle>
              <div slot="content">
                <p v-for="item in statusInfo.cpu.cpu" :key="item">{{ item }}</p>
                <p>物理CPU个数：{{ statusInfo.cpu.cpuPhysical }}</p>
                <p>物理CPU的总核心数：{{ statusInfo.cpu.cpuCore }}</p>
                <p>物理CPU的线程总数：{{ statusInfo.cpu.cpuProcessor }}</p>
              </div>
            </Tooltip>
            <div class="statusBottom">{{ statusInfo.cpu.cpuCore }}核心</div>
          </Col>
          <Col span="4">
            <div class="statusTop">内存使用率</div>
            <Circle
              :percent="statusInfo.mem.percent"
              dashboard
              :size="100"
              :stroke-color="statusInfo.mem.color"
              class="statusCircle"
            >
              <span class="demo-circle-inner" style="font-size: 24px"
                >{{ statusInfo.mem.percent }}%</span
              >
            </Circle>
            <div class="statusBottom">
              {{ statusInfo.mem.memUsed }} / {{ statusInfo.mem.memTotal }}(MB)
            </div>
          </Col>
          <Col span="4" v-for="(item, index) in diskList" :key="index">
            <div class="statusTop">{{ item.mountedOn }}</div>
            <Tooltip placement="bottom" max-width="200">
              <div slot="content">
                <p>文件系统：{{ item.fileSystem }}</p>
                <p>容量：{{ item.size }}</p>
                <p>已使用：{{ item.used }}</p>
                <p>可使用：{{ item.avail }}</p>
                <p>使用率：{{ item.percent }}%</p>
                <p>挂载点：{{ item.mountedOn }}</p>
              </div>
              <Circle
                :percent="item.percent"
                dashboard
                :size="100"
                :stroke-color="item.color"
                class="statusCircle"
              >
                <span class="demo-circle-inner" style="font-size: 24px"
                  >{{ item.percent }}%</span
                >
              </Circle>
            </Tooltip>
            <div class="statusBottom">
              {{ item.used }} /
              {{ item.size }}
            </div>
          </Col>
        </Row>
      </div>
    </Card>
    <Card :bordered="false" :dis-hover="true" style="margin-bottom: 10px">
      <p slot="title">
        <Icon type="ios-options" />
        统计
      </p>
      <div>
        <Row :gutter="16">
          <Col span="4">
            <Card :dis-hover="true">
              <div style="text-align: center">
                <p>仓库占用</p>
                <h2 style="color: #28bcfe">{{ systemBrif.repSize }}</h2>
              </div>
            </Card>
          </Col>
          <Col span="4">
            <Card :dis-hover="true">
              <div style="text-align: center">
                <p>备份占用</p>
                <h2 style="color: #28bcfe">{{ systemBrif.backupSize }}</h2>
              </div>
            </Card>
          </Col>
          <Col span="4">
            <Card :dis-hover="true">
              <div style="text-align: center">
                <p>SVN仓库</p>
                <h2 style="color: #28bcfe">{{ systemBrif.repCount }}</h2>
              </div>
            </Card>
          </Col>
          <Col span="4">
            <Card :dis-hover="true">
              <div style="text-align: center">
                <p>SVN用户</p>
                <h2 style="color: #28bcfe">{{ systemBrif.repUser }}</h2>
              </div>
            </Card>
          </Col>
          <Col span="4">
            <Card :dis-hover="true">
              <div style="text-align: center">
                <p>SVN分组</p>
                <h2 style="color: #28bcfe">{{ systemBrif.repGroup }}</h2>
              </div>
            </Card>
          </Col>
          <Col span="4">
            <Card :dis-hover="true">
              <div style="text-align: center">
                <p>运行日志/条</p>
                <h2 style="color: #28bcfe">{{ systemBrif.logCount }}</h2>
              </div>
            </Card>
          </Col>
        </Row>
      </div>
    </Card>
  </div>
</template>

<script>
export default {
  data() {
    return {
      /**
       * 两个板块的显示控制
       */
      display: {
        part1: true,
        part2: true,
      },
      /**
       * 硬盘信息
       */
      diskList: [],
      /**
       * 状态信息
       */
      statusInfo: {
        load: {
          cpuLoad15Min: 0.22,
          cpuLoad5Min: 0.28,
          cpuLoad1Min: 0.32,
          percent: 16,
          color: "#28bcfe",
        },
        cpu: {
          percent: 28.2,
          cpu: ["Intel(R) Xeon(R) Platinum 8255C CPU @ 2.50GHz"],
          cpuPhysical: 1,
          cpuPhysicalCore: 1,
          cpuCore: 1,
          cpuProcessor: 1,
          color: "#28bcfe",
        },
        mem: {
          memTotal: 1838,
          memUsed: 975,
          memFree: 863,
          percent: 53,
          color: "#28bcfe",
        },
      },
      /**
       * 统计信息
       */
      systemBrif: {
        os: "",
        repSize: 0,
        repCount: 0,
        repUser: 0,
        repGroup: 0,
        logCount: 0,
        backupSize: 0,
      },
    };
  },
  computed: {},
  created() {},
  mounted() {
    var that = this;
    if (that.display.part1) {
      that.GetDisk();
      that.GetSystemStatus();
      //设置定时器
      that.timer = window.setInterval(() => {
        setTimeout(that.GetSystemStatus(), 0);
      }, 3000);
      //离开页面清除定时器
      that.$once("hook:beforeDestroy", () => {
        clearInterval(that.timer);
      });
    }
    if (that.display.part2) {
      that.GetSystemAnalysis();
    }
  },
  methods: {
    /**
     * 获取磁盘
     */
    GetDisk() {
      var that = this;
      var data = {};
      that.$axios
        .post("/api.php?c=Statistics&a=GetDisk&t=web", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            // that.$Message.success(result.message);
            that.diskList = result.data;
          } else {
            that.$Message.error({content: result.message,duration: 2,});
          }
        })
        .catch(function (error) {
          console.log(error);
          that.$Message.error("出错了 请联系管理员！");
        });
    },
    /**
     * 获取状态
     */
    GetSystemStatus() {
      var that = this;
      var data = {};
      that.$axios
        .post("/api.php?c=Statistics&a=GetSystemStatus&t=web", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            // that.$Message.success(result.message);
            that.statusInfo = result.data;
          } else {
            that.$Message.error({content: result.message,duration: 2,});
          }
        })
        .catch(function (error) {
          console.log(error);
          that.$Message.error("出错了 请联系管理员！");
        });
    },
    /**
     * 获取统计
     */
    GetSystemAnalysis() {
      var that = this;
      var data = {};
      that.$axios
        .post("/api.php?c=Statistics&a=GetSystemAnalysis&t=web", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            // that.$Message.success(result.message);
            that.systemBrif = result.data;
          } else {
            that.$Message.error({content: result.message,duration: 2,});
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
.statusTop {
  width: 140px;
  text-align: center;
  margin-bottom: 5px;

  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.statusCircle {
  margin-left: 20px;
}
.statusBottom {
  width: 140px;
  text-align: center;
  margin-bottom: 15px;
}
</style>