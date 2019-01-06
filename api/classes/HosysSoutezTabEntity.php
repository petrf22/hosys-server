<?php
class HosysSoutezTabEntity extends AbstractEntity
{
    public $hosysSoutezTabId;
    public $hosysSoutezId;
    public $htmlTabulka;
    public $htmlSoutez;
    public $zmeneno;

    public static function fromRow(array $data, $keyPref = "") {
        $self = new self();

        $self->hosysSoutezTabId = $self->getIntValue('hosys_soutez_tab_id', $data);
        $self->hosysSoutezId = $self->getValue('hosys_soutez_id', $data);
        $self->htmlTabulka = $self->getValue('html_tabulka', $data);
        $self->htmlSoutez = $self->getValue('html_soutez', $data);
        $self->zmeneno = $self->getValue('zmeneno', $data);

        return $self;
    }
}
