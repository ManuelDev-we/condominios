<?php 
class Visitantes {
    public ?int $id_visitante;
    public ?string $nombre;
    public ?string $foto_identificacion;
    public ?int $id_condominio;
    public ?int $id_casa;
    public ?string $placas;
    public ?string $fecha_hora_qr_generado;
    public ?string $fecha_hora_entrada;
    public ?string $fecha_hora_salida;

    public function __construct(
        ?int $id_visitante = null,
        ?string $nombre = null,
        ?string $foto_identificacion = null,
        ?int $id_condominio = null,
        ?int $id_casa = null,
        ?string $placas = null,
        ?string $fecha_hora_qr_generado = null,
        ?string $fecha_hora_entrada = null,
        ?string $fecha_hora_salida = null
    ) {
        $this->id_visitante = $id_visitante;
        $this->nombre = $nombre;
        $this->foto_identificacion = $foto_identificacion;
        $this->id_condominio = $id_condominio;
        $this->id_casa = $id_casa;
        $this->placas = $placas;
        $this->fecha_hora_qr_generado = $fecha_hora_qr_generado;
        $this->fecha_hora_entrada = $fecha_hora_entrada;
        $this->fecha_hora_salida = $fecha_hora_salida;
    }
}