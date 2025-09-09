<?php

class CobrosAutorizados {
    public ?int $id_cobro_autorizado;
    public ?int $id_persona;
    public ?int $id_casa;
    public ?string $concepto;
    public ?int $es_recurrente;
    public ?string $fecha;
    public ?int $activo;
    public ?string $token_pago;
    public ?string $creado_en;
    public ?int $id_cuota;
    public ?string $rfc;
    public ?string $metodo_pago;

    public function __construct(
        ?int $id_cobro_autorizado = null,
        ?int $id_persona = null,
        ?int $id_casa = null,
        ?string $concepto = null,
        ?int $es_recurrente = null,
        ?string $fecha = null,
        ?int $activo = null,
        ?string $token_pago = null,
        ?string $creado_en = null,
        ?int $id_cuota = null,
        ?string $rfc = null,
        ?string $metodo_pago = null
    ) {
        $this->id_cobro_autorizado = $id_cobro_autorizado;
        $this->id_persona = $id_persona;
        $this->id_casa = $id_casa;
        $this->concepto = $concepto;
        $this->es_recurrente = $es_recurrente;
        $this->fecha = $fecha;
        $this->activo = $activo;
        $this->token_pago = $token_pago;
        $this->creado_en = $creado_en;
        $this->id_cuota = $id_cuota;
        $this->rfc = $rfc;
        $this->metodo_pago = $metodo_pago;
    }
}

