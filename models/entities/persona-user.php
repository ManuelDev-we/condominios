<?php

class Personas {
    public ?int $id_persona;
    public ?string $curp;
    public ?string $rfc;
    public ?string $razon_social;
    public ?string $regimen_fiscal;
    public ?string $cp_fiscal;
    public ?string $nombres;
    public ?string $apellido1;
    public ?string $apellido2;
    public ?string $correo_electronico;
    public ?string $contrasena;
    public ?string $fecha_nacimiento;
    public ?string $creado_en;
    public ?string $recovery_token;
    public ?string $recovery_token_expiry;
    public ?int $email_verificado;
    public ?string $email_verification_token;
    public ?string $email_verification_expires;

    public function __construct(
        ?int $id_persona = null,
        ?string $curp = null,
        ?string $rfc = null,
        ?string $razon_social = null,
        ?string $regimen_fiscal = null,
        ?string $cp_fiscal = null,
        ?string $nombres = null,
        ?string $apellido1 = null,
        ?string $apellido2 = null,
        ?string $correo_electronico = null,
        ?string $contrasena = null,
        ?string $fecha_nacimiento = null,
        ?string $creado_en = null,
        ?string $recovery_token = null,
        ?string $recovery_token_expiry = null,
        ?int $email_verificado = null,
        ?string $email_verification_token = null,
        ?string $email_verification_expires = null
    ) {
        $this->id_persona = $id_persona;
        $this->curp = $curp;
        $this->rfc = $rfc;
        $this->razon_social = $razon_social;
        $this->regimen_fiscal = $regimen_fiscal;
        $this->cp_fiscal = $cp_fiscal;
        $this->nombres = $nombres;
        $this->apellido1 = $apellido1;
        $this->apellido2 = $apellido2;
        $this->correo_electronico = $correo_electronico;
        $this->contrasena = $contrasena;
        $this->fecha_nacimiento = $fecha_nacimiento;
        $this->creado_en = $creado_en;
        $this->recovery_token = $recovery_token;
        $this->recovery_token_expiry = $recovery_token_expiry;
        $this->email_verificado = $email_verificado;
        $this->email_verification_token = $email_verification_token;
        $this->email_verification_expires = $email_verification_expires;
    }
}
