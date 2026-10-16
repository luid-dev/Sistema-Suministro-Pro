<?php
/**
 * API REST /wp-json/uac/v1: consulta de estudiantes y registro de donaciones (RN-01 a RN-10).
 *
 * @package Sistema_Suministro_Pro
 */

defined( 'ABSPATH' ) || exit;

trait UAC_SSP_Api_Donaciones {

    /**
     * Registro de endpoints REST API: /wp-json/uac/v1/...
     */
    public function registrar_rutas_rest() {
        // 1. GET /wp-json/uac/v1/estudiante/{codigo}
        register_rest_route( 'uac/v1', '/estudiante/(?P<codigo>[a-zA-Z0-9]+)', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'api_consultar_estudiante' ),
            'permission_callback' => array( $this, 'tiene_permiso_registro' ),
        ) );

        // 2. POST /wp-json/uac/v1/donaciones
        register_rest_route( 'uac/v1', '/donaciones', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'api_registrar_donacion' ),
            'permission_callback' => array( $this, 'tiene_permiso_registro' ),
        ) );

        // GET /wp-json/uac/v1/reporte (solo voluntarios y administradores)
        register_rest_route( 'uac/v1', '/reporte', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'api_reporte' ),
            'permission_callback' => array( $this, 'tiene_permiso_registro' ),
        ) );

        // 3. GET /wp-json/uac/v1/resumen (Público)
        register_rest_route( 'uac/v1', '/resumen', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'api_obtener_resumen' ),
            'permission_callback' => '__return_true',
        ) );
    }

    /** RN-01 y RN-03: busca al estudiante en el padrón y devuelve su carrera. */
    public function api_consultar_estudiante( WP_REST_Request $request ) {
        global $wpdb;
        $codigo = strtoupper( sanitize_text_field( $request->get_param( 'codigo' ) ) );

        if ( ! $this->validar_codigo( $codigo ) ) {
            return new WP_REST_Response( array(
                'ok'    => false,
                'error' => 'RN-01: El código debe tener exactamente 9 dígitos seguidos de 1 letra (ej. 012345678A).',
            ), 200 );
        }

        $mapa       = $this->mapa_carreras();
        $carrera_id = $wpdb->get_var( $wpdb->prepare( "SELECT carrera_id FROM {$this->t('estudiantes')} WHERE codigo = %s", $codigo ) );

        if ( $carrera_id ) {
            $info = isset( $mapa[ $carrera_id ] ) ? $mapa[ $carrera_id ] : array( 'nombre' => $carrera_id, 'facultad' => '' );
            return new WP_REST_Response( array(
                'ok'             => true,
                'registrado'     => true,
                'codigo'         => $codigo,
                'carrera_id'     => $carrera_id,
                'carrera_nombre' => $info['nombre'],
                'facultad'       => $info['facultad'],
            ), 200 );
        }

        $disponibles = array();
        foreach ( $mapa as $id => $info ) {
            $disponibles[] = array( 'carrera_id' => $id, 'nombre' => $info['nombre'], 'facultad' => $info['facultad'] );
        }
        return new WP_REST_Response( array(
            'ok'                   => true,
            'registrado'           => false,
            'codigo'               => $codigo,
            'mensaje'              => 'no registrado',
            'carreras_disponibles' => $disponibles,
        ), 200 );
    }

    /** RN-01 a RN-10: valida y guarda una donación completa. */
    public function api_registrar_donacion( WP_REST_Request $request ) {
        $params = $request->get_json_params();
        $params = is_array( $params ) ? $params : array();
        $user   = wp_get_current_user();

        $resultado = $this->procesar_donacion(
            isset( $params['codigo'] ) ? strtoupper( sanitize_text_field( $params['codigo'] ) ) : '',
            isset( $params['carrera_id'] ) ? strtoupper( sanitize_text_field( $params['carrera_id'] ) ) : '',
            isset( $params['items'] ) && is_array( $params['items'] ) ? $params['items'] : array(),
            $user->user_login ? $user->user_login : 'voluntario_wp'
        );

        if ( ! empty( $resultado['ok'] ) ) {
            $this->limpiar_cache();
        }
        return new WP_REST_Response( $resultado, ! empty( $resultado['ok'] ) ? 200 : 400 );
    }

    private function procesar_donacion( $codigo, $carrera_propuesta, array $items, $registrado_por ) {
        global $wpdb;
        $ahora     = $this->ahora();
        $hoy       = $ahora->format( 'Y-m-d' );
        $ahora_str = $ahora->format( 'Y-m-d H:i:s' );

        // RN-01
        if ( ! $this->validar_codigo( $codigo ) ) {
            return array( 'ok' => false, 'error' => 'RN-01: Código inválido. Formato requerido: 9 dígitos + 1 letra (ej. 012345678A).' );
        }

        // RN-10: corte activo = primer corte cuya fecha de entrega es >= hoy
        $corte = $wpdb->get_var( $wpdb->prepare( "SELECT corte FROM {$this->t('cortes')} WHERE fecha_entrega >= %s ORDER BY fecha_entrega ASC LIMIT 1", $hoy ) );
        if ( ! $corte ) {
            return array( 'ok' => false, 'error' => 'RN-10: No hay un corte abierto para hoy. El administrador debe agregar una fecha de entrega en "Cortes y metas".' );
        }
        $corte = (int) $corte;

        // RN-03: carrera del padrón o alta por primera vez
        $mapa       = $this->mapa_carreras();
        $carrera_id = $wpdb->get_var( $wpdb->prepare( "SELECT carrera_id FROM {$this->t('estudiantes')} WHERE codigo = %s", $codigo ) );
        $nuevo      = false;
        if ( ! $carrera_id ) {
            if ( '' === $carrera_propuesta || ! isset( $mapa[ $carrera_propuesta ] ) ) {
                return array( 'ok' => false, 'error' => 'RN-03: Estudiante nuevo. Debe seleccionar una carrera válida del catálogo para registrarlo.' );
            }
            $carrera_id = $carrera_propuesta;
            $nuevo      = true;
        }

        // RN-09
        if ( ! $items ) {
            return array( 'ok' => false, 'error' => 'RN-09: La donación debe incluir al menos un producto.' );
        }

        // RN-06: vencimiento mínimo = hoy + 3 meses (ajustando fin de mes)
        $primero = $ahora->setTime( 0, 0 )->modify( 'first day of +3 month' );
        $dia     = min( (int) $ahora->format( 'j' ), (int) $primero->format( 't' ) );
        $minimo  = $primero->setDate( (int) $primero->format( 'Y' ), (int) $primero->format( 'n' ), $dia )->format( 'Y-m-d' );
        $minimo_fmt = date_create( $minimo )->format( 'd/m/Y' );

        // Se valida todo antes de escribir nada
        $lineas = array();
        foreach ( $items as $item ) {
            $item     = is_array( $item ) ? $item : array();
            $tipo     = isset( $item['tipo'] ) ? trim( sanitize_text_field( $item['tipo'] ) ) : '';
            $cantidad = isset( $item['cantidad'] ) && is_numeric( $item['cantidad'] ) ? $item['cantidad'] + 0 : 0;
            $venc     = isset( $item['fecha_vencimiento'] ) ? trim( sanitize_text_field( $item['fecha_vencimiento'] ) ) : '';
            $empaque  = ! isset( $item['empaque_ok'] ) || false !== $item['empaque_ok'];
            $fisico   = isset( $item['motivo_rechazo_fisico'] ) ? trim( sanitize_text_field( $item['motivo_rechazo_fisico'] ) ) : '';

            if ( ! in_array( $tipo, self::suministros(), true ) ) {
                return array( 'ok' => false, 'error' => 'RN-04: Tipo de suministro no válido: ' . $tipo );
            }
            if ( ! is_int( $cantidad ) || $cantidad < 1 ) {
                return array( 'ok' => false, 'error' => 'RN-05: La cantidad para ' . $tipo . ' debe ser un entero mayor o igual a 1.' );
            }
            if ( ! preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $venc, $m ) || ! checkdate( (int) $m[2], (int) $m[3], (int) $m[1] ) ) {
                return array( 'ok' => false, 'error' => 'Fecha de vencimiento inválida para ' . $tipo . '. Use formato YYYY-MM-DD.' );
            }
            $lineas[] = compact( 'tipo', 'cantidad', 'venc', 'empaque', 'fisico' );
        }

        $wpdb->query( 'START TRANSACTION' );
        $falla = false;

        if ( $nuevo ) {
            $falla = false === $wpdb->query( $wpdb->prepare(
                "INSERT INTO {$this->t('estudiantes')} (codigo, carrera_id, fecha_alta) VALUES (%s, %s, %s) ON DUPLICATE KEY UPDATE codigo = codigo",
                $codigo, $carrera_id, $hoy
            ) );
        }

        $donacion_num = 0;
        if ( ! $falla ) {
            $falla = false === $wpdb->insert( $this->t( 'donaciones' ), array(
                'codigo'         => $codigo,
                'carrera_id'     => $carrera_id,
                'fecha_hora'     => $ahora_str,
                'corte'          => $corte,
                'registrado_por' => mb_substr( $registrado_por, 0, 60 ),
            ) );
            $donacion_num = (int) $wpdb->insert_id;
        }

        $aceptados  = array();
        $rechazados = array();
        $lote_id    = 'LOTE-C' . $corte;

        foreach ( $lineas as $l ) {
            if ( $falla ) {
                break;
            }
            $cumple = $l['venc'] >= $minimo;
            $venc_fmt = date_create( $l['venc'] )->format( 'd/m/Y' );

            if ( ! $l['empaque'] || ! $cumple ) {
                if ( ! $l['empaque'] && ! $cumple ) {
                    $motivo = 'Falla física (' . ( $l['fisico'] ? $l['fisico'] : 'empaque dañado' ) . ') y vence ' . $venc_fmt . ' (mín. ' . $minimo_fmt . ')';
                } elseif ( ! $l['empaque'] ) {
                    $motivo = 'Inspección física rechazada: ' . ( $l['fisico'] ? $l['fisico'] : 'empaque no conforme' );
                } else {
                    $motivo = 'Vence ' . $venc_fmt . ' (requiere mínimo ' . $minimo_fmt . ')';
                }
                $motivo = mb_substr( $motivo, 0, 255 );

                $falla = false === $wpdb->insert( $this->t( 'descartes' ), array(
                    'donacion_id'       => $donacion_num,
                    'tipo'              => $l['tipo'],
                    'cantidad'          => $l['cantidad'],
                    'fecha_vencimiento' => $l['venc'],
                    'motivo'            => $motivo,
                ) );
                $rechazados[] = array(
                    'descarte_id'       => 'DESC-' . str_pad( (string) $wpdb->insert_id, 4, '0', STR_PAD_LEFT ),
                    'tipo'              => $l['tipo'],
                    'cantidad'          => $l['cantidad'],
                    'fecha_vencimiento' => $l['venc'],
                    'motivo'            => $motivo,
                );
            } else {
                $falla = false === $wpdb->insert( $this->t( 'detalle' ), array(
                    'donacion_id'       => $donacion_num,
                    'tipo'              => $l['tipo'],
                    'cantidad'          => $l['cantidad'],
                    'fecha_vencimiento' => $l['venc'],
                    'lote_id'           => $lote_id,
                    'corte'             => $corte,
                    'carrera_id'        => $carrera_id,
                ) );
                $aceptados[] = array(
                    'detalle_id'        => 'DET-' . str_pad( (string) $wpdb->insert_id, 4, '0', STR_PAD_LEFT ),
                    'tipo'              => $l['tipo'],
                    'cantidad'          => $l['cantidad'],
                    'fecha_vencimiento' => $l['venc'],
                    'lote_id'           => $lote_id,
                );
            }
        }

        if ( $falla ) {
            $wpdb->query( 'ROLLBACK' );
            return array( 'ok' => false, 'error' => 'No se pudo guardar la donación en la base de datos. Inténtalo de nuevo.' );
        }
        $wpdb->query( 'COMMIT' );

        return array(
            'ok'             => true,
            'donacion_id'    => 'DON-' . str_pad( (string) $donacion_num, 4, '0', STR_PAD_LEFT ),
            'codigo'         => $codigo,
            'carrera_id'     => $carrera_id,
            'carrera_nombre' => isset( $mapa[ $carrera_id ] ) ? $mapa[ $carrera_id ]['nombre'] : $carrera_id,
            'fecha_ingreso'  => $ahora_str,
            'corte'          => $corte,
            'aceptados'      => $aceptados,
            'rechazados'     => $rechazados,
        );
    }
}
