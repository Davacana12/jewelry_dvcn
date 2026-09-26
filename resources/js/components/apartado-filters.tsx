import { Link, router, usePage } from '@inertiajs/react';
import { Search, X } from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import { estadoLabels } from '@/components/estado-apartado-badge';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { index } from '@/routes/apartados';
import type { ApartadoFilters as Filters, Product } from '@/types';

/**
 * Radix Select does not allow an empty value, so "all" options use this
 * placeholder, which is stripped from the query string before navigating.
 */
const TODOS = 'todos';

const ordenLabels: Record<string, string> = {
    recientes: 'Más recientes',
    antiguos: 'Más antiguos',
    fecha_limite: 'Vencen primero',
    cliente: 'Cliente (A-Z)',
};

export default function ApartadoFilters({
    filters,
    products,
}: {
    filters: Filters;
    products: Pick<Product, 'idProduct' | 'Name_Product'>[];
}) {
    const { errors } = usePage().props;
    const [processing, setProcessing] = useState(false);
    const hasFilters = Object.values(filters).some((value) => value !== '');

    /**
     * Inertia's <Form> copies GET fields into the URL before its `transform`
     * runs, so empty fields are removed here and the visit is made manually.
     */
    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();

        const query = Object.fromEntries(
            [...new FormData(event.currentTarget)]
                .map(([name, value]) => [
                    name,
                    typeof value === 'string' ? value.trim() : '',
                ])
                .filter(([, value]) => value !== '' && value !== TODOS),
        );

        router.get(index.url(), query, {
            preserveScroll: true,
            onStart: () => setProcessing(true),
            onFinish: () => setProcessing(false),
        });
    }

    return (
        <form
            onSubmit={submit}
            className="grid gap-3 rounded-xl border p-4 sm:grid-cols-2 lg:grid-cols-3"
        >
            <div className="grid gap-1.5">
                <Label htmlFor="buscar">Cliente o teléfono</Label>
                <Input
                    id="buscar"
                    name="buscar"
                    defaultValue={filters.buscar}
                    placeholder="Ej. María o 5512"
                />
                <InputError message={errors.buscar} />
            </div>

            <div className="grid gap-1.5">
                <Label htmlFor="estado">Estado</Label>
                <Select name="estado" defaultValue={filters.estado || TODOS}>
                    <SelectTrigger id="estado" className="w-full">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value={TODOS}>Todos</SelectItem>
                        {Object.entries(estadoLabels).map(([value, label]) => (
                            <SelectItem key={value} value={value}>
                                {label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <InputError message={errors.estado} />
            </div>

            <div className="grid gap-1.5">
                <Label htmlFor="producto_id">Producto</Label>
                <Select
                    name="producto_id"
                    defaultValue={filters.producto_id || TODOS}
                >
                    <SelectTrigger id="producto_id" className="w-full">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value={TODOS}>Todos</SelectItem>
                        {products.map((product) => (
                            <SelectItem
                                key={product.idProduct}
                                value={String(product.idProduct)}
                            >
                                {product.Name_Product}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <InputError message={errors.producto_id} />
            </div>

            <div className="grid gap-1.5">
                <Label htmlFor="desde">Apartado desde</Label>
                <Input
                    id="desde"
                    name="desde"
                    type="date"
                    defaultValue={filters.desde}
                />
                <InputError message={errors.desde} />
            </div>

            <div className="grid gap-1.5">
                <Label htmlFor="hasta">Apartado hasta</Label>
                <Input
                    id="hasta"
                    name="hasta"
                    type="date"
                    defaultValue={filters.hasta}
                />
                <InputError message={errors.hasta} />
            </div>

            <div className="grid gap-1.5">
                <Label htmlFor="orden">Ordenar por</Label>
                <Select
                    name="orden"
                    defaultValue={filters.orden || 'recientes'}
                >
                    <SelectTrigger id="orden" className="w-full">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        {Object.entries(ordenLabels).map(([value, label]) => (
                            <SelectItem key={value} value={value}>
                                {label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <InputError message={errors.orden} />
            </div>

            <div className="flex flex-wrap gap-2 sm:col-span-2 lg:col-span-3">
                <Button type="submit" disabled={processing}>
                    <Search />
                    Filtrar
                </Button>

                {hasFilters && (
                    <Button variant="outline" asChild>
                        <Link href={index()}>
                            <X />
                            Limpiar filtros
                        </Link>
                    </Button>
                )}
            </div>
        </form>
    );
}
