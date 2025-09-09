<?php


class AdminCond {
    public ?int $id_admin;
    public ?int $id_condominio;

    public function __construct(
        ?int $id_admin = null,
        ?int $id_condominio = null
    ) {
        $this->id_admin = $id_admin;
        $this->id_condominio = $id_condominio;
    }
}