<style scoped>
@import "styles/common.css";
</style>
<template>
  <div class="app">
    <router-view></router-view>
  </div>
</template>
<script>
export default {
  data() {
    return {};
  },
  mounted() {},
  beforeDestroy() {},
  methods: {},
};
</script>

<style lang="less">
.size {
  width: 100%;
  height: 100%;
}
.app,
html,
body {
  .size;
  overflow: hidden;
  margin: 0;
  padding: 0;
}
#app {
  .size;
}
</style>
