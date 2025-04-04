export default function janusAudioPlayer(config = {}) {
    return {
        // Config
        ws: null,
        wsUrl: config.wsUrl || "ws://localhost:3000/ws",
        streamId: config.streamId || 1,
        autoplay: config.autoplay ?? true,
        debug: config.debug ?? false,

        // State
        poweredOn: false,
        sessionId: null,
        handleId: null,
        pc: null,
        isProcessingSDP: false,
        isWatching: false,
        iceQueue: [],
        muted: true,
        autoplayUnlocked: false,
        currentTime: "00:00",
        showVolumeSlider: false,
        volume: 1.0,
        volumeSliderStyle: "",

        title: "Rapid Radio",
        artist: "Live Feed",
        album: "Scanner",

        init() {
            this.log("Janus Audio Player initializing...");
            this.connect();
            this.updateTimeLoop();
            // Global click listener to close the volume slider if click occurs outside
            document.addEventListener("click", (event) => {
                if (this.showVolumeSlider) {
                    // If the click target is NOT inside the slider (using x-ref) and not on a volume toggle button
                    if (
                        this.$refs.volumeSlider &&
                        !this.$refs.volumeSlider.contains(event.target) &&
                        !event.target.closest(".volume-toggle")
                    ) {
                        this.showVolumeSlider = false;
                    }
                }
            });
        },

        togglePower() {
            this.poweredOn = !this.poweredOn;
            if (this.poweredOn) {
                this.unlockAudio();
                this.startStream();
            } else {
                this.stopStream();
            }
        },

        unlockAudio() {
            try {
                const AudioContext =
                    window.AudioContext || window.webkitAudioContext;
                const context = new AudioContext();
                const buffer = context.createBuffer(1, 1, 22050);
                const source = context.createBufferSource();
                source.buffer = buffer;
                source.connect(context.destination);
                source.start(0);
                this.autoplayUnlocked = true;
                this.muted = false;
                this.$refs.audio.muted = false;
                this.log("🔓 Audio context unlocked");
            } catch (err) {
                console.warn("[JanusPlayer] 🔒 Audio unlock failed:", err);
            }
        },

        connect() {
            this.log("Connecting to WebSocket:", this.wsUrl);
            this.ws = new WebSocket(this.wsUrl);

            this.ws.onopen = () => {
                const waitAndSend = () => {
                    if (this.ws.readyState === WebSocket.OPEN) {
                        this.ws.send(JSON.stringify({ service: "janus" }));
                    } else {
                        setTimeout(waitAndSend, 50);
                    }
                };
                waitAndSend();
            };

            this.ws.onmessage = (e) => {
                this.log("WebSocket message:", e.data);
                this.handleMessage(JSON.parse(e.data));
            };

            this.ws.onclose = () => {
                this.log("WebSocket closed, reconnecting...");
                this.cleanup();
                setTimeout(() => this.connect(), 3000);
            };

            this.ws.onerror = (err) => {
                console.error("[JanusPlayer] WebSocket error:", err);
            };
        },

        handleMessage(msg) {
            if (msg.event === "janus_session") {
                this.sessionId = msg.sessionId;
                this.handleId = msg.handleId;
            } else if (msg.janus === "event" && msg.jsep) {
                this.handleRemoteSDP(msg.jsep);
            } else if (msg.janus === "trickle") {
                this.addIceCandidate(msg.candidate);
            }
        },

        async handleRemoteSDP(jsep) {
            if (this.isProcessingSDP) return;
            this.isProcessingSDP = true;
            this.cleanup();

            this.pc = new RTCPeerConnection({
                iceServers: [{ urls: "stun:stun.l.google.com:19302" }],
            });

            this.pc.onicecandidate = (e) => {
                if (e.candidate) {
                    this.ws.send(
                        JSON.stringify({
                            janus: "trickle",
                            session_id: this.sessionId,
                            handle_id: this.handleId,
                            candidate: e.candidate,
                            transaction: `txn_${Date.now()}`,
                            service: "janus",
                        })
                    );
                }
            };

            this.pc.ontrack = (e) => {
                const audio = this.$refs.audio;
                audio.srcObject = e.streams[0];
                audio.preload = "auto";
                audio.autoplay = true;
                audio.muted = false;
                audio.volume = this.volume;
                audio
                    .play()
                    .then(() => {
                        this.muted = false;
                        this.log("✅ Audio playback started");
                        this.setupMediaSession();
                    })
                    .catch((err) => {
                        this.muted = true;
                        this.log("⚠️ Audio play failed:", err);
                    });
            };

            this.pc.oniceconnectionstatechange = () => {
                if (
                    ["failed", "disconnected"].includes(
                        this.pc.iceConnectionState
                    )
                ) {
                    this.cleanup();
                    this.isWatching = false;
                    setTimeout(() => this.startStream(), 2000);
                }
            };

            try {
                await this.pc.setRemoteDescription(jsep);
                for (const cand of this.iceQueue) {
                    await this.pc.addIceCandidate(cand);
                }
                this.iceQueue = [];

                const answer = await this.pc.createAnswer();
                await this.pc.setLocalDescription(answer);
                this.ws.send(
                    JSON.stringify({
                        janus: "message",
                        session_id: this.sessionId,
                        handle_id: this.handleId,
                        body: { request: "start" },
                        jsep: answer,
                        transaction: `txn_${Date.now()}`,
                        service: "janus",
                    })
                );
            } catch (err) {
                console.error("[JanusPlayer] SDP error:", err);
            } finally {
                this.isProcessingSDP = false;
            }
        },

        startStream() {
            if (!this.sessionId || !this.handleId || this.isWatching) return;

            this.isWatching = true;
            this.ws.send(
                JSON.stringify({
                    janus: "message",
                    session_id: this.sessionId,
                    handle_id: this.handleId,
                    body: {
                        request: "watch",
                        id: this.streamId,
                        audio: true,
                        video: false,
                    },
                    transaction: `txn_${Date.now()}`,
                    service: "janus",
                })
            );
        },

        stopStream() {
            const audio = this.$refs.audio;
            audio.pause();
            audio.srcObject = null;
            this.cleanup();
        },

        cleanup() {
            if (this.pc) {
                this.pc.close();
                this.pc = null;
            }
            this.isProcessingSDP = false;
        },

        addIceCandidate(candidate) {
            if (this.pc) {
                this.pc.addIceCandidate(candidate).catch(console.error);
            } else {
                this.iceQueue.push(candidate);
            }
        },

        sendCommand(cmd) {
            this.ws.send(
                JSON.stringify({
                    janus: "message",
                    session_id: this.sessionId,
                    handle_id: this.handleId,
                    body: { request: cmd },
                    transaction: `txn_${Date.now()}`,
                    service: "janus",
                })
            );
        },

        setupMediaSession() {
            if (!("mediaSession" in navigator)) return;

            navigator.mediaSession.metadata = new MediaMetadata({
                title: this.title,
                artist: this.artist,
                album: this.album,
                artwork: [
                    {
                        src: "/logo-192.png",
                        sizes: "192x192",
                        type: "image/png",
                    },
                ],
            });

            navigator.mediaSession.setActionHandler("play", () => {
                this.$refs.audio.play().catch(() => {});
            });

            navigator.mediaSession.setActionHandler("pause", () => {
                this.$refs.audio.pause();
            });
        },

        updateTimeLoop() {
            const format = (s) => String(Math.floor(s)).padStart(2, "0");
            const loop = () => {
                if (this.$refs.audio) {
                    const secs = this.$refs.audio.currentTime;
                    this.currentTime = `${format(secs / 60)}:${format(
                        secs % 60
                    )}`;
                }
                requestAnimationFrame(loop);
            };
            requestAnimationFrame(loop);
        },

        toggleVolumeSlider(event) {
            this.showVolumeSlider = !this.showVolumeSlider;
            if (this.showVolumeSlider && event) {
                let rect = event.currentTarget.getBoundingClientRect();
                let top = rect.bottom + window.scrollY;
                let left = rect.left + window.scrollX;
                this.volumeSliderStyle = `position: absolute; top: ${top}px; left: ${left}px;`;
            }
        },

        setVolume(event) {
            this.volume = parseFloat(event.target.value);
            this.$refs.audio.volume = this.volume;
        },

        log(...args) {
            if (this.debug) console.log("[JanusPlayer]", ...args);
        },
    };
}
