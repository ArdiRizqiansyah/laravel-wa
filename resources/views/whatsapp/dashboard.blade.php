<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>WhatsApp Gateway Dashboard</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <!-- Tailwind fallback CDN for immediate aesthetics -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Instrument Sans', 'sans-serif'],
                    },
                }
            }
        }
    </script>
</head>
<body class="h-full font-sans antialiased bg-gradient-to-br from-slate-950 via-slate-900 to-emerald-950/20 text-slate-200">

    <div class="min-h-full flex flex-col">
        <!-- Header -->
        <header class="border-b border-slate-800/80 bg-slate-900/50 backdrop-blur-md sticky top-0 z-40">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 0 1-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8Z" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-lg font-bold tracking-tight text-white">WhatsApp Gateway</h1>
                        <p class="text-xs text-slate-400">Laravel 11 &amp; Web Sidecar integration</p>
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    <span id="sidecar-badge" class="inline-flex items-center gap-x-1.5 rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset transition-all duration-300">
                        <span id="sidecar-dot" class="h-1.5 w-1.5 rounded-full"></span>
                        <span id="sidecar-text">Checking server...</span>
                    </span>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Flash Alerts -->
            <div id="alert-container" class="space-y-4 mb-6">
                @if (session('success'))
                    <div class="alert-success p-4 rounded-xl border border-emerald-500/20 bg-emerald-950/20 text-emerald-400 flex items-start space-x-3 transition duration-300">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mt-0.5 shrink-0">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert-error p-4 rounded-xl border border-rose-500/20 bg-rose-950/20 text-rose-400 flex items-start space-x-3 transition duration-300">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mt-0.5 shrink-0">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                        </svg>
                        <span>{{ session('error') }}</span>
                    </div>
                @endif
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Left: Connection Management & QR -->
                <div class="lg:col-span-1 space-y-6">
                    <!-- Service Manager Card -->
                    <div class="bg-slate-900/50 border border-slate-800 rounded-2xl p-6 backdrop-blur-sm shadow-xl">
                        <h2 class="text-base font-bold text-white mb-4 flex items-center space-x-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                            <span>Service Manager</span>
                        </h2>

                        <div class="space-y-4">
                            <div class="flex items-center justify-between p-3 rounded-xl bg-slate-950/60 border border-slate-800/40">
                                <div>
                                    <div class="text-xs text-slate-400 font-medium">Node Sidecar Status</div>
                                    <div id="sidecar-status-detail" class="text-sm font-bold text-white mt-0.5">Checking...</div>
                                </div>
                                <div id="sidecar-actions">
                                    <form id="sidecar-form" method="POST" action="">
                                        @csrf
                                        <button type="submit" id="sidecar-btn" class="inline-flex items-center justify-center rounded-xl bg-emerald-600 px-3 py-2 text-xs font-semibold text-white shadow-sm hover:bg-emerald-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600 transition-all duration-300">
                                            Please wait...
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <div id="session-card" class="hidden flex items-center justify-between p-3 rounded-xl bg-slate-950/60 border border-slate-800/40">
                                <div>
                                    <div class="text-xs text-slate-400 font-medium">WhatsApp Session</div>
                                    <div id="session-status-detail" class="text-sm font-bold text-slate-200 mt-0.5 capitalize">Checking...</div>
                                </div>
                                <div id="session-actions" class="flex gap-2">
                                    <form id="session-start-form" method="POST" action="/whatsapp/session/start" class="hidden">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-500 shadow-sm transition">
                                            Start Session
                                        </button>
                                    </form>
                                    <form id="session-destroy-form" method="POST" action="/whatsapp/session/destroy" class="hidden">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-rose-600/20 border border-rose-500/20 px-3 py-2 text-xs font-semibold text-rose-400 hover:bg-rose-600/30 hover:text-white shadow-sm transition">
                                            Disconnect
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Connection Status & QR Code Card -->
                    <div id="qr-card" class="bg-slate-900/50 border border-slate-800 rounded-2xl p-6 backdrop-blur-sm shadow-xl hidden">
                        <h3 class="text-base font-bold text-white mb-3">WhatsApp Linking</h3>
                        
                        <!-- Disconnected State QR Code -->
                        <div id="qr-container" class="hidden flex flex-col items-center justify-center py-6">
                            <div class="p-3 bg-white rounded-xl shadow-inner border border-slate-200 mb-4 animate-fade-in">
                                <img id="qr-image" src="" alt="Scan QR Code" class="w-52 h-52">
                            </div>
                            <p class="text-xs text-slate-400 text-center max-w-[220px]">
                                Scan this QR code with WhatsApp Link Device on your phone.
                            </p>
                        </div>

                        <!-- Connecting / Initializing Loading State -->
                        <div id="session-loading" class="hidden flex flex-col items-center justify-center py-10 space-y-4">
                            <div class="relative w-12 h-12">
                                <div class="absolute inset-0 rounded-full border-4 border-emerald-500/20"></div>
                                <div class="absolute inset-0 rounded-full border-4 border-t-emerald-500 animate-spin"></div>
                            </div>
                            <div class="text-sm font-semibold text-slate-300" id="loading-text">Initializing session...</div>
                        </div>

                        <!-- Connected Status -->
                        <div id="connected-container" class="hidden flex flex-col items-center justify-center py-6 text-center">
                            <div class="w-16 h-16 rounded-full bg-emerald-500/10 border-2 border-emerald-500/30 flex items-center justify-center text-emerald-400 mb-4 animate-pulse">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-8 h-8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z" />
                                </svg>
                            </div>
                            <h4 class="text-lg font-bold text-white" id="connected-phone">Connected</h4>
                            <p class="text-xs text-slate-400 mt-1" id="connected-name">Session: main</p>
                        </div>
                    </div>
                </div>

                <!-- Right: Send Message Form -->
                <div class="lg:col-span-2">
                    <div class="bg-slate-900/50 border border-slate-800 rounded-2xl p-6 backdrop-blur-sm shadow-xl h-full flex flex-col">
                        <h2 class="text-base font-bold text-white mb-6 flex items-center space-x-2">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 text-emerald-400">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                            </svg>
                            <span>Send Message</span>
                        </h2>

                        <form method="POST" action="/whatsapp/send" class="space-y-5 flex-1 flex flex-col justify-between">
                            @csrf
                            <div class="space-y-4">
                                <!-- Phone -->
                                <div>
                                    <label for="phone" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">Receiver Phone Number</label>
                                    <div class="mt-1.5 relative rounded-xl shadow-sm">
                                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                                            <span class="text-slate-500 sm:text-sm">+</span>
                                        </div>
                                        <input type="text" name="phone" id="phone" required class="block w-full rounded-xl border border-slate-800 bg-slate-950/60 py-3 pl-8 pr-4 text-slate-200 placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500/80 sm:text-sm" placeholder="628123456789">
                                    </div>
                                    <p class="mt-1.5 text-xs text-slate-500">Include country code without spaces, plus sign, or leading zeroes (e.g. 628123456789 for Indonesia, 14155552671 for US).</p>
                                </div>

                                <!-- Message Body -->
                                <div class="flex-1">
                                    <label for="message" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">Message Body</label>
                                    <div class="mt-1.5">
                                        <textarea id="message" name="message" rows="5" required class="block w-full rounded-xl border border-slate-800 bg-slate-950/60 px-4 py-3 text-slate-200 placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500/80 sm:text-sm" placeholder="Type your WhatsApp message here..."></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="pt-4">
                                <button type="submit" id="send-msg-btn" disabled class="w-full inline-flex items-center justify-center rounded-xl bg-slate-800 px-4 py-3 text-sm font-semibold text-slate-500 shadow-sm border border-slate-700 cursor-not-allowed transition-all duration-300">
                                    WhatsApp Disconnected
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Live Polling Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const sidecarBadge = document.getElementById('sidecar-badge');
            const sidecarDot = document.getElementById('sidecar-dot');
            const sidecarText = document.getElementById('sidecar-text');

            const sidecarStatusDetail = document.getElementById('sidecar-status-detail');
            const sidecarBtn = document.getElementById('sidecar-btn');
            const sidecarForm = document.getElementById('sidecar-form');

            const sessionCard = document.getElementById('session-card');
            const sessionStatusDetail = document.getElementById('session-status-detail');
            const sessionStartForm = document.getElementById('session-start-form');
            const sessionDestroyForm = document.getElementById('session-destroy-form');

            const qrCard = document.getElementById('qr-card');
            const qrContainer = document.getElementById('qr-container');
            const qrImage = document.getElementById('qr-image');

            const sessionLoading = document.getElementById('session-loading');
            const loadingText = document.getElementById('loading-text');

            const connectedContainer = document.getElementById('connected-container');
            const connectedPhone = document.getElementById('connected-phone');
            const connectedName = document.getElementById('connected-name');

            const sendMsgBtn = document.getElementById('send-msg-btn');

            // Fade out flash messages after 5 seconds
            const alerts = document.querySelectorAll('.alert-success, .alert-error');
            alerts.forEach(function (alert) {
                setTimeout(function () {
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 300);
                }, 5000);
            });

            async function pollStatus() {
                try {
                    const response = await fetch('/whatsapp/status');
                    if (!response.ok) throw new Error('API request failed');
                    const data = await response.json();

                    // 1. Update Sidecar Server Status
                    if (!data.sidecarRunning) {
                        // Offline
                        sidecarBadge.className = "inline-flex items-center gap-x-1.5 rounded-md px-2 py-1 text-xs font-medium bg-rose-500/10 text-rose-400 ring-1 ring-inset ring-rose-500/20";
                        sidecarDot.className = "h-1.5 w-1.5 rounded-full bg-rose-400";
                        sidecarText.textContent = "Server Offline";

                        sidecarStatusDetail.textContent = "Stopped";
                        sidecarBtn.textContent = "Start Server";
                        sidecarBtn.className = "inline-flex items-center justify-center rounded-xl bg-emerald-600 px-3 py-2 text-xs font-semibold text-white shadow-sm hover:bg-emerald-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600 transition-all duration-300";
                        sidecarForm.action = "/whatsapp/sidecar/start";

                        sessionCard.classList.add('hidden');
                        qrCard.classList.add('hidden');
                        updateSendButton(false, "Server Offline");
                    } else {
                        // Online
                        sidecarBadge.className = "inline-flex items-center gap-x-1.5 rounded-md px-2 py-1 text-xs font-medium bg-emerald-500/10 text-emerald-400 ring-1 ring-inset ring-emerald-500/20 animate-pulse";
                        sidecarDot.className = "h-1.5 w-1.5 rounded-full bg-emerald-400";
                        sidecarText.textContent = "Server Online";

                        sidecarStatusDetail.textContent = "Running";
                        sidecarBtn.textContent = "Stop Server";
                        sidecarBtn.className = "inline-flex items-center justify-center rounded-xl bg-rose-600/20 border border-rose-500/20 px-3 py-2 text-xs font-semibold text-rose-400 hover:bg-rose-600/30 hover:text-white shadow-sm transition-all duration-300";
                        sidecarForm.action = "/whatsapp/sidecar/stop";

                        sessionCard.classList.remove('hidden');
                        qrCard.classList.remove('hidden');

                        // 2. Update WhatsApp Session Status
                        const status = data.sessionStatus;
                        sessionStatusDetail.textContent = status;

                        if (status === 'disconnected') {
                            sessionStatusDetail.className = "text-sm font-bold text-slate-400 mt-0.5 capitalize";
                            sessionStartForm.classList.remove('hidden');
                            sessionDestroyForm.classList.add('hidden');

                            qrContainer.classList.add('hidden');
                            sessionLoading.classList.add('hidden');
                            connectedContainer.classList.add('hidden');
                            
                            updateSendButton(false, "WhatsApp Disconnected");
                        } else {
                            sessionStartForm.classList.add('hidden');
                            sessionDestroyForm.classList.remove('hidden');

                            if (status === 'initializing') {
                                sessionStatusDetail.className = "text-sm font-bold text-yellow-500 mt-0.5 capitalize";
                                qrContainer.classList.add('hidden');
                                sessionLoading.classList.remove('hidden');
                                loadingText.textContent = "Booting session...";
                                connectedContainer.classList.add('hidden');
                                updateSendButton(false, "WhatsApp Initializing");
                            } else if (status === 'qr') {
                                sessionStatusDetail.className = "text-sm font-bold text-yellow-500 mt-0.5 capitalize";
                                sessionLoading.classList.add('hidden');
                                connectedContainer.classList.add('hidden');

                                if (data.qrCode) {
                                    qrImage.src = data.qrCode;
                                    qrContainer.classList.remove('hidden');
                                } else {
                                    sessionLoading.classList.remove('hidden');
                                    loadingText.textContent = "Generating QR Code...";
                                }
                                updateSendButton(false, "Scan QR Code to Send");
                            } else if (status === 'authenticated') {
                                sessionStatusDetail.className = "text-sm font-bold text-emerald-500 mt-0.5 capitalize animate-pulse";
                                qrContainer.classList.add('hidden');
                                sessionLoading.classList.remove('hidden');
                                loadingText.textContent = "Authenticated, loading chats...";
                                connectedContainer.classList.add('hidden');
                                updateSendButton(false, "Finalizing Connection");
                            } else if (status === 'ready') {
                                sessionStatusDetail.className = "text-sm font-bold text-emerald-500 mt-0.5 capitalize";
                                qrContainer.classList.add('hidden');
                                sessionLoading.classList.add('hidden');
                                connectedContainer.classList.remove('hidden');

                                const userNumber = data.info?.info?.wid?.user || 'Connected';
                                const userName = data.info?.info?.pushname || 'WhatsApp Session';
                                connectedPhone.textContent = "+" + userNumber;
                                connectedName.textContent = userName;

                                updateSendButton(true);
                            } else {
                                sessionStatusDetail.className = "text-sm font-bold text-rose-500 mt-0.5 capitalize";
                                qrContainer.classList.add('hidden');
                                sessionLoading.classList.add('hidden');
                                connectedContainer.classList.add('hidden');
                                updateSendButton(false, `Status: ${status}`);
                            }
                        }
                    }
                } catch (error) {
                    console.error('Polling failed:', error);
                }
            }

            function updateSendButton(enabled, message = "Send Message") {
                if (enabled) {
                    sendMsgBtn.disabled = false;
                    sendMsgBtn.textContent = "Send Message";
                    sendMsgBtn.className = "w-full inline-flex items-center justify-center rounded-xl bg-emerald-600 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600 transition-all duration-300 cursor-pointer";
                } else {
                    sendMsgBtn.disabled = true;
                    sendMsgBtn.textContent = message;
                    sendMsgBtn.className = "w-full inline-flex items-center justify-center rounded-xl bg-slate-800 px-4 py-3 text-sm font-semibold text-slate-500 shadow-sm border border-slate-700 cursor-not-allowed transition-all duration-300";
                }
            }

            // Run initial poll and schedule interval
            pollStatus();
            setInterval(pollStatus, 3000);
        });
    </script>
</body>
</html>
