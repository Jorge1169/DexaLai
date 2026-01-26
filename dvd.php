<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pantalla de Carga DVD</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: #000;
            color: #fff;
            font-family: Arial, sans-serif;
            overflow: hidden;
            height: 100vh;
            cursor: none;
        }

        #main-content {
            padding: 20px;
            text-align: center;
            height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        #screensaver {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: #000;
            z-index: 9999;
            display: none;
            overflow: hidden;
        }

        #dvd-logo {
            position: absolute;
            width: 350px;
            height: 350px;
            transition: transform 0.1s linear;
        }

        #logo-img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            /* Efecto de iluminado que cambiará dinámicamente */
            filter: drop-shadow(0 0 15px #4CAF50);
            transition: filter 0.3s ease;
        }

        .info-box {
            background-color: rgba(0, 0, 0, 0.8);
            padding: 20px;
            border-radius: 10px;
            max-width: 600px;
            margin: 20px;
            border: 2px solid #333;
        }

        h1 {
            color: #4CAF50;
            margin-bottom: 15px;
            font-size: 2.5em;
            text-shadow: 0 0 10px rgba(76, 175, 80, 0.5);
        }

        p {
            margin: 10px 0;
            line-height: 1.6;
        }

        .highlight {
            color: #4CAF50;
            font-weight: bold;
        }

        .color-display {
            display: inline-block;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            margin: 0 5px;
            vertical-align: middle;
            border: 2px solid #fff;
        }

        .instructions {
            margin-top: 30px;
            padding: 15px;
            background-color: rgba(30, 30, 30, 0.9);
            border-radius: 8px;
            border-left: 4px solid #4CAF50;
        }
    </style>
