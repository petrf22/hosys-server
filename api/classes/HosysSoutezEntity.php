<?php
class HosysSoutezEntity extends AbstractEntity
{
    public $hosysSoutezId;
    public $uroven;
    public $nazev;
    public $poradi;

    public static function fromRow(array $data, $keyPref = "") {
        $self = new self();

        $self->hosysSoutezId = $self->getValue('hosys_soutez_id', $data);
        $self->uroven = $self->getIntValue('uroven', $data);
        $self->nazev = $self->getValue('nazev', $data);
        $self->poradi = $self->getIntValue('poradi', $data);

        return $self;
    }
}
