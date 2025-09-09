<?php

class Blog {
    public ?int $id_blog;
    public ?string $titulo;
    public ?string $contenido;
    public ?string $imagen;
    public ?int $creado_por_admin;
    public ?int $id_condominio;
    public ?string $fecha_creacion;

    public function __construct(
        ?int $id_blog = null,
        ?string $titulo = null,
        ?string $contenido = null,
        ?string $imagen = null,
        ?int $creado_por_admin = null,
        ?int $id_condominio = null,
        ?string $fecha_creacion = null
    ) {
        $this->id_blog = $id_blog;
        $this->titulo = $titulo;
        $this->contenido = $contenido;
        $this->imagen = $imagen;
        $this->creado_por_admin = $creado_por_admin;
        $this->id_condominio = $id_condominio;
        $this->fecha_creacion = $fecha_creacion;
    }
}