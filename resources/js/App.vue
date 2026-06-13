<template>
  <div id="app" class="app">
    <router-view></router-view>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import axios from 'axios';

onMounted(() => {
  const token = localStorage.getItem('token');
  if (token) {
    axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
  }
});
</script>

<style>
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  background-color: #f5f5f5;
}

.app {
  min-height: 100vh;
}
</style>
