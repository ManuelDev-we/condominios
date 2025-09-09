<?php

class PersonaCasa {
    public ?int $id_persona;
    public ?int $id_casa;

    public function __construct(
        ?int $id_persona = null,
        ?int $id_casa = null
    ) {
        $this->id_persona = $id_persona;
        $this->id_casa = $id_casa;
    }
}
