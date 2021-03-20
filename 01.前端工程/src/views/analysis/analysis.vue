<style scoped lang="less">
.circle {
  // display: inline-block;
  width: 100px;
  height: 100px;
  margin: 0 auto;
}
.gailan {
  width: 200px;
  height: 40px;
  line-height: 40px;
  font-size: 32px;
  color: #28bcfe;
}
.p_top {
  margin-bottom: 4px;
  color: #999;
}
.p_bottom {
  margin-top: 4px;
  color: #999;
}
/* 一级导航 */
</style>
<template>
  <div>
    <Card :bordered="false" :dis-hover="true">
      <p slot="title" font-size:10px>状态</p>
      <Row>
        <Col span="4">
          <Card style="width: 200px" :dis-hover="true" :bordered="false">
            <div style="text-align: center">
              <P class="p_top">负载状态</P>
              <div id="fuzai" class="circle"></div>
              <P class="p_bottom">运行流畅</P>
            </div>
          </Card>
        </Col>
        <Col span="4">
          <Card style="width: 200px" :dis-hover="true" :bordered="false">
            <div style="text-align: center">
              <P class="p_top">CPU利用率</P>
              <div id="cpu" class="circle"></div>
              <P class="p_bottom">1核心</P>
            </div>
          </Card>
        </Col>
        <Col span="4">
          <Card style="width: 200px" :dis-hover="true" :bordered="false">
            <div style="text-align: center">
              <P class="p_top">内存利用率</P>
              <div id="neicun" class="circle"></div>
              <P class="p_bottom"
                >{{ this.system_mem_status.used }}/{{
                  this.system_mem_status.total
                }}(MB)</P
              >
            </div>
          </Card>
        </Col>
        <Col span="4">
          <Card style="width: 200px" :dis-hover="true" :bordered="false">
            <div style="text-align: center">
              <P class="p_top">硬盘利用率</P>
              <div id="yingpan" class="circle"></div>
              <P class="p_bottom"
                >{{ this.system_disk_status.DiskUsed }}/{{
                  this.system_disk_status.DiskTotal
                }}(GB)</P
              >
            </div>
          </Card>
        </Col>
      </Row>
    </Card>
    <br />
    <Card :bordered="false" :dis-hover="true">
      <p slot="title" font-size:10px>概览</p>
      <Row>
        <Col span="4">
          <Card style="width: 235px" :dis-hover="true" :bordered="true">
            <div style="text-align: center">
              <P class="p_top">操作系统</P>
              <div class="gailan">{{ this.gailan.os_type }}</div>
            </div>
          </Card>
        </Col>
        <Col span="4">
          <Card style="width: 235px" :dis-hover="true" :bordered="true">
            <div style="text-align: center">
              <P class="p_top">服务器运行天数</P>
              <div class="gailan">{{ this.gailan.os_runtime }}</div>
            </div>
          </Card>
        </Col>
        <Col span="4">
          <Card style="width: 235px" :dis-hover="true" :bordered="true">
            <div style="text-align: center">
              <P class="p_top">SVN仓库</P>
              <div class="gailan">{{ this.gailan.repository_count }}</div>
            </div>
          </Card>
        </Col>
        <Col span="4">
          <Card style="width: 235px" :dis-hover="true" :bordered="true">
            <div style="text-align: center">
              <P class="p_top">超级管理员</P>
              <div class="gailan">{{ this.gailan.super_count }}</div>
            </div>
          </Card>
        </Col>
        <Col span="4">
          <Card style="width: 235px" :dis-hover="true" :bordered="true">
            <div style="text-align: center">
              <P class="p_top">系统管理员</P>
              <div class="gailan">{{ this.gailan.sys_count }}</div>
            </div>
          </Card>
        </Col>
        <Col span="4">
          <Card style="width: 235px" :dis-hover="true" :bordered="true">
            <div style="text-align: center">
              <P class="p_top">普通用户</P>
              <div class="gailan">{{ this.gailan.user_count }}</div>
            </div>
          </Card>
        </Col>
      </Row>
    </Card>
    <br />
    <Card :bordered="false" :dis-hover="true">
      <p slot="title" font-size:10px>实时流量</p>
      <div v-for="(item, index) in network" :key="item.index">
        <Row>
          <Col span="20">
            <Card :dis-hover="true" :bordered="true">
              <p slot="title" font-size:10px>{{ index }}</p>
              <div :id="index" style="height: 230px"></div>
            </Card>
          </Col>
        </Row>
        <br />
      </div>
      <br />
    </Card>
  </div>
</template>

