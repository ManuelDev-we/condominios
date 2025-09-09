<?php 

class PendingRegistrations {
    public ?int $id;
    public ?string $registration_data;
    public ?string $verification_token;
    public ?string $token_expiry;
    public ?string $created_at;

    public function __construct(
        ?int $id = null,
        ?string $registration_data = null,
        ?string $verification_token = null,
        ?string $token_expiry = null,
        ?string $created_at = null
    ) {
        $this->id = $id;
        $this->registration_data = $registration_data;
        $this->verification_token = $verification_token;
        $this->token_expiry = $token_expiry;
        $this->created_at = $created_at;
    }
}