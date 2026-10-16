<?php
/**
 * Base de datos propia: tablas {prefijo}uac_* en MySQL/MariaDB, creación con dbDelta y utilidades.
 *
 * @package Sistema_Suministro_Pro
 */

defined( 'ABSPATH' ) || exit;

trait UAC_SSP_Base_Datos {

    /** Suministros oficiales de la campaña. */
    private static function suministros() {
        return array( 'Bebida rehidratante', 'Chocolate', 'Galletas soda', 'Atún' );
    }

    /** Nombre completo de una tabla del sistema. */
    private function t( $nombre ) {
        global $wpdb;
        return $wpdb->prefix . 'uac_' . $nombre;
    }

    private function zona() {
        return new DateTimeZone( apply_filters( 'uac_suministros_zona_horaria', 'America/Lima' ) );
    }

    private function ahora() {
        return new DateTimeImmutable( 'now', $this->zona() );
    }

    private function fecha_hoy() {
        return $this->ahora()->format( 'Y-m-d' );
    }

    /** Crea o actualiza las tablas cuando cambia la versión del esquema. */
    public function verificar_base_datos() {
        if ( get_option( self::OPT_DB_VERSION ) !== self::DB_VERSION ) {
            $this->crear_tablas();
        }
    }

    public function crear_tablas() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $cs = $wpdb->get_charset_collate();

        dbDelta( "CREATE TABLE {$this->t('carreras')} (
  carrera_id varchar(20) NOT NULL,
  nombre varchar(150) NOT NULL,
  facultad varchar(200) NOT NULL DEFAULT '',
  PRIMARY KEY  (carrera_id)
) $cs;" );

        dbDelta( "CREATE TABLE {$this->t('estudiantes')} (
  codigo varchar(10) NOT NULL,
  carrera_id varchar(20) NOT NULL,
  fecha_alta date NOT NULL,
  PRIMARY KEY  (codigo),
  KEY carrera_id (carrera_id)
) $cs;" );

        dbDelta( "CREATE TABLE {$this->t('cortes')} (
  corte int(10) unsigned NOT NULL AUTO_INCREMENT,
  fecha_entrega date NOT NULL,
  PRIMARY KEY  (corte),
  UNIQUE KEY fecha_entrega (fecha_entrega)
) $cs;" );

        dbDelta( "CREATE TABLE {$this->t('metas')} (
  corte int(10) unsigned NOT NULL,
  tipo varchar(60) NOT NULL,
  cantidad int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY  (corte,tipo)
) $cs;" );

        dbDelta( "CREATE TABLE {$this->t('donaciones')} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  codigo varchar(10) NOT NULL,
  carrera_id varchar(20) NOT NULL,
  fecha_hora datetime NOT NULL,
  corte int(10) unsigned NOT NULL,
  registrado_por varchar(60) NOT NULL DEFAULT '',
  PRIMARY KEY  (id),
  KEY corte (corte),
  KEY fecha_hora (fecha_hora)
) $cs;" );

        dbDelta( "CREATE TABLE {$this->t('detalle')} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  donacion_id bigint(20) unsigned NOT NULL,
  tipo varchar(60) NOT NULL,
  cantidad int(10) unsigned NOT NULL,
  fecha_vencimiento date NOT NULL,
  lote_id varchar(20) NOT NULL DEFAULT '',
  corte int(10) unsigned NOT NULL,
  carrera_id varchar(20) NOT NULL,
  PRIMARY KEY  (id),
  KEY donacion_id (donacion_id),
  KEY corte_tipo (corte,tipo),
  KEY carrera_id (carrera_id)
) $cs;" );

        dbDelta( "CREATE TABLE {$this->t('descartes')} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  donacion_id bigint(20) unsigned NOT NULL,
  tipo varchar(60) NOT NULL,
  cantidad int(10) unsigned NOT NULL,
  fecha_vencimiento date NOT NULL,
  motivo varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY  (id),
  KEY donacion_id (donacion_id)
) $cs;" );

        $this->sembrar_carreras();
        update_option( self::OPT_DB_VERSION, self::DB_VERSION, false );
    }

    /**
     * Catálogo inicial de carreras (solo si la tabla está vacía).
     * Se puede reemplazar o ampliar con la importación CSV de Ajustes > Sistema-Suministro-Pro.
     */
    private function sembrar_carreras() {
        global $wpdb;
        if ( (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->t('carreras')}" ) > 0 ) {
            return;
        }
        $salud = 'Facultad de Ciencias de la Salud';
        $ing   = 'Facultad de Ingeniería y Arquitectura';
        $der   = 'Facultad de Derecho y Ciencia Política';
        $eco   = 'Facultad de Ciencias Económicas, Administrativas y Contables';
        $hum   = 'Facultad de Ciencias y Humanidades';
        $lista = array(
            array( 'MEDH', 'Medicina Humana', $salud ),
            array( 'ENFE', 'Enfermería', $salud ),
            array( 'ESTO', 'Estomatología', $salud ),
            array( 'PSIC', 'Psicología', $salud ),
            array( 'TMED', 'Tecnología Médica', $salud ),
            array( 'ICIV', 'Ingeniería Civil', $ing ),
            array( 'ISIS', 'Ingeniería de Sistemas', $ing ),
            array( 'IIND', 'Ingeniería Industrial', $ing ),
            array( 'IAMB', 'Ingeniería Ambiental', $ing ),
            array( 'ARQU', 'Arquitectura', $ing ),
            array( 'DERE', 'Derecho', $der ),
            array( 'ADMI', 'Administración', $eco ),
            array( 'CONT', 'Contabilidad', $eco ),
            array( 'ECON', 'Economía', $eco ),
            array( 'COMU', 'Ciencias de la Comunicación', $hum ),
            array( 'TURI', 'Turismo', $hum ),
        );
        foreach ( $lista as $c ) {
            $wpdb->insert( $this->t( 'carreras' ), array( 'carrera_id' => $c[0], 'nombre' => $c[1], 'facultad' => $c[2] ) );
        }
    }

    private function mapa_carreras() {
        global $wpdb;
        $mapa = array();
        foreach ( (array) $wpdb->get_results( "SELECT carrera_id, nombre, facultad FROM {$this->t('carreras')} ORDER BY nombre", ARRAY_A ) as $c ) {
            $mapa[ $c['carrera_id'] ] = array( 'nombre' => $c['nombre'], 'facultad' => $c['facultad'] );
        }
        return $mapa;
    }

    private function validar_codigo( $codigo ) {
        return (bool) preg_match( '/^[0-9]{9}[A-Z]$/', $codigo );
    }

    private function limpiar_cache() {
        delete_transient( 'uac_resumen_cache' );
    }
}
