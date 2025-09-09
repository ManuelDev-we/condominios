<?php


class AccesosEmpleados {
    public ?int $id_acceso;
    public ?int $id_empleado;
    public ?int $id_condominio;
    public ?string $id_acceso_empleado;
    public ?string $fecha_hora_entrada;
    public ?string $fecha_hora_salida;

    public function __construct(
        ?int $id_acceso = null,
        ?int $id_empleado = null,
        ?int $id_condominio = null,
        ?string $id_acceso_empleado = null,
        ?string $fecha_hora_entrada = null,
        ?string $fecha_hora_salida = null
    ) {
        $this->id_acceso = $id_acceso;
        $this->id_empleado = $id_empleado;
        $this->id_condominio = $id_condominio;
        $this->id_acceso_empleado = $id_acceso_empleado;
        $this->fecha_hora_entrada = $fecha_hora_entrada;
        $this->fecha_hora_salida = $fecha_hora_salida;
    }
}
