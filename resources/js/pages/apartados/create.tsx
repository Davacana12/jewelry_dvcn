import { Form, Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import ApartadoController from '@/actions/App/Http/Controllers/ApartadoController';
import Heading from '@/components/heading';
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
import { formatMoney } from '@/lib/format';
import { create, index } from '@/routes/apartados';
import { create as createProduct } from '@/routes/products';
import type { Product } from '@/types';

export default function CreateApartado({
    products,
    diasParaPagar,
    porcentajeAnticipoMinimo,
}: {
    products: Product[];
    diasParaPagar: number;
    porcentajeAnticipoMinimo: number;
}) {
    const [productoId, setProductoId] = useState('');
    const [cantidad, setCantidad] = useState('1');

    const producto = products.find(
        (product) => String(product.idProduct) === productoId,
    );
    const piezas = Number.parseInt(cantidad, 10);
    const totalCents =
        producto && piezas > 0 ? Math.round(producto.Cost * 100) * piezas : 0;
    const anticipoMinimoCents = Math.ceil(
        (totalCents * porcentajeAnticipoMinimo) / 100,
    );

    return (
        <>
            <Head title="Nuevo apartado" />

            <div className="max-w-xl p-4">
                <Heading
                    title="Nuevo apartado"
                    description={`El cliente tendrá ${diasParaPagar} días para liquidar el total.`}
                />

                {products.length === 0 ? (
                    <div className="space-y-4 rounded-xl border border-dashed p-6 text-sm text-muted-foreground">
                        <p>No hay productos con stock disponible.</p>
                        <Button variant="outline" asChild>
                            <Link href={createProduct()}>
                                Registrar producto
                            </Link>
                        </Button>
                    </div>
                ) : (
                    <Form
                        {...ApartadoController.store.form()}
                        noValidate
                        className="space-y-6"
                    >
                        {({ processing, errors }) => (
                            <>
                                <div className="grid gap-2">
                                    <Label htmlFor="nombre_cliente">
                                        Nombre del cliente
                                    </Label>
                                    <Input
                                        id="nombre_cliente"
                                        name="nombre_cliente"
                                        autoComplete="off"
                                        aria-invalid={
                                            errors.nombre_cliente
                                                ? true
                                                : undefined
                                        }
                                    />
                                    <InputError
                                        message={errors.nombre_cliente}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="telefono_cliente">
                                        Teléfono
                                    </Label>
                                    <Input
                                        id="telefono_cliente"
                                        name="telefono_cliente"
                                        type="tel"
                                        autoComplete="off"
                                        placeholder="5512345678"
                                        aria-invalid={
                                            errors.telefono_cliente
                                                ? true
                                                : undefined
                                        }
                                    />
                                    <InputError
                                        message={errors.telefono_cliente}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="producto_id">
                                        Producto
                                    </Label>
                                    <Select
                                        name="producto_id"
                                        value={productoId}
                                        onValueChange={setProductoId}
                                    >
                                        <SelectTrigger
                                            id="producto_id"
                                            className="w-full"
                                            aria-invalid={
                                                errors.producto_id
                                                    ? true
                                                    : undefined
                                            }
                                        >
                                            <SelectValue placeholder="Selecciona un producto" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {products.map((product) => (
                                                <SelectItem
                                                    key={product.idProduct}
                                                    value={String(
                                                        product.idProduct,
                                                    )}
                                                >
                                                    {product.Name_Product} ·{' '}
                                                    {formatMoney(product.Cost)}{' '}
                                                    · {product.Stock} disp.
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.producto_id} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="cantidad">Cantidad</Label>
                                    <Input
                                        id="cantidad"
                                        name="cantidad"
                                        type="number"
                                        step="1"
                                        inputMode="numeric"
                                        value={cantidad}
                                        onChange={(event) =>
                                            setCantidad(event.target.value)
                                        }
                                        aria-invalid={
                                            errors.cantidad ? true : undefined
                                        }
                                    />
                                    <InputError message={errors.cantidad} />
                                </div>

                                <div className="grid gap-1 rounded-lg bg-muted/50 p-4 text-sm">
                                    <div className="flex justify-between">
                                        <span className="text-muted-foreground">
                                            Total de la compra
                                        </span>
                                        <span className="font-medium tabular-nums">
                                            {formatMoney(totalCents / 100)}
                                        </span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-muted-foreground">
                                            Anticipo mínimo (
                                            {porcentajeAnticipoMinimo}%)
                                        </span>
                                        <span className="font-medium tabular-nums">
                                            {formatMoney(
                                                anticipoMinimoCents / 100,
                                            )}
                                        </span>
                                    </div>
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="anticipo">Anticipo</Label>
                                    <Input
                                        id="anticipo"
                                        name="anticipo"
                                        type="number"
                                        step="any"
                                        inputMode="decimal"
                                        placeholder="0.00"
                                        aria-invalid={
                                            errors.anticipo ? true : undefined
                                        }
                                    />
                                    <InputError message={errors.anticipo} />
                                </div>

                                <div className="flex items-center gap-4">
                                    <Button disabled={processing}>
                                        Registrar apartado
                                    </Button>

                                    <Button variant="outline" asChild>
                                        <Link href={index()}>Cancelar</Link>
                                    </Button>
                                </div>
                            </>
                        )}
                    </Form>
                )}
            </div>
        </>
    );
}

CreateApartado.layout = {
    breadcrumbs: [
        {
            title: 'Apartados',
            href: index(),
        },
        {
            title: 'Nuevo',
            href: create(),
        },
    ],
};
