<?php
// Limpiar string general
function limpiarString($str) {
    if ($str === null) return null;
    return trim(htmlspecialchars($str, ENT_QUOTES, 'UTF-8'));
}

// Limpiar email
function limpiarEmail($email) {
    if ($email === null) return null;
    return filter_var(trim($email), FILTER_SANITIZE_EMAIL);
}

// Validar email
function esEmailValido($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Limpiar número entero
function limpiarInt($num) {
    return filter_var($num, FILTER_SANITIZE_NUMBER_INT);
}

// Limpiar número decimal
function limpiarFloat($num) {
    return filter_var($num, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
}

// Limpiar teléfono (solo números, +, -, espacios)
function limpiarTelefono($tel) {
    if ($tel === null) return null;
    return preg_replace('/[^0-9+\-\s]/', '', trim($tel));
}

// Validar fecha (formato Y-m-d)
function esFechaValida($fecha) {
    $d = DateTime::createFromFormat('Y-m-d', $fecha);
    return $d && $d->format('Y-m-d') === $fecha;
}

// Validar hora (formato H:i)
function esHoraValida($hora) {
    $d = DateTime::createFromFormat('H:i', $hora);
    return $d && $d->format('H:i') === $hora;
}

// Limpiar array de datos
function limpiarArray($arr) {
    $limpio = [];
    foreach ($arr as $key => $value) {
        if (is_array($value)) {
            $limpio[$key] = limpiarArray($value);
        } else {
            $limpio[$key] = limpiarString($value);
        }
    }
    return $limpio;
}
