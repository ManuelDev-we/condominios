<?php


class AccesosResidentes {
    public ?int $id_acceso;
    public ?int $id_persona;
    public ?int $id_condominio;
    public ?int $id_casa;
    public ?int $id_persona_dispositivo;
    public ?string $fecha_hora_entrada;
    public ?string $fecha_hora_salida;

    public function __construct(
        ?int $id_acceso = null,
        ?int $id_persona = null,
        ?int $id_condominio = null,
        ?int $id_casa = null,
        ?int $id_persona_dispositivo = null,
        ?string $fecha_hora_entrada = null,
        ?string $fecha_hora_salida = null
    ) {
        $this->id_acceso = $id_acceso;
        $this->id_persona = $id_persona;
        $this->id_condominio = $id_condominio;
        $this->id_casa = $id_casa;
        $this->id_persona_dispositivo = $id_persona_dispositivo;
        $this->fecha_hora_entrada = $fecha_hora_entrada;
        $this->fecha_hora_salida = $fecha_hora_salida;
    }
}
