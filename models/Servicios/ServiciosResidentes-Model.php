<?php

class ServiciosResidentes {
    public ?int $id_servicio_residente;
    public ?int $id_condominio;
    public ?int $id_calle;
    public ?int $id_casa;
    public ?int $id_persona;
    public ?string $rfc;
    public ?int $id_servicio_condominio;
    public ?string $descripcion;
    public ?string $fecha_servicio;
    public ?string $hora_servicio;
    public ?int $es_recurrente;
    public ?string $firma_token;
    public ?int $pagado;
    public ?string $foto1;
    public ?string $foto2;
    public ?string $foto3;
    public ?string $foto4;
    public ?string $foto5;
    public ?string $foto_entrega;

    public function __construct(
        ?int $id_servicio_residente = null,
        ?int $id_condominio = null,
        ?int $id_calle = null,
        ?int $id_casa = null,
        ?int $id_persona = null,
        ?string $rfc = null,
        ?int $id_servicio_condominio = null,
        ?string $descripcion = null,
        ?string $fecha_servicio = null,
        ?string $hora_servicio = null,
        ?int $es_recurrente = null,
        ?string $firma_token = null,
        ?int $pagado = null,
        ?string $foto1 = null,
        ?string $foto2 = null,
        ?string $foto3 = null,
        ?string $foto4 = null,
        ?string $foto5 = null,
        ?string $foto_entrega = null
    ) {
        $this->id_servicio_residente = $id_servicio_residente;
        $this->id_condominio = $id_condominio;
        $this->id_calle = $id_calle;
        $this->id_casa = $id_casa;
        $this->id_persona = $id_persona;
        $this->rfc = $rfc;
        $this->id_servicio_condominio = $id_servicio_condominio;
        $this->descripcion = $descripcion;
        $this->fecha_servicio = $fecha_servicio;
        $this->hora_servicio = $hora_servicio;
        $this->es_recurrente = $es_recurrente;
        $this->firma_token = $firma_token;
        $this->pagado = $pagado;
        $this->foto1 = $foto1;
        $this->foto2 = $foto2;
        $this->foto3 = $foto3;
        $this->foto4 = $foto4;
        $this->foto5 = $foto5;
        $this->foto_entrega = $foto_entrega;
    }
}