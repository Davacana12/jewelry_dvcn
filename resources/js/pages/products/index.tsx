import { Head, Link } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import ProductController from '@/actions/App/Http/Controllers/ProductController';
import Heading from '@/components/heading';
import Pagination from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { formatMoney } from '@/lib/format';
import { create, edit, index } from '@/routes/products';
import type { Paginated, Product } from '@/types';

function DeleteProduct({ product }: { product: Product }) {
    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button variant="ghost" size="icon" aria-label="Eliminar">
                    <Trash2 className="text-destructive" />
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogTitle>¿Eliminar este producto?</DialogTitle>
                <DialogDescription>
                    Se eliminará «{product.Name_Product}» de forma permanente.
                </DialogDescription>

                <DialogFooter className="gap-2">
                    <DialogClose asChild>
                        <Button variant="secondary">Cancelar</Button>
                    </DialogClose>

                    <Button variant="destructive" asChild>
                        <Link
                            href={ProductController.destroy(product)}
                            as="button"
                            preserveScroll
                        >
                            Eliminar
                        </Link>
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

export default function Products({
    products,
}: {
    products: Paginated<Product>;
}) {
    return (
        <>
            <Head title="Productos" />

            <div className="flex flex-col gap-4 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Productos"
                        description={`${products.total} productos en el inventario`}
                    />

                    <Button asChild>
                        <Link href={create()}>
                            <Plus />
                            Nuevo producto
                        </Link>
                    </Button>
                </div>

                {products.data.length === 0 ? (
                    <div className="rounded-xl border border-dashed p-10 text-center text-sm text-muted-foreground">
                        Aún no hay productos registrados.
                    </div>
                ) : (
                    <div className="overflow-x-auto rounded-xl border">
                        <table className="w-full text-sm">
                            <thead className="bg-muted/50 text-left text-muted-foreground">
                                <tr>
                                    <th className="px-4 py-3 font-medium">
                                        Nombre
                                    </th>
                                    <th className="px-4 py-3 text-right font-medium">
                                        Costo
                                    </th>
                                    <th className="px-4 py-3 text-right font-medium">
                                        Stock
                                    </th>
                                    <th className="px-4 py-3">
                                        <span className="sr-only">
                                            Acciones
                                        </span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {products.data.map((product) => (
                                    <tr
                                        key={product.idProduct}
                                        className="border-t"
                                    >
                                        <td className="px-4 py-2">
                                            {product.Name_Product}
                                        </td>
                                        <td className="px-4 py-2 text-right tabular-nums">
                                            {formatMoney(product.Cost)}
                                        </td>
                                        <td className="px-4 py-2 text-right tabular-nums">
                                            {product.Stock === 0 ? (
                                                <Badge variant="destructive">
                                                    Agotado
                                                </Badge>
                                            ) : (
                                                product.Stock
                                            )}
                                        </td>
                                        <td className="px-4 py-2">
                                            <div className="flex justify-end">
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    aria-label="Editar"
                                                    asChild
                                                >
                                                    <Link href={edit(product)}>
                                                        <Pencil />
                                                    </Link>
                                                </Button>
                                                <DeleteProduct
                                                    product={product}
                                                />
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}

                <Pagination links={products.links} />
            </div>
        </>
    );
}

Products.layout = {
    breadcrumbs: [
        {
            title: 'Productos',
            href: index(),
        },
    ],
};
