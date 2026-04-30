<?php
// Archivo principal de la aplicación para registro y reclamo de refrigerios.
// Se usa SQLite para almacenar datos locales sin necesidad de configurar un servidor MySQL.
$dbPath = __DIR__ . '/data/refrigerio.db';
$pdo = new PDO('sqlite:' . $dbPath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Crear tablas necesarias si no existen.
$pdo->exec('CREATE TABLE IF NOT EXISTS estudiantes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    documento TEXT UNIQUE NOT NULL,
    nombre TEXT NOT NULL,
    creado_el TEXT NOT NULL
)');

$pdo->exec('CREATE TABLE IF NOT EXISTS reclamos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    estudiante_id INTEGER NOT NULL,
    reclamo_el TEXT NOT NULL,
    FOREIGN KEY(estudiante_id) REFERENCES estudiantes(id)
)');

$message = '';
$messageClass = '';
$qrDocument = '';
$qrReadyToClaim = false;
$qrGenerateDocument = '';
$qrGenerateReady = false;

// Verificar si el formulario fue enviado.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Si se envía el formulario de registro, guardar estudiante nuevo.
    if (isset($_POST['register'])) {
        $documento = trim($_POST['documento_registro'] ?? '');
        $nombre = trim($_POST['nombre_registro'] ?? '');

        if ($documento === '' || $nombre === '') {
            $message = 'Debe completar el documento y el nombre para registrar el estudiante.';
            $messageClass = 'error';
        } else {
            // Verificar si el documento ya está registrado.
            $stmt = $pdo->prepare('SELECT id FROM estudiantes WHERE documento = ?');
            $stmt->execute([$documento]);
            $exists = $stmt->fetchColumn();

            if ($exists) {
                $message = 'Este estudiante ya está registrado.';
                $messageClass = 'warning';
            } else {
                // Insertar nuevo registro de estudiante.
                $stmt = $pdo->prepare('INSERT INTO estudiantes (documento, nombre, creado_el) VALUES (?, ?, ?)');
                $stmt->execute([$documento, $nombre, date('Y-m-d H:i:s')]);
                $message = 'Estudiante registrado correctamente. Ahora puede reclamar su refrigerio.';
                $messageClass = 'success';
            }
        }
    }

    // Si se envía el formulario de reclamo directo, verificar documento y permitir reclamo.
    if (isset($_POST['claim'])) {
        $documento = trim($_POST['documento_reclamo'] ?? '');

        if ($documento === '') {
            $message = 'Debe ingresar el documento para reclamar el refrigerio.';
            $messageClass = 'error';
        } else {
            $stmt = $pdo->prepare('SELECT id, nombre FROM estudiantes WHERE documento = ?');
            $stmt->execute([$documento]);
            $estudiante = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$estudiante) {
                $message = 'El estudiante no puede reclamar refrigerio porque no está registrado.';
                $messageClass = 'error';
            } else {
                // Verificar si ya existe un reclamo para el estudiante en el día actual.
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM reclamos WHERE estudiante_id = ? AND reclamo_el >= ?');
                $fechaHoy = date('Y-m-d') . ' 00:00:00';
                $stmt->execute([$estudiante['id'], $fechaHoy]);
                $reclamosHoy = (int)$stmt->fetchColumn();

                if ($reclamosHoy > 0) {
                    $message = 'No puede reclamar más refrigerios por hoy. Ya se hizo un reclamo hoy.';
                    $messageClass = 'warning';
                } else {
                    // Insertar un nuevo reclamo en la tabla reclamos.
                    $stmt = $pdo->prepare('INSERT INTO reclamos (estudiante_id, reclamo_el) VALUES (?, ?)');
                    $stmt->execute([$estudiante['id'], date('Y-m-d H:i:s')]);
                    $message = 'Refrigerio reclamado correctamente para ' . htmlspecialchars($estudiante['nombre']) . '.';
                    $messageClass = 'success';
                }
            }
        }
    }

    // Si se escanea un QR para verificación, no se reclama directamente.
    // Aquí solo se verifica que el documento esté registrado y no haya reclamos hoy.
    if (isset($_POST['qr_action']) && $_POST['qr_action'] === 'check') {
        $qrDocument = trim($_POST['documento_reclamo'] ?? '');

        if ($qrDocument === '') {
            $message = 'No se detectó ningún documento en el QR.';
            $messageClass = 'error';
        } else {
            $stmt = $pdo->prepare('SELECT id, nombre FROM estudiantes WHERE documento = ?');
            $stmt->execute([$qrDocument]);
            $estudiante = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$estudiante) {
                $message = 'El estudiante no puede reclamar refrigerio porque no está registrado.';
                $messageClass = 'error';
            } else {
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM reclamos WHERE estudiante_id = ? AND reclamo_el >= ?');
                $fechaHoy = date('Y-m-d') . ' 00:00:00';
                $stmt->execute([$estudiante['id'], $fechaHoy]);
                $reclamosHoy = (int)$stmt->fetchColumn();

                if ($reclamosHoy > 0) {
                    $message = 'No puede reclamar más refrigerios por hoy. Ya se hizo un reclamo hoy.';
                    $messageClass = 'warning';
                } else {
                    $message = 'Estudiante registrado. Puede reclamar el refrigerio.';
                    $messageClass = 'success';
                    $qrReadyToClaim = true;
                }
            }
        }
    }

    // Si se solicita generar el QR para un documento registrado.
    if (isset($_POST['generate_qr'])) {
        $qrGenerateDocument = trim($_POST['documento_qr_generar'] ?? '');

        if ($qrGenerateDocument === '') {
            $message = 'Ingrese el documento para generar el QR.';
            $messageClass = 'error';
        } else {
            $stmt = $pdo->prepare('SELECT id FROM estudiantes WHERE documento = ?');
            $stmt->execute([$qrGenerateDocument]);
            $exists = $stmt->fetchColumn();

            if (!$exists) {
                $message = 'No se puede generar QR porque el estudiante no está registrado.';
                $messageClass = 'error';
            } else {
                $message = 'QR generado correctamente para el documento ' . htmlspecialchars($qrGenerateDocument) . '.';
                $messageClass = 'success';
                $qrGenerateReady = true;
            }
        }
    }
}

