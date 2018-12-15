<?php
class HosysRozpisEntity extends AbstractEntity
{
    public $hosysRozpisId;
    public $hosysSoutezId;
    public $statusRow;
    public $denTitle;
    public $den;
    public $datumTitle;
    public $datum;
    public $casTitle;
    public $cas;
    public $stadionTitle;
    public $stadion;
    public $soutezTitle;
    public $soutez;
    public $cisloTitle;
    public $cislo;
    public $domaciTitle;
    public $domaci;
    public $domaciZkrTitle;
    public $domaciZkr;
    public $hoste;
    public $hosteTitle;
    public $hosteZkrTitle;
    public $hosteZkr;
    public $status;
    public $zmena;
    public $vlozeno;

    public static function fromRow(array $data, $keyPref = "") {
        $self = new self();

		$self->hosysRozpisId = $self->getValue('hosys_rozpis_id', $data);
		$self->hosysSoutezId = $self->getValue('hosys_soutez_id', $data);
        $self->statusRow = $self->getValue('status_row', $data);
        $self->denTitle = $self->getValue('den_title', $data);
        $self->den = $self->getValue('den', $data);
        $self->datumTitle = $self->getValue('datum_title', $data);
        $self->datum = $self->getValue('datum', $data);
        $self->casTitle = $self->getValue('cas_title', $data);
        $self->cas = $self->getValue('cas', $data);
        $self->stadionTitle = $self->getValue('stadion_title', $data);
        $self->stadion = $self->getValue('stadion', $data);
        $self->soutezTitle = $self->getValue('soutez_title', $data);
        $self->soutez = $self->getValue('soutez', $data);
        $self->cisloTitle = $self->getValue('cislo_title', $data);
        $self->cislo = $self->getValue('cislo', $data);
        $self->domaciTitle = $self->getValue('domaci_title', $data);
        $self->domaci = $self->getValue('domaci', $data);
        $self->domaciZkrTitle = $self->getValue('domaci_zkr_title', $data);
        $self->domaciZkr = $self->getValue('domaci_zkr', $data);
        $self->hoste = $self->getValue('hoste', $data);
        $self->hosteTitle = $self->getValue('hoste_title', $data);
        $self->hosteZkrTitle = $self->getValue('hoste_zkr_title', $data);
        $self->hosteZkr = $self->getValue('hoste_zkr', $data);
        $self->status = $self->getValue('status', $data);
        $self->zmena = $self->getBoolValue('zmena', $data);
        $self->vlozeno = $self->getValue('vlozeno', $data);

        return $self;
    }
}
