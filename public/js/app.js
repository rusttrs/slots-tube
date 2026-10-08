import "swiper/css";
import "swiper/css/navigation";
import "swiper/css/pagination";
import Swiper from "swiper";
import { Navigation, Pagination } from "swiper/modules";

window.Swiper = Swiper;
window.SwiperModules = { Navigation, Pagination };

await import("./main.js");