$estudiantes = $pdo->query('SELECT documento, nombre, creado_el FROM estudiantes ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reclamo de Refrigerio</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f8; color: #333; margin: 0; padding: 20px; }
        .container { max-width: 800px; margin: auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1, h2 { color: #2c3e50; }
        form { margin-bottom: 24px; }
        input[type=text] { width: 100%; padding: 10px; margin: 8px 0; border: 1px solid #ccc; border-radius: 4px; }
        button { background: #2d89ef; color: white; border: none; padding: 10px 16px; border-radius: 4px; cursor: pointer; }
        button:hover { background: #1d6fd2; }
        .message { padding: 12px; margin-bottom: 20px; border-radius: 4px; }
        .message.success { background: #daf5d8; border: 1px solid #7bc065; color: #2f5f2d; }
        .message.error { background: #f8d7da; border: 1px solid #e18b96; color: #842029; }
        .message.warning { background: #fff3cd; border: 1px solid #ffeeba; color: #664d03; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; border-bottom: 1px solid #ddd; text-align: left; }
        th { background: #f2f2f2; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Registro y Reclamo de Refrigerio</h1>

        <?php if ($message): ?>
            <div class="message <?= htmlspecialchars($messageClass) ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <section>
            <h2>Registrar estudiante</h2>
            <form method="post">
                <label for="documento_registro">Documento</label>
                <input type="text" id="documento_registro" name="documento_registro" placeholder="Número de documento" required>

                <label for="nombre_registro">Nombre</label>
                <input type="text" id="nombre_registro" name="nombre_registro" placeholder="Nombre completo" required>

                <button type="submit" name="register">Registrar estudiante</button>
            </form>
        </section>

        <section>
            <h2>Reclamar refrigerio</h2>
            <form method="post">
                <label for="documento_reclamo">Documento</label>
                <input type="text" id="documento_reclamo" name="documento_reclamo" placeholder="Número de documento" required>

                <button type="submit" name="claim">Reclamar refrigerio</button>
            </form>
        </section>

        <section>
            <h2>Generar QR para estudiante</h2>
            <form method="post" style="margin-bottom: 20px;">
                <label for="documento_qr_generar">Documento</label>
                <input type="text" id="documento_qr_generar" name="documento_qr_generar" placeholder="Número de documento" value="<?= htmlspecialchars($qrGenerateDocument) ?>" required>
                <button type="submit" name="generate_qr">Generar QR</button>
            </form>
            <div id="qr-generate-output" style="display: <?= $qrGenerateReady ? 'block' : 'none' ?>; margin-top: 16px; padding: 16px; background: #f8f9fc; border: 1px solid #ccd4e0; border-radius: 6px;">
                <p><strong>QR para documento:</strong> <?= htmlspecialchars($qrGenerateDocument) ?></p>
                <div id="qr-code" style="width: 220px; height: 220px;"></div>
            </div>
        </section>

        <section>
            <h2>Escanear QR para reclamar</h2>
            <p>Escanea el QR con el documento del estudiante para reclamar automáticamente. Si ya reclamó hoy, verás la restricción.</p>
            <button type="button" id="start-qr-button">Iniciar cámara</button>
            <button type="button" id="stop-qr-button" style="display:none; margin-left: 10px;">Detener cámara</button>
            <div id="qr-status" class="message" style="display:none; margin-top: 16px;"></div>
            <video id="qr-video" playsinline style="display:none; width:100%; margin-top:16px; border:1px solid #ccc; border-radius:4px;"></video>
            <form method="post" id="qr-claim-form">
                <input type="hidden" name="documento_reclamo" id="documento_reclamo_qr">
                <input type="hidden" name="qr_action" value="check">
            </form>
            <?php if ($qrReadyToClaim && $qrDocument !== ''): ?>
                <div style="margin-top: 16px; padding: 16px; background: #f8f9fc; border: 1px solid #ccd4e0; border-radius: 6px;">
                    <p><strong>Documento detectado:</strong> <?= htmlspecialchars($qrDocument) ?></p>
                    <p>El estudiante está registrado y puede reclamar su refrigerio.</p>
                    <form method="post" style="margin-top: 12px;">
                        <input type="hidden" name="documento_reclamo" value="<?= htmlspecialchars($qrDocument) ?>">
                        <button type="submit" name="claim">Reclamar refrigerio</button>
                    </form>
                </div>
            <?php endif; ?>
            <canvas id="qr-canvas" style="display:none;"></canvas>
        </section>

        <section>
            <h2>Estudiantes registrados</h2>
            <?php if (count($estudiantes) === 0): ?>
                <p>No hay estudiantes registrados aún.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Documento</th>
                            <th>Nombre</th>
                            <th>Fecha de registro</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($estudiantes as $est): ?>
                            <tr>
                                <td><?= htmlspecialchars($est['documento']) ?></td>
                                <td><?= htmlspecialchars($est['nombre']) ?></td>
                                <td><?= htmlspecialchars($est['creado_el']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.1/build/qrcode.min.js"></script>
    <script>
        const startButton = document.getElementById('start-qr-button');
        const stopButton = document.getElementById('stop-qr-button');
        const video = document.getElementById('qr-video');
        const canvas = document.getElementById('qr-canvas');
        const qrStatus = document.getElementById('qr-status');
        const qrClaimForm = document.getElementById('qr-claim-form');
        // Elementos del DOM para el lector de QR y el generador de QR.
        const qrDocumentInput = document.getElementById('documento_reclamo_qr');
        const qrGenerateText = <?= json_encode($qrGenerateReady ? $qrGenerateDocument : '') ?>;
        let scanning = false;
        let videoStream = null;

        // Renderiza el QR generado en la sección correspondiente usando la librería qrcode.
        function renderGeneratedQr() {
            if (!qrGenerateText) {
                return;
            }

            const qrCodeElement = document.getElementById('qr-code');
            qrCodeElement.innerHTML = '';
            QRCode.toCanvas(qrCodeElement, qrGenerateText, { width: 200, margin: 2 }, function (error) {
                if (error) {
                    console.error('Error al generar el QR:', error);
                }
            });
        }

        renderGeneratedQr();

        function showStatus(message, type = 'success') {
            qrStatus.textContent = message;
            qrStatus.className = 'message ' + type;
            qrStatus.style.display = 'block';
        }

        // Detiene la cámara y oculta el elemento de video.
        function stopScanner() {
            scanning = false;
            if (videoStream) {
                videoStream.getTracks().forEach(track => track.stop());
            }
            video.style.display = 'none';
            stopButton.style.display = 'none';
            startButton.style.display = 'inline-block';
        }

        // Inicia la cámara para escanear QR desde un dispositivo móvil o webcam.
        async function startScanner() {
            try {
                videoStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
                video.srcObject = videoStream;
                video.setAttribute('playsinline', true);
                video.play();
                video.style.display = 'block';
                startButton.style.display = 'none';
                stopButton.style.display = 'inline-block';
                qrStatus.style.display = 'none';
                scanning = true;
                tick();
            } catch (error) {
                showStatus('No se pudo iniciar la cámara: ' + error.message, 'error');
            }
        }

        // Lee fotogramas del video para buscar un código QR usando jsQR.
        function tick() {
            if (!scanning) {
                return;
            }

            if (video.readyState === video.HAVE_ENOUGH_DATA) {
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                const context = canvas.getContext('2d');
                context.drawImage(video, 0, 0, canvas.width, canvas.height);
                const imageData = context.getImageData(0, 0, canvas.width, canvas.height);
                const code = jsQR(imageData.data, imageData.width, imageData.height);

                if (code) {
                    scanning = false;
                    stopScanner();
                    qrDocumentInput.value = code.data.trim();
                    showStatus('QR detectado: ' + code.data + '. Enviando reclamo...', 'success');
                    qrClaimForm.submit();
                    return;
                }
            }

            requestAnimationFrame(tick);
        }

        startButton.addEventListener('click', startScanner);
        stopButton.addEventListener('click', () => {
            stopScanner();
            showStatus('Escaneo detenido.', 'warning');
        });
    </script>
</body>
</html>
