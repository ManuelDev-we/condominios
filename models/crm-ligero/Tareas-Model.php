<?php

class Tareas {
    public ?int $id_tarea;
    public ?int $id_condominio;
    public ?int $id_calle;
    public ?int $id_trabajador;
    public ?string $descripcion;
    public ?string $imagen;

    public function __construct(
        ?int $id_tarea = null,
        ?int $id_condominio = null,
        ?int $id_calle = null,
        ?int $id_trabajador = null,
        ?string $descripcion = null,
        ?string $imagen = null
    ) {
        $this->id_tarea = $id_tarea;
        $this->id_condominio = $id_condominio;
        $this->id_calle = $id_calle;
        $this->id_trabajador = $id_trabajador;
        $this->descripcion = $descripcion;
        $this->imagen = $imagen;
    }
}
