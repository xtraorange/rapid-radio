@props(['wsUrl' => 'ws://scanner:3000/ws', 'streamId' => 1, 'autoplay' => false, 'debug' => false])

<!-- External Assets -->
<link href="https://fonts.googleapis.com/css2?family=Digital+7+Mono&display=swap" rel="stylesheet" />


<style>
    /* Shared Styles */
    .digital {
        font-family: "Digital 7 Mono", monospace;
    }

    .neon {
        text-shadow: 0 0 4px #00ff00;
    }

    .display-recessed {
        border: 3px solid #222;
        box-shadow: inset 0 0 8px rgba(0, 0, 0, 0.8);
    }

    .outer-bezel {
        border-top: 4px solid rgba(255, 255, 255, 0.2);
        border-left: 4px solid rgba(255, 255, 255, 0.2);
        border-bottom: 4px solid rgba(0, 0, 0, 0.3);
        border-right: 4px solid rgba(0, 0, 0, 0.3);
    }

    .scanner-bg {
        background: #2a3439;
    }

    .btn-3d {
        border: 1px solid #555;
        box-shadow: inset 2px 2px 4px rgba(0, 0, 0, 0.5);
    }

    .btn-convex {
        position: relative;
        border-top: 1px solid rgba(255, 255, 255, 0.3);
        border-left: 1px solid rgba(255, 255, 255, 0.3);
        border-bottom: 1px solid rgba(0, 0, 0, 0.2);
        border-right: 1px solid rgba(0, 0, 0, 0.2);
        box-shadow: 2px 2px 3px rgba(0, 0, 0, 0.1);
        border-radius: 4px;
    }

    .btn-convex::before {
        content: "";
        position: absolute;
        top: -2px;
        left: -2px;
        right: -2px;
        bottom: -2px;
        border: 1px solid #000;
        border-radius: 5px;
        pointer-events: none;
    }

    .slider-container {
        box-shadow: inset 2px 2px 4px rgba(0, 0, 0, 0.5);
    }

    .power-slider {
        width: 4rem;
        /* 64px */
        height: 5.5rem;
    }

    .power-toggle-btn {
        width: 3.5rem;
        /* 56px */
        height: 3rem;
    }

    .volume-knob {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background: white;
        border: 2px solid #555;
        box-shadow: 0 6px 10px rgba(0, 0, 0, 0.25);
    }

    .volume-knob::before {
        content: none;
    }

    .label {
        font-family: sans-serif;
        font-size: 0.75rem;
        text-transform: uppercase;
        font-weight: normal;
    }

    .btn-text {
        font-family: sans-serif;
        font-weight: bold;
        text-transform: uppercase;
    }

    /* Mobile-Specific Styles */
    .mobile-scanner {
        background: #2a3439;
        border: 4px solid rgba(0, 0, 0, 0.5);
        border-radius: 1rem;
        padding: 1rem;
        box-shadow: 0 8px 12px rgba(0, 0, 0, 0.4);
        position: relative;
        margin-top: 2rem;
        /* Pushes the scanner down */
    }

    /* Antenna: bottom edge touches the top of the scanner */
    .antenna {
        position: absolute;
        bottom: 100%;
        left: 2rem;
        width: 4.5rem;
        height: 5rem;
        border-top: 1px solid rgba(255, 255, 255, 0.3);
        border-left: 1px solid rgba(255, 255, 255, 0.3);
        border-right: 1px solid rgba(100, 100, 100, 0.2);
        background: linear-gradient(to right, #2a3439, #151d22);
        border-radius: 0.25rem;
    }

    /* Mobile volume knob: same gradient as the antenna,
         but remove the bottom border so its bottom edge appears attached */
    .mobile-volume-knob {
        position: absolute;
        bottom: 100%;
        right: 2rem;
        width: 3.75rem;
        height: 3rem;
        background: linear-gradient(to right, #2a3439, #151d22);
        border-top: 1px solid rgba(255, 255, 255, 0.3);
        border-left: 1px solid rgba(255, 255, 255, 0.3);
        border-right: 1px solid rgba(100, 100, 100, 0.2);
        border-bottom: none;
        /* Removed bottom border */
        border-radius: 0.25rem;
    }

    .mobile-volume-knob::after {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: repeating-linear-gradient(90deg,
                transparent,
                transparent 0.4rem,
                rgba(0, 0, 0, 0.8) 0.4rem,
                rgba(0, 0, 0, 0.8) 0.5rem);
        pointer-events: none;
    }

    /* Mobile power button, antenna, and other styles remain unchanged */
    .mobile-power-btn {
        background: orange;
        color: white;
        width: 2.5rem;
        height: 2.5rem;
        border: none;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
    }

    .mobile-btns,
    .mobile-presets {
        width: 100%;
    }
</style>

<!-- Scanner HTML and Alpine logic follow -->
<div x-data="janusAudioPlayer({
    wsUrl: '{{ $wsUrl }}',
    streamId: {{ $streamId }},
    autoplay: {{ $autoplay ? 'true' : 'false' }},
    debug: {{ $debug ? 'true' : 'false' }}
})" x-init="init" class="w-full">
    <!-- Desktop Layout (visible on md and up) -->
    <div class="items-center justify-center hidden min-h-screen md:flex">
        <div class="flex flex-row w-full max-w-6xl p-6 scanner-bg outer-bezel rounded-xl">
            <!-- Left Section -->
            <div class="flex flex-col justify-between mr-6">
                <!-- Power Slider (replaced with toggle button) -->
                <div class="flex flex-col items-center mb-2">
                    <button @click="togglePower()"
                        class="relative flex items-center justify-center bg-gray-700 rounded-md cursor-pointer power-slider slider-container">
                        <div x-bind:style="poweredOn ? 'top: 4px; bottom: auto;' : 'bottom: 4px; top: auto;'"
                            class="absolute transition-all duration-300 transform -translate-x-1/2 bg-gray-200 rounded-md left-1/2 power-toggle-btn">
                        </div>
                    </button>
                    <div class="mt-2 text-white label">Power</div>
                </div>
                <!-- Volume Knob -->
                <div class="flex flex-col items-center">
                    <div class="volume-knob btn-convex"></div>
                    <div class="mt-1 text-white label">Volume</div>
                </div>
            </div>
            <!-- Center Section -->
            <div class="flex flex-col justify-between flex-1">
                <div class="relative p-6 bg-black rounded-md display-recessed">
                    <div class="absolute inset-0 bg-green-800 rounded-md opacity-20"></div>
                    <div class="relative text-center">
                        <div class="text-5xl text-green-400 digital" :class="{ 'neon': poweredOn }"
                            x-text="poweredOn ? 'CH05' : '-'">
                            CH05
                        </div>
                        <div class="mt-2 text-5xl text-green-400 digital" :class="{ 'neon': poweredOn }"
                            x-text="poweredOn ? 'ACTIVE' : '-'">
                            ACTIVE
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-2 mt-2">
                    <button @click="sendCommand('skip')"
                        class="px-2 py-1 text-xs text-white bg-blue-600 rounded btn-convex hover:bg-blue-700 btn-text">
                        Skip
                    </button>
                    <button @click="sendCommand('hold')"
                        class="px-2 py-1 text-xs text-white bg-blue-600 rounded btn-convex hover:bg-blue-700 btn-text">
                        Hold
                    </button>
                    <button @click="sendCommand('goto')"
                        class="px-2 py-1 text-xs text-white bg-blue-600 rounded btn-convex hover:bg-blue-700 btn-text">
                        Goto
                    </button>
                    <button @click="sendCommand('blacklist')"
                        class="px-2 py-1 text-xs text-white bg-blue-600 rounded btn-convex hover:bg-blue-700 btn-text">
                        Blacklist
                    </button>
                    <button @click="sendCommand('whitelist')"
                        class="px-2 py-1 text-xs text-white bg-blue-600 rounded btn-convex hover:bg-blue-700 btn-text">
                        Whitelist
                    </button>
                    <button @click="sendCommand('log')"
                        class="px-2 py-1 text-xs text-white bg-blue-600 rounded btn-convex hover:bg-blue-700 btn-text">
                        Log
                    </button>
                </div>
            </div>
            <!-- Right Section -->
            <div class="flex flex-col justify-between w-1/3 ml-6">
                <div class="flex justify-end mt-4 space-x-4">
                    <template x-for="label in ['Hold', 'Recv', 'Scan']">
                        <div class="flex flex-col items-center">
                            <div :class="poweredOn ? 'bg-green-500' : 'bg-gray-600'" class="w-20 h-4 rounded btn-3d">
                            </div>
                            <span class="mt-1 text-white label" x-text="label"></span>
                        </div>
                    </template>
                </div>
                <div class="grid grid-cols-3 gap-2 mt-6">
                    <template x-for="n in 9">
                        <button
                            class="px-2 py-2 font-bold text-gray-900 uppercase bg-gray-200 rounded btn-convex hover:bg-gray-300 btn-text"
                            x-text="n"></button>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile Layout (Handheld Scanner) -->
    <div class="flex flex-col items-center justify-center p-4 md:hidden">
        <div class="relative w-full max-w-sm p-4 mx-auto mobile-scanner rounded-xl">
            <div class="antenna"></div>
            <div class="mobile-volume-knob"></div>
            <div class="relative w-full p-4 mt-4 bg-black rounded-md display-recessed">
                <div class="absolute inset-0 bg-green-800 rounded-md opacity-20"></div>
                <div class="relative text-center">
                    <div class="text-4xl text-green-400 digital" :class="{ 'neon': poweredOn }"
                        x-text="poweredOn ? 'CH05' : '-'">
                        CH05
                    </div>
                    <div class="mt-2 text-4xl text-green-400 digital" :class="{ 'neon': poweredOn }"
                        x-text="poweredOn ? 'ACTIVE' : '-'">
                        ACTIVE
                    </div>
                </div>
            </div>
            <div class="flex justify-end w-full mt-4">
                <button @click="togglePower()" class="flex items-center justify-center mobile-power-btn">
                    ⏻
                </button>
            </div>
            <div class="w-full mt-4 space-y-4">
                <div class="grid grid-cols-3 gap-2 mobile-btns">
                    <button @click="sendCommand('skip')"
                        class="py-1 text-xs text-white bg-blue-600 rounded btn-convex hover:bg-blue-700 btn-text">
                        Skip
                    </button>
                    <button @click="sendCommand('hold')"
                        class="py-1 text-xs text-white bg-blue-600 rounded btn-convex hover:bg-blue-700 btn-text">
                        Hold
                    </button>
                    <button @click="sendCommand('goto')"
                        class="py-1 text-xs text-white bg-blue-600 rounded btn-convex hover:bg-blue-700 btn-text">
                        Goto
                    </button>
                    <button @click="sendCommand('blacklist')"
                        class="py-1 text-xs text-white bg-blue-600 rounded btn-convex hover:bg-blue-700 btn-text">
                        Blacklist
                    </button>
                    <button @click="sendCommand('whitelist')"
                        class="py-1 text-xs text-white bg-blue-600 rounded btn-convex hover:bg-blue-700 btn-text">
                        Whitelist
                    </button>
                    <button @click="sendCommand('log')"
                        class="py-1 text-xs text-white bg-blue-600 rounded btn-convex hover:bg-blue-700 btn-text">
                        Log
                    </button>
                </div>
                <div class="grid grid-cols-3 gap-2 mobile-presets">
                    <template x-for="n in 9">
                        <button
                            class="py-2 font-bold text-gray-900 uppercase bg-gray-200 rounded btn-convex hover:bg-gray-300 btn-text"
                            x-text="n"></button>
                    </template>
                </div>
                <div class="flex justify-center mt-4 space-x-4">
                    <template x-for="label in ['Hold', 'Recv', 'Scan']">
                        <div class="flex flex-col items-center">
                            <div :class="poweredOn ? 'bg-green-500' : 'bg-gray-600'" class="w-16 h-3 btn-3d"></div>
                            <span class="mt-1 text-white label" x-text="label"></span>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <!-- Audio Elements -->
    <audio x-ref="audio" playsinline preload="auto" class="hidden"></audio>
    <audio x-ref="activator" src="/silent.mp3" preload="auto" class="hidden"></audio>
</div>
