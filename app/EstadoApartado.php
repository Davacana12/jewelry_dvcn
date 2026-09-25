<?php

namespace App;

enum EstadoApartado: string
{
    case Activo = 'activo';
    case Liquidado = 'liquidado';
    case Vencido = 'vencido';
    case Cancelado = 'cancelado';
}
