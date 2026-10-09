<?php
require_once __DIR__ . '/../modelos/util.php';
iniciarApi();   // requiere sesión iniciada, igual que los demás módulos

// ---------- Utilidades ----------
function filas(PDO $db, string $sql, array $params = []): array {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fila(PDO $db, string $sql, array $params = []): array {
    $r = filas($db, $sql, $params);
    return $r[0] ?? [];
}

// Convierte a número (las columnas numéricas llegan como texto desde MySQL)
function num($valor) {
    return is_numeric($valor) ? $valor + 0 : 0;
}

function numerosEn(array $fila, array $campos): array {
    foreach ($campos as $c) {
        $fila[$c] = num($fila[$c] ?? 0);
    }
    return $fila;
}

// Cada sección se calcula por separado: si una consulta falla (por ejemplo, una columna con otro nombre),
// el resto del dashboard sigue funcionando y se informa qué sección falló.
function seccion(string $nombre, array &$errores, callable $calcular) {
    try {
        return $calcular();
    } catch (Throwable $e) {
        error_log("dashboard.php [$nombre]: " . $e->getMessage());
        $errores[] = $nombre;
        return null;
    }
}

// Estados de venta que NO cuentan como ingreso (anuladas, canceladas...). Se leen del ENUM de la BD.
function estadosAnulados(PDO $db): array {
    $fila = $db->query("SHOW COLUMNS FROM ventas LIKE 'estado'")->fetch(PDO::FETCH_ASSOC);
    if ($fila && preg_match_all("/'([^']*)'/", $fila['Type'], $m)) {
        return array_values(array_filter($m[1], function ($e) {
            return preg_match('/anul|cancel|devuel/i', $e) === 1;
        }));
    }
    return ['anulada', 'cancelada'];
}

function condicionVentas(array $anulados, string $columna): array {
    if (!$anulados) {
        return ['1 = 1', []];
    }
    return [$columna . ' NOT IN (' . implode(',', array_fill(0, count($anulados), '?')) . ')', $anulados];
}

// ---------- Resumen ----------
function construirResumen(PDO $db): array {
    $errores = [];

    try {
        $anulados = estadosAnulados($db);
    } catch (Throwable $e) {
        $anulados = ['anulada', 'cancelada'];
    }
    [$condV, $parV]   = condicionVentas($anulados, 'estado');
    [$condVa, $parVa] = condicionVentas($anulados, 'v.estado');

    $mesActual   = "DATE_FORMAT(fecha_venta, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')";
    $mesAnterior = "DATE_FORMAT(fecha_venta, '%Y-%m') = DATE_FORMAT(CURDATE() - INTERVAL 1 MONTH, '%Y-%m')";

    // --- Ventas: mes actual, mes anterior y hoy ---
    $ventas = seccion('ventas', $errores, function () use ($db, $condV, $parV, $mesActual, $mesAnterior) {
        $f = fila($db, "SELECT
                COALESCE(SUM(CASE WHEN $mesActual THEN total END), 0)    AS mes_total,
                SUM($mesActual)                                          AS mes_cantidad,
                COALESCE(SUM(CASE WHEN $mesAnterior THEN total END), 0)  AS mes_anterior_total,
                COALESCE(SUM(CASE WHEN DATE(fecha_venta) = CURDATE() THEN total END), 0) AS hoy_total,
                SUM(DATE(fecha_venta) = CURDATE())                       AS hoy_cantidad
            FROM ventas WHERE $condV", $parV);
        return numerosEn($f, ['mes_total', 'mes_cantidad', 'mes_anterior_total', 'hoy_total', 'hoy_cantidad']);
    });

    // --- Ventas de los últimos 6 meses (los meses sin ventas salen en 0) ---
    $ventasPorMes = seccion('ventas por mes', $errores, function () use ($db, $condV, $parV) {
        $datos = filas($db, "SELECT DATE_FORMAT(fecha_venta, '%Y-%m') AS mes, COALESCE(SUM(total), 0) AS total, COUNT(*) AS cantidad
                             FROM ventas
                             WHERE fecha_venta >= DATE_FORMAT(CURDATE() - INTERVAL 5 MONTH, '%Y-%m-01') AND $condV
                             GROUP BY DATE_FORMAT(fecha_venta, '%Y-%m')", $parV);
        $porMes = [];
        foreach ($datos as $d) {
            $porMes[$d['mes']] = $d;
        }
        $inicio = new DateTime((string)$db->query("SELECT CURDATE()")->fetchColumn());
        $inicio->modify('first day of this month');
        $lista = [];
        for ($i = 5; $i >= 0; $i--) {
            $mes = (clone $inicio)->modify("-$i month")->format('Y-m');
            $lista[] = [
                'mes'      => $mes,
                'total'    => num($porMes[$mes]['total'] ?? 0),
                'cantidad' => num($porMes[$mes]['cantidad'] ?? 0),
            ];
        }
        return $lista;
    });

    $ultimasVentas = seccion('últimas ventas', $errores, function () use ($db) {
        return filas($db, "SELECT v.id_venta, v.fecha_venta, v.total, v.estado, c.nombre AS cliente
                           FROM ventas v LEFT JOIN clientes c ON c.id_cliente = v.id_cliente
                           ORDER BY v.fecha_venta DESC, v.id_venta DESC LIMIT 5");
    });

    // --- Artículos más vendidos (necesita que las ventas tengan detalle) ---
    $masVendidos = seccion('más vendidos', $errores, function () use ($db, $condVa, $parVa) {
        $datos = filas($db, "SELECT COALESCE(a.nombre, CONCAT('Artículo #', d.id_articulo)) AS nombre, SUM(d.cantidad) AS unidades
                             FROM detalle_ventas d
                             JOIN ventas v ON v.id_venta = d.id_venta
                             LEFT JOIN articulos a ON a.id_articulo = d.id_articulo
                             WHERE $condVa
                             GROUP BY d.id_articulo, a.nombre
                             ORDER BY unidades DESC LIMIT 5", $parVa);
        foreach ($datos as &$d) {
            $d['unidades'] = num($d['unidades']);
        }
        return $datos;
    });

    // --- Inventario ---
    $inventario = seccion('inventario', $errores, function () use ($db) {
        $f = fila($db, "SELECT COUNT(*) AS articulos,
                               COALESCE(SUM(stock_actual), 0) AS unidades,
                               COALESCE(SUM(stock_actual * precio_costo), 0) AS valor_costo,
                               COALESCE(SUM(stock_actual * precio_venta), 0) AS valor_venta,
                               COALESCE(SUM(stock_actual <= stock_minimo), 0) AS stock_bajo,
                               COALESCE(SUM(stock_actual <= 0), 0) AS agotados
                        FROM articulos");
        return numerosEn($f, ['articulos', 'unidades', 'valor_costo', 'valor_venta', 'stock_bajo', 'agotados']);
    });

    $stockBajo = seccion('stock bajo', $errores, function () use ($db) {
        $datos = filas($db, "SELECT id_articulo, nombre, talla, color, stock_actual, stock_minimo
                             FROM articulos WHERE stock_actual <= stock_minimo
                             ORDER BY (stock_actual / GREATEST(stock_minimo, 1)) ASC, nombre ASC LIMIT 6");
        foreach ($datos as &$d) {
            $d['stock_actual'] = num($d['stock_actual']);
            $d['stock_minimo'] = num($d['stock_minimo']);
        }
        return $datos;
    });

    $stockCategoria = seccion('stock por categoría', $errores, function () use ($db) {
        $datos = filas($db, "SELECT COALESCE(c.nombre, 'Sin categoría') AS categoria,
                                    COUNT(a.id_articulo) AS articulos,
                                    COALESCE(SUM(a.stock_actual), 0) AS unidades
                             FROM articulos a LEFT JOIN categorias c ON c.id_categoria = a.id_categoria
                             GROUP BY c.id_categoria, c.nombre
                             ORDER BY unidades DESC LIMIT 6");
        foreach ($datos as &$d) {
            $d['articulos'] = num($d['articulos']);
            $d['unidades']  = num($d['unidades']);
        }
        return $datos;
    });

    $movimientos = seccion('movimientos de inventario', $errores, function () use ($db) {
        return filas($db, "SELECT m.id_movimiento, m.fecha, m.tipo_movimiento, m.cantidad, m.motivo, a.nombre AS articulo
                           FROM movimientos_inventario m LEFT JOIN articulos a ON a.id_articulo = m.id_articulo
                           ORDER BY m.fecha DESC, m.id_movimiento DESC LIMIT 5");
    });

    // --- Compras ---
    $compras = seccion('compras', $errores, function () use ($db) {
        $ordenes = filas($db, "SELECT estado, COUNT(*) AS cantidad, COALESCE(SUM(total), 0) AS total
                               FROM ordenes_compra GROUP BY estado ORDER BY cantidad DESC");
        foreach ($ordenes as &$o) {
            $o['cantidad'] = num($o['cantidad']);
            $o['total']    = num($o['total']);
        }
        $solicitudes = filas($db, "SELECT estado, COUNT(*) AS cantidad FROM solicitudes_compra GROUP BY estado ORDER BY cantidad DESC");
        foreach ($solicitudes as &$s) {
            $s['cantidad'] = num($s['cantidad']);
        }
        $pendientes = 0;
        foreach ($solicitudes as $s) {
            if (strtolower((string)$s['estado']) === 'pendiente') {
                $pendientes += $s['cantidad'];
            }
        }
        return ['ordenes' => $ordenes, 'solicitudes' => $solicitudes, 'solicitudes_pendientes' => $pendientes];
    });

    // --- Personas ---
    $personas = seccion('personas', $errores, function () use ($db) {
        $f = fila($db, "SELECT
                (SELECT COUNT(*) FROM clientes)                                      AS clientes,
                (SELECT COUNT(*) FROM proveedores)                                   AS proveedores,
                (SELECT COUNT(*) FROM empleados WHERE estado_laboral = 'activo')     AS empleados_activos,
                (SELECT COUNT(*) FROM usuarios WHERE estado <> 'activo')             AS cuentas_por_activar");
        return numerosEn($f, ['clientes', 'proveedores', 'empleados_activos', 'cuentas_por_activar']);
    });

    // --- Contratos: nómina vigente y contratos que vencen en 30 días ---
    $contratos = seccion('contratos', $errores, function () use ($db) {
        $vigentes = fila($db, "SELECT COALESCE(SUM(salario), 0) AS nomina, COUNT(*) AS vigentes
                               FROM contratos_documentos
                               WHERE fecha_inicio <= CURDATE() AND (fecha_fin IS NULL OR fecha_fin >= CURDATE())");
        $porVencer = filas($db, "SELECT c.id_contrato, c.tipo_contrato, c.fecha_fin,
                                        TRIM(CONCAT(COALESCE(e.nombres, ''), ' ', COALESCE(e.apellidos, ''))) AS empleado
                                 FROM contratos_documentos c LEFT JOIN empleados e ON e.id_empleado = c.id_empleado
                                 WHERE c.fecha_fin IS NOT NULL AND c.fecha_fin >= CURDATE() AND c.fecha_fin <= CURDATE() + INTERVAL 30 DAY
                                 ORDER BY c.fecha_fin ASC LIMIT 5");
        return [
            'nomina'     => num($vigentes['nomina'] ?? 0),
            'vigentes'   => num($vigentes['vigentes'] ?? 0),
            'por_vencer' => $porVencer,
        ];
    });

    return [
        'status'         => 'success',
        'generado'       => date('Y-m-d H:i:s'),
        'ventas'         => $ventas,
        'ventas_por_mes' => $ventasPorMes,
        'ultimas_ventas' => $ultimasVentas,
        'mas_vendidos'   => $masVendidos,
        'inventario'     => $inventario,
        'stock_bajo'     => $stockBajo,
        'stock_categoria'=> $stockCategoria,
        'movimientos'    => $movimientos,
        'compras'        => $compras,
        'personas'       => $personas,
        'contratos'      => $contratos,
        'errores'        => $errores,
    ];
}

try {
    $db = Conexion::conectar();

    switch (operacion()) {
        case 'resumen':
            responder(construirResumen($db));

        default:
            fallo('Opción no válida');
    }
} catch (PDOException $e) {
    errorBD($e);
}
