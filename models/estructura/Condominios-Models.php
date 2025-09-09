<?php


class Condominios {
    public ?int $id_condominio;
    public ?string $nombre;
    public ?string $rfc;
    public ?string $direccion;

    public function __construct(
        ?int $id_condominio = null,
        ?string $nombre = null,
        ?string $rfc = null,
        ?string $direccion = null
    ) {
        $this->id_condominio = $id_condominio;
        $this->nombre = $nombre;
        $this->rfc = $rfc;
        $this->direccion = $direccion;
    }
}


