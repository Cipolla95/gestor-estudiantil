<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'C:/xampp/htdocs/app_225/logs/error_log.txt');


require("conexion.php"); // Incluye el archivo de conexión
include "creartablaestudiantes.php";  
include "./api/google-api-php-client--PHP7.4/vendor/autoload.php";

$conn->set_charset("utf8");

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Datos personales 14 campos
        $nombres = htmlspecialchars(trim($_POST["nombres"]));
        $apellido = htmlspecialchars(trim($_POST["apellido"]));
        $email = htmlspecialchars(trim($_POST["email"]));
        $correo = filter_var($email, FILTER_SANITIZE_EMAIL);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("La dirección $email no es válida");
        }
        $telefono = htmlspecialchars(trim($_POST["telefono"]));
        $tipo_documento = htmlspecialchars(trim($_POST["tipo_documento"]));
        $nro_documento = htmlspecialchars(trim($_POST["nro_documento"]));
        $genero = htmlspecialchars(trim($_POST["genero"]));
        $fecha_nac = htmlspecialchars(trim($_POST["fecha_nac"]));
        $pais_nac = htmlspecialchars(trim($_POST["pais_nac"]));
        $lugar_nac = htmlspecialchars(trim($_POST["lugar_nac"]));
        $familia_cargo = ($_POST["familia_cargo"] === "Si");
        $hijos = htmlspecialchars(trim($_POST["hijos"]));
        $trabaja = ($_POST["trabaja"] === "Si");

        // Domicilio contacto 10 campos
        $pais_dom = htmlspecialchars(trim($_POST["pais_dom"]));
        $provincia = htmlspecialchars(trim($_POST["provincia"]));
        $localidad = htmlspecialchars(trim($_POST["localidad"]));
        $partido = htmlspecialchars(trim($_POST["partido"]));
        $calle = htmlspecialchars(trim($_POST["calle"]));
        $numero = htmlspecialchars(trim($_POST["numero"]));
        $edificio = htmlspecialchars(trim($_POST["edificio"]));
        $piso = htmlspecialchars(trim($_POST["piso"]));
        $departamento = htmlspecialchars(trim($_POST["departamento"]));
        $codigo_postal = htmlspecialchars(trim($_POST["codigo_postal"]));

        // Estudios Secundarios 6 campos
        $titulo_secundario = htmlspecialchars(trim($_POST["titulo_secundario"]));
        $nombre_escuela = htmlspecialchars(trim($_POST["nombre_escuela"]));
        $anio_egreso = htmlspecialchars(trim($_POST["anio_egreso"]));
        $titulo_certificado = htmlspecialchars(trim($_POST["titulo_certificado"]));
        $titulo_tecnico = ($_POST["titulo_tecnico"] === "Si");
        $titulo_hab = ($_POST["titulo_hab"] === "Si");

        // Estudios Adicionales - Otro Recorrido Académico 5 campos Opcionales
        $carreras = $instituciones = $estudios_finalizados = $anios_egresos2 = $titulos_academicos = array_fill(0, 2, null);
        if (!empty($_POST["carrera"]) && is_array($_POST["carrera"])) {
            $maxcarrera = count($_POST["carrera"]);
            for ($i = 0; $i < $maxcarrera; $i++) {
                $carreras[$i] = isset($_POST['carrera'][$i]) ? htmlspecialchars(trim($_POST["carrera"][$i])) : null;
                $instituciones[$i] = isset($_POST["institucion"][$i]) ? htmlspecialchars(trim($_POST["institucion"][$i])) : null;
                $estudios_finalizados[$i] = ($_POST["estudio_finalizado"][$i] === "Si");
                $anios_egresos2[$i] = (isset($_POST["anio_egreso2"][$i]) && ctype_digit($_POST["anio_egreso2"][$i])) ? $_POST["anio_egreso2"][$i] : null;
                $titulos_academicos[$i] = isset($_POST["titulo_academico"][$i]) ? htmlspecialchars(trim($_POST["titulo_academico"][$i])) : null;
            }
        }

        // Documentación requerida 9 campos
        $doc_dni = isset($_POST['doc_dni']);
        $doc_medica = isset($_POST['doc_medica']);
        $doc_analitico = isset($_POST['doc_analitico']);
        $doc_nacimiento = isset($_POST['doc_nacimiento']);
        $doc_completa = isset($_POST['doc_dni']) && isset($_POST['doc_medica']) && isset($_POST['doc_analitico']) && isset($_POST['doc_nacimiento']);

        // Otras variables
        $archivos = $_FILES["adjunto"];
        $plan_carrera = htmlspecialchars(trim($_POST["plan_carrera"]));
        $estado_inscripcion = htmlspecialchars(trim($_POST["estado_inscripcion"]));
        $estado_estudiante = htmlspecialchars(trim($_POST["estado_estudiante"]));
        $observaciones = htmlspecialchars(trim($_POST["observaciones"]));
        $ruta_archivo = "https://drive.google.com/open?id=";

        /*try {  // arranca de aca el comentario
            // Configuración de Google Drive
            putenv('GOOGLE_APPLICATION_CREDENTIALS=hardmente.json');
            $cliente = new Google_Client();
            $cliente->useApplicationDefaultCredentials();
            $cliente->setScopes(['https://www.googleapis.com/auth/drive.file']);

            $servicio = new Google_Service_Drive($cliente);

            // Crear subcarpeta en Google Drive
            $carpeta_raiz_id = "1MG8j4iMAMCsXirLqyHkPY3qqjWxwD6IR";
            $subcarpeta = new Google_Service_Drive_DriveFile();
            $subcarpeta->setName($nro_documento);
            $subcarpeta->setMimeType('application/vnd.google-apps.folder');
            $subcarpeta->setParents([$carpeta_raiz_id]);
            $creada_subcarpeta = $servicio->files->create($subcarpeta, array('fields' => 'id'));

            $subcarpeta_id = $creada_subcarpeta->id;
            $ruta_archivo .= $subcarpeta_id;

            // Subir archivos a Google Drive
            foreach ($archivos["name"] as $key => $nombre_archivo) {
                if (isset($archivos["name"][$key])) {
                    $archivo_temporal = $archivos["tmp_name"][$key];
                }

                $archivos_drive = new Google_Service_Drive_DriveFile();
                $archivos_drive->setName($nombre_archivo);
                $archivos_drive->setParents([$subcarpeta_id]);

                $resultado = $servicio->files->create(
                    $archivos_drive,
                    array(
                        'data' => file_get_contents($archivo_temporal),
                        'mimeType' => mime_content_type($archivo_temporal),
                        'uploadType' => 'media'
                    )
                );
            }

        } catch (Google_Service_Exception $gs) {
            error_log("Excepción de Google Drive: " . $gs->getMessage());
            echo "Error en Google Drive: " . $gs->getMessage();
        } catch (Exception $e) {
            error_log("Excepción capturada: " . $e->getMessage());
            echo "Error: " . $e->getMessage();
        }*/ //comente la linea de codigoclase 27/8

        // Insertar datos en la base de datos
        $insert_estudiantes = "INSERT INTO estudiantes (nro_legajo, tipo_documento, dni_estudiante, nombres, apellidos, email, telefono, genero, fecha_nacimiento, pais_nacimiento, lugar_nacimiento,
        familiares_a_cargo, hijos, trabaja, pais_dom, provincia, calle, numero, piso, departamento, edificio, localidad, partido,
        codigo_postal, nombre_escuela, titulo_secundario, anio_de_egreso, titulo_certificado, titulo_tecnico, titulo_hab, documentacion_completa, repositorio_documentacion, plan_carrera, estado_inscripcion, estado_estudiante, observaciones
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";      

        $stmt_ins_est = $conn->prepare($insert_estudiantes);
        if (!$stmt_ins_est) {
            throw new Exception("Error al preparar la consulta.");
        } 

        $stmt_ins_est->bind_param(
            "sssssssssssiiissssisssssssisiiisssss",
            $nro_documento,
            $tipo_documento,
            $nro_documento,
            $nombres,
            $apellido,
            $correo,
            $telefono,
            $genero,
            $fecha_nac,
            $pais_nac,
            $lugar_nac,
            $familia_cargo,
            $hijos,
            $trabaja,
            $pais_dom,
            $provincia,
            $calle,
            $numero,
            $piso,
            $departamento,
            $edificio,
            $localidad,
            $partido,
            $codigo_postal,
            $nombre_escuela,
            $titulo_secundario,
            $anio_egreso,
            $titulo_certificado,
            $titulo_tecnico,
            $titulo_hab,
            $doc_completa,
            $ruta_archivo,
            $plan_carrera,
            $estado_inscripcion,
            $estado_estudiante,
            $observaciones
        );

        if (!$stmt_ins_est->execute()) {
            throw new Exception("Error al ejecutar la consulta: " . $stmt_ins_est->error);
        }
        echo "Datos insertados exitosamente.";
        $stmt_ins_est->close();
        $conn->close();

    } else {
        echo "Método de solicitud no soportado.";
    }
} catch (Exception $e) {
    error_log("Excepción capturada: " . $e->getMessage());
    echo "Error: " . $e->getMessage();
}
?>
