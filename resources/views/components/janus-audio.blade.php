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
    <div class="mt-4 text-gray-700">
        <h2 class="text-xl font-bold" x-text="title"></h2>
        <p class="text-sm" x-text="artist + ' — ' + album"></p>
    </div>

    <audio x-ref="audio" controls playsinline preload="auto" autoplay muted style="display:none"></audio>


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

    <button class="px-3 py-1 mt-2 text-white bg-green-600 rounded"
        @click="updateMediaMetadata({
      title: 'Scanner Test',
      artist: 'Zone 5',
      album: 'Special Ops'
    })">
        🔄 Update Metadata
    </button>

</div>
