<?php 


class ApartarAreasComunes {
    public ?int $id_apartado;
    public ?int $id_area_comun;
    public ?int $id_condominio;
    public ?int $id_calle;
    public ?int $id_casa;
    public ?string $fecha_apartado;
    public ?string $descripcion;

    public function __construct(
        ?int $id_apartado = null,
        ?int $id_area_comun = null,
        ?int $id_condominio = null,
        ?int $id_calle = null,
        ?int $id_casa = null,
        ?string $fecha_apartado = null,
        ?string $descripcion = null
    ) {
        $this->id_apartado = $id_apartado;
        $this->id_area_comun = $id_area_comun;
        $this->id_condominio = $id_condominio;
        $this->id_calle = $id_calle;
        $this->id_casa = $id_casa;
        $this->fecha_apartado = $fecha_apartado;
        $this->descripcion = $descripcion;
    }
}