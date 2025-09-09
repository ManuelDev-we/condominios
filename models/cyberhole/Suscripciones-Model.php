<?php

class Suscripciones {
    public ?int $id_suscripcion;
    public ?int $id_admin;
    public ?int $id_condominio;
    public ?int $id_plan;
    public ?int $cantidad;
    public ?string $fecha_inicio;
    public ?string $fecha_fin;
    public ?int $es_mensual;
    public ?string $rfc_receptor;
    public ?string $razon_social_receptor;
    public ?string $regimen_fiscal_receptor;
    public ?string $cp_fiscal_receptor;
    public ?string $uso_cfdi;
    public ?string $forma_pago;
    public ?string $metodo_pago;
    public ?string $moneda;
    public ?string $uuid_factura;
    public ?string $archivo_xml;
    public ?string $archivo_pdf;
    public ?string $estatus;

    public function __construct(
        ?int $id_suscripcion = null,
        ?int $id_admin = null,
        ?int $id_condominio = null,
        ?int $id_plan = null,
        ?int $cantidad = null,
        ?string $fecha_inicio = null,
        ?string $fecha_fin = null,
        ?int $es_mensual = null,
        ?string $rfc_receptor = null,
        ?string $razon_social_receptor = null,
        ?string $regimen_fiscal_receptor = null,
        ?string $cp_fiscal_receptor = null,
        ?string $uso_cfdi = null,
        ?string $forma_pago = null,
        ?string $metodo_pago = null,
        ?string $moneda = null,
        ?string $uuid_factura = null,
        ?string $archivo_xml = null,
        ?string $archivo_pdf = null,
        ?string $estatus = null
    ) {
        $this->id_suscripcion = $id_suscripcion;
        $this->id_admin = $id_admin;
        $this->id_condominio = $id_condominio;
        $this->id_plan = $id_plan;
        $this->cantidad = $cantidad;
        $this->fecha_inicio = $fecha_inicio;
        $this->fecha_fin = $fecha_fin;
        $this->es_mensual = $es_mensual;
        $this->rfc_receptor = $rfc_receptor;
        $this->razon_social_receptor = $razon_social_receptor;
        $this->regimen_fiscal_receptor = $regimen_fiscal_receptor;
        $this->cp_fiscal_receptor = $cp_fiscal_receptor;
        $this->uso_cfdi = $uso_cfdi;
        $this->forma_pago = $forma_pago;
        $this->metodo_pago = $metodo_pago;
        $this->moneda = $moneda;
        $this->uuid_factura = $uuid_factura;
        $this->archivo_xml = $archivo_xml;
        $this->archivo_pdf = $archivo_pdf;
        $this->estatus = $estatus;
    }
}





