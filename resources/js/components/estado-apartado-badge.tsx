import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';
import type { EstadoApartado } from '@/types';

export const estadoLabels: Record<EstadoApartado, string> = {
    activo: 'Activo',
    liquidado: 'Liquidado',
    vencido: 'Vencido',
    cancelado: 'Cancelado',
};

const estadoStyles: Record<EstadoApartado, string> = {
    activo: 'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-900 dark:bg-blue-950 dark:text-blue-300',
    liquidado:
        'border-green-200 bg-green-50 text-green-700 dark:border-green-900 dark:bg-green-950 dark:text-green-300',
    vencido:
        'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-300',
    cancelado: 'text-muted-foreground',
};

export default function EstadoApartadoBadge({
    estado,
}: {
    estado: EstadoApartado;
}) {
    return (
        <Badge variant="outline" className={cn(estadoStyles[estado])}>
            {estadoLabels[estado]}
        </Badge>
    );
}
