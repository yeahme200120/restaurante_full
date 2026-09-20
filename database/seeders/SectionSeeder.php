<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Section;
use Illuminate\Database\Seeder;

class SectionSeeder extends Seeder
{
    public function run(): void
    {
        $sections = [
            'caja' => [
                'cajas',
                'aperturas',
                'cierres',
                'movimientos',
                'ingresos',
                'egresos',
                'retiros',
                'arqueos',
                'diferencias',
                'cortes',
                'historial',
            ],

            'mesas' => [
                'mesas',
                'areas',
                'zonas',
                'estados',
                'asignaciones',
                'movimientos',
                'reservas',
                'configuracion',
                'historial',
            ],

            'productos' => [
                'productos',
                'categorias',
                'subcategorias',
                'unidades',
                'precios',
                'listas_precios',
                'codigos',
                'presentaciones',
                'variantes',
                'productos_compuestos',
                'ingredientes',
                'recetas',
                'modificadores',
                'complementos',
                'imagenes',
                'configuracion',
                'historial',
            ],

            'ventas' => [
                'ventas',
                'detalles',
                'comandas',
                'pagos',
                'metodos_pago',
                'descuentos',
                'impuestos',
                'propinas',
                'cancelaciones',
                'devoluciones',
                'notas_credito',
                'tickets',
                'reimpresiones',
                'folios',
                'ventas_pendientes',
                'ventas_canceladas',
                'ventas_cerradas',
                'historial',
            ],

            'inventario' => [
                'existencias',
                'movimientos',
                'entradas',
                'salidas',
                'ajustes',
                'traspasos',
                'lotes',
                'caducidades',
                'ubicaciones',
                'minimos',
                'maximos',
                'alertas',
                'kardex',
                'costos',
                'valorizacion',
                'historial',
            ],

            'cocina' => [
                'pedidos',
                'comandas',
                'estaciones',
                'prioridades',
                'estados',
                'preparacion',
                'pendientes',
                'listos',
                'entregados',
                'cancelados',
                'tiempos',
                'configuracion',
                'historial',
            ],

            'bar' => [
                'pedidos',
                'comandas',
                'estaciones',
                'prioridades',
                'estados',
                'preparacion',
                'pendientes',
                'listos',
                'entregados',
                'cancelados',
                'tiempos',
                'configuracion',
                'historial',
            ],

            'clientes' => [
                'clientes',
                'historial',
                'configuracion',
            ],

            'compras_proveedores' => [
                'compras',
                'proveedores',
                'ordenes_compra',
                'recepciones',
                'historial',
            ],

            'impuestos' => [
                'impuestos',
                'configuracion',
                'historial',
            ],

            'descuentos' => [
                'descuentos',
                'promociones',
                'cupones',
                'configuracion',
                'historial',
            ],

            'propinas' => [
                'propinas',
                'distribucion',
                'historial',
                'configuracion',
            ],

            'solicitudes' => [
                'solicitudes',
                'autorizaciones',
                'historial',
                'configuracion',
            ],

            'eventos' => [
                'eventos',
                'notificaciones',
                'tiempo_real',
                'historial',
                'configuracion',
            ],

            'estadisticas' => [
                'ventas',
                'productos',
                'inventario',
                'caja',
                'compras_proveedores',
                'clientes',
                'cocina',
                'bar',
                'reportes',
            ],

            'importaciones' => [
                'importaciones',
                'plantillas',
                'errores',
                'progreso',
                'historial',
                'configuracion',
            ],

            'exportaciones' => [
                'exportaciones',
                'formatos',
                'progreso',
                'archivos',
                'historial',
                'configuracion',
            ],

            'facturacion' => [
                'facturas',
                'clientes_fiscales',
                'conceptos',
                'respuestas',
                'archivos',
                'cancelaciones',
                'historial',
                'configuracion',
            ],
        ];

        foreach ($sections as $moduleCode => $sectionCodes) {
            $module = Module::where('code', $moduleCode)->first();

            if (! $module) {
                $this->command?->error(
                    "Módulo no encontrado: {$moduleCode}"
                );

                continue;
            }

            foreach ($sectionCodes as $sortOrder => $sectionCode) {
                Section::updateOrCreate(
                    [
                        'module_id' => $module->id,
                        'code' => $sectionCode,
                    ],
                    [
                        'name' => $this->sectionName($sectionCode),
                        'description' => null,
                        'status' => 'activo',
                        'sort_order' => $sortOrder,
                        'metadata' => null,
                    ]
                );
            }
        }

        $this->command?->info(
            'Catálogo funcional de secciones sincronizado correctamente.'
        );
    }

