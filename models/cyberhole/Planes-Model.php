<?php

class Planes {
    public ?int $id_plan;
    public ?string $tipo_plan;
    public ?int $duracion_dias;
    public ?int $es_mensual;
    public ?string $descripcion;

    public function __construct(
        ?int $id_plan = null,
        ?string $tipo_plan = null,
        ?int $duracion_dias = null,
        ?int $es_mensual = null,
        ?string $descripcion = null
    ) {
        $this->id_plan = $id_plan;
        $this->tipo_plan = $tipo_plan;
        $this->duracion_dias = $duracion_dias;
        $this->es_mensual = $es_mensual;
        $this->descripcion = $descripcion;
    }
}

