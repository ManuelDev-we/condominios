<?php

class ClavesRegistro {
    public ?string $codigo;
    public ?int $id_condominio;
    public ?int $id_calle;
    public ?int $id_casa;
    public ?string $fecha_creacion;
    public ?string $fecha_expiracion;
    public ?int $usado;
    public ?string $fecha_canje;

    public function __construct(
        ?string $codigo = null,
        ?int $id_condominio = null,
        ?int $id_calle = null,
        ?int $id_casa = null,
        ?string $fecha_creacion = null,
        ?string $fecha_expiracion = null,
        ?int $usado = null,
        ?string $fecha_canje = null
    ) {
        $this->codigo = $codigo;
        $this->id_condominio = $id_condominio;
        $this->id_calle = $id_calle;
        $this->id_casa = $id_casa;
        $this->fecha_creacion = $fecha_creacion;
        $this->fecha_expiracion = $fecha_expiracion;
        $this->usado = $usado;
        $this->fecha_canje = $fecha_canje;
    }
}