    private function sectionName(string $code): string
    {
        return match ($code) {
            'cajas' => 'Cajas',
            'aperturas' => 'Aperturas',
            'cierres' => 'Cierres',
            'movimientos' => 'Movimientos',
            'ingresos' => 'Ingresos',
            'egresos' => 'Egresos',
            'retiros' => 'Retiros',
            'arqueos' => 'Arqueos',
            'diferencias' => 'Diferencias',
            'cortes' => 'Cortes',
            'historial' => 'Historial',

            'mesas' => 'Mesas',
            'areas' => 'Áreas',
            'zonas' => 'Zonas',
            'estados' => 'Estados',
            'asignaciones' => 'Asignaciones',
            'reservas' => 'Reservas',
            'configuracion' => 'Configuración',

            'productos' => 'Productos',
            'categorias' => 'Categorías',
            'subcategorias' => 'Subcategorías',
            'unidades' => 'Unidades',
            'precios' => 'Precios',
            'listas_precios' => 'Listas de precios',
            'codigos' => 'Códigos',
            'presentaciones' => 'Presentaciones',
            'variantes' => 'Variantes',
            'productos_compuestos' => 'Productos compuestos',
            'ingredientes' => 'Ingredientes',
            'recetas' => 'Recetas',
            'modificadores' => 'Modificadores',
            'complementos' => 'Complementos',
            'imagenes' => 'Imágenes',

            'ventas' => 'Ventas',
            'detalles' => 'Detalles',
            'comandas' => 'Comandas',
            'pagos' => 'Pagos',
            'metodos_pago' => 'Métodos de pago',
            'descuentos' => 'Descuentos',
            'impuestos' => 'Impuestos',
            'propinas' => 'Propinas',
            'cancelaciones' => 'Cancelaciones',
            'devoluciones' => 'Devoluciones',
            'notas_credito' => 'Notas de crédito',
            'tickets' => 'Tickets',
            'reimpresiones' => 'Reimpresiones',
            'folios' => 'Folios',
            'ventas_pendientes' => 'Ventas pendientes',
            'ventas_canceladas' => 'Ventas canceladas',
            'ventas_cerradas' => 'Ventas cerradas',

            'existencias' => 'Existencias',
            'entradas' => 'Entradas',
            'salidas' => 'Salidas',
            'ajustes' => 'Ajustes',
            'traspasos' => 'Traspasos',
            'lotes' => 'Lotes',
            'caducidades' => 'Caducidades',
            'ubicaciones' => 'Ubicaciones',
            'minimos' => 'Mínimos',
            'maximos' => 'Máximos',
            'alertas' => 'Alertas',
            'kardex' => 'Kardex',
            'costos' => 'Costos',
            'valorizacion' => 'Valorización',

            'pedidos' => 'Pedidos',
            'estaciones' => 'Estaciones',
            'prioridades' => 'Prioridades',
            'preparacion' => 'Preparación',
            'pendientes' => 'Pendientes',
            'listos' => 'Listos',
            'entregados' => 'Entregados',
            'cancelados' => 'Cancelados',
            'tiempos' => 'Tiempos',

            'clientes' => 'Clientes',

            'compras' => 'Compras',
            'proveedores' => 'Proveedores',
            'ordenes_compra' => 'Órdenes de compra',
            'recepciones' => 'Recepciones',

            'promociones' => 'Promociones',
            'cupones' => 'Cupones',

            'distribucion' => 'Distribución',

            'solicitudes' => 'Solicitudes',
            'autorizaciones' => 'Autorizaciones',

            'eventos' => 'Eventos',
            'notificaciones' => 'Notificaciones',
            'tiempo_real' => 'Tiempo real',

            'reportes' => 'Reportes',

            'importaciones' => 'Importaciones',
            'plantillas' => 'Plantillas',
            'errores' => 'Errores',
            'progreso' => 'Progreso',

            'exportaciones' => 'Exportaciones',
            'formatos' => 'Formatos',
            'archivos' => 'Archivos',

            'facturas' => 'Facturas',
            'clientes_fiscales' => 'Clientes fiscales',
            'conceptos' => 'Conceptos',
            'respuestas' => 'Respuestas',
            'cancelaciones' => 'Cancelaciones',

            default => str($code)
                ->replace('_', ' ')
                ->title()
                ->toString(),
        };
    }
}
