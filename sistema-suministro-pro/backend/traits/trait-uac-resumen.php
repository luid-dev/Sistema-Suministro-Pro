<?php
/**
 * Resumen del tablero público (RN-11 y RN-12): solo cifras agregadas, sin datos personales.
 *
 * @package Sistema_Suministro_Pro
 */

defined( 'ABSPATH' ) || exit;

trait UAC_SSP_Resumen {

    /** Resumen calculado desde la base de datos (caché de 10 s para muchos visitantes simultáneos). */
    private function resumen_base() {
        $cache = get_transient( 'uac_resumen_cache' );
        if ( is_array( $cache ) ) {
            return $cache;
        }
        $data = $this->calcular_resumen();
        set_transient( 'uac_resumen_cache', $data, 10 );
        return $data;
    }

    private function calcular_resumen() {
        global $wpdb;
        $suministros = self::suministros();
        $cortes      = $this->leer_cortes();
        $ph          = implode( ',', array_fill( 0, count( $suministros ), '%s' ) );

        $filas = (array) $wpdb->get_results( $wpdb->prepare(
            "SELECT corte, tipo, carrera_id, SUM(cantidad) AS c FROM {$this->t('detalle')} WHERE tipo IN ($ph) AND cantidad > 0 GROUP BY corte, tipo, carrera_id",
            $suministros
        ), ARRAY_A );

        $por_corte_tipo = array();
        $por_tipo       = array();
        $por_carrera    = array();
        foreach ( $filas as $f ) {
            $n = (int) $f['c'];
            $k = $f['corte'] . '|' . $f['tipo'];
            $por_corte_tipo[ $k ]       = ( isset( $por_corte_tipo[ $k ] ) ? $por_corte_tipo[ $k ] : 0 ) + $n;
            $por_tipo[ $f['tipo'] ]     = ( isset( $por_tipo[ $f['tipo'] ] ) ? $por_tipo[ $f['tipo'] ] : 0 ) + $n;
            $por_carrera[ $f['carrera_id'] ] = ( isset( $por_carrera[ $f['carrera_id'] ] ) ? $por_carrera[ $f['carrera_id'] ] : 0 ) + $n;
        }

        $avance = array();
        foreach ( $cortes as $c ) {
            foreach ( $suministros as $tipo ) {
                $meta = (int) $c['metas'][ $tipo ];
                $acep = isset( $por_corte_tipo[ $c['corte'] . '|' . $tipo ] ) ? $por_corte_tipo[ $c['corte'] . '|' . $tipo ] : 0;
                $avance[] = array(
                    'corte'             => $c['corte'],
                    'fecha_entrega'     => $c['fecha_entrega'],
                    'tipo'              => $tipo,
                    'meta_cantidad'     => $meta,
                    'cantidad_aceptada' => $acep,
                    'faltante'          => max( 0, $meta - $acep ),
                    'porcentaje_avance' => $meta > 0 ? $acep / $meta : 0,
                );
            }
        }

        $totales = array();
        foreach ( $suministros as $tipo ) {
            $meta_total = 0;
            foreach ( $cortes as $c ) {
                $meta_total += (int) $c['metas'][ $tipo ];
            }
            $totales[] = array( 'tipo' => $tipo, 'aceptado' => isset( $por_tipo[ $tipo ] ) ? $por_tipo[ $tipo ] : 0, 'meta_total' => $meta_total );
        }

        $ranking = array();
        foreach ( $this->mapa_carreras() as $id => $info ) {
            $ranking[] = array(
                'carrera_id'               => $id,
                'nombre'                   => $info['nombre'],
                'facultad'                 => $info['facultad'],
                'total_unidades_aceptadas' => isset( $por_carrera[ $id ] ) ? $por_carrera[ $id ] : 0,
            );
        }
        usort( $ranking, function ( $a, $b ) {
            return $b['total_unidades_aceptadas'] <=> $a['total_unidades_aceptadas'];
        } );

        $por_facultad = array();
        foreach ( $ranking as $r ) {
            $por_facultad[ $r['facultad'] ] = ( isset( $por_facultad[ $r['facultad'] ] ) ? $por_facultad[ $r['facultad'] ] : 0 ) + $r['total_unidades_aceptadas'];
        }
        arsort( $por_facultad );
        $ranking_fac = array();
        foreach ( $por_facultad as $f => $n ) {
            $ranking_fac[] = array( 'facultad' => (string) $f, 'total_unidades_aceptadas' => $n );
        }

        // Actividad de los últimos 7 días
        $desde  = $this->ahora()->modify( '-6 days' )->format( 'Y-m-d' );
        $por_dia = array();
        foreach ( (array) $wpdb->get_results( $wpdb->prepare(
            "SELECT DATE(d.fecha_hora) AS dia, SUM(dt.cantidad) AS u FROM {$this->t('donaciones')} d JOIN {$this->t('detalle')} dt ON dt.donacion_id = d.id WHERE d.fecha_hora >= %s GROUP BY DATE(d.fecha_hora)",
            $desde . ' 00:00:00'
        ), ARRAY_A ) as $r ) {
            $por_dia[ $r['dia'] ] = (int) $r['u'];
        }
        $actividad = array();
        for ( $d = 6; $d >= 0; $d-- ) {
            $dia         = $this->ahora()->modify( "-$d days" )->format( 'Y-m-d' );
            $actividad[] = array( 'fecha' => $dia, 'unidades' => isset( $por_dia[ $dia ] ) ? $por_dia[ $dia ] : 0 );
        }

        // Últimas 6 donaciones con unidades aceptadas (sin datos personales)
        $ultimas = array();
        foreach ( (array) $wpdb->get_results(
            "SELECT d.id, d.fecha_hora, d.carrera_id, c.nombre, SUM(dt.cantidad) AS u
             FROM {$this->t('donaciones')} d
             JOIN {$this->t('detalle')} dt ON dt.donacion_id = d.id
             LEFT JOIN {$this->t('carreras')} c ON c.carrera_id = d.carrera_id
             GROUP BY d.id, d.fecha_hora, d.carrera_id, c.nombre
             ORDER BY d.id DESC LIMIT 6", ARRAY_A ) as $r ) {
            $ultimas[] = array(
                'fecha_hora' => $r['fecha_hora'],
                'carrera'    => $r['nombre'] ? $r['nombre'] : $r['carrera_id'],
                'unidades'   => (int) $r['u'],
            );
        }

        $donaciones   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->t('donaciones')}" );
        $voluntarios  = (int) $wpdb->get_var( "SELECT COUNT(DISTINCT registrado_por) FROM {$this->t('donaciones')}" );
        $aceptadas    = (int) $wpdb->get_var( "SELECT COALESCE(SUM(cantidad),0) FROM {$this->t('detalle')} WHERE cantidad > 0" );
        $descartadas  = (int) $wpdb->get_var( "SELECT COALESCE(SUM(cantidad),0) FROM {$this->t('descartes')}" );
        $total        = $aceptadas + $descartadas;

        $activo = $this->corte_activo( $cortes );

        return array(
            'ok'                 => true,
            'actualizado_en'     => $this->ahora()->format( 'Y-m-d H:i:s' ),
            'totales_por_tipo'   => $totales,
            'avance_por_corte'   => $avance,
            'ranking_carreras'   => $ranking,
            'corte_activo'       => $activo ? $activo['corte'] : null,
            'corte_activo_fecha' => $activo ? $activo['fecha_entrega'] : null,
            'ranking_facultades' => $ranking_fac,
            'actividad_diaria'   => $actividad,
            'ultimas_donaciones' => $ultimas,
            'kpis'               => array(
                'donaciones'             => $donaciones,
                'unidades_aceptadas'     => $aceptadas,
                'unidades_descartadas'   => $descartadas,
                'tasa_aceptacion'        => $total > 0 ? round( $aceptadas / $total, 3 ) : 0,
                'carreras_participantes' => count( array_filter( $ranking, function ( $r ) {
                    return $r['total_unidades_aceptadas'] > 0;
                } ) ),
                'voluntarios_activos'    => $voluntarios,
            ),
        );
    }

    public function api_obtener_resumen() {
        $data = $this->resumen_base();

        if ( $this->modo_demo() ) {
            $data = $this->aplicar_demo( $data );
            $data['ok'] = true;
            unset( $data['_demo_donaciones'] );
            return new WP_REST_Response( $data, 200 );
        }
        return new WP_REST_Response( $data, 200 );
    }
}
