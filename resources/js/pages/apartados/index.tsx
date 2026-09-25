import { Form, Head, Link } from '@inertiajs/react';
import { Plus, Search } from 'lucide-react';
import ApartadoController from '@/actions/App/Http/Controllers/ApartadoController';
import EstadoApartadoBadge, {
    estadoLabels,
} from '@/components/estado-apartado-badge';
import Heading from '@/components/heading';
import Pagination from '@/components/pagination';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { formatDate, formatMoney } from '@/lib/format';
import { create, index, show } from '@/routes/apartados';
import type { ApartadoListItem, EstadoApartado, Paginated } from '@/types';

const TODOS = 'todos';

export default function Apartados({
    apartados,
    filters,
}: {
    apartados: Paginated<ApartadoListItem>;
    filters: { buscar: string; estado: EstadoApartado | '' };
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

                <Form
                    {...ApartadoController.index.form()}
                    transform={(data) => ({
                        buscar: data.buscar || undefined,
                        estado:
                            data.estado === TODOS
                                ? undefined
                                : data.estado || undefined,
                    })}
                    className="flex flex-wrap gap-2"
                >
                    <Input
                        name="buscar"
                        defaultValue={filters.buscar}
                        placeholder="Buscar por cliente"
                        aria-label="Buscar por cliente"
                        className="w-full sm:w-64"
                    />

                    <Select
                        name="estado"
                        defaultValue={filters.estado || TODOS}
                    >
                        <SelectTrigger
                            className="w-full sm:w-44"
                            aria-label="Estado"
                        >
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={TODOS}>
                                Todos los estados
                            </SelectItem>
                            {Object.entries(estadoLabels).map(
                                ([value, label]) => (
                                    <SelectItem key={value} value={value}>
                                        {label}
                                    </SelectItem>
                                ),
                            )}
                        </SelectContent>
                    </Select>

                    <Button type="submit" variant="outline">
                        <Search />
                        Buscar
                    </Button>
                </Form>

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
                                        Total
                                    </th>
                                    <th className="px-4 py-3 text-right font-medium">
                                        Saldo
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Fecha límite
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Estado
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
