export default function janusAudioPlayer(config = {}) {
    return {
        // Config
        ws: null,
        wsUrl: config.wsUrl || "ws://localhost:3000/ws",
        streamId: config.streamId || 1,
        autoplay: config.autoplay ?? true,
        debug: config.debug ?? false,

        // Session
        sessionId: null,
        handleId: null,
        pc: null,
        isProcessingSDP: false,
        isWatching: false,
        iceQueue: [],

        // Audio state
        muted: true,
        autoplayUnlocked: false,

        // Metadata state
        title: "Rapid Radio",
        artist: "Live Feed",
        album: "Scanner",

        init() {
            this.log("Janus Audio Player initializing...");
            this.connect();
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

        log(...args) {
            if (this.debug) {
                console.log("[JanusPlayer]", ...args);
            }
        },

        connect() {
            this.log("Connecting to WebSocket:", this.wsUrl);
            this.ws = new WebSocket(this.wsUrl);

            this.ws.onopen = () => {
                const waitAndSend = () => {
                    if (this.ws.readyState === WebSocket.OPEN) {
                        this.log("WebSocket open — sending janus init");
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
                this.log("Janus session ready:", msg.sessionId);
                this.sessionId = msg.sessionId;
                this.handleId = msg.handleId;
            } else if (msg.janus === "event" && msg.jsep) {
                this.log("Received JSEP offer");
                this.handleRemoteSDP(msg.jsep);
            } else if (msg.janus === "trickle") {
                this.log("Got ICE candidate");
                this.addIceCandidate(msg.candidate);
            } else if (msg.janus === "webrtcup") {
                this.log("WebRTC is up!");
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
                const stream = e.streams[0];
                this.$refs.audio.srcObject = stream;
                this.$refs.audio.muted = false;
                this.muted = false;

                this.$refs.audio
                    .play()
                    .then(() => {
                        this.log("✅ Audio playback started");
                        this.setupMediaSession(); // Only after play starts
                    })
                    .catch((err) => {
                        this.muted = true;
                        console.warn("[JanusPlayer] ⚠️ Playback error:", err);
                    });
            };

            this.pc.oniceconnectionstatechange = () => {
                if (
                    ["failed", "disconnected"].includes(
                        this.pc.iceConnectionState
                    )
                ) {
                    this.log("ICE failed, reconnecting...");
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

        addIceCandidate(candidate) {
            if (this.pc) {
                this.pc.addIceCandidate(candidate).catch(console.error);
            } else {
                this.iceQueue.push(candidate);
            }
        },

        cleanup() {
            if (this.pc) {
                this.pc.close();
                this.pc = null;
            }
            this.isProcessingSDP = false;
        },

        startStream() {
            if (!this.sessionId || !this.handleId || this.isWatching) return;

            this.isWatching = true;
            this.log("Starting stream:", this.streamId);

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
            console.log("🎧 Attempting to set up MediaSession...");

            if (!("mediaSession" in navigator)) {
                console.warn("❌ MediaSession not supported");
                return;
            }

            console.log("✅ MediaSession is available");

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
                    {
                        src: "/logo-512.png",
                        sizes: "512x512",
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

            console.log("🎶 MediaSession metadata set.");
        },

        updateMediaMetadata({ title, artist, album, artwork } = {}) {
            this.title = title || this.title;
            this.artist = artist || this.artist;
            this.album = album || this.album;

            if (!("mediaSession" in navigator)) return;

            navigator.mediaSession.metadata = new MediaMetadata({
                title: this.title,
                artist: this.artist,
                album: this.album,
                artwork: artwork || [
                    {
                        src: "/logo-192.png",
                        sizes: "192x192",
                        type: "image/png",
                    },
                    {
                        src: "/logo-512.png",
                        sizes: "512x512",
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
    };
}
