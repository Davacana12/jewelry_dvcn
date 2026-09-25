import { Form, Head, Link } from '@inertiajs/react';
import ProductController from '@/actions/App/Http/Controllers/ProductController';
import Heading from '@/components/heading';
import ProductFormFields from '@/components/product-form-fields';
import { Button } from '@/components/ui/button';
import { create, index } from '@/routes/products';

export default function CreateProduct() {
    return (
        <>
            <Head title="Nuevo producto" />

            <div className="max-w-xl p-4">
                <Heading
                    title="Nuevo producto"
                    description="Registra un producto en el inventario"
                />

                <Form
                    {...ProductController.store.form()}
                    noValidate
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <ProductFormFields errors={errors} />

                            <div className="flex items-center gap-4">
                                <Button disabled={processing}>Guardar</Button>

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

CreateProduct.layout = {
    breadcrumbs: [
        {
            title: 'Productos',
            href: index(),
        },
        {
            title: 'Nuevo',
            href: create(),
        },
    ],
};
