<template>
  <div class="home">
    <h1>Home Page</h1>
    <p>Welcome to Laravel BFF + Vue.js!</p>
    <p>User: {{ user ? user.name : 'Guest' }}</p>
    <button @click="updateUser">Update User</button>
    <router-link to="/about">Go to About</router-link>
  </div>
</template>

<script>
export default {
  name: 'Home',
  computed: {
    user() {
      return this.$store.state.user;
    }
  },
  methods: {
    updateUser() {
      this.$store.commit('setUser', { name: 'John Doe', id: 1 });
    }
  }
}
</script>

<style scoped>
.home {
  padding: 20px;
}
button {
  margin: 10px;
  padding: 8px 16px;
}
</style>