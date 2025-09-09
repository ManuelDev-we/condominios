<?php

class Inventarios {
    public ?int $id_inventario;
    public ?int $id_condominio;
    public ?string $rfc;
    public ?string $nombre;
    public ?string $descripcion;
    public ?int $cantidad_actual;
    public ?string $unidad_medida;
    public ?int $tiempo_vida_dias;
    public ?string $fecha_alta;

    public function __construct(
        ?int $id_inventario = null,
        ?int $id_condominio = null,
        ?string $rfc = null,
        ?string $nombre = null,
        ?string $descripcion = null,
        ?int $cantidad_actual = null,
        ?string $unidad_medida = null,
        ?int $tiempo_vida_dias = null,
        ?string $fecha_alta = null
    ) {
        $this->id_inventario = $id_inventario;
        $this->id_condominio = $id_condominio;
        $this->rfc = $rfc;
        $this->nombre = $nombre;
        $this->descripcion = $descripcion;
        $this->cantidad_actual = $cantidad_actual;
        $this->unidad_medida = $unidad_medida;
        $this->tiempo_vida_dias = $tiempo_vida_dias;
        $this->fecha_alta = $fecha_alta;
    }
}