<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MedCore EHR API — Clinical Command & Architecture Console</title>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        clinical: {
                            50: '#ecfeff',
                            100: '#cffafe',
                            400: '#22d3ee',
                            500: '#06b6d4',
                            600: '#0891b2',
                            900: '#164e63',
                        },
                        ehr: {
                            500: '#10b981',
                            600: '#059669',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap');
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }
        code, pre, .font-mono {
            font-family: 'JetBrains Mono', monospace;
        }
        .glow-cyan {
            box-shadow: 0 0 25px -5px rgba(6, 182, 212, 0.25);
        }
        .glow-emerald {
            box-shadow: 0 0 25px -5px rgba(16, 185, 129, 0.2);
        }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen antialiased flex flex-col selection:bg-cyan-500 selection:text-white">

    <!-- Top Navigation Bar -->
    <header class="border-b border-slate-800/80 bg-slate-900/60 backdrop-blur-md sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-9 h-9 rounded-lg bg-gradient-to-tr from-cyan-600 to-emerald-500 flex items-center justify-center shadow-lg shadow-cyan-500/20">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                    </svg>
                </div>
                <div>
                    <span class="text-base font-bold tracking-tight text-white flex items-center gap-2">
                        MedCore <span class="text-xs px-2 py-0.5 rounded-full bg-cyan-500/20 text-cyan-300 border border-cyan-500/30 font-semibold uppercase tracking-wider">EHR API</span>
                    </span>
                    <p class="text-[11px] text-slate-400">Clinical Architecture & Health Operations Console</p>
                </div>
            </div>

            <div class="flex items-center gap-2 sm:gap-3">
                <a href="{{ url('/api/v1/health') }}" target="_blank"
                   class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-medium text-slate-300 bg-slate-800 hover:bg-slate-700 hover:text-white transition border border-slate-700">
                    <span class="w-2 h-2 rounded-full {{ $db_connected && $redis_connected ? 'bg-emerald-400 animate-pulse' : 'bg-amber-400' }}"></span>
                    JSON Health
                </a>
                <a href="{{ url('/docs/api.json') }}" target="_blank"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-medium text-slate-300 bg-slate-800 hover:bg-slate-700 hover:text-white transition border border-slate-700">
                    <svg class="w-3.5 h-3.5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                    OpenAPI Spec
                </a>
                <a href="{{ url('/docs/api') }}" target="_blank"
                   class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-md text-xs font-semibold text-white bg-gradient-to-r from-cyan-600 to-emerald-600 hover:from-cyan-500 hover:to-emerald-500 shadow-md shadow-cyan-600/30 transition transform hover:-translate-y-0.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    Interactive Docs
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content Container -->
    <main class="flex-grow max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

        <!-- Hero & Telemetry Status -->
        <section class="relative overflow-hidden rounded-2xl border border-slate-800 bg-gradient-to-b from-slate-900/90 to-slate-900/40 p-6 sm:p-8 backdrop-blur-xl">
            <div class="absolute -right-16 -top-16 w-80 h-80 rounded-full bg-cyan-500/10 blur-3xl pointer-events-none"></div>
            <div class="absolute -left-16 -bottom-16 w-80 h-80 rounded-full bg-emerald-500/10 blur-3xl pointer-events-none"></div>

            <div class="relative z-10 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                <div class="space-y-3 max-w-2xl">
                    <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full bg-slate-800/80 border border-slate-700 text-xs text-slate-300">
                        <span class="flex h-2 w-2 rounded-full bg-emerald-400 animate-ping"></span>
                        <span class="font-medium text-emerald-400">Enterprise Ready</span>
                        <span class="text-slate-500">•</span>
                        <span>State of the Art Architecture</span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl lg:text-4xl font-extrabold text-white tracking-tight">
                        Healthcare & Clinical Appointments API
                    </h1>
                    <p class="text-sm sm:text-base text-slate-400 leading-relaxed">
                        Mission-critical, strictly-typed EHR backend with AES-256 encrypted Protected Health Information (PHI) at rest, HIPAA immutable audit trails, and concurrency-safe slot availability booking.
                    </p>
                </div>

                <!-- Primary Action Buttons -->
                <div class="flex flex-wrap items-center gap-3">
                    <a href="{{ url('/docs/api') }}" target="_blank"
                       class="px-5 py-3 rounded-xl bg-gradient-to-r from-cyan-600 to-emerald-600 hover:from-cyan-500 hover:to-emerald-500 text-white font-semibold text-sm shadow-lg shadow-cyan-600/30 flex items-center gap-2 transition transform hover:-translate-y-0.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Explore OpenAPI 3.1 Portal
                    </a>
                    <a href="{{ url('/docs/api.json') }}" target="_blank"
                       class="px-5 py-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 hover:text-white font-medium text-sm border border-slate-700 flex items-center gap-2 transition">
                        <svg class="w-4 h-4 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        Download OpenAPI Spec
                    </a>
                </div>
            </div>

            <!-- Live Telemetry Badges Strip -->
            <div class="mt-8 pt-6 border-t border-slate-800/80 flex flex-wrap items-center gap-2.5 sm:gap-3 text-xs">
                <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-800/90 border border-slate-700/80 font-mono text-cyan-300">
                    <span class="w-2 h-2 rounded-full bg-cyan-400"></span>
                    <span>PHP {{ $php_version }}</span>
                </div>
                <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-800/90 border border-slate-700/80 font-mono text-cyan-300">
                    <span class="w-2 h-2 rounded-full bg-cyan-400"></span>
                    <span>Laravel {{ $laravel_version }}</span>
                </div>
                <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg {{ $db_connected ? 'bg-emerald-950/40 border-emerald-800/60 text-emerald-300' : 'bg-red-950/40 border-red-800/60 text-red-300' }} border font-medium">
                    <span class="w-2 h-2 rounded-full {{ $db_connected ? 'bg-emerald-400' : 'bg-red-500' }}"></span>
                    <span>PostgreSQL 16 {{ $db_connected ? 'Connected' : 'Offline' }}</span>
                </div>
                <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg {{ $redis_connected ? 'bg-emerald-950/40 border-emerald-800/60 text-emerald-300' : 'bg-red-950/40 border-red-800/60 text-red-300' }} border font-medium">
                    <span class="w-2 h-2 rounded-full {{ $redis_connected ? 'bg-emerald-400' : 'bg-red-500' }}"></span>
                    <span>Redis 7 Queue {{ $redis_connected ? 'Ready' : 'Offline' }}</span>
                </div>
                <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-indigo-950/40 border border-indigo-800/60 text-indigo-300 font-medium">
                    <svg class="w-3.5 h-3.5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    <span>HIPAA AES-256 PHI Encryption Active</span>
                </div>
            </div>
        </section>

        <!-- Live Database Metrics Counters -->
        <section class="space-y-3">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-semibold tracking-wider text-slate-400 uppercase">Live Database Metrics</h2>
                <span class="text-xs text-slate-500 font-mono">Real-time PostgreSQL count telemetry</span>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
                <!-- Doctors -->
                <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-4 transition hover:border-slate-700">
                    <div class="flex items-center justify-between text-slate-400 mb-1">
                        <span class="text-xs font-medium">Doctors</span>
                        <svg class="w-4 h-4 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    </div>
                    <div class="text-2xl font-bold text-white font-mono">{{ $counts['doctors'] }}</div>
                    <div class="text-[11px] text-slate-500 mt-1">Specialists onboarded</div>
                </div>

                <!-- Patients -->
                <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-4 transition hover:border-slate-700">
                    <div class="flex items-center justify-between text-slate-400 mb-1">
                        <span class="text-xs font-medium">Patients</span>
                        <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </div>
                    <div class="text-2xl font-bold text-white font-mono">{{ $counts['patients'] }}</div>
                    <div class="text-[11px] text-slate-500 mt-1">Clinical profiles</div>
                </div>

                <!-- Appointments -->
                <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-4 transition hover:border-slate-700">
                    <div class="flex items-center justify-between text-slate-400 mb-1">
                        <span class="text-xs font-medium">Appointments</span>
                        <svg class="w-4 h-4 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                    <div class="text-2xl font-bold text-white font-mono">{{ $counts['appointments'] }}</div>
                    <div class="text-[11px] text-slate-500 mt-1">Booked & completed</div>
                </div>

                <!-- Encrypted EHR Records -->
                <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-4 transition hover:border-slate-700">
                    <div class="flex items-center justify-between text-slate-400 mb-1">
                        <span class="text-xs font-medium">Encrypted EHR</span>
                        <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <div class="text-2xl font-bold text-white font-mono">{{ $counts['encrypted_records'] }}</div>
                    <div class="text-[11px] text-slate-500 mt-1">AES-256 encrypted</div>
                </div>

                <!-- Immutable Audit Logs -->
                <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-4 transition hover:border-slate-700 col-span-2 sm:col-span-1">
                    <div class="flex items-center justify-between text-slate-400 mb-1">
                        <span class="text-xs font-medium">Audit Logs</span>
                        <svg class="w-4 h-4 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                    </div>
                    <div class="text-2xl font-bold text-white font-mono">{{ $counts['audit_logs'] }}</div>
                    <div class="text-[11px] text-slate-500 mt-1">Immutable HIPAA trail</div>
                </div>
            </div>
        </section>

        <!-- Pre-Seeded Demo Accounts & Instant Interactive Authenticator -->
        <section class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

            <!-- Pre-Seeded Accounts Table (7 cols) -->
            <div class="lg:col-span-7 rounded-2xl border border-slate-800 bg-slate-900/60 p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-bold text-white flex items-center gap-2">
                            <span>Pre-Seeded Demo Credentials</span>
                            <span class="text-xs font-normal text-slate-400">(DatabaseSeeder)</span>
                        </h2>
                        <p class="text-xs text-slate-400">All demo accounts share password: <code class="px-1.5 py-0.5 rounded bg-slate-800 text-cyan-300 font-mono text-xs">Password123!</code></p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-slate-800 text-slate-400 font-medium uppercase tracking-wider">
                                <th class="py-2.5 px-3">Role</th>
                                <th class="py-2.5 px-3">Account / Name</th>
                                <th class="py-2.5 px-3">Clinical Metadata</th>
                                <th class="py-2.5 px-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60">
                            <!-- Admin -->
                            <tr class="hover:bg-slate-800/40 transition">
                                <td class="py-3 px-3">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-rose-950/60 text-rose-300 border border-rose-800/60">ADMIN</span>
                                </td>
                                <td class="py-3 px-3 font-mono text-slate-200">
                                    admin@medcore.com
                                    <div class="text-[11px] text-slate-500 font-sans">EHR Administrator</div>
                                </td>
                                <td class="py-3 px-3 text-slate-400">HIPAA Audit Trail Access</td>
                                <td class="py-3 px-3 text-right">
                                    <button onclick="fillAndLogin('admin@medcore.com', 'Password123!')"
                                            class="px-2.5 py-1 rounded bg-slate-800 hover:bg-cyan-600 hover:text-white text-slate-300 border border-slate-700 transition text-[11px] font-medium">
                                        Test Login
                                    </button>
                                </td>
                            </tr>
                            <!-- Dr House -->
                            <tr class="hover:bg-slate-800/40 transition">
                                <td class="py-3 px-3">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-cyan-950/60 text-cyan-300 border border-cyan-800/60">DOCTOR</span>
                                </td>
                                <td class="py-3 px-3 font-mono text-slate-200">
                                    dr.house@medcore.com
                                    <div class="text-[11px] text-slate-500 font-sans">Dr. Gregory House</div>
                                </td>
                                <td class="py-3 px-3 text-slate-400">Diagnostic Medicine (MED-100421)</td>
                                <td class="py-3 px-3 text-right">
                                    <button onclick="fillAndLogin('dr.house@medcore.com', 'Password123!')"
                                            class="px-2.5 py-1 rounded bg-slate-800 hover:bg-cyan-600 hover:text-white text-slate-300 border border-slate-700 transition text-[11px] font-medium">
                                        Test Login
                                    </button>
                                </td>
                            </tr>
                            <!-- Dr Cuddy -->
                            <tr class="hover:bg-slate-800/40 transition">
                                <td class="py-3 px-3">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-cyan-950/60 text-cyan-300 border border-cyan-800/60">DOCTOR</span>
                                </td>
                                <td class="py-3 px-3 font-mono text-slate-200">
                                    dr.cuddy@medcore.com
                                    <div class="text-[11px] text-slate-500 font-sans">Dr. Lisa Cuddy</div>
                                </td>
                                <td class="py-3 px-3 text-slate-400">Endocrinology (MED-100892)</td>
                                <td class="py-3 px-3 text-right">
                                    <button onclick="fillAndLogin('dr.cuddy@medcore.com', 'Password123!')"
                                            class="px-2.5 py-1 rounded bg-slate-800 hover:bg-cyan-600 hover:text-white text-slate-300 border border-slate-700 transition text-[11px] font-medium">
                                        Test Login
                                    </button>
                                </td>
                            </tr>
                            <!-- Patient Ana -->
                            <tr class="hover:bg-slate-800/40 transition">
                                <td class="py-3 px-3">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-950/60 text-emerald-300 border border-emerald-800/60">PATIENT</span>
                                </td>
                                <td class="py-3 px-3 font-mono text-slate-200">
                                    patient.ana@medcore.com
                                    <div class="text-[11px] text-slate-500 font-sans">Ana Martinez (O+)</div>
                                </td>
                                <td class="py-3 px-3 text-slate-400">Allergies: Penicillin, Latex</td>
                                <td class="py-3 px-3 text-right">
                                    <button onclick="fillAndLogin('patient.ana@medcore.com', 'Password123!')"
                                            class="px-2.5 py-1 rounded bg-slate-800 hover:bg-cyan-600 hover:text-white text-slate-300 border border-slate-700 transition text-[11px] font-medium">
                                        Test Login
                                    </button>
                                </td>
                            </tr>
                            <!-- Patient Carlos -->
                            <tr class="hover:bg-slate-800/40 transition">
                                <td class="py-3 px-3">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-950/60 text-emerald-300 border border-emerald-800/60">PATIENT</span>
                                </td>
                                <td class="py-3 px-3 font-mono text-slate-200">
                                    patient.carlos@medcore.com
                                    <div class="text-[11px] text-slate-500 font-sans">Carlos Mendoza (A+)</div>
                                </td>
                                <td class="py-3 px-3 text-slate-400">Upcoming Mon 11:00 Appt</td>
                                <td class="py-3 px-3 text-right">
                                    <button onclick="fillAndLogin('patient.carlos@medcore.com', 'Password123!')"
                                            class="px-2.5 py-1 rounded bg-slate-800 hover:bg-cyan-600 hover:text-white text-slate-300 border border-slate-700 transition text-[11px] font-medium">
                                        Test Login
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- One-Click Interactive Test Login Widget (5 cols) -->
            <div class="lg:col-span-5 rounded-2xl border border-slate-800 bg-slate-900/60 p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-bold text-white flex items-center gap-2">
                            <span>Interactive Token Authenticator</span>
                        </h2>
                        <p class="text-xs text-slate-400">Dispatches real <code class="text-cyan-400 font-mono">POST /api/v1/auth/login</code> via Fetch</p>
                    </div>
                    <span id="login-status-badge" class="hidden text-[10px] font-semibold px-2 py-0.5 rounded"></span>
                </div>

                <form id="login-form" onsubmit="handleManualLogin(event)" class="space-y-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Email Address</label>
                        <input type="email" id="input-email" required value="admin@medcore.com"
                               class="w-full px-3 py-2 rounded-lg bg-slate-800/90 border border-slate-700 text-xs text-slate-100 font-mono focus:outline-none focus:border-cyan-500 transition">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Password</label>
                        <input type="password" id="input-password" required value="Password123!"
                               class="w-full px-3 py-2 rounded-lg bg-slate-800/90 border border-slate-700 text-xs text-slate-100 font-mono focus:outline-none focus:border-cyan-500 transition">
                    </div>
                    <button type="submit" id="btn-login"
                            class="w-full py-2.5 rounded-lg bg-gradient-to-r from-cyan-600 to-emerald-600 hover:from-cyan-500 hover:to-emerald-500 text-white font-semibold text-xs transition shadow-md shadow-cyan-600/20 flex items-center justify-center gap-2">
                        <span>Authenticate & Acquire Bearer Token</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                    </button>
                </form>

                <!-- Token Output Box -->
                <div id="token-container" class="hidden pt-3 border-t border-slate-800 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-300 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                            Sanctum Bearer Token
                        </span>
                        <button onclick="copyBearerToken()" id="btn-copy-token"
                                class="text-xs px-2 py-1 rounded bg-slate-800 hover:bg-slate-700 text-cyan-300 border border-slate-700 transition flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            <span id="copy-text">Copy Token</span>
                        </button>
                    </div>
                    <textarea id="output-token" readonly rows="3"
                              class="w-full px-3 py-2 rounded-lg bg-slate-950 border border-slate-800 text-[11px] font-mono text-emerald-300 focus:outline-none resize-none selection:bg-emerald-600 selection:text-white"></textarea>
                    <p class="text-[11px] text-slate-400">
                        Use the <strong>Live REST API Sandbox</strong> below to test endpoints immediately with this token, or paste it into <a href="{{ url('/docs/api') }}" target="_blank" class="text-cyan-400 underline font-medium hover:text-cyan-300">Stoplight Elements (/docs/api)</a>.
                    </p>
                </div>
            </div>
        </section>

        <!-- Live REST API Endpoint Inspector (RBAC & PHI Encryption Sandbox) -->
        <section class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-800 pb-4">
                <div>
                    <h2 class="text-base font-bold text-white flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-cyan-400"></span>
                        <span>Live REST API Endpoint Inspector (RBAC, BOLA &amp; HIPAA Audit Sandbox)</span>
                    </h2>
                    <p class="text-xs text-slate-400 mt-0.5">
                        1) Click <strong>Test Login</strong> on any account above (Admin, Doctor, or Patient) &rarr; 2) Click any endpoint below to inspect live JSON, RBAC filtering, and <code class="text-cyan-400 font-mono">403 Forbidden</code> ability enforcement.
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <span id="sandbox-status" class="text-[11px] font-mono px-2.5 py-1 rounded bg-slate-800 text-slate-400 border border-slate-700">Ready</span>
                    <span id="sandbox-latency" class="text-[11px] font-mono px-2.5 py-1 rounded bg-slate-950 text-cyan-400 border border-slate-800">-- ms</span>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <button onclick="callApiEndpoint('GET', '/api/v1/auth/me')"
                        class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-cyan-600 hover:text-white text-slate-200 border border-slate-700 transition text-xs font-mono flex items-center gap-1.5">
                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-emerald-950 text-emerald-300 border border-emerald-800">GET</span>
                    <span>/api/v1/auth/me</span>
                </button>
                <button onclick="callApiEndpoint('GET', '/api/v1/doctors')"
                        class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-cyan-600 hover:text-white text-slate-200 border border-slate-700 transition text-xs font-mono flex items-center gap-1.5">
                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-emerald-950 text-emerald-300 border border-emerald-800">GET</span>
                    <span>/api/v1/doctors</span>
                </button>
                <button onclick="callApiEndpoint('GET', '/api/v1/appointments')"
                        class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-cyan-600 hover:text-white text-slate-200 border border-slate-700 transition text-xs font-mono flex items-center gap-1.5">
                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-emerald-950 text-emerald-300 border border-emerald-800">GET</span>
                    <span>/api/v1/appointments</span>
                </button>
                <button onclick="callApiEndpoint('GET', '/api/v1/medical-records')"
                        class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-cyan-600 hover:text-white text-slate-200 border border-slate-700 transition text-xs font-mono flex items-center gap-1.5">
                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-purple-950 text-purple-300 border border-purple-800">PHI</span>
                    <span>/api/v1/medical-records</span>
                </button>
                <button onclick="callApiEndpoint('GET', '/api/v1/audit-logs')"
                        class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-cyan-600 hover:text-white text-slate-200 border border-slate-700 transition text-xs font-mono flex items-center gap-1.5">
                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-amber-950 text-amber-300 border border-amber-800">ADMIN</span>
                    <span>/api/v1/audit-logs</span>
                </button>
                <button onclick="callApiEndpoint('GET', '/api/v1/health')"
                        class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-cyan-600 hover:text-white text-slate-200 border border-slate-700 transition text-xs font-mono flex items-center gap-1.5">
                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-cyan-950 text-cyan-300 border border-cyan-800">OPS</span>
                    <span>/api/v1/health</span>
                </button>
            </div>

            <pre id="sandbox-output" class="w-full p-4 rounded-xl bg-slate-950 border border-slate-800 text-xs font-mono text-slate-300 overflow-x-auto max-h-96 overflow-y-auto">// Click "Test Login" on any demo account above, then click an endpoint button to inspect the live API response here...</pre>
        </section>

        <!-- Technical Architecture Deep Dive (Four Pillars) -->
        <section class="space-y-4">
            <h2 class="text-sm font-semibold tracking-wider text-slate-400 uppercase">Architecture Highlights (October 2026)</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">

                <!-- Pillar 1 -->
                <div class="rounded-xl border border-slate-800 bg-slate-900/40 p-5 space-y-2.5">
                    <div class="w-8 h-8 rounded-lg bg-cyan-950/60 border border-cyan-800/60 text-cyan-400 flex items-center justify-center font-bold text-xs font-mono">
                        8.5
                    </div>
                    <h3 class="text-sm font-bold text-white">PHP 8.5 & Type System</h3>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Features Pipe operator (<code class="text-cyan-300 font-mono">|&gt;</code>) for clean functional sanitization pipelines, <code class="text-cyan-300 font-mono">#[\NoDiscard]</code> on state transitions, and constructor property promotion.
                    </p>
                </div>

                <!-- Pillar 2 -->
                <div class="rounded-xl border border-slate-800 bg-slate-900/40 p-5 space-y-2.5">
                    <div class="w-8 h-8 rounded-lg bg-emerald-950/60 border border-emerald-800/60 text-emerald-400 flex items-center justify-center font-bold text-xs font-mono">
                        L13
                    </div>
                    <h3 class="text-sm font-bold text-white">Laravel 13 Architecture</h3>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Minimal declarative configuration via <code class="text-emerald-300 font-mono">bootstrap/app.php</code> without legacy Kernels, hardened JSON session serialization, and Sanctum v4 token abilities.
                    </p>
                </div>

                <!-- Pillar 3 -->
                <div class="rounded-xl border border-slate-800 bg-slate-900/40 p-5 space-y-2.5">
                    <div class="w-8 h-8 rounded-lg bg-purple-950/60 border border-purple-800/60 text-purple-400 flex items-center justify-center font-bold text-xs font-mono">
                        PHI
                    </div>
                    <h3 class="text-sm font-bold text-white">HIPAA PHI Encryption</h3>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Clinical diagnosis, treatment plans, progress notes, and patient allergies are transparently encrypted with AES-256-CBC at rest via Eloquent Encrypted Casts with immutable <code class="text-purple-300 font-mono">audit_logs</code>.
                    </p>
                </div>

                <!-- Pillar 4 -->
                <div class="rounded-xl border border-slate-800 bg-slate-900/40 p-5 space-y-2.5">
                    <div class="w-8 h-8 rounded-lg bg-amber-950/60 border border-amber-800/60 text-amber-400 flex items-center justify-center font-bold text-xs font-mono">
                        Q7
                    </div>
                    <h3 class="text-sm font-bold text-white">Redis Event Queues</h3>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Domain event <code class="text-amber-300 font-mono">AppointmentScheduled</code> dispatches async notification jobs with exponential backoff (<code class="text-amber-300 font-mono">[10, 30, 60]</code>s) and anti double-booking pessimistic locking.
                    </p>
                </div>

            </div>
        </section>

    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-800/80 bg-slate-950 py-6 text-center text-xs text-slate-500">
        <div class="max-w-7xl mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-2">
            <div>
                MedCore Health Systems — MedCore EHR API © {{ date('Y') }}. High-Integrity Clinical Infrastructure.
            </div>
            <div class="flex items-center gap-4 text-slate-400">
                <a href="{{ url('/docs/api') }}" class="hover:text-cyan-400 transition">API Documentation</a>
                <span>•</span>
                <a href="{{ url('/api/v1/health') }}" class="hover:text-cyan-400 transition">System Health</a>
                <span>•</span>
                <a href="{{ url('/docs/api.json') }}" class="hover:text-cyan-400 transition">JSON Spec</a>
            </div>
        </div>
    </footer>

    <!-- Interactive Authenticator Scripts -->
    <script>
        let currentBearerToken = '';

        function fillAndLogin(email, password) {
            document.getElementById('input-email').value = email;
            document.getElementById('input-password').value = password;
            executeLogin(email, password);
        }

        function handleManualLogin(event) {
            event.preventDefault();
            const email = document.getElementById('input-email').value.trim();
            const password = document.getElementById('input-password').value;
            executeLogin(email, password);
        }

        async function executeLogin(email, password) {
            const btn = document.getElementById('btn-login');
            const statusBadge = document.getElementById('login-status-badge');
            const tokenContainer = document.getElementById('token-container');
            const outputToken = document.getElementById('output-token');

            btn.disabled = true;
            btn.innerHTML = `<svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg> Authenticating...`;

            statusBadge.className = 'text-[10px] font-semibold px-2 py-0.5 rounded bg-slate-800 text-slate-300';
            statusBadge.innerText = 'Connecting...';
            statusBadge.classList.remove('hidden');

            try {
                const response = await fetch('/api/v1/auth/login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ email, password })
                });

                const data = await response.json();

                if (response.ok && data.token) {
                    currentBearerToken = data.token;
                    outputToken.value = currentBearerToken;
                    tokenContainer.classList.remove('hidden');

                    statusBadge.className = 'text-[10px] font-semibold px-2 py-0.5 rounded bg-emerald-950 text-emerald-300 border border-emerald-800';
                    statusBadge.innerText = 'HTTP 200 OK — Token Generated';
                } else if (response.ok && data.data && data.data.token) {
                    currentBearerToken = data.data.token;
                    outputToken.value = currentBearerToken;
                    tokenContainer.classList.remove('hidden');

                    statusBadge.className = 'text-[10px] font-semibold px-2 py-0.5 rounded bg-emerald-950 text-emerald-300 border border-emerald-800';
                    statusBadge.innerText = 'HTTP 200 OK — Token Generated';
                } else {
                    tokenContainer.classList.remove('hidden');
                    outputToken.value = JSON.stringify(data, null, 2);
                    statusBadge.className = 'text-[10px] font-semibold px-2 py-0.5 rounded bg-amber-950 text-amber-300 border border-amber-800';
                    statusBadge.innerText = `HTTP ${response.status} — ${data.message || 'Check Response'}`;
                }
            } catch (err) {
                tokenContainer.classList.remove('hidden');
                outputToken.value = 'Fetch Error: ' + err.message + '\nMake sure the Docker backend container or artisan serve is running.';
                statusBadge.className = 'text-[10px] font-semibold px-2 py-0.5 rounded bg-red-950 text-red-300 border border-red-800';
                statusBadge.innerText = 'Network Connection Error';
            } finally {
                btn.disabled = false;
                btn.innerHTML = `<span>Authenticate & Acquire Bearer Token</span><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>`;
            }
        }

        function copyBearerToken() {
            const token = currentBearerToken || document.getElementById('output-token').value;
            if (!token) return;

            navigator.clipboard.writeText(token).then(() => {
                const copyText = document.getElementById('copy-text');
                const prev = copyText.innerText;
                copyText.innerText = 'Copied!';
                setTimeout(() => {
                    copyText.innerText = prev;
                }, 2000);
            });
        }

        async function callApiEndpoint(method, path) {
            const statusEl = document.getElementById('sandbox-status');
            const latencyEl = document.getElementById('sandbox-latency');
            const outputEl = document.getElementById('sandbox-output');
            const token = currentBearerToken || document.getElementById('output-token').value.trim();

            statusEl.className = 'text-[11px] font-mono px-2.5 py-1 rounded bg-slate-800 text-slate-300 border border-slate-700';
            statusEl.innerText = `${method} ${path}...`;
            latencyEl.innerText = '...';

            const headers = {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            };

            if (token && token.includes('|')) {
                headers['Authorization'] = `Bearer ${token}`;
            }

            const start = performance.now();
            try {
                const response = await fetch(path, { method, headers });
                const elapsed = Math.round(performance.now() - start);
                const data = await response.json();

                latencyEl.innerText = `${elapsed} ms`;
                if (response.ok) {
                    statusEl.className = 'text-[11px] font-mono px-2.5 py-1 rounded bg-emerald-950 text-emerald-300 border border-emerald-800';
                    statusEl.innerText = `HTTP ${response.status} OK`;
                } else {
                    statusEl.className = 'text-[11px] font-mono px-2.5 py-1 rounded bg-rose-950 text-rose-300 border border-rose-800';
                    statusEl.innerText = `HTTP ${response.status}`;
                }

                outputEl.textContent = JSON.stringify(data, null, 2);
            } catch (err) {
                statusEl.className = 'text-[11px] font-mono px-2.5 py-1 rounded bg-rose-950 text-rose-300 border border-rose-800';
                statusEl.innerText = 'Network Error';
                outputEl.textContent = err.message;
            }
        }
    </script>
</body>
</html>
