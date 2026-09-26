import { Head, Link } from '@inertiajs/react';
import { HandCoins, Plus } from 'lucide-react';
import ApartadoFilters from '@/components/apartado-filters';
import EstadoApartadoBadge from '@/components/estado-apartado-badge';
import Heading from '@/components/heading';
import Pagination from '@/components/pagination';
import { Button } from '@/components/ui/button';
import { formatDate, formatMoney } from '@/lib/format';
import { create, index, show } from '@/routes/apartados';
import type {
    ApartadoFilters as Filters,
    ApartadoListItem,
    Paginated,
    Product,
} from '@/types';

export default function Apartados({
    apartados,
    filters,
    products,
}: {
    apartados: Paginated<ApartadoListItem>;
    filters: Filters;
    products: Pick<Product, 'idProduct' | 'Name_Product'>[];
}) {
    return (
        <>
            <Head title="Apartados" />

            <div className="flex flex-col gap-4 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Apartados"
                        description={`${apartados.total} apartados registrados`}
                    />

                    <Button asChild>
                        <Link href={create()}>
                            <Plus />
                            Nuevo apartado
                        </Link>
                    </Button>
                </div>

                <ApartadoFilters filters={filters} products={products} />

                {apartados.data.length === 0 ? (
                    <div className="rounded-xl border border-dashed p-10 text-center text-sm text-muted-foreground">
                        No hay apartados que coincidan.
                    </div>
                ) : (
                    <div className="overflow-x-auto rounded-xl border">
                        <table className="w-full text-sm">
                            <thead className="bg-muted/50 text-left text-muted-foreground">
                                <tr>
                                    <th className="px-4 py-3 font-medium">
                                        Cliente
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Producto
                                    </th>
                                    <th className="px-4 py-3 text-right font-medium">
                                        Total de compra
                                    </th>
                                    <th className="px-4 py-3 text-right font-medium">
                                        Saldo ya pagado
                                    </th>
                                    <th className="px-4 py-3 text-right font-medium">
                                        Saldo pendiente
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Fecha límite
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Estado
                                    </th>
                                    <th className="px-4 py-3">
                                        <span className="sr-only">
                                            Acciones
                                        </span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {apartados.data.map((apartado) => (
                                    <tr
                                        key={apartado.id_apartados}
                                        className="border-t hover:bg-muted/30"
                                    >
                                        <td className="px-4 py-2">
                                            <Link
                                                href={show(apartado)}
                                                className="font-medium hover:underline"
                                            >
                                                {apartado.nombre_cliente}
                                            </Link>
                                            <div className="text-xs text-muted-foreground">
                                                {apartado.telefono_cliente}
                                            </div>
                                        </td>
                                        <td className="px-4 py-2">
                                            {apartado.producto.Name_Product}
                                            <span className="text-muted-foreground">
                                                {' '}
                                                × {apartado.cantidad}
                                            </span>
                                        </td>
                                        <td className="px-4 py-2 text-right tabular-nums">
                                            {formatMoney(apartado.total_compra)}
                                            {Number(apartado.multas) > 0 && (
                                                <div className="text-xs text-red-600 dark:text-red-400">
                                                    +{' '}
                                                    {formatMoney(
                                                        apartado.multas,
                                                    )}{' '}
                                                    en multas
                                                </div>
                                            )}
                                        </td>
                                        <td className="px-4 py-2 text-right tabular-nums">
                                            {formatMoney(apartado.saldo_pagado)}
                                        </td>
                                        <td className="px-4 py-2 text-right tabular-nums">
                                            {formatMoney(
                                                apartado.saldo_pendiente,
                                            )}
                                        </td>
                                        <td className="px-4 py-2 whitespace-nowrap">
                                            {formatDate(apartado.fecha_limite)}
                                        </td>
                                        <td className="px-4 py-2">
                                            <EstadoApartadoBadge
                                                estado={apartado.estado}
                                            />
                                        </td>
                                        <td className="px-4 py-2 text-right">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                asChild
                                            >
                                                <Link href={show(apartado)}>
                                                    <HandCoins />
                                                    {apartado.estado ===
                                                        'activo' ||
                                                    apartado.estado ===
                                                        'en_riesgo'
                                                        ? 'Abonar'
                                                        : 'Ver abonos'}
                                                </Link>
                                            </Button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}

                <Pagination links={apartados.links} />
            </div>
        </>
    );
}

Apartados.layout = {
    breadcrumbs: [
        {
            title: 'Apartados',
            href: index(),
        },
    ],
};
