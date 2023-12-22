<template>
  <!-- <img alt="Vue logo" src="./assets/logo.png"> -->
  <Header title="Raj" />
  <Button text="Add task" color="green"/>
  <Button text="Edit task" color="yellow"/>
  <Button text="Delete task" color="red"/>
  <Tasks :tasks="tasks"/>

  <div class="lifecycle-container">
    <Button 
      text="Press this button to learn about Vue lifeCycles" 
      color="green" 
      @click="onLifeCycleClick($event)"
    />
    <LifeCycle
      v-if="showLifeCycleComponent" 
    />
  </div>
</template>

<script>
import Header from './components/Header.vue';
import Button from './components/Button.vue';
import Tasks from './components/Tasks.vue';
import LifeCycle from './components/LifeCycle.vue';

export default {
  name: 'App',
  components: {
    Header,
    Button,
    Tasks,
    LifeCycle,
  },
  data () {
    return {
      tasks: [],
      showLifeCycleComponent: false,
    }
  },
  methods: {
    onLifeCycleClick ($event) {
      // console.log($event);
      this.showLifeCycleComponent = !this.showLifeCycleComponent;
    }
  },
  created () {
    console.log(this.tasks);
    this.tasks = [
      {
        id: 0,
        text: "text0",
        day: "day0",
        reminder: "reminder0"
      },
      {
        id: 1,
        text: "text1",
        day: "day1",
        reminder: "reminder1"
      },
      {
        id: 2,
        text: "text2",
        day: "day2",
        reminder: "reminder2"
      },
      {
        id: 3,
        text: "text3",
        day: "day3",
        reminder: "reminder3"
      }
    ];
  }
}
</script>

<style lang="css">
  @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400&display=swap');

  * {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
  }

  body {
    font-family: 'Poppins', sans-serif;
  }

  .container {
    max-width: 500px;
    margin: 30px auto;
    overflow: auto;
    min-height: 300px;
    border: 1px solid steelblue;
    padding: 30px;
    border-radius: 5px;
  }

  .btn {
    display: inline-block;
    background: #000;
    color: #fff;
    border: none;
    padding: 10px 20px;
    margin: 5px;
    border-radius: 5px;
    cursor: pointer;
    text-decoration: none;
    font-size: 15px;
    font-family: inherit;
  }

  .btn:focus {
    outline: none;
  }

  .btn:active {
    transform: scale(0.98);
  }

  .btn-block {
    display: block;
    width: 100%;
  }

  .lifecycle-container {
    margin-top: 40px;
    margin-left: 20px;
  }
</style>
