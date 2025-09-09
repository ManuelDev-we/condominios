<?php
class Cuotas {
    public ?int $id_cuota;
    public ?int $id_condominio;
    public ?int $id_calle;
    public ?string $descripcion;
    public ?string $fecha_generacion;
    public ?string $fecha_vencimiento;
    public ?string $uso_cfdi;
    public ?string $moneda;
    public ?string $uuid_factura;
    public ?string $archivo_xml;
    public ?string $archivo_pdf;
    public ?string $estatus;

    public function __construct(
        ?int $id_cuota = null,
        ?int $id_condominio = null,
        ?int $id_calle = null,
        ?string $descripcion = null,
        ?string $fecha_generacion = null,
        ?string $fecha_vencimiento = null,
        ?string $uso_cfdi = null,
        ?string $moneda = null,
        ?string $uuid_factura = null,
        ?string $archivo_xml = null,
        ?string $archivo_pdf = null,
        ?string $estatus = null
    ) {
        $this->id_cuota = $id_cuota;
        $this->id_condominio = $id_condominio;
        $this->id_calle = $id_calle;
        $this->descripcion = $descripcion;
        $this->fecha_generacion = $fecha_generacion;
        $this->fecha_vencimiento = $fecha_vencimiento;
        $this->uso_cfdi = $uso_cfdi;
        $this->moneda = $moneda;
        $this->uuid_factura = $uuid_factura;
        $this->archivo_xml = $archivo_xml;
        $this->archivo_pdf = $archivo_pdf;
        $this->estatus = $estatus;
    }
}

