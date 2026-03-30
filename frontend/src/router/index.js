import { createRouter, createWebHistory } from "vue-router";
import HomeView from "../views/HomeView.vue";
import EventDetailView from "../views/EventDetailView.vue";
import InformationView from "../views/InformationView.vue";

const routes = [
  { path: "/", name: "Home", component: HomeView },
  { path: "/event/:id", name: "EventDetail", component: EventDetailView },
  { path: "/information", name: "Information", component: InformationView },
];

export default createRouter({
    history: createWebHistory(),
    routes,
})
