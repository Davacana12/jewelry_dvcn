import { Form, Head, Link } from '@inertiajs/react';
import ProductController from '@/actions/App/Http/Controllers/ProductController';
import Heading from '@/components/heading';
import ProductFormFields from '@/components/product-form-fields';
import { Button } from '@/components/ui/button';
import { index } from '@/routes/products';
import type { Product } from '@/types';

export default function EditProduct({ product }: { product: Product }) {
    return (
        <>
            <Head title={`Editar ${product.Name_Product}`} />

            <div className="max-w-xl p-4">
                <Heading
                    title="Editar producto"
                    description={product.Name_Product}
                />

                <Form
                    {...ProductController.update.form(product)}
                    noValidate
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <ProductFormFields
                                product={product}
                                errors={errors}
                            />

                            <div className="flex items-center gap-4">
                                <Button disabled={processing}>
                                    Guardar cambios
                                </Button>

                                <Button variant="outline" asChild>
                                    <Link href={index()}>Cancelar</Link>
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

EditProduct.layout = {
    breadcrumbs: [
        {
            title: 'Productos',
            href: index(),
        },
        {
            title: 'Editar',
            href: index(),
        },
    ],
};
