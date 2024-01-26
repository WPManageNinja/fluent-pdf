<template>
  <div>
    <GlobalSettings />
    <router-view />
  </div>
</template>
<script type="module">
import GlobalSettings from './GlobalSettings.vue'
export default {
  name: 'admin-layout',
  components: {
    GlobalSettings
  }
}
</script>
