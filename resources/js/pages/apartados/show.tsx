import { Form, Head, Link } from '@inertiajs/react';
import { useState } from 'react';
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
import type { ApartadoDetail, ReglasAbono } from '@/types';

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

function AbonoForm({
    apartado,
    reglas,
}: {
    apartado: ApartadoDetail;
    reglas: ReglasAbono;
}) {
    const [monto, setMonto] = useState('');

    const montoCents = Math.round(Number(monto) * 100);
    const saldoCents = Math.round(Number(apartado.saldo_pendiente) * 100);
    const multaCents = Math.round(Number(apartado.multa_si_abona) * 100);
    const esMontoValido = montoCents > 0 && montoCents <= saldoCents;

    return (
        <Form
            {...AbonoController.store.form(apartado)}
            noValidate
            resetOnSuccess
            onSuccess={() => setMonto('')}
            options={{ preserveScroll: true }}
            className="grid gap-3 rounded-xl border p-4"
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
                            value={monto}
                            onChange={(event) => setMonto(event.target.value)}
                            className="w-full sm:w-48"
                            aria-invalid={errors.monto ? true : undefined}
                        />
                        <Button disabled={processing}>Abonar</Button>
                    </div>
                    <InputError message={errors.monto} />

                    {multaCents > 0 && (
                        <div className="grid gap-1 rounded-lg bg-red-50 p-3 text-sm text-red-700 dark:bg-red-950 dark:text-red-300">
                            <span>
                                Multa por pago tardío ({reglas.porcentajeMulta}%
                                del saldo pendiente):{' '}
                                <strong>
                                    {formatMoney(apartado.multa_si_abona)}
                                </strong>
                            </span>
                            {esMontoValido && (
                                <span>
                                    Total a cobrar: {formatMoney(monto)} +{' '}
                                    {formatMoney(apartado.multa_si_abona)} ={' '}
                                    <strong>
                                        {formatMoney(
                                            (montoCents + multaCents) / 100,
                                        )}
                                    </strong>
                                </span>
                            )}
                        </div>
                    )}
                </>
            )}
        </Form>
    );
}

export default function ShowApartado({
    apartado,
    reglas,
}: {
    apartado: ApartadoDetail;
    reglas: ReglasAbono;
}) {
    const pagado =
        Number(apartado.total_compra) - Number(apartado.saldo_pendiente);
    const multas = apartado.abonos.reduce(
        (total, abono) => total + Number(abono.multa),
        0,
    );
    const esVigente =
        apartado.estado === 'activo' || apartado.estado === 'en_riesgo';

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

                    {esVigente && <CancelApartado apartado={apartado} />}
                </div>

                {apartado.en_gracia && (
                    <div className="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-900 dark:bg-red-950 dark:text-red-300">
                        La fecha límite ({formatDate(apartado.fecha_limite)}) ya
                        pasó. El cliente puede abonar hasta el{' '}
                        {formatDate(apartado.fecha_fin_gracia)} pagando una
                        multa del {reglas.porcentajeMulta}% de su saldo
                        pendiente. Al abonar, tendrá {reglas.diasParaPagar} días
                        más para pagar. Si no abona, el apartado pasará a
                        vencido y las piezas volverán al stock.
                    </div>
                )}

                {apartado.plazo_vencido && (
                    <div className="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-300">
                        Terminaron los {reglas.diasDeGracia} días de gracia el{' '}
                        {formatDate(apartado.fecha_fin_gracia)}. Este apartado
                        se marcará como vencido automáticamente y sus piezas
                        volverán al stock.
                    </div>
                )}

                <div className="grid gap-4 sm:grid-cols-3">
                    <Summary
                        label={
                            multas > 0
                                ? 'Costo total (con multas)'
                                : 'Total de compra'
                        }
                        value={formatMoney(
                            Number(apartado.total_compra) + multas,
                        )}
                    />
                    <Summary
                        label="Saldo ya pagado"
                        value={formatMoney(pagado)}
                    />
                    <Summary
                        label="Saldo pendiente"
                        value={formatMoney(apartado.saldo_pendiente)}
                    />
                </div>

                {multas > 0 && (
                    <p className="-mt-3 text-sm text-muted-foreground">
                        Total de compra {formatMoney(apartado.total_compra)} +{' '}
                        <span className="text-red-600 dark:text-red-400">
                            {formatMoney(multas)} en multas
                        </span>{' '}
                        pagadas por abonos tardíos.
                    </p>
                )}

                {apartado.acepta_abonos && (
                    <AbonoForm apartado={apartado} reglas={reglas} />
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
                                    <th className="px-4 py-3 text-right font-medium">
                                        Multa
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {apartado.abonos.map((abono) => (
                                    <tr
                                        key={abono.id_abono}
                                        className="border-t"
                                    >
                                        <td className="px-4 py-2 whitespace-nowrap">
                                            {formatDateTime(abono.fecha_monto)}
                                        </td>
                                        <td className="px-4 py-2">Abono</td>
                                        <td className="px-4 py-2 text-right tabular-nums">
                                            {formatMoney(abono.monto)}
                                        </td>
                                        <td className="px-4 py-2 text-right text-red-600 tabular-nums dark:text-red-400">
                                            {Number(abono.multa) > 0 &&
                                                formatMoney(abono.multa)}
                                        </td>
                                    </tr>
                                ))}
                                <tr className="border-t">
                                    <td className="px-4 py-2 whitespace-nowrap">
                                        {formatDateTime(
                                            apartado.fecha_apartada,
                                        )}
                                    </td>
                                    <td className="px-4 py-2">Anticipo</td>
                                    <td className="px-4 py-2 text-right tabular-nums">
                                        {formatMoney(apartado.anticipo)}
                                    </td>
                                    <td className="px-4 py-2" />
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
