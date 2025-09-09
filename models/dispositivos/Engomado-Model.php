<?php

class Engomados {
    public ?int $id_engomado;
    public ?int $id_persona;
    public ?int $id_casa;
    public ?int $id_condominio;
    public ?int $id_calle;
    public ?string $placa;
    public ?string $modelo;
    public ?string $color;
    public ?int $anio;
    public ?string $foto;
    public ?int $activo;
    public ?string $creado_en;

    public function __construct(
        ?int $id_engomado = null,
        ?int $id_persona = null,
        ?int $id_casa = null,
        ?int $id_condominio = null,
        ?int $id_calle = null,
        ?string $placa = null,
        ?string $modelo = null,
        ?string $color = null,
        ?int $anio = null,
        ?string $foto = null,
        ?int $activo = null,
        ?string $creado_en = null
    ) {
        $this->id_engomado = $id_engomado;
        $this->id_persona = $id_persona;
        $this->id_casa = $id_casa;
        $this->id_condominio = $id_condominio;
        $this->id_calle = $id_calle;
        $this->placa = $placa;
        $this->modelo = $modelo;
        $this->color = $color;
        $this->anio = $anio;
        $this->foto = $foto;
        $this->activo = $activo;
        $this->creado_en = $creado_en;
    }
}