</head>
<body>
    <!-- Contenido principal -->
    <div id="main-content">
        <div class="info-box">
            <h1>Pantalla de Carga DVD</h1>
            <p>Este sistema mostrará una pantalla de carga al estilo DVD después de <span class="highlight">5 minutos</span> de inactividad.</p>
            <p>El logo cambiará de color cada vez que golpee los bordes de la pantalla.</p>
            <p>Mueve el ratón o haz clic para desactivar la pantalla de carga.</p>
            
            <div class="instructions">
                <p>🎯 <span class="highlight">Cómo funciona:</span></p>
                <p>1. Quédate inactivo por 5 minutos (no muevas el ratón)</p>
                <p>2. Se activará la pantalla de carga DVD</p>
                <p>3. El logo rebotará cambiando de color en los bordes</p>
                <p>4. Mueve el ratón para volver al contenido normal</p>
            </div>
            
            <p style="margin-top: 20px;">Tiempo hasta pantalla de carga: <span id="countdown" class="highlight">05:00</span></p>
            <p>Color actual: <span id="current-color">#4CAF50</span> <span class="color-display" id="color-preview" style="background-color: #4CAF50;"></span></p>
        </div>
    </div>

    <!-- Pantalla de carga DVD -->
    <div id="screensaver">
        <div id="dvd-logo">
            <img id="logo-img" src="img/sujeto/sujeto3.png" alt="Logo DVD">
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Elementos del DOM
            const screensaver = document.getElementById('screensaver');
            const dvdLogo = document.getElementById('dvd-logo');
            const logoImg = document.getElementById('logo-img');
            const countdownElement = document.getElementById('countdown');
            const currentColorElement = document.getElementById('current-color');
            const colorPreviewElement = document.getElementById('color-preview');
            const body = document.body;
            
            // Variables de control
            let inactivityTimer;
            let inactivityTime = 0.1 * 60 * 1000; // 30 segundos para pruebas (cámbialo a 5 minutos)
            let lastActivity = Date.now();
            let countdownInterval;
            let screensaverActive = false;
            
            // Variables para el movimiento del logo
            let posX = Math.random() * (window.innerWidth - dvdLogo.offsetWidth);
            let posY = Math.random() * (window.innerHeight - dvdLogo.offsetHeight);
            let speedX = 2;
            let speedY = 1.5;
            let animationId;
            
            // Colores para el logo (puedes agregar más)
            const colors = [
                '#4CAF50', '#2196F3', '#FF9800', '#E91E63', 
                '#9C27B0', '#00BCD4', '#FF5722', '#8BC34A',
                '#3F51B5', '#009688', '#FFC107', '#795548',
                '#FF4081', '#536DFE', '#FF6E40', '#1DE9B6',
                '#F50057', '#651FFF', '#FFD740', '#69F0AE'
            ];
            let currentColorIndex = 0;
            
            // Inicializar
            function init() {
                resetInactivityTimer();
                updateCountdownDisplay();
                setupEventListeners();
                applyColorEffect(colors[currentColorIndex]);
                startCountdown();
            }
            
            // Configurar event listeners
            function setupEventListeners() {
                // Eventos de actividad del usuario
                document.addEventListener('mousemove', resetInactivityTimer);
                document.addEventListener('mousedown', resetInactivityTimer);
                document.addEventListener('keypress', resetInactivityTimer);
                document.addEventListener('touchstart', resetInactivityTimer);
                document.addEventListener('wheel', resetInactivityTimer);
                
                // Evento para redimensionar ventana
                window.addEventListener('resize', handleResize);
            }
            
            // Reiniciar temporizador de inactividad
            function resetInactivityTimer() {
                lastActivity = Date.now();
                
                // Si la pantalla de carga está activa, desactivarla
                if (screensaverActive) {
                    hideScreensaver();
                }
                
                // Reiniciar el temporizador
                clearTimeout(inactivityTimer);
                inactivityTimer = setTimeout(showScreensaver, inactivityTime);
                
                // Reiniciar contador
                startCountdown();
            }
            
            // Mostrar pantalla de carga
            function showScreensaver() {
                screensaverActive = true;
                screensaver.style.display = 'block';
                body.style.cursor = 'none';
                
                // Posición inicial aleatoria
                posX = Math.random() * (window.innerWidth - dvdLogo.offsetWidth);
                posY = Math.random() * (window.innerHeight - dvdLogo.offsetHeight);
                dvdLogo.style.transform = `translate(${posX}px, ${posY}px)`;
                
                // Aplicar color inicial
                applyColorEffect(colors[currentColorIndex]);
                
                // Iniciar animación
                animateLogo();
            }
            
            // Ocultar pantalla de carga
            function hideScreensaver() {
                screensaverActive = false;
                screensaver.style.display = 'none';
                body.style.cursor = 'default';
                
                // Detener animación
                cancelAnimationFrame(animationId);
            }
            
            // Animación del logo DVD
            function animateLogo() {
                // Actualizar posición
                posX += speedX;
                posY += speedY;
                
                // Detectar colisiones con los bordes
                let hitEdge = false;
                
                // Borde derecho
                if (posX + dvdLogo.offsetWidth >= window.innerWidth) {
                    speedX = -Math.abs(speedX);
                    posX = window.innerWidth - dvdLogo.offsetWidth;
                    hitEdge = true;
                }
                
                // Borde izquierdo
                if (posX <= 0) {
                    speedX = Math.abs(speedX);
                    posX = 0;
                    hitEdge = true;
                }
                
                // Borde inferior
                if (posY + dvdLogo.offsetHeight >= window.innerHeight) {
                    speedY = -Math.abs(speedY);
                    posY = window.innerHeight - dvdLogo.offsetHeight;
                    hitEdge = true;
                }
                
                // Borde superior
                if (posY <= 0) {
                    speedY = Math.abs(speedY);
                    posY = 0;
                    hitEdge = true;
                }
                
                // Cambiar color si golpea un borde
                if (hitEdge) {
                    currentColorIndex = (currentColorIndex + 1) % colors.length;
                    const newColor = colors[currentColorIndex];
                    applyColorEffect(newColor);
                    
                    // Actualizar información del color
                    currentColorElement.textContent = newColor;
                    colorPreviewElement.style.backgroundColor = newColor;
                }
                
                // Aplicar nueva posición
                dvdLogo.style.transform = `translate(${posX}px, ${posY}px)`;
                
                // Continuar animación
                if (screensaverActive) {
                    animationId = requestAnimationFrame(animateLogo);
                }
            }
            
            // Aplicar efecto de color a la imagen PNG
            function applyColorEffect(color) {
                // Convertir color HEX a RGB
                const hex = color.replace('#', '');
                const r = parseInt(hex.substring(0, 2), 16);
                const g = parseInt(hex.substring(2, 4), 16);
                const b = parseInt(hex.substring(4, 6), 16);
                
                // Crear múltiples efectos de sombra para un brillo más intenso
                const shadow1 = `drop-shadow(0 0 10px ${color})`;
                const shadow2 = `drop-shadow(0 0 20px ${color}80)`;
                const shadow3 = `drop-shadow(0 0 30px ${color}40)`;
                
                // Aplicar efecto de color + brillo
                logoImg.style.filter = `
                    ${shadow1}
                    ${shadow2}
                    ${shadow3}
                    sepia(100%)
                    saturate(1000%)
                    hue-rotate(${getHueRotation(color)}deg)
                    brightness(1.2)
                `;
            }
            
            // Calcular rotación de tono basado en el color
            function getHueRotation(color) {
                const hex = color.replace('#', '');
                const r = parseInt(hex.substring(0, 2), 16);
                const g = parseInt(hex.substring(2, 4), 16);
                const b = parseInt(hex.substring(4, 6), 16);
                
                // Convertir RGB a HSL para obtener el tono
                const max = Math.max(r, g, b);
                const min = Math.min(r, g, b);
                let h = 0;
                
                if (max !== min) {
                    if (max === r) {
                        h = ((g - b) / (max - min)) % 6;
                    } else if (max === g) {
                        h = (2 + (b - r) / (max - min));
                    } else {
                        h = (4 + (r - g) / (max - min));
                    }
                    h = Math.round(h * 60);
                    if (h < 0) h += 360;
                }
                
                return h;
            }
            
            // Manejar redimensionamiento de ventana
            function handleResize() {
                if (screensaverActive) {
                    // Ajustar posición si el logo sale de la pantalla
                    if (posX > window.innerWidth - dvdLogo.offsetWidth) {
                        posX = window.innerWidth - dvdLogo.offsetWidth;
                    }
                    if (posY > window.innerHeight - dvdLogo.offsetHeight) {
                        posY = window.innerHeight - dvdLogo.offsetHeight;
                    }
                }
            }
            
            // Contador regresivo
            function startCountdown() {
                clearInterval(countdownInterval);
                
                countdownInterval = setInterval(function() {
                    const timeLeft = Math.max(0, inactivityTime - (Date.now() - lastActivity));
                    updateCountdownDisplay(timeLeft);
                    
                    if (timeLeft <= 0) {
                        clearInterval(countdownInterval);
                    }
                }, 1000);
            }
            
            // Actualizar display del contador
            function updateCountdownDisplay(timeLeft = inactivityTime) {
                const seconds = Math.ceil(timeLeft / 1000);
                const minutes = Math.floor(seconds / 60);
                const remainingSeconds = seconds % 60;
                
                countdownElement.textContent = 
                    `${minutes.toString().padStart(2, '0')}:${remainingSeconds.toString().padStart(2, '0')}`;
            }
            
            // Inicializar la aplicación
            init();
        });
    </script>
</body>
</html>