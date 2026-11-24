<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cerrando sesión...</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen flex items-center justify-center bg-slate-50">

    <div class="text-center">
        <i class="fa-solid fa-spinner fa-spin text-2xl text-[#063221]"></i>
        <p class="text-xs text-slate-500 mt-3">Cerrando sesión...</p>
    </div>

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

        firebase.auth().signOut().finally(function () {
            window.location.href = "{{ route('login') }}";
        });
    </script>
</body>
</html>