<script>
export default {
  data() {
    return {
      //网卡流量
      network: {
        // eth0: [
        //   {
        //     ReceiveSpeed: 0,
        //     TransmitSpeed: 0,
        //     time: "17:32:03",
        //   },
        // ],
      },
      //系统概览
      gailan: {
        os_type: "",
        os_runtime: "",
        repository_count: "",
        super_count: "",
        sys_count: "",
        user_count: "",
      },
      //系统负载状态
      system_fuzai_status: {
        avg_percent: "5",
        minute_1_avg: "最近1分钟平均负载：",
        minute_5_avg: "最近5分钟平均负载：",
        minute_15_avg: "最近15分钟平均负载：",
      },
      //CPU状态
      system_cpu_status: {
        percent: "5",
        num: "",
      },
      //内存状态
      system_mem_status: {
        total: "5",
        free: "5",
        used: "5",
        percent: "5",
      },
      //硬盘状态
      system_disk_status: {
        DiskTotal: "5",
        DiskFree: "5",
        DiskUsed: "5",
        DiskPercent: "5",
      },
    };
  },
  methods: {
    //1 第一次加载，请求所有网卡的数据，同时也等于得到了所有网卡的列表
    GetNetwork() {
      console.log("加载函数GetNetwork");
      var that = this;
      var data = {};
      that.$axios
        .post("/api.php?c=system&a=GetNetwork", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
            that.network = result.data;
          } else {
            that.$Message.error(result.message);
          }
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    //2 遍历网卡列表调用数据请求函数和画图函数
    NetworkDraw() {
      console.log("加载函数NetworkDraw");
      var that = this;
      Object.keys(that.network).forEach(function (item, index) {
        that.liuliangTable(item);
      });
    },
    //3 根据id画图
    liuliangTable(network_name) {
      console.log("加载函数liuliangTable");
      var that = this;
      var liuliang = that.$echarts.init(document.getElementById(network_name));
      var charts = {
        lineX: ["17:01", "17:02", "17:03", "17:04", "17:05", "17:06", "17:07"],
        // lineX: [],
        value: [
          [1451, 352, 303, 534, 95, 236, 217, 328],
          [360, 545, 80, 192, 330, 580, 192, 80],
        ],
        // value: [
        //   [],
        //   [],
        // ],
      };
      var option = {
        series: [
          {
            name: "上行",
            type: "line",
            color: "rgba(23, 255, 243)",
            smooth: true, //使用圆滑的线条
            symbol: "circle",
            data: charts.value[0],
            areaStyle: {}, //显示折线图对应的面积
            symbol: "none", //不显示折点圆圈
          },
          {
            name: "下行",
            type: "line",
            color: "rgba(255,100,97)",
            smooth: true,
            symbol: "circle",
            data: charts.value[1],
            areaStyle: {}, //显示折线图对应的面积
            symbol: "none", //不显示折点圆圈
          },
        ],
        tooltip: {
          trigger: "axis",
        },
        legend: {
          textStyle: {
            fontSize: 12,
          },
          right: "18%",
        },
        grid: {
          top: "14%",
          left: "1%",
          right: "13%",
          bottom: "0",
          containLabel: true, //防止标签溢出
        },
        xAxis: {
          type: "category",
          data: charts.lineX,
          boundaryGap: false,
        },
        yAxis: {
          name: "Kbps",
          type: "value",
        },
      };
      liuliang.setOption(option);
      // setInterval(function () {
      //   that.GetNetworkByName(network_name);
      //   liuliang.setOption(option);
      // }, 10000);
    },
    //4 接下来的加载，通过本地的网卡名称请求对应的网卡数据，动态push到data并显示data的长度及时删除超过范围的数据
    GetNetworkByName(network_name) {
      var that = this;
      var data = {
        network_name: network_name,
      };
      that.$axios
        .post("/api.php?c=system&a=GetNetworkByName", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.$Message.success(result.message);
            that.network[network_name].push(result.data[0].data[0]);
          } else {
            that.$Message.error(result.message);
          }
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    flush_on_time() {
      var that = this;
      that.GetCPURate();
      that.GetLoadAvg();
      that.GetMemInfo();
      that.GetDiskInfo();
    },
    GetGailan() {
      var that = this;
      var data = {
      };
      that.$axios
        .post("/api.php?c=svnserve&a=GetGailan", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            // that.$Message.success(result.message);
            that.gailan = result.data;
          } else {
            that.$Message.error(result.message);
          }
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    GetLoadAvg() {
      var that = this;
      var data = {};
      that.$axios
        .post("/api.php?c=system&a=GetLoadAvg", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            // that.$Message.success(result.message);
            that.system_fuzai_status = result.data;

            that.fuzaicircle();
          } else {
            that.$Message.error(result.message);
          }
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    GetCPURate() {
      var that = this;
      var data = {};
      that.$axios
        .post("/api.php?c=system&a=GetCPURate", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            // that.$Message.success(result.message);
            that.system_cpu_status = result.data;
            // console.log(that.system_cpu_status);
            that.cpucircle();
          } else {
            // console.log(result);
            that.$Message.error(result.message);
          }
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    GetMemInfo() {
      var that = this;
      var data = {};
      that.$axios
        .post("/api.php?c=system&a=GetMemInfo", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            // that.$Message.success(result.message);
            that.system_mem_status = result.data;

            that.neicuncricle();
          } else {
            that.$Message.error(result.message);
          }
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    GetDiskInfo() {
      var that = this;
      var data = {};
      that.$axios
        .post("/api.php?c=system&a=GetDiskInfo", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            // that.$Message.success(result.message);
            that.system_disk_status = result.data;

            that.yingpancricle();
          } else {
            that.$Message.error(result.message);
          }
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    fuzaicircle() {
      var that = this;
      let fuzai = that.$echarts.init(document.getElementById("fuzai"));
      var getvalue = [that.system_fuzai_status.avg_percent];
      fuzai.setOption({
        title: {
          text: getvalue + "%",
          textStyle: {
            color: "#28BCFE",
            fontSize: 20,
          },
          // subtext: "负载",
          // subtextStyle: {
          //   color: "#666666",
          //   fontSize: 12,
          // },
          // itemGap: 20,
          left: "center",
          top: "40%",
        },
        tooltip: {
          position: ["50%", "50%"],
          formatter: function (params) {
            return (
              '<span style="color: #fff;">' +
              that.system_fuzai_status.minute_1_avg +
              "<br/>" +
              that.system_fuzai_status.minute_5_avg +
              "<br/>" +
              that.system_fuzai_status.minute_15_avg +
              "</span>"
            );
          },
        },
        angleAxis: {
          max: 100,
          clockwise: true, // 逆时针
          // 隐藏刻度线
          show: false,
        },
        radiusAxis: {
          type: "category",
          show: true,
          axisLabel: {
            show: false,
          },
          axisLine: {
            show: false,
          },
          axisTick: {
            show: false,
          },
        },
        polar: {
          center: ["50%", "50%"],
          radius: ["85%", "100%"], //图形大小
        },
        series: [
          {
            type: "bar",
            data: getvalue,
            showBackground: true,
            backgroundStyle: {
              color: "#BDEBFF",
            },
            coordinateSystem: "polar",
            roundCap: true,
            barWidth: 9,
            itemStyle: {
              normal: {
                opacity: 1,
                color: new that.$echarts.graphic.LinearGradient(0, 0, 0, 1, [
                  {
                    offset: 0,
                    color: "#25BFFF",
                  },
                  {
                    offset: 1,
                    color: "#5284DE",
                  },
                ]),
                shadowBlur: 5,
                shadowColor: "#2A95F9",
              },
            },
          },
        ],
      });
    },
    cpucircle() {
      var that = this;
      let cpu = that.$echarts.init(document.getElementById("cpu"));
      var getvalue = [that.system_cpu_status.percent];
      cpu.setOption({
        title: {
          text: getvalue + "%",
          textStyle: {
            color: "#28BCFE",
            fontSize: 20,
          },
          // subtext: "CPU",
          // subtextStyle: {
          //   color: "#666666",
          //   fontSize: 12,
          // },
          itemGap: 20,
          left: "center",
          top: "40%",
        },
        tooltip: {
          formatter: function (params) {
            return (
              '<span style="color: #fff;">CPU使用率：' + getvalue + "%</span>"
            );
          },
        },
        angleAxis: {
          max: 100,
          clockwise: true, // 逆时针
          // 隐藏刻度线
          show: false,
        },
        radiusAxis: {
          type: "category",
          show: true,
          axisLabel: {
            show: false,
          },
          axisLine: {
            show: false,
          },
          axisTick: {
            show: false,
          },
        },
        polar: {
          center: ["50%", "50%"],
          radius: ["85%", "100%"], //图形大小
        },
        series: [
          {
            type: "bar",
            data: getvalue,
            showBackground: true,
            backgroundStyle: {
              color: "#BDEBFF",
            },
            coordinateSystem: "polar",
            roundCap: true,
            barWidth: 9,
            itemStyle: {
              normal: {
                opacity: 1,
                color: new that.$echarts.graphic.LinearGradient(0, 0, 0, 1, [
                  {
                    offset: 0,
                    color: "#25BFFF",
                  },
                  {
                    offset: 1,
                    color: "#5284DE",
                  },
                ]),
                shadowBlur: 5,
                shadowColor: "#2A95F9",
              },
            },
          },
        ],
      });
    },
    neicuncricle() {
      var that = this;
      let neicun = that.$echarts.init(document.getElementById("neicun"));
      //绘制内存使用情况图
      var getvalue = [that.system_mem_status.percent];
      neicun.setOption({
        title: {
          text: getvalue + "%",
          textStyle: {
            color: "#28BCFE",
            fontSize: 20,
          },
          // subtext: "内存",
          // subtextStyle: {
          //   color: "#666666",
          //   fontSize: 12,
          // },
          itemGap: 20,
          left: "center",
          top: "40%",
        },
        tooltip: {
          formatter: function (params) {
            return (
              '<span style="color: #fff;">内存使用率：' + getvalue + "%</span>"
            );
          },
        },
        angleAxis: {
          max: 100,
          clockwise: true, // 逆时针
          // 隐藏刻度线
          show: false,
        },
        radiusAxis: {
          type: "category",
          show: true,
          axisLabel: {
            show: false,
          },
          axisLine: {
            show: false,
          },
          axisTick: {
            show: false,
          },
        },
        polar: {
          center: ["50%", "50%"],
          radius: ["85%", "100%"], //图形大小
        },
        series: [
          {
            type: "bar",
            data: getvalue,
            showBackground: true,
            backgroundStyle: {
              color: "#BDEBFF",
            },
            coordinateSystem: "polar",
            roundCap: true,
            barWidth: 9,
            itemStyle: {
              normal: {
                opacity: 1,
                color: new that.$echarts.graphic.LinearGradient(0, 0, 0, 1, [
                  {
                    offset: 0,
                    color: "#25BFFF",
                  },
                  {
                    offset: 1,
                    color: "#5284DE",
                  },
                ]),
                shadowBlur: 5,
                shadowColor: "#2A95F9",
              },
            },
          },
        ],
      });
    },
    yingpancricle() {
      var that = this;
      let yingpan = that.$echarts.init(document.getElementById("yingpan"));
      var getvalue = [that.system_disk_status.DiskPercent];
      yingpan.setOption({
        title: {
          text: getvalue + "%",
          textStyle: {
            color: "#28BCFE",
            fontSize: 20,
          },
          // subtext: "硬盘",
          // subtextStyle: {
          //   color: "#666666",
          //   fontSize: 12,
          // },
          itemGap: 20,
          left: "center",
          top: "40%",
        },
        tooltip: {
          formatter: function (params) {
            return (
              '<span style="color: #fff;">硬盘使用率：' + getvalue + "%</span>"
            );
          },
        },
        angleAxis: {
          max: 100,
          clockwise: true, // 逆时针
          // 隐藏刻度线
          show: false,
        },
        radiusAxis: {
          type: "category",
          show: true,
          axisLabel: {
            show: false,
          },
          axisLine: {
            show: false,
          },
          axisTick: {
            show: false,
          },
        },
        polar: {
          center: ["50%", "50%"],
          radius: ["85%", "100%"], //图形大小
        },
        series: [
          {
            type: "bar",
            data: getvalue,
            showBackground: true,
            backgroundStyle: {
              color: "#BDEBFF",
            },
            coordinateSystem: "polar",
            roundCap: true,
            barWidth: 9,
            itemStyle: {
              normal: {
                opacity: 1,
                color: new that.$echarts.graphic.LinearGradient(0, 0, 0, 1, [
                  {
                    offset: 0,
                    color: "#25BFFF",
                  },
                  {
                    offset: 1,
                    color: "#5284DE",
                  },
                ]),
                shadowBlur: 5,
                shadowColor: "#2A95F9",
              },
            },
          },
        ],
      });
    },
  },
  created() {},
  mounted() {
    var that = this;
    const timer = window.setInterval(() => {
      setTimeout(that.flush_on_time(), 0);
    }, 5000);
    that.$once("hook:beforeDestroy", () => {
      clearInterval(timer);
    });

    that.GetGailan();

    that.GetLoadAvg();
    that.GetCPURate();
    that.GetMemInfo();
    that.GetDiskInfo();

    that.cpucircle();
    that.neicuncricle();
    that.yingpancricle();
    that.fuzaicircle();

    that.GetNetwork();
  },
  beforeDestroy() {
    // clearInterval(this.timer);
  },
  updated() {
    // var that = this;
    // that.NetworkDraw();
  },
};
</script>