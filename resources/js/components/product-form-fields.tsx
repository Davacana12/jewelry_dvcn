import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Product } from '@/types';

export default function ProductFormFields({
    product,
    errors,
}: {
    product?: Product;
    errors: Partial<Record<string, string>>;
}) {
    return (
        <>
            <div className="grid gap-2">
                <Label htmlFor="Name_Product">Nombre del producto</Label>

                <Input
                    id="Name_Product"
                    name="Name_Product"
                    defaultValue={product?.Name_Product}
                    placeholder="Ej. Anillo de plata"
                    aria-invalid={errors.Name_Product ? true : undefined}
                />

                <InputError message={errors.Name_Product} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="Cost">Costo</Label>

                <Input
                    id="Cost"
                    name="Cost"
                    type="number"
                    step="any"
                    inputMode="decimal"
                    defaultValue={product?.Cost}
                    placeholder="0.00"
                    aria-invalid={errors.Cost ? true : undefined}
                />

                <InputError message={errors.Cost} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="Stock">Stock</Label>

                <Input
                    id="Stock"
                    name="Stock"
                    type="number"
                    step="1"
                    inputMode="numeric"
                    defaultValue={product?.Stock}
                    placeholder="0"
                    aria-invalid={errors.Stock ? true : undefined}
                />

                <InputError message={errors.Stock} />
            </div>
        </>
    );
}
