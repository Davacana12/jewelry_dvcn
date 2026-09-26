import type { Product } from './product';

export type EstadoApartado =
    | 'activo'
    | 'en_riesgo'
    | 'liquidado'
    | 'vencido'
    | 'cancelado';

export type Abono = {
    id_abono: number;
    id_apartado: number;
    monto: string;
    multa: string;
    fecha_monto: string;
};

export type Apartado = {
    id_apartados: number;
    nombre_cliente: string;
    telefono_cliente: string;
    producto_id: number;
    cantidad: number;
    total_compra: string;
    anticipo: string;
    fecha_apartada: string;
    fecha_limite: string;
    estado: EstadoApartado;
    saldo_pendiente: string;
};

export type ApartadoListItem = Apartado & {
    producto: Pick<Product, 'idProduct' | 'Name_Product'>;
    saldo_pagado: string;
    multas: string;
};

export type ApartadoDetail = Apartado & {
    producto: Product;
    abonos: Abono[];
    acepta_abonos: boolean;
    en_gracia: boolean;
    plazo_vencido: boolean;
    fecha_fin_gracia: string;
    multa_si_abona: string;
};

export type ReglasAbono = {
    diasParaPagar: number;
    diasDeGracia: number;
    porcentajeMulta: number;
};

export type ApartadoFilters = {
    buscar: string;
    estado: EstadoApartado | '';
    producto_id: string;
    desde: string;
    hasta: string;
    orden: string;
};
