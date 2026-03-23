import { createRouter, createWebHistory } from "vue-router";
import HomeView from "../views/HomeView.vue";
import EventDetailView from "../views/EventDetailView.vue";

const routes = [
  { path: "/", name: "Home", component: HomeView },
  { path: "/event/:id", name: "EventDetail", component: EventDetailView },
];

export default createRouter({
    history: createWebHistory(),
    routes,
})
