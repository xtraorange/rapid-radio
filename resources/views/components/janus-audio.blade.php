@props(['wsUrl' => 'ws://scanner:3000/ws', 'streamId' => 1, 'autoplay' => false, 'debug' => false])

<!-- External Assets -->
<link href="https://fonts.googleapis.com/css2?family=Digital+7+Mono&display=swap" rel="stylesheet" />

<style>
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
        border-radius: 1rem;
        box-shadow: 0 8px 12px rgba(0, 0, 0, 0.4);
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
        height: 5.5rem;
    }

    .power-toggle-btn {
        width: 3.5rem;
        height: 3rem;
    }

    .volume-knob {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background: white;
        border: 2px solid #555;
        box-shadow: 0 6px 10px rgba(0, 0, 0, 0.25);
        cursor: pointer;
        z-index: 20;
        position: relative;
    }

    .volume-knob::before {
        content: none;
    }

    .volume-slider-wrapper {
        background: white;
        border: 2px solid #555;
        border-radius: 9999px;
        padding: 0.25rem 0.75rem;
        z-index: 100;
        pointer-events: auto;
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

    .mobile-scanner {
        max-width: 24rem;
        width: 100%;
        position: relative;
        border: 4px solid rgba(0, 0, 0, 0.5);
        border-radius: 1rem;
        box-shadow: 0 8px 12px rgba(0, 0, 0, 0.4);
        background: #2a3439;
        padding: 1rem;
        z-index: 10;
    }

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
        z-index: 5;
    }

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
        border-radius: 0.25rem;
        cursor: pointer;
        z-index: 20;
    }

    .mobile-volume-knob::after {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: repeating-linear-gradient(90deg, transparent, transparent 0.4rem, rgba(0, 0, 0, 0.8) 0.4rem, rgba(0, 0, 0, 0.8) 0.5rem);
        pointer-events: none;
    }

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
        z-index: 30;
    }
</style>

<div x-data="janusAudioPlayer({ wsUrl: '{{ $wsUrl }}', streamId: {{ $streamId }}, autoplay: {{ $autoplay ? 'true' : 'false' }}, debug: {{ $debug ? 'true' : 'false' }} })" x-init="init" class="flex items-center justify-center w-full min-h-screen p-4">
    <div class="w-[72rem]">

        <!-- Mobile Layout -->
        <div class="mx-auto md:hidden mobile-scanner">
            <div class="antenna"></div>
            <div class="mobile-volume-knob volume-toggle" @click="toggleVolumeSlider($event)"></div>

            <div class="relative p-6 mt-4 bg-black rounded-md display-recessed">
                <div class="absolute inset-0 bg-green-800 rounded-md opacity-20"></div>
                <div class="relative text-center">
                    <div class="text-4xl text-green-400 digital" :class="{ 'neon': poweredOn }"
                        x-text="poweredOn ? 'CH05' : '-'"></div>
                    <div class="mt-1 text-xl text-green-400 digital" :class="{ 'neon': poweredOn }"
                        x-text="currentTime"></div>
                </div>
            </div>
            <div class="flex justify-end w-full mt-4">
                <button @click="togglePower()" class="mobile-power-btn">⏻</button>
            </div>
            <div class="grid grid-cols-3 gap-2 mt-4">
                <template x-for="cmd in ['skip','hold','goto','blacklist','whitelist','log']">
                    <button @click="sendCommand(cmd)"
                        class="py-1 text-xs text-white bg-blue-600 rounded btn-convex hover:bg-blue-700 btn-text"
                        x-text="cmd.charAt(0).toUpperCase() + cmd.slice(1)"></button>
                </template>
            </div>
            <div class="grid grid-cols-3 gap-2 mt-4">
                <template x-for="n in 9">
                    <button
                        class="py-2 font-bold text-gray-900 uppercase bg-gray-200 rounded btn-convex hover:bg-gray-300 btn-text"
                        x-text="n"></button>
                </template>
            </div>
            <!-- LED Indicator Row: Added w-full to center the indicators -->
            <div class="flex items-center justify-center w-full mt-4">
                <template x-for="label in ['Hold', 'Recv', 'Scan']">
                    <div class="flex flex-col items-center mx-2">
                        <div :class="poweredOn ? 'bg-green-500' : 'bg-gray-600'" class="w-16 h-3 rounded btn-3d"></div>
                        <span class="mt-1 text-white label" x-text="label"></span>
                    </div>
                </template>
            </div>
        </div>

        <!-- Desktop Layout -->
        <div class="hidden md:flex scanner-bg outer-bezel rounded-xl w-[72rem] max-w-[72rem] p-4">
            <div class="flex flex-col items-center justify-between mr-6">
                <div class="flex flex-col items-center mb-2">
                    <button @click="togglePower()"
                        class="relative flex items-center justify-center bg-gray-700 rounded-md power-slider slider-container">
                        <div :class="poweredOn ? 'top-1 bottom-auto' : 'bottom-1 top-auto'"
                            class="absolute transition-all duration-300 transform -translate-x-1/2 bg-gray-200 rounded-md power-toggle-btn left-1/2">
                        </div>
                    </button>
                    <div class="mt-2 text-white label">Power</div>
                </div>
                <div class="relative flex flex-col items-center">
                    <div class="cursor-pointer volume-knob btn-convex volume-toggle"
                        @click="toggleVolumeSlider($event)"></div>
                    <div class="mt-1 text-white label">Volume</div>

                </div>
            </div>
            <div class="flex flex-col justify-between flex-1">
                <div class="relative p-6 bg-black rounded-md display-recessed">
                    <div class="absolute inset-0 bg-green-800 rounded-md opacity-20"></div>
                    <div class="relative text-center">
                        <div class="text-5xl text-green-400 digital" :class="{ 'neon': poweredOn }"
                            x-text="poweredOn ? 'CH05' : '-'"></div>
                        <div class="mt-1 text-xl text-green-400 digital" :class="{ 'neon': poweredOn }"
                            x-text="currentTime"></div>
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-2 mt-4">
                    <template x-for="cmd in ['skip','hold','goto','blacklist','whitelist','log']">
                        <button @click="sendCommand(cmd)"
                            class="py-1 text-xs text-white bg-blue-600 rounded btn-convex hover:bg-blue-700 btn-text"
                            x-text="cmd.charAt(0).toUpperCase() + cmd.slice(1)"></button>
                    </template>
                </div>
            </div>
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
                            class="py-2 font-bold text-gray-900 uppercase bg-gray-200 rounded btn-convex hover:bg-gray-300 btn-text"
                            x-text="n"></button>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <!-- Centralized Volume Slider Element -->
    <div class="volume-slider-wrapper" x-show="showVolumeSlider" :style="volumeSliderStyle" x-ref="volumeSlider">
        <input type="range" min="0" max="1" step="0.01" @input="setVolume" x-model="volume">
    </div>

    <audio x-ref="audio" playsinline preload="auto" aria-hidden="true" class="absolute invisible w-0 h-0"></audio>
</div>
