<?php
/**
 * Modo demostración (datos de ejemplo que no se guardan) y reporte del equipo.
 *
 * @package Sistema_Suministro_Pro
 */

defined( 'ABSPATH' ) || exit;

trait UAC_SSP_Demo {

    private function modo_demo() {
        return (bool) get_option( self::OPT_DEMO );
    }

    /** Usuarios que figuran como voluntarios en los reportes de demostración. */
    private function demo_voluntarios() {
        $logins = wp_list_pluck( get_users( array( 'role' => 'uac_registrador', 'fields' => array( 'user_login' ) ) ), 'user_login' );
        foreach ( array( 'vol_ana', 'vol_luis', 'vol_rosa', 'vol_jorge' ) as $extra ) {
            if ( count( $logins ) < 5 ) {
                $logins[] = $extra;
            }
        }
        return array_slice( array_values( array_unique( $logins ) ), 0, 8 );
    }

    /** Elige un índice según pesos (determinista con mt_srand). */
    private function elegir_pesado( array $pesos ) {
        $total = array_sum( $pesos );
        $r     = mt_rand( 1, max( 1, (int) $total ) );
        foreach ( $pesos as $i => $p ) {
            $r -= $p;
            if ( $r <= 0 ) {
                return $i;
            }
        }
        return array_key_last( $pesos );
    }

    /** Superpone cifras de demostración (y las métricas nuevas) a la estructura real del resumen. */
    private function aplicar_demo( array $base ) {
        mt_srand( 20261015 );
        $tipos = array( 'Bebida rehidratante', 'Chocolate', 'Galletas soda', 'Atún' );
        $factor = array();
        foreach ( $tipos as $t ) {
            $factor[ $t ] = mt_rand( 40, 92 ) / 100;
        }

        $rows   = isset( $base['avance_por_corte'] ) && is_array( $base['avance_por_corte'] ) ? $base['avance_por_corte'] : array();
        $acept  = array_fill_keys( $tipos, 0 );
        foreach ( $rows as $i => $r ) {
            $meta = (int) $r['meta_cantidad'];
            $a    = (int) min( $meta, round( $meta * ( isset( $factor[ $r['tipo'] ] ) ? $factor[ $r['tipo'] ] : 0.5 ) ) );
            $rows[ $i ]['cantidad_aceptada']  = $a;
            $rows[ $i ]['faltante']           = max( 0, $meta - $a );
            $rows[ $i ]['porcentaje_avance']  = $meta > 0 ? $a / $meta : 0;
            if ( isset( $acept[ $r['tipo'] ] ) ) {
                $acept[ $r['tipo'] ] += $a;
            }
        }
        $totales = array();
        foreach ( $tipos as $t ) {
            $meta_total = 0;
            foreach ( (array) ( isset( $base['totales_por_tipo'] ) ? $base['totales_por_tipo'] : array() ) as $x ) {
                if ( $x['tipo'] === $t ) {
                    $meta_total = (int) $x['meta_total'];
                }
            }
            if ( ! $meta_total ) {
                $meta_total = 12;
            }
            $a = $rows ? $acept[ $t ] : (int) round( $meta_total * $factor[ $t ] );
            $totales[] = array( 'tipo' => $t, 'aceptado' => $a, 'meta_total' => $meta_total );
        }
        $T = array_sum( wp_list_pluck( $totales, 'aceptado' ) );

        // Carreras: se reparte el total con pesos desiguales para que haya una competencia visible.
        $carreras = isset( $base['ranking_carreras'] ) && is_array( $base['ranking_carreras'] ) && $base['ranking_carreras'] ? $base['ranking_carreras'] : array(
            array( 'nombre' => 'Medicina Humana', 'facultad' => 'Facultad de Ciencias de la Salud' ),
            array( 'nombre' => 'Ingeniería Civil', 'facultad' => 'Facultad de Ingeniería y Arquitectura' ),
            array( 'nombre' => 'Derecho', 'facultad' => 'Facultad de Derecho y Ciencia Política' ),
            array( 'nombre' => 'Administración', 'facultad' => 'Facultad de Ciencias Económicas, Administrativas y Contables' ),
        );
        $pesos = array();
        foreach ( $carreras as $i => $c ) {
            $pesos[ $i ] = pow( mt_rand( 1, 100 ), 2 );
            $carreras[ $i ]['total_unidades_aceptadas'] = 0;
        }
        for ( $u = 0; $u < $T; $u++ ) {
            $carreras[ $this->elegir_pesado( $pesos ) ]['total_unidades_aceptadas']++;
        }
        usort( $carreras, function ( $a, $b ) {
            return $b['total_unidades_aceptadas'] <=> $a['total_unidades_aceptadas'];
        } );

        // Facultades
        $fac = array();
        foreach ( $carreras as $c ) {
            $f = isset( $c['facultad'] ) ? $c['facultad'] : '';
            $fac[ $f ] = ( isset( $fac[ $f ] ) ? $fac[ $f ] : 0 ) + $c['total_unidades_aceptadas'];
        }
        arsort( $fac );
        $ranking_fac = array();
        foreach ( $fac as $f => $n ) {
            $ranking_fac[] = array( 'facultad' => $f, 'total_unidades_aceptadas' => $n );
        }

        // Actividad de los últimos 7 días
        $pd = array();
        for ( $d = 0; $d < 7; $d++ ) {
            $pd[ $d ] = mt_rand( 3, 20 ) + ( $d >= 4 ? 6 : 0 );
        }
        $act = array_fill( 0, 7, 0 );
        for ( $u = 0; $u < $T; $u++ ) {
            $act[ $this->elegir_pesado( $pd ) ]++;
        }
        $actividad = array();
        for ( $d = 0; $d < 7; $d++ ) {
            $actividad[] = array( 'fecha' => wp_date( 'Y-m-d', strtotime( '-' . ( 6 - $d ) . ' days' ) ), 'unidades' => $act[ $d ] );
        }

        $donaciones = max( 1, (int) round( $T / 3.2 ) );
        $descartadas = (int) round( $T * 0.14 ) + 1;
        $con_aporte = array_values( array_filter( $carreras, function ( $c ) {
            return $c['total_unidades_aceptadas'] > 0;
        } ) );
        $ultimas = array();
        $min = 4;
        for ( $k = 0; $k < min( 6, count( $con_aporte ) ); $k++ ) {
            $c = $con_aporte[ mt_rand( 0, count( $con_aporte ) - 1 ) ];
            $ultimas[] = array(
                'fecha_hora' => wp_date( 'Y-m-d H:i:s', time() - $min * 60 ),
                'carrera'    => $c['nombre'],
                'unidades'   => mt_rand( 1, 4 ),
            );
            $min += mt_rand( 7, 55 );
        }

        $base['demo']             = true;
        $base['actualizado_en']   = wp_date( 'Y-m-d H:i:s' );
        $base['totales_por_tipo'] = $totales;
        $base['avance_por_corte'] = $rows;
        $base['ranking_carreras'] = $carreras;
        $base['ranking_facultades'] = $ranking_fac;
        $base['actividad_diaria'] = $actividad;
        $base['ultimas_donaciones'] = $ultimas;
        $base['kpis'] = array(
            'donaciones'              => $donaciones,
            'unidades_aceptadas'      => $T,
            'unidades_descartadas'    => $descartadas,
            'tasa_aceptacion'         => $T + $descartadas > 0 ? round( $T / ( $T + $descartadas ), 3 ) : 0,
            'carreras_participantes'  => count( $con_aporte ),
            'voluntarios_activos'     => count( $this->demo_voluntarios() ),
        );
        $base['_demo_donaciones'] = $donaciones;
        return $base;
    }

