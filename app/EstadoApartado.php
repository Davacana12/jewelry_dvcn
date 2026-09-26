<?php

namespace App;

enum EstadoApartado: string
{
    case Activo = 'activo';
    case EnRiesgo = 'en_riesgo';
    case Liquidado = 'liquidado';
    case Vencido = 'vencido';
    case Cancelado = 'cancelado';

    /**
     * Determine whether an apartado in this status is still open, meaning it
     * can receive payments or be cancelled.
     */
    public function isVigente(): bool
    {
        return $this === self::Activo || $this === self::EnRiesgo;
    }
}
