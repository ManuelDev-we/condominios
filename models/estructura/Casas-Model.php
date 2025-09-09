<?php
class Casas {
    public ?int $id_casa;
    public ?string $casa;
    public ?int $id_condominio;
    public ?int $id_calle;

    public function __construct(
        ?int $id_casa = null,
        ?string $casa = null,
        ?int $id_condominio = null,
        ?int $id_calle = null
    ) {
        $this->id_casa = $id_casa;
        $this->casa = $casa;
        $this->id_condominio = $id_condominio;
        $this->id_calle = $id_calle;
    }
}