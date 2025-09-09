<?php

class EmpleadosCondominio {
    public ?int $id_empleado;
    public ?string $rfc;
    public ?int $id_condominio;
    public ?string $_email;
    public ?string $_contrasena;
    public ?string $nombres;
    public ?string $apellido1;
    public ?string $apellido2;
    public ?string $fecha_contrato;
    public ?string $id_acceso;
    public ?int $activo;

    public function __construct(
        ?int $id_empleado = null,
        ?string $rfc = null,
        ?int $id_condominio = null,
        ?string $_email = null,
        ?string $_contrasena = null,
        ?string $nombres = null,
        ?string $apellido1 = null,
        ?string $apellido2 = null,
        ?string $fecha_contrato = null,
        ?string $id_acceso = null,
        ?int $activo = null
    ) {
        $this->id_empleado = $id_empleado;
        $this->email = $_email;
        $this->contrasena = $_contrasena;
        $this->rfc = $rfc;
        $this->id_condominio = $id_condominio;
        $this->nombres = $nombres;
        $this->apellido1 = $apellido1;
        $this->apellido2 = $apellido2;
        $this->fecha_contrato = $fecha_contrato;
        $this->id_acceso = $id_acceso;
        $this->activo = $activo;
    }
}