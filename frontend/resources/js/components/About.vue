<template>
  <div class="about">
    <h1>About Page</h1>
    <p>This is the About page of our Laravel BFF application.</p>
    <p>Current user: {{ user ? user.name : 'No user logged in' }}</p>
    <router-link to="/">Back to Home</router-link>
  </div>
</template>

<script>
export default {
  name: 'About',
  computed: {
    user() {
      return this.$store.state.user;
    }
  }
}
</script>

<style scoped>
.about {
  padding: 20px;
}
</style>