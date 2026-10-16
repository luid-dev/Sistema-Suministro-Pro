<?php
/**
 * Cortes (fechas de entrega) y metas por suministro: /wp-json/uac/v1/cortes (solo administrador).
 *
 * @package Sistema_Suministro_Pro
 */

defined( 'ABSPATH' ) || exit;

trait UAC_SSP_Cortes {

    public function registrar_rutas_cortes() {
        register_rest_route( 'uac/v1', '/cortes', array(
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'api_listar_cortes' ),
                'permission_callback' => array( $this, 'es_administrador' ),
            ),
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'api_guardar_cortes' ),
                'permission_callback' => array( $this, 'es_administrador' ),
            ),
        ) );

        register_rest_route( 'uac/v1', '/cortes/(?P<corte>\d+)', array(
            'methods'             => 'DELETE',
            'callback'            => array( $this, 'api_quitar_corte' ),
            'permission_callback' => array( $this, 'es_administrador' ),
        ) );
    }

    public function es_administrador() {
        return current_user_can( 'manage_options' );
    }

    /**
     * Cortes con sus metas, ordenados por fecha:
     * [{ corte, fecha_entrega: 'yyyy-MM-dd', metas: { tipo: cantidad }, donaciones }]
     */
    private function leer_cortes() {
        global $wpdb;
        $cortes = array();
        foreach ( (array) $wpdb->get_results( "SELECT corte, fecha_entrega FROM {$this->t('cortes')} ORDER BY fecha_entrega ASC", ARRAY_A ) as $c ) {
            $metas = array();
            foreach ( self::suministros() as $tipo ) {
                $metas[ $tipo ] = 0;
            }
            $cortes[ (int) $c['corte'] ] = array(
                'corte'         => (int) $c['corte'],
                'fecha_entrega' => $c['fecha_entrega'],
                'metas'         => $metas,
                'donaciones'    => 0,
            );
        }
        foreach ( (array) $wpdb->get_results( "SELECT corte, tipo, cantidad FROM {$this->t('metas')}", ARRAY_A ) as $m ) {
            if ( isset( $cortes[ (int) $m['corte'] ] ) && in_array( $m['tipo'], self::suministros(), true ) ) {
                $cortes[ (int) $m['corte'] ]['metas'][ $m['tipo'] ] = (int) $m['cantidad'];
            }
        }
        foreach ( (array) $wpdb->get_results( "SELECT corte, COUNT(*) AS n FROM {$this->t('donaciones')} GROUP BY corte", ARRAY_A ) as $d ) {
            if ( isset( $cortes[ (int) $d['corte'] ] ) ) {
                $cortes[ (int) $d['corte'] ]['donaciones'] = (int) $d['n'];
            }
        }
        return array_values( $cortes );
    }

    /** RN-10: primer corte cuya fecha de entrega es hoy o posterior. */
    private function corte_activo( array $cortes ) {
        $hoy = $this->fecha_hoy();
        foreach ( $cortes as $c ) {
            if ( $c['fecha_entrega'] >= $hoy ) {
                return $c;
            }
        }
        return null;
    }

    /** Respuesta para la pantalla "Cortes y metas". */
    private function listar_cortes() {
        $cortes = $this->leer_cortes();
        $activo = $this->corte_activo( $cortes );
        return array(
            'ok'          => true,
            'hoy'         => $this->fecha_hoy(),
            'suministros' => self::suministros(),
            'corte_activo' => $activo ? $activo['corte'] : null,
            'cortes'      => $cortes,
        );
    }

    public function api_listar_cortes() {
        return new WP_REST_Response( $this->listar_cortes(), 200 );
    }

    /**
     * Crea UN corte nuevo. Los cortes existentes no se editan desde el sistema
     * y conservan su número (las donaciones ya guardadas lo usan).
     */
    public function api_guardar_cortes( WP_REST_Request $request ) {
        global $wpdb;
        $params = $request->get_json_params();
        $fecha  = isset( $params['fecha_entrega'] ) ? sanitize_text_field( $params['fecha_entrega'] ) : '';
        $metas  = array();
        foreach ( self::suministros() as $tipo ) {
            $meta  = isset( $params['metas'][ $tipo ] ) ? $params['metas'][ $tipo ] : 0;
            $valor = filter_var( $meta, FILTER_VALIDATE_INT, array( 'options' => array( 'min_range' => 0 ) ) );
            if ( false === $valor ) {
                return new WP_REST_Response( array( 'ok' => false, 'error' => 'Las metas deben ser números enteros mayores o iguales a 0.' ), 400 );
            }
            $metas[ $tipo ] = $valor;
        }

        if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $fecha ) || ! checkdate( (int) substr( $fecha, 5, 2 ), (int) substr( $fecha, 8, 2 ), (int) substr( $fecha, 0, 4 ) ) ) {
            return new WP_REST_Response( array( 'ok' => false, 'error' => 'Elige una fecha de entrega válida.' ), 400 );
        }
        if ( ! array_sum( $metas ) ) {
            return new WP_REST_Response( array( 'ok' => false, 'error' => 'Indica la meta de al menos un suministro.' ), 400 );
        }
        if ( $fecha < $this->fecha_hoy() ) {
            return new WP_REST_Response( array( 'ok' => false, 'error' => 'La fecha de entrega no puede ser anterior a hoy.' ), 400 );
        }

        $existente = $wpdb->get_var( $wpdb->prepare( "SELECT corte FROM {$this->t('cortes')} WHERE fecha_entrega = %s", $fecha ) );
        if ( $existente ) {
            return new WP_REST_Response( array( 'ok' => false, 'error' => 'Ya existe un corte con esa fecha de entrega (Corte ' . (int) $existente . ').' ), 400 );
        }

        $wpdb->query( 'START TRANSACTION' );
        $falla = false === $wpdb->insert( $this->t( 'cortes' ), array( 'fecha_entrega' => $fecha ) );
        $num   = (int) $wpdb->insert_id;
        foreach ( $metas as $tipo => $valor ) {
            if ( $falla ) {
                break;
            }
            $falla = false === $wpdb->insert( $this->t( 'metas' ), array( 'corte' => $num, 'tipo' => $tipo, 'cantidad' => $valor ) );
        }
        if ( $falla ) {
            $wpdb->query( 'ROLLBACK' );
            return new WP_REST_Response( array( 'ok' => false, 'error' => 'No se pudo guardar el corte en la base de datos.' ), 500 );
        }
        $wpdb->query( 'COMMIT' );

        $this->limpiar_cache();
        return new WP_REST_Response( $this->listar_cortes(), 200 );
    }

    /** Quita un corte solo si no tiene donaciones registradas. */
    public function api_quitar_corte( WP_REST_Request $request ) {
        global $wpdb;
        $numero = absint( $request->get_param( 'corte' ) );

        if ( ! $wpdb->get_var( $wpdb->prepare( "SELECT corte FROM {$this->t('cortes')} WHERE corte = %d", $numero ) ) ) {
            return new WP_REST_Response( array( 'ok' => false, 'error' => 'El Corte ' . $numero . ' no existe.' ), 404 );
        }
        $n = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$this->t('donaciones')} WHERE corte = %d", $numero ) );
        if ( $n > 0 ) {
            return new WP_REST_Response( array( 'ok' => false, 'error' => 'No se puede quitar el Corte ' . $numero . ': ya tiene ' . $n . ' donación(es) registrada(s).' ), 400 );
        }

        $wpdb->delete( $this->t( 'metas' ), array( 'corte' => $numero ) );
        $wpdb->delete( $this->t( 'cortes' ), array( 'corte' => $numero ) );

        $this->limpiar_cache();
        return new WP_REST_Response( $this->listar_cortes(), 200 );
    }
}
