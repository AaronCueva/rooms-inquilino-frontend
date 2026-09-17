<?php
namespace App\Core;

/**
 * Manejo de subida de archivos a Azure Blob Storage.
 * Lee las credenciales desde .env (propio proyecto o compartido en WS-ROOMS/.env).
 * Espejo de rooms-propietario-frontend/app/core/AzureStorage.php.
 */
class AzureStorage
{
    private static function getEnvVariables()
    {
        $env = [];
        // 1) .env del propio proyecto (prioridad)
        $localEnv  = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . '.env';
        // 2) .env compartido en WS-ROOMS/ (fallback para claves que falten)
        $sharedEnv = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . '.env';

        foreach ([$localEnv, $sharedEnv] as $envFile) {
            if (!file_exists($envFile)) continue;
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) continue;
                if (strpos($line, '=') !== false) {
                    list($name, $value) = explode('=', $line, 2);
                    $name = trim($name);
                    if ($name === '') continue;
                    if (!isset($env[$name])) {
                        $env[$name] = trim($value, '"\'');
                    }
                }
            }
        }

        return $env;
    }

    private static function logError($message) {
        $logFile = dirname(__DIR__, 2) . '/azure_debug.log';
        $time = date('Y-m-d H:i:s');
        @file_put_contents($logFile, "[$time] $message\n", FILE_APPEND);
    }

    /**
     * Sube un archivo a Azure Blob Storage.
     *
     * @param string $localFilePath Ruta temporal del archivo (ej. $_FILES['imagen']['tmp_name'])
     * @param string $fileName Nombre destino del archivo, puede incluir carpeta (ej. usuarios/foto.jpg)
     * @param string $contentType Tipo MIME del archivo (ej. image/jpeg)
     * @return string|false Devuelve la URL final (sin token SAS) si es exitoso, o false si falla.
     */
    public static function uploadFile($localFilePath, $fileName, $contentType)
    {
        self::logError("Iniciando uploadFile: local=$localFilePath, dest=$fileName, mime=$contentType");

        $env = self::getEnvVariables();
        $baseUrl = $env['AZURE_BLOB_BASE_URL'] ?? '';
        $sasToken = $env['AZURE_BLOB_SAS_TOKEN'] ?? '';

        if (empty($baseUrl) || empty($sasToken)) {
            self::logError("ERROR: Variables AZURE_BLOB_BASE_URL o AZURE_BLOB_SAS_TOKEN vacías.");
            return false;
        }

        // Si el fileName contiene carpetas (ej. usuarios/foto.jpg), codificamos cada parte
        // por separado para no codificar el slash (/) como %2F
        $parts = explode('/', $fileName);
        $encodedParts = array_map('rawurlencode', $parts);
        $encodedFileName = implode('/', $encodedParts);

        // Construir la URL completa con el token SAS
        $uploadUrl = $baseUrl . '/' . $encodedFileName . $sasToken;

        // Leer el contenido del archivo
        if (!file_exists($localFilePath)) {
            self::logError("ERROR: El archivo local no existe: $localFilePath");
            return false;
        }
        $fileContent = file_get_contents($localFilePath);
        if ($fileContent === false) {
            self::logError("ERROR: No se pudo leer el archivo local: $localFilePath");
            return false;
        }

        self::logError("Archivo leido correctamente. Size: " . strlen($fileContent));

        // ── Intentar con cURL (más confiable, especialmente en Windows) ──
        if (extension_loaded('curl')) {
            self::logError("Usando cURL...");
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $uploadUrl,
                CURLOPT_CUSTOMREQUEST  => 'PUT',
                CURLOPT_POSTFIELDS     => $fileContent,
                CURLOPT_HTTPHEADER     => [
                    'x-ms-blob-type: BlockBlob',
                    'Content-Type: ' . $contentType,
                    'Content-Length: ' . strlen($fileContent),
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_TIMEOUT        => 60,
            ]);

            $response   = curl_exec($ch);
            $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError  = curl_error($ch);
            $curlErrno  = curl_errno($ch);
            if (PHP_VERSION_ID < 80000) {
                curl_close($ch);
            }

            if ($curlErrno !== 0) {
                self::logError("cURL error (#$curlErrno): $curlError");
            }

            if ($statusCode === 201) {
                self::logError("ÉXITO cURL! HTTP 201");
                return $baseUrl . '/' . $encodedFileName;
            }

            self::logError("cURL upload falló. HTTP $statusCode | Respuesta: " . substr($response, 0, 300));
            return false;
        }

        // ── Fallback: file_get_contents (si cURL no está disponible) ──
        self::logError("AVISO: cURL no disponible, usando file_get_contents como fallback.");
        $options = [
            'http' => [
                'method'        => 'PUT',
                'header'        => [
                    "x-ms-blob-type: BlockBlob",
                    "Content-Type: $contentType",
                    "Content-Length: " . strlen($fileContent)
                ],
                'content'       => $fileContent,
                'ignore_errors' => true,
                'timeout'       => 60,
            ],
            'ssl' => [
                'verify_peer'      => false,
                'verify_peer_name' => false,
            ]
        ];

        $context = stream_context_create($options);
        $result  = @file_get_contents($uploadUrl, false, $context);

        $statusCode = 0;
        if (isset($http_response_header) && is_array($http_response_header)) {
            if (preg_match('#^HTTP/\d(?:\.\d)?\s+(\d{3})#', $http_response_header[0], $matches)) {
                $statusCode = intval($matches[1]);
            }
        }

        if ($statusCode === 201) {
            self::logError("ÉXITO fgc! HTTP 201");
            return $baseUrl . '/' . $encodedFileName;
        }

        $lastError = error_get_last();
        self::logError("file_get_contents falló. HTTP $statusCode | Error: " . ($lastError['message'] ?? 'desconocido'));
        return false;
    }

    /**
     * Fallback local: guarda el archivo en public/uploads/<fileName> y devuelve
     * la URL web (/public/uploads/<fileName>) para guardarla en BD.
     * Se usa cuando Azure no está configurado o la subida a Azure falla.
     *
     * @param string $localFilePath Ruta temporal del archivo (ej. $_FILES['fotos']['tmp_name'])
     * @param string $fileName Nombre destino, puede incluir carpeta (ej. alojamientos/1_1_x.jpg)
     * @return string|false URL web del archivo guardado, o false si falla.
     */
    public static function uploadFileLocal($localFilePath, $fileName)
    {
        if (!is_uploaded_file($localFilePath) && !file_exists($localFilePath)) {
            return false;
        }

        // app/core -> app -> raíz del proyecto (rooms-inquilino-frontend)
        $projectRoot = dirname(__DIR__, 2);
        $uploadsRoot = $projectRoot . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads';

        $destRel = str_replace('/', DIRECTORY_SEPARATOR, $fileName);
        $destPath = $uploadsRoot . DIRECTORY_SEPARATOR . $destRel;

        $destDir = dirname($destPath);
        if (!is_dir($destDir)) {
            @mkdir($destDir, 0775, true);
        }

        if (!copy($localFilePath, $destPath)) {
            return false;
        }

        // URL web pública (el directorio public/ se sirve vía /public/...)
        return '/public/uploads/' . $fileName;
    }
}
