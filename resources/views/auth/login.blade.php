<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Pouani - Plataforma Agrotech</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: system-ui, -apple-system, sans-serif;
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 bg-[#063221] relative overflow-x-hidden">

    <!-- Imagen de fondo fija y capa oscura para contraste -->
    <img src="https://images.unsplash.com/photo-1530836369250-ef72a3f5cda8?q=80&w=1920&auto=format&fit=crop"
         alt=""
         class="fixed inset-0 w-full h-full object-cover pointer-events-none select-none z-0">
    <div class="fixed inset-0 bg-[#063221]/55 pointer-events-none z-0"></div>

    <div class="relative z-10 w-full max-w-4xl grid grid-cols-1 lg:grid-cols-2 rounded-2xl overflow-hidden shadow-2xl border border-white/20">

        <!-- Columna izquierda: qué es el sistema (usa la misma imagen de fondo, con overlay) -->
        <div class="hidden lg:flex flex-col justify-between p-8 bg-black/25 backdrop-blur-[2px] text-white">
            <div>
                <div class="inline-flex items-center justify-center w-11 h-11 rounded-xl bg-white/15 border border-white/20 mb-4">
                    <i class="fa-solid fa-leaf text-xl"></i>
                </div>
                <h1 class="text-2xl font-bold tracking-tight">Pouani</h1>
                <p class="text-xs text-emerald-100/90 font-medium mb-6">Sistema de Monitoreo Hidropónico de Precisión</p>

                <p class="text-xs text-white/85 leading-relaxed mb-6">
                    Supervisa tus cultivos hidropónicos con datos reales de tus sensores IoT: temperatura, humedad,
                    oxígeno y nutrientes, todo conectado a Firebase en tiempo real.
                </p>

                <ul class="space-y-3 text-xs">
                    <li class="flex items-center gap-2.5">
                        <span class="w-7 h-7 rounded-lg bg-white/15 flex items-center justify-center flex-shrink-0"><i class="fa-solid fa-chart-line"></i></span>
                        Panel con el estado real de todos tus cultivos
                    </li>
                    <li class="flex items-center gap-2.5">
                        <span class="w-7 h-7 rounded-lg bg-white/15 flex items-center justify-center flex-shrink-0"><i class="fa-solid fa-bell"></i></span>
                        Alertas automáticas cuando algo requiere atención
                    </li>
                    <li class="flex items-center gap-2.5">
                        <span class="w-7 h-7 rounded-lg bg-white/15 flex items-center justify-center flex-shrink-0"><i class="fa-solid fa-brain"></i></span>
                        Diagnóstico e IA basados en tus lecturas reales
                    </li>
                </ul>
            </div>

            <p class="text-[10px] text-white/60">Pouani Agrotech Platform &copy; {{ date('Y') }}</p>
        </div>

        <!-- Columna derecha: formulario de acceso -->
        <div class="bg-white/95 backdrop-blur-md p-8 flex flex-col justify-center">

            <div class="text-center lg:hidden mb-6">
                <div class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-[#063221] text-white mb-2 shadow-md">
                    <i class="fa-solid fa-leaf text-2xl"></i>
                </div>
                <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Pouani</h1>
                <p class="text-xs text-gray-500 font-medium">Plataforma de Precisión Agrotech</p>
            </div>

            <h2 class="text-lg font-bold text-gray-900 mb-1 hidden lg:block">Bienvenido de nuevo</h2>
            <p class="text-xs text-gray-500 mb-6">
                Accede con tu cuenta autorizada de Google. Tu rol (Administrador o Encargado) se detecta
                automáticamente según tu cuenta registrada.
            </p>

            <!-- Contenedor para Alertas de Error -->
            <div id="alert-box" class="hidden mb-4 p-3 rounded-lg text-xs font-semibold bg-red-100 text-red-700 border border-red-200 text-center"></div>

            <!-- Botón Acceder con Google -->
            <button id="btn-google" type="button" class="w-full py-3 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 font-bold rounded-xl text-xs transition flex items-center justify-center gap-3 shadow-sm active:scale-95">
                <svg class="w-5 h-5" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1c-4.3 0-8.01 2.47-9.82 6.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                </svg>
                <span>Acceder con Google</span>
            </button>

            <p class="text-[10px] text-gray-400 mt-4 text-center">
                ¿Necesitas ayuda para acceder? <a href="mailto:soporte@pouani.com" class="underline font-medium text-gray-600">Contacta a Soporte</a>
            </p>
        </div>

    </div>

    <!-- SDKs de Firebase -->
    <script src="https://www.gstatic.com/firebasejs/10.7.1/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/10.7.1/firebase-auth-compat.js"></script>

    <script>
        const firebaseConfig = {
            apiKey: "AIzaSyBCUUeQ_otA4_OS4am6igNzNWBYYdO_rJw",
            authDomain: "sistema-hidroponico-97751.firebaseapp.com",
            projectId: "sistema-hidroponico-97751",
            storageBucket: "sistema-hidroponico-97751.firebasestorage.app",
            messagingSenderId: "1049109407978",
            appId: "1:1049109407978:web:1f64fecfad8a8f80594f93"
        };

        if (!firebase.apps.length) {
            firebase.initializeApp(firebaseConfig);
        }

        const provider = new firebase.auth.GoogleAuthProvider();
        provider.setCustomParameters({ prompt: 'select_account' });

        document.addEventListener("DOMContentLoaded", function () {
            const btnGoogle = document.getElementById("btn-google");
            const alertBox = document.getElementById("alert-box");

            if (btnGoogle) {
                btnGoogle.addEventListener("click", function () {
                    alertBox.classList.add("hidden");
                    btnGoogle.disabled = true;
                    btnGoogle.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Autenticando...';

                    firebase.auth().signInWithPopup(provider)
                        .then((result) => {
                            const user = result.user;

                            fetch("/login-google", {
                                method: "POST",
                                headers: {
                                    "Content-Type": "application/json",
                                    "Accept": "application/json",
                                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                },
                                body: JSON.stringify({
                                    email: user.email,
                                    name: user.displayName
                                })
                            })
                            .then(async res => {
                                const data = await res.json();
                                if (!res.ok) {
                                    throw new Error(data.message || `Error del servidor (${res.status})`);
                                }
                                return data;
                            })
                            .then(data => {
                                if (data.success) {
                                    window.location.href = data.redirect;
                                } else {
                                    showError(data.message);
                                }
                            })
                            .catch(err => {
                                showError(err.message);
                            });
                        })
                        .catch((error) => {
                            showError("Error en Google Auth: " + error.message);
                        });
                });
            }

            function showError(msg) {
                alertBox.innerText = msg;
                alertBox.classList.remove("hidden");
                btnGoogle.disabled = false;
                btnGoogle.innerHTML = '<svg class="w-5 h-5" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1c-4.3 0-8.01 2.47-9.82 6.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/></svg><span>Acceder con Google</span>';
            }
        });
    </script>
</body>
</html>