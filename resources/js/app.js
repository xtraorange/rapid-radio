import "./bootstrap";
import janusAudioPlayer from "./components/janus-player";
import Alpine from "alpinejs";

window.Alpine = Alpine;

Alpine.data("janusAudioPlayer", janusAudioPlayer);
Alpine.start();
