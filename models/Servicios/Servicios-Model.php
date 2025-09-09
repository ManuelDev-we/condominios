<?php 

class Servicios {
    public ?int $id_servicio;
    public ?int $id_condominio;
    public ?string $rfc;
    public ?string $nombre;
    public ?string $descripcion;
    public ?string $fecha_inicio;
    public ?string $fecha_fin;

    public function __construct(
        ?int $id_servicio = null,
        ?int $id_condominio = null,
        ?string $rfc = null,
        ?string $nombre = null,
        ?string $descripcion = null,
        ?string $fecha_inicio = null,
        ?string $fecha_fin = null
    ) {
        $this->id_servicio = $id_servicio;
        $this->id_condominio = $id_condominio;
        $this->rfc = $rfc;
        $this->nombre = $nombre;
        $this->descripcion = $descripcion;
        $this->fecha_inicio = $fecha_inicio;
        $this->fecha_fin = $fecha_fin;
    }
}