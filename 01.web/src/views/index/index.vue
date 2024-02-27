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
            <div class="statusTop">{{ $t('index.loadStatus') }}</div>
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
              <div slot="content" style="font-size:11px">
                <p>{{ $t('index.cpuLoad1Min') + statusInfo.load.cpuLoad1Min }}</p>
                <p>{{ $t('index.cpuLoad5Min') + statusInfo.load.cpuLoad5Min }}</p>
                <p>{{ $t('index.cpuLoad15Min') + statusInfo.load.cpuLoad15Min }}</p>
              </div>
            </Tooltip>
            <div class="statusBottom">{{ statusInfo.load.title }}</div>
          </Col>
          <Col span="4">
            <div class="statusTop">{{ $t('index.cpuUsage') }}</div>
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
              <div slot="content" style="font-size:11px">
                <p v-for="item in statusInfo.cpu.cpu" :key="item">{{ item }}</p>
                <p>{{ statusInfo.cpu.cpuPhysical + $t('index.cpuPhysical') }}</p>
                <p>{{ statusInfo.cpu.cpuCore + $t('index.cpuCore') }}</p>
                <p>{{ statusInfo.cpu.cpuProcessor + $t('index.cpuProcessor') }}</p>
              </div>
            </Tooltip>
            <div class="statusBottom">{{ statusInfo.cpu.cpuCore }}核心</div>
          </Col>
          <Col span="4">
            <div class="statusTop">{{ $t('index.memUsage') }}</div>
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
              <div slot="content" style="font-size:11px">
                <p>{{ $t('index.fileSystem') + item.fileSystem }}</p>
                <p>{{ $t('index.fsSize') + item.size }}</p>
                <p>{{ $t('index.fsUsed') + item.used }}</p>
                <p>{{ $t('index.fsAvail') + item.avail }}</p>
                <p>{{ $t('index.fsPercent') + item.percent }}%</p>
                <p>{{ $t('index.mountOn') + item.mountedOn }}</p>
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
        {{ $t('index.statistics') }}
      </p>
      <div>
        <Row :gutter="16" style="margin-bottom: 10px">
          <Col span="4">
            <Card :dis-hover="true">
              <div style="text-align: center">
                <p>{{ $t('index.svnRepo') }}</p>
                <h2 style="color: #28bcfe">{{ systemBrif.repCount }}</h2>
              </div>
            </Card>
          </Col>
          <Col span="4">
            <Card :dis-hover="true">
              <div style="text-align: center">
                <p>{{ $t('index.repoSize') }}</p>
                <h2 style="color: #28bcfe">{{ systemBrif.repSize }}</h2>
              </div>
            </Card>
          </Col>
          <Col span="4">
            <Card :dis-hover="true">
              <div style="text-align: center">
                <p>{{ $t('index.repoBackup') }}</p>
                <h2 style="color: #28bcfe">{{ systemBrif.backupCount }}</h2>
              </div>
            </Card>
          </Col>
          <Col span="4">
            <Card :dis-hover="true">
              <div style="text-align: center">
                <p>{{ $t('index.backupSize') }}</p>
                <h2 style="color: #28bcfe">{{ systemBrif.backupSize }}</h2>
              </div>
            </Card>
          </Col>
          <Col span="4">
            <Card :dis-hover="true">
              <div style="text-align: center">
                <p>{{ $t('index.logs') }}</p>
                <h2 style="color: #28bcfe">{{ systemBrif.logCount }}</h2>
              </div>
            </Card>
          </Col>
        </Row>
        <Row :gutter="16">
          <Col span="4">
            <Card :dis-hover="true">
              <div style="text-align: center">
                <p>{{ $t('roles.管理员') }}</p>
                <h2 style="color: #28bcfe">{{ systemBrif.adminCount }}</h2>
              </div>
            </Card>
          </Col>
          <Col span="4">
            <Card :dis-hover="true">
              <div style="text-align: center">
                <p>{{ $t('roles.子管理员') }}</p>
                <h2 style="color: #28bcfe">{{ systemBrif.subadminCount }}</h2>
              </div>
            </Card>
          </Col>
          <Col span="4">
            <Card :dis-hover="true">
              <div style="text-align: center">
                <p>{{ $t('roles.SVN用户') }}</p>
                <h2 style="color: #28bcfe">{{ systemBrif.userCount }}</h2>
              </div>
            </Card>
          </Col>
          <Col span="4">
            <Card :dis-hover="true">
              <div style="text-align: center">
                <p>{{ $t('menus.SVN分组') }}</p>
                <h2 style="color: #28bcfe">{{ systemBrif.groupCount }}</h2>
              </div>
            </Card>
          </Col>
          <Col span="4">
            <Card :dis-hover="true">
              <div style="text-align: center">
                <p>{{ $t('index.svnAlias') }}</p>
                <h2 style="color: #28bcfe">{{ systemBrif.aliaseCount }}</h2>
              </div>
            </Card>
          </Col>
        </Row>
      </div>
    </Card>
  </div>
</template>

<script>
import i18n from "@/i18n";
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

        repCount: 0,
        repSize: 0,

        backupCount: 0,
        backupSize: 0,

        logCount: 0,

        adminCount: 0,
        subadminCount: 0,
        userCount: 0,
        groupCount: 0,
        aliaseCount: 0,
      },
    };
  },
  computed: {},
  created() {},
  mounted() {
    var that = this;
    if (that.display.part1) {
      that.GetDiskInfo();
      that.GetLoadInfo();
      //设置定时器
      that.timer = window.setInterval(() => {
        setTimeout(that.GetLoadInfo(), 0);
      }, 3000);
      //离开页面清除定时器
      that.$once("hook:beforeDestroy", () => {
        clearInterval(that.timer);
      });
    }
    if (that.display.part2) {
      that.GetStatisticsInfo();
    }
  },
  methods: {
    /**
     * 获取磁盘
     */
    GetDiskInfo() {
      var that = this;
      var data = {};
      that.$axios
        .post("api.php?c=Statistics&a=GetDiskInfo&t=web", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            // that.$Message.success(result.message);
            that.diskList = result.data;
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
     * 获取状态
     */
    GetLoadInfo() {
      var that = this;
      var data = {};
      that.$axios
        .post("api.php?c=Statistics&a=GetLoadInfo&t=web", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            // that.$Message.success(result.message);
            that.statusInfo = result.data;
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
     * 获取统计
     */
    GetStatisticsInfo() {
      var that = this;
      var data = {};
      that.$axios
        .post("api.php?c=Statistics&a=GetStatisticsInfo&t=web", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            // that.$Message.success(result.message);
            that.systemBrif = result.data;
          } else {
            that.$Message.error({ content: result.message, duration: 2 });
          }
        })
        .catch(function (error) {
          console.log(error);
          that.$Message.error(i18n.t('errors.contactAdmin'));
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