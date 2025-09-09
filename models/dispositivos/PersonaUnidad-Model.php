<?php

class PersonasUnidad {
    public ?int $id_persona_unidad;
    public ?string $telefono_1;
    public ?string $telefono_2;
    public ?string $curp;
    public ?string $nombres;
    public ?string $apellido1;
    public ?string $apellido2;
    public ?string $fecha_nacimiento;
    public ?string $foto;
    public ?string $creado_en;

    public function __construct(
        ?int $id_persona_unidad = null,
        ?string $telefono_1 = null,
        ?string $telefono_2 = null,
        ?string $curp = null,
        ?string $nombres = null,
        ?string $apellido1 = null,
        ?string $apellido2 = null,
        ?string $fecha_nacimiento = null,
        ?string $foto = null,
        ?string $creado_en = null
    ) {
        $this->id_persona_unidad = $id_persona_unidad;
        $this->telefono_1 = $telefono_1;
        $this->telefono_2 = $telefono_2;
        $this->curp = $curp;
        $this->nombres = $nombres;
        $this->apellido1 = $apellido1;
        $this->apellido2 = $apellido2;
        $this->fecha_nacimiento = $fecha_nacimiento;
        $this->foto = $foto;
        $this->creado_en = $creado_en;
    }
}