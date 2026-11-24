<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Google\Cloud\Firestore\FirestoreClient;

class AuthController extends Controller
{
    /**
     * Procesa la autenticación por Google enviada desde la vista.
     */
    public function loginGoogle(Request $request)
    {
        $email = $request->input('email');
        $name = $request->input('name');

        if (!$email) {
            return response()->json([
                'success' => false,
                'message' => 'El correo electrónico es requerido.'
            ], 400);
        }

        try {
            // 1. Buscar el archivo JSON de credenciales en storage/app/ o subcarpetas
            $credentialsPath = storage_path('app/firebase-credentials.json');

            if (!file_exists($credentialsPath)) {
                $jsonFiles = array_merge(
                    glob(storage_path('app/*.json')) ?: [],
                    glob(storage_path('app/private/*.json')) ?: [],
                    glob(storage_path('*.json')) ?: []
                );

                // Filtrar solo archivos JSON que parezcan credenciales válidas de Service Account
                $validJson = null;
                foreach ($jsonFiles as $file) {
                    $content = @file_get_contents($file);
                    $data = json_decode($content, true);
                    if (is_array($data) && isset($data['private_key'], $data['client_email'])) {
                        $validJson = $file;
                        break;
                    }
                }

                if ($validJson) {
                    $credentialsPath = $validJson;
                } else {
                    $presentFiles = array_map('basename', glob(storage_path('app/*')));
                    return response()->json([
                        'success' => false,
                        'message' => 'No se encontró el archivo de credenciales .json en storage/app/. Archivos detectados: ' . (empty($presentFiles) ? 'Ninguno' : implode(', ', $presentFiles))
                    ], 404);
                }
            }

            // 2. Leer y obtener la ruta absoluta estandarizada
            $realPath = realpath($credentialsPath) ?: $credentialsPath;
            $jsonContent = @file_get_contents($realPath);
            $keyFileData = json_decode($jsonContent, true);

            if (!$keyFileData || !is_array($keyFileData)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El archivo ' . basename($realPath) . ' no contiene un formato JSON válido.'
                ], 400);
            }

            if (!isset($keyFileData['private_key']) || !isset($keyFileData['client_email'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'El archivo ' . basename($realPath) . ' no es una Clave Privada de Servicio de Firebase válida (faltan llaves requeridas).'
                ], 400);
            }

            // 3. Asignar variables de entorno de forma explícita para PHP
            putenv('GOOGLE_APPLICATION_CREDENTIALS=' . $realPath);
            $_ENV['GOOGLE_APPLICATION_CREDENTIALS'] = $realPath;
            $_SERVER['GOOGLE_APPLICATION_CREDENTIALS'] = $realPath;

            // 4. Conectar a Firestore mediante el transporte REST y el archivo de claves directo
            $firestore = new FirestoreClient([
                'projectId'   => $keyFileData['project_id'] ?? 'sistema-hidroponico-97751',
                'keyFile'     => $keyFileData,
                'keyFilePath' => $realPath,
                'transport'   => 'rest',
            ]);

            // 5. Buscar el usuario en la colección 'usuarios'
            $usersRef = $firestore->collection('usuarios');
            $query = $usersRef->where('correo', '=', $email)->documents();

            if ($query->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'El correo ' . $email . ' no está registrado o no tiene permisos en Firestore.'
                ], 403);
            }

            // 6. Obtener los datos del usuario
            $userData = null;
            foreach ($query as $document) {
                if ($document->exists()) {
                    $userData = $document->data();
                    break;
                }
            }

            // 7. Guardar la sesión
            session([
                'user_email' => $email,
                'user_name'  => $userData['nombre'] ?? $name,
                'user_role'  => $userData['rol'] ?? 'Encargado',
                'authenticated' => true
            ]);

            return response()->json([
                'success' => true,
                'redirect' => route('dashboard')
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error en el servidor Laravel: ' . $e->getMessage()
            ], 500);
        }
    }
}