    /** GET /wp-json/uac/v1/reporte (solo equipo): ranking de voluntarios y motivos de descarte. */
    public function api_reporte() {
        if ( $this->modo_demo() ) {
            $resumen = $this->aplicar_demo( $this->resumen_base() );
            mt_srand( 77 );
            $vol    = $this->demo_voluntarios();
            $pesos  = array();
            foreach ( $vol as $i => $v ) {
                $pesos[ $i ] = mt_rand( 5, 40 );
            }
            $don = (int) $resumen['_demo_donaciones'];
            $cnt = array_fill( 0, count( $vol ), 0 );
            for ( $k = 0; $k < $don; $k++ ) {
                $cnt[ $this->elegir_pesado( $pesos ) ]++;
            }
            $lista = array();
            foreach ( $vol as $i => $v ) {
                $lista[] = array( 'usuario' => $v, 'donaciones' => $cnt[ $i ], 'unidades' => (int) round( $cnt[ $i ] * $resumen['kpis']['unidades_aceptadas'] / max( 1, $don ) ) );
            }
            usort( $lista, function ( $a, $b ) {
                return $b['donaciones'] <=> $a['donaciones'];
            } );
            $d = (int) $resumen['kpis']['unidades_descartadas'];
            $d1 = (int) round( $d * 0.6 );
            return new WP_REST_Response( array(
                'ok'         => true,
                'demo'       => true,
                'voluntarios' => $lista,
                'descartes'  => array(
                    array( 'motivo' => 'Vence en menos de 3 meses', 'cantidad' => $d1 ),
                    array( 'motivo' => 'Empaque en mal estado', 'cantidad' => $d - $d1 ),
                ),
            ), 200 );
        }

        global $wpdb;
        $vols = array();
        foreach ( (array) $wpdb->get_results(
            "SELECT d.registrado_por AS usuario, COUNT(DISTINCT d.id) AS donaciones, COALESCE(SUM(dt.cantidad),0) AS unidades
             FROM {$this->t('donaciones')} d LEFT JOIN {$this->t('detalle')} dt ON dt.donacion_id = d.id
             GROUP BY d.registrado_por ORDER BY donaciones DESC, unidades DESC", ARRAY_A ) as $v ) {
            $vols[] = array( 'usuario' => $v['usuario'] ? $v['usuario'] : 'sin_usuario', 'donaciones' => (int) $v['donaciones'], 'unidades' => (int) $v['unidades'] );
        }
        $desc = array();
        foreach ( (array) $wpdb->get_results(
            "SELECT CASE WHEN motivo LIKE '%vence%' THEN 'Vence en menos de 3 meses' ELSE 'Empaque en mal estado' END AS motivo, SUM(cantidad) AS cantidad
             FROM {$this->t('descartes')} GROUP BY 1", ARRAY_A ) as $r ) {
            $desc[] = array( 'motivo' => $r['motivo'], 'cantidad' => (int) $r['cantidad'] );
        }
        return new WP_REST_Response( array( 'ok' => true, 'voluntarios' => $vols, 'descartes' => $desc ), 200 );
    }
}
