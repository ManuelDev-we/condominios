<?php

class Tags {
    public ?int $id_tag;
    public ?int $id_persona;
    public ?int $id_casa;
    public ?int $id_condominio;
    public ?int $id_calle;
    public ?string $codigo_tag;
    public ?int $activo;
    public ?string $creado_en;

    public function __construct(
        ?int $id_tag = null,
        ?int $id_persona = null,
        ?int $id_casa = null,
        ?int $id_condominio = null,
        ?int $id_calle = null,
        ?string $codigo_tag = null,
        ?int $activo = null,
        ?string $creado_en = null
    ) {
        $this->id_tag = $id_tag;
        $this->id_persona = $id_persona;
        $this->id_casa = $id_casa;
        $this->id_condominio = $id_condominio;
        $this->id_calle = $id_calle;
        $this->codigo_tag = $codigo_tag;
        $this->activo = $activo;
        $this->creado_en = $creado_en;
    }
}