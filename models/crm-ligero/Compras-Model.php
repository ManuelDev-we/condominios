<?php


class Compras {
    public ?int $id_compra;
    public ?int $id_condominio;
    public ?int $id_inventario;
    public ?int $id_servicio;
    public ?int $cantidad;
    public ?string $fecha_compra;
    public ?string $descripcion;
    public ?int $id_emisor;
    public ?string $rfc_emisor;
    public ?string $razon_social_emisor;
    public ?string $regimen_fiscal_emisor;
    public ?string $cp_fiscal_emisor;
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
        ?int $id_compra = null,
        ?int $id_condominio = null,
        ?int $id_inventario = null,
        ?int $id_servicio = null,
        ?int $cantidad = null,
        ?string $fecha_compra = null,
        ?string $descripcion = null,
        ?int $id_emisor = null,
        ?string $rfc_emisor = null,
        ?string $razon_social_emisor = null,
        ?string $regimen_fiscal_emisor = null,
        ?string $cp_fiscal_emisor = null,
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
        $this->id_compra = $id_compra;
        $this->id_condominio = $id_condominio;
        $this->id_inventario = $id_inventario;
        $this->id_servicio = $id_servicio;
        $this->cantidad = $cantidad;
        $this->fecha_compra = $fecha_compra;
        $this->descripcion = $descripcion;
        $this->id_emisor = $id_emisor;
        $this->rfc_emisor = $rfc_emisor;
        $this->razon_social_emisor = $razon_social_emisor;
        $this->regimen_fiscal_emisor = $regimen_fiscal_emisor;
        $this->cp_fiscal_emisor = $cp_fiscal_emisor;
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
