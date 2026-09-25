import type { Product } from './product';

export type EstadoApartado = 'activo' | 'liquidado' | 'vencido' | 'cancelado';

export type Abono = {
    id_abono: number;
    id_apartado: number;
    monto: string;
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
};

export type ApartadoDetail = Apartado & {
    producto: Product;
    abonos: Abono[];
    vencido_por_fecha: boolean;
};
