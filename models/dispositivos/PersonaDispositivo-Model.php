<?php

class PersonaDispositivo {
    public ?int $id_persona_dispositivo;
    public ?int $id_persona_unidad;
    public ?int $id_dispositivo;
    public ?string $creado_en;

    public function __construct(
        ?int $id_persona_dispositivo = null,
        ?int $id_persona_unidad = null,
        ?int $id_dispositivo = null,
        ?string $creado_en = null
    ) {
        $this->id_persona_dispositivo = $id_persona_dispositivo;
        $this->id_persona_unidad = $id_persona_unidad;
        $this->id_dispositivo = $id_dispositivo;
        $this->creado_en = $creado_en;
    }
}
