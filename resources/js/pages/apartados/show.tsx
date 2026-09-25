import { Form, Head, Link } from '@inertiajs/react';
import AbonoController from '@/actions/App/Http/Controllers/AbonoController';
import ApartadoController from '@/actions/App/Http/Controllers/ApartadoController';
import EstadoApartadoBadge from '@/components/estado-apartado-badge';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatDate, formatDateTime, formatMoney } from '@/lib/format';
import { index } from '@/routes/apartados';
import type { ApartadoDetail } from '@/types';

function CancelApartado({ apartado }: { apartado: ApartadoDetail }) {
    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button variant="destructive">Cancelar apartado</Button>
            </DialogTrigger>
            <DialogContent>
                <DialogTitle>¿Cancelar este apartado?</DialogTitle>
                <DialogDescription>
                    {apartado.cantidad} pieza(s) de «
                    {apartado.producto.Name_Product}» volverán al stock. Esta
                    acción no se puede deshacer.
                </DialogDescription>

                <DialogFooter className="gap-2">
                    <DialogClose asChild>
                        <Button variant="secondary">Volver</Button>
                    </DialogClose>

                    <Button variant="destructive" asChild>
                        <Link
                            href={ApartadoController.cancel(apartado)}
                            as="button"
                        >
                            Sí, cancelar
                        </Link>
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

function Summary({ label, value }: { label: string; value: string }) {
    return (
        <div className="rounded-xl border p-4">
            <div className="text-sm text-muted-foreground">{label}</div>
            <div className="mt-1 text-xl font-semibold tabular-nums">
                {value}
            </div>
        </div>
    );
}

export default function ShowApartado({
    apartado,
}: {
    apartado: ApartadoDetail;
}) {
    const abonado =
        Number(apartado.total_compra) - Number(apartado.saldo_pendiente);
    const aceptaAbonos =
        apartado.estado === 'activo' && !apartado.vencido_por_fecha;

    return (
        <>
            <Head title={`Apartado de ${apartado.nombre_cliente}`} />

            <div className="flex max-w-4xl flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <div className="flex items-center gap-3">
                            <Heading
                                title={apartado.nombre_cliente}
                                description={`Tel. ${apartado.telefono_cliente}`}
                            />
                            <EstadoApartadoBadge estado={apartado.estado} />
                        </div>
                        <p className="-mt-6 text-sm text-muted-foreground">
                            {apartado.producto.Name_Product} ×{' '}
                            {apartado.cantidad} · Apartado el{' '}
                            {formatDateTime(apartado.fecha_apartada)} · Fecha
                            límite {formatDate(apartado.fecha_limite)}
                        </p>
                    </div>

                    {apartado.estado === 'activo' && (
                        <CancelApartado apartado={apartado} />
                    )}
                </div>

                {apartado.vencido_por_fecha && (
                    <div className="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-300">
                        La fecha límite ya pasó. Este apartado se marcará como
                        vencido automáticamente y sus piezas volverán al stock.
                    </div>
                )}

                <div className="grid gap-4 sm:grid-cols-3">
                    <Summary
                        label="Total de la compra"
                        value={formatMoney(apartado.total_compra)}
                    />
                    <Summary label="Pagado" value={formatMoney(abonado)} />
                    <Summary
                        label="Saldo pendiente"
                        value={formatMoney(apartado.saldo_pendiente)}
                    />
                </div>

                {aceptaAbonos && (
                    <Form
                        {...AbonoController.store.form(apartado)}
                        noValidate
                        resetOnSuccess
                        options={{ preserveScroll: true }}
                        className="grid gap-2 rounded-xl border p-4"
                    >
                        {({ processing, errors }) => (
                            <>
                                <Label htmlFor="monto">Registrar abono</Label>
                                <div className="flex flex-wrap gap-2">
                                    <Input
                                        id="monto"
                                        name="monto"
                                        type="number"
                                        step="any"
                                        inputMode="decimal"
                                        placeholder="0.00"
                                        className="w-full sm:w-48"
                                        aria-invalid={
                                            errors.monto ? true : undefined
                                        }
                                    />
                                    <Button disabled={processing}>
                                        Abonar
                                    </Button>
                                </div>
                                <InputError message={errors.monto} />
                            </>
                        )}
                    </Form>
                )}

                <section className="space-y-3">
                    <h3 className="font-medium">Historial de pagos</h3>

                    <div className="overflow-x-auto rounded-xl border">
                        <table className="w-full text-sm">
                            <thead className="bg-muted/50 text-left text-muted-foreground">
                                <tr>
                                    <th className="px-4 py-3 font-medium">
                                        Fecha
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Concepto
                                    </th>
                                    <th className="px-4 py-3 text-right font-medium">
                                        Monto
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {apartado.abonos.map((abono) => (
                                    <tr
                                        key={abono.id_abono}
                                        className="border-t"
                                    >
                                        <td className="px-4 py-2">
                                            {formatDateTime(abono.fecha_monto)}
                                        </td>
                                        <td className="px-4 py-2">Abono</td>
                                        <td className="px-4 py-2 text-right tabular-nums">
                                            {formatMoney(abono.monto)}
                                        </td>
                                    </tr>
                                ))}
                                <tr className="border-t">
                                    <td className="px-4 py-2">
                                        {formatDateTime(
                                            apartado.fecha_apartada,
                                        )}
                                    </td>
                                    <td className="px-4 py-2">Anticipo</td>
                                    <td className="px-4 py-2 text-right tabular-nums">
                                        {formatMoney(apartado.anticipo)}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </>
    );
}

ShowApartado.layout = {
    breadcrumbs: [
        {
            title: 'Apartados',
            href: index(),
        },
        {
            title: 'Detalle',
            href: index(),
        },
    ],
};
