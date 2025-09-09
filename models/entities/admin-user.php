<?php

class Admin {
    public ?int $id_admin;
    public ?string $nombres;
    public ?string $apellido1;
    public ?string $apellido2;
    public ?string $rfc;
    public ?string $razon_social;
    public ?string $regimen_fiscal;
    public ?string $cp_fiscal;
    public ?string $correo;
    public ?string $contrasena;
    public ?string $fecha_alta;
    public ?string $recovery_token;
    public ?string $recovery_token_expiry;
    public ?int $email_verificado;
    public ?string $email_verification_token;
    public ?string $email_verification_expires;

    public function __construct(
        ?int $id_admin = null,
        ?string $nombres = null,
        ?string $apellido1 = null,
        ?string $apellido2 = null,
        ?string $rfc = null,
        ?string $razon_social = null,
        ?string $regimen_fiscal = null,
        ?string $cp_fiscal = null,
        ?string $correo = null,
        ?string $contrasena = null,
        ?string $fecha_alta = null,
        ?string $recovery_token = null,
        ?string $recovery_token_expiry = null,
        ?int $email_verificado = null,
        ?string $email_verification_token = null,
        ?string $email_verification_expires = null
    ) {
        $this->id_admin = $id_admin;
        $this->nombres = $nombres;
        $this->apellido1 = $apellido1;
        $this->apellido2 = $apellido2;
        $this->rfc = $rfc;
        $this->razon_social = $razon_social;
        $this->regimen_fiscal = $regimen_fiscal;
        $this->cp_fiscal = $cp_fiscal;
        $this->correo = $correo;
        $this->contrasena = $contrasena;
        $this->fecha_alta = $fecha_alta;
        $this->recovery_token = $recovery_token;
        $this->recovery_token_expiry = $recovery_token_expiry;
        $this->email_verificado = $email_verificado;
        $this->email_verification_token = $email_verification_token;
        $this->email_verification_expires = $email_verification_expires;
    }

    function Mail_Exists_Encrypted(): bool {
        // Verificar si el correo existe en la base de datos
        $query = "SELECT COUNT(*) FROM admins WHERE correo = :correo";
        $stmt = Database::prepare($query);
        $stmt->bindValue(':correo', $this->correo);
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    }

}