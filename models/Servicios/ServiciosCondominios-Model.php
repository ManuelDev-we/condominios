<?php 

class ServiciosCondominio {
    public ?int $id_servicio_condominio;
    public ?int $id_condominio;
    public ?string $descripcion;
    public ?string $foto1;
    public ?string $foto2;
    public ?string $foto3;
    public ?string $foto4;
    public ?string $foto5;
    public ?string $lunes_apertura;
    public ?string $lunes_cierre;
    public ?string $martes_apertura;
    public ?string $martes_cierre;
    public ?string $miercoles_apertura;
    public ?string $miercoles_cierre;
    public ?string $jueves_apertura;
    public ?string $jueves_cierre;
    public ?string $viernes_apertura;
    public ?string $viernes_cierre;
    public ?string $sabado_apertura;
    public ?string $sabado_cierre;
    public ?string $domingo_apertura;
    public ?string $domingo_cierre;

    public function __construct(
        ?int $id_servicio_condominio = null,
        ?int $id_condominio = null,
        ?string $descripcion = null,
        ?string $foto1 = null,
        ?string $foto2 = null,
        ?string $foto3 = null,
        ?string $foto4 = null,
        ?string $foto5 = null,
        ?string $lunes_apertura = null,
        ?string $lunes_cierre = null,
        ?string $martes_apertura = null,
        ?string $martes_cierre = null,
        ?string $miercoles_apertura = null,
        ?string $miercoles_cierre = null,
        ?string $jueves_apertura = null,
        ?string $jueves_cierre = null,
        ?string $viernes_apertura = null,
        ?string $viernes_cierre = null,
        ?string $sabado_apertura = null,
        ?string $sabado_cierre = null,
        ?string $domingo_apertura = null,
        ?string $domingo_cierre = null
    ) {
        $this->id_servicio_condominio = $id_servicio_condominio;
        $this->id_condominio = $id_condominio;
        $this->descripcion = $descripcion;
        $this->foto1 = $foto1;
        $this->foto2 = $foto2;
        $this->foto3 = $foto3;
        $this->foto4 = $foto4;
        $this->foto5 = $foto5;
        $this->lunes_apertura = $lunes_apertura;
        $this->lunes_cierre = $lunes_cierre;
        $this->martes_apertura = $martes_apertura;
        $this->martes_cierre = $martes_cierre;
        $this->miercoles_apertura = $miercoles_apertura;
        $this->miercoles_cierre = $miercoles_cierre;
        $this->jueves_apertura = $jueves_apertura;
        $this->jueves_cierre = $jueves_cierre;
        $this->viernes_apertura = $viernes_apertura;
        $this->viernes_cierre = $viernes_cierre;
        $this->sabado_apertura = $sabado_apertura;
        $this->sabado_cierre = $sabado_cierre;
        $this->domingo_apertura = $domingo_apertura;
        $this->domingo_cierre = $domingo_cierre;
    }
}



