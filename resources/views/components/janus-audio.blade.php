@props([
    'wsUrl' => 'ws://scanner:3000/ws',
    'streamId' => 1,
    'autoplay' => true,
    'debug' => false,
])

<div x-data="janusAudioPlayer({
    wsUrl: '{{ $wsUrl }}',
    streamId: {{ $streamId }},
    autoplay: {{ $autoplay ? 'true' : 'false' }},
    debug: {{ $debug ? 'true' : 'false' }}
})" x-init="init">
    <audio x-ref="audio" autoplay muted controls></audio>

    <div class="mt-4 space-x-2">
        <button @click="unlockAudio(); startStream()" :disabled="!sessionId || !handleId"
            class="px-4 py-2 text-white bg-blue-600 rounded disabled:opacity-50">
            ▶️ Start Stream
        </button>

        <button @click="sendCommand('hold')">⏸️ Hold</button>
        <button @click="sendCommand('lock')">🔒 Lock</button>
        <button @click="sendCommand('whitelist')">✅ Whitelist</button>
        <button @click="sendCommand('blacklist')">⛔ Blacklist</button>
    </div>


</div>
