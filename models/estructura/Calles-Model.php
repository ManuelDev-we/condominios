<?php

class Calles {
    public ?int $id_calle;
    public ?int $id_condominio;
    public ?string $nombre;
    public ?string $descripcion;

    public function __construct(
        ?int $id_calle = null,
        ?int $id_condominio = null,
        ?string $nombre = null,
        ?string $descripcion = null
    ) {
        $this->id_calle = $id_calle;
        $this->id_condominio = $id_condominio;
        $this->nombre = $nombre;
        $this->descripcion = $descripcion;
    }
}