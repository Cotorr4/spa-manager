<?php
require_once __DIR__ . '/../../private/database/config_public.php';
header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

if ($action === 'guardar_publico') {
    guardarFicha($conn);
} else {
    echo json_encode(['success'=>false, 'message'=>'Acción no válida']);
}

function guardarFicha($conn) {
    $cid = intval($_POST['cliente_id'] ?? 0);
    if (!$cid) { echo json_encode(['success'=>false, 'message'=>'ID Cliente inválido']); return; }

    // Construir JSONs
    $clinicos = [];
    foreach($_POST as $k=>$v) {
        if(strpos($k, 'clinica_')===0) $clinicos[$k] = $v;
    }
    $json_clinicos = json_encode($clinicos);
    
    $habitos = json_encode(['agua_litros' => $_POST['alim_agua_litros'] ?? 0]);
    $actividad = json_encode(['tipo_ejercicio' => $_POST['act_tipo_ejercicio'] ?? '']);
    $sueno = json_encode(['horas_sueno' => $_POST['sueno_horas'] ?? 0]);

    // CORRECCIÓN AQUÍ: Validar Sexo
    $sexoRaw = $_POST['sexo'] ?? '';
    $sexo = '';
    
    // Lista blanca de valores permitidos por el ENUM
    $valoresPermitidos = ['Femenino', 'Masculino', 'Otro'];
    
    if (in_array($sexoRaw, $valoresPermitidos)) {
        $sexo = $sexoRaw;
    } else {
        // Si viene vacío o inválido, forzamos un valor seguro o NULL si la tabla lo permite
        // Asumiremos que 'Otro' es seguro, o si prefieres NULL cambia esto.
        $sexo = 'Otro'; 
    }

    $sexo = $conn->real_escape_string($sexo);

    // Verificar si ya existe ficha
    $check = $conn->query("SELECT id FROM fichas_salud WHERE cliente_id = $cid");
    
    if ($check->num_rows > 0) {
        // Actualizar
        $sql = "UPDATE fichas_salud SET 
                sexo='$sexo', datos_clinicos='$json_clinicos', habitos_alimenticios='$habitos', 
                actividad_fisica='$actividad', habitos_sueno='$sueno', completada=1, updated_at=NOW()
                WHERE cliente_id=$cid";
    } else {
        // Insertar
        $sql = "INSERT INTO fichas_salud (cliente_id, sexo, datos_clinicos, habitos_alimenticios, actividad_fisica, habitos_sueno, completada)
                VALUES ($cid, '$sexo', '$json_clinicos', '$habitos', '$actividad', '$sueno', 1)";
    }

    if ($conn->query($sql)) echo json_encode(['success'=>true]);
    else echo json_encode(['success'=>false, 'message'=>'Error BD: ' . $conn->error]);
}
?>
