<!-- Modal Editar Venta -->
<div id="modalEditarVenta" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 overflow-y-auto">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl p-6 m-4 relative">
        <button onclick="cerrarModalEditarVenta()" class="absolute top-4 right-4 text-gray-500 hover:text-gray-700">
            <i class="fas fa-times text-xl"></i>
        </button>

        <h2 class="text-2xl font-bold text-gray-800 mb-4">Editar Venta #<span id="lblVentaId"></span></h2>

        <!-- Warning Alert -->
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-yellow-700">
                        <span class="font-bold">Nota Importante:</span> 
                        Únicamente se permite la edición de <strong>Series</strong> y <strong>Lotes</strong>. 
                        <br>
                        Por razones de trazabilidad contable, si desea cambiar productos o cantidades, debe 
                        <strong>anular esta venta</strong> y crear una nueva.
                    </p>
                </div>
            </div>
        </div>

        <!-- Info Venta -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6 bg-gray-50 p-4 rounded border">
            <div>
                <label class="block text-xs font-medium text-gray-500 uppercase">Cliente</label>
                <div id="lblClienteNombre" class="font-semibold text-gray-800"></div>
                <div id="lblClienteNit" class="text-sm text-gray-600"></div>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 uppercase">Estado</label>
                <span id="lblVentaEstado" class="px-2 py-1 rounded-full text-xs font-semibold"></span>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 uppercase">Fecha</label>
                <div id="lblVentaFecha" class="text-gray-800"></div>
            </div>
        </div>

        <!-- Tabla Detalles -->
        <div class="overflow-x-auto border rounded-lg mb-6">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Producto</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Cant.</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Series / Lotes Asignados</th>
                    </tr>
                </thead>
                <tbody id="tbodyDetallesVenta" class="bg-white divide-y divide-gray-200">
                    <!-- JS Rendering -->
                </tbody>
            </table>
        </div>

        <div class="flex justify-end">
            <button onclick="cerrarModalEditarVenta()" class="px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300">
                Cerrar
            </button>
        </div>
    </div>
</div>

<!-- Sub-Modal Cambiar Serie (Nested) -->
<div id="modalCambiarSerie" class="fixed inset-0 bg-black bg-opacity-60 hidden items-center justify-center z-[60]">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6">
        <h3 class="text-lg font-bold mb-4">Cambiar Serie</h3>
        <p class="text-sm text-gray-600 mb-4">Serie actual: <span id="lblSerieActual" class="font-mono font-bold"></span></p>
        
        <input type="hidden" id="inputDetalleId">
        <input type="hidden" id="inputSerieIdActual">
        <input type="hidden" id="inputProductoId">
        <input type="hidden" id="inputVentaIdForSerie">

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Nueva Serie</label>
            <select id="selectNuevaSerie" class="w-full border rounded-lg p-2">
                <option value="">Cargando series disponibles...</option>
            </select>
        </div>

        <div class="flex justify-end gap-3">
            <button onclick="cerrarModalSerie()" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded">Cancelar</button>
            <button onclick="guardarCambioSerie()" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Guardar Cambio</button>
        </div>
    </div>
</div>

<script>
    // Functions for Modal Logic
    async function abrirModalEditarVenta(ventaId) {
        const modal = document.getElementById('modalEditarVenta');
        const tbody = document.getElementById('tbodyDetallesVenta');
        
        // Reset UI
        document.getElementById('lblVentaId').textContent = '...';
        tbody.innerHTML = '<tr><td colspan="3" class="text-center py-4">Cargando detalles...</td></tr>';
        modal.classList.remove('hidden');
        modal.classList.add('flex');

        try {
            const res = await fetch(`/ventas/${ventaId}/detalles-editables`);
            const data = await res.json();

            if (!data.success) {
                alert(data.message || 'Error al cargar venta');
                cerrarModalEditarVenta();
                return;
            }

            const v = data.venta;
            document.getElementById('lblVentaId').textContent = v.ven_id;
            document.getElementById('lblClienteNombre').textContent = `${v.cliente_nombre1 || ''} ${v.cliente_apellido1 || ''}`;
            document.getElementById('lblClienteNit').textContent = `NIT: ${v.cliente_nit || 'C/F'}`;
            document.getElementById('lblVentaFecha').textContent = v.ven_fecha;
            
            const lblEstado = document.getElementById('lblVentaEstado');
            lblEstado.textContent = v.ven_situacion;
            lblEstado.className = 'px-2 py-1 rounded-full text-xs font-semibold ' + 
                (v.ven_situacion === 'PENDIENTE' ? 'bg-yellow-100 text-yellow-800' : 
                 v.ven_situacion === 'RESERVADA' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800');

            // Render Details
            tbody.innerHTML = '';
            data.detalles.forEach(det => {
                let seriesHtml = '<span class="text-xs text-gray-400 italic">No aplica (Sin serie/lote)</span>';
                
                if (det.producto_requiere_serie && det.series && det.series.length > 0) {
                    seriesHtml = `<div class="flex flex-col gap-2">`;
                    det.series.forEach(s => {
                        seriesHtml += `
                            <div class="flex items-center justify-between bg-gray-50 p-2 rounded border border-gray-200">
                                <div class="flex flex-col">
                                    <span class="text-[10px] text-gray-500 uppercase">Serie Actual</span>
                                    <span class="text-sm font-mono font-bold text-gray-800">${s.serie_numero}</span>
                                </div>
                                <button onclick="prepararCambioSerie(${v.ven_id}, ${det.det_id}, ${s.serie_id}, '${s.serie_numero}', ${det.det_producto_id})" 
                                    class="flex items-center gap-1 bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 px-3 py-1.5 rounded text-xs font-semibold transition-colors shadow-sm">
                                    <i class="fas fa-exchange-alt"></i> Cambiar
                                </button>
                            </div>`;
                    });
                    seriesHtml += `</div>`;
                } else if (det.producto_requiere_serie) {
                     seriesHtml = '<span class="text-xs text-red-500 italic">Requiere serie pero no tiene asignada</span>';
                }

                tbody.innerHTML += `
                    <tr>
                        <td class="px-4 py-3 text-sm text-gray-900">
                            <div class="font-medium">${det.producto_nombre}</div>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900">${det.det_cantidad}</td>
                        <td class="px-4 py-3">${seriesHtml}</td>
                    </tr>
                `;
            });

        } catch (e) {
            console.error(e);
            alert('Error de conexión al cargar detalles');
            cerrarModalEditarVenta();
        }
    }

    function cerrarModalEditarVenta() {
        document.getElementById('modalEditarVenta').classList.add('hidden');
        document.getElementById('modalEditarVenta').classList.remove('flex');
    }

    // --- Logic for Sub-Modal (Change Series) ---

    function cerrarModalSerie() {
        document.getElementById('modalCambiarSerie').classList.add('hidden');
        document.getElementById('modalCambiarSerie').classList.remove('flex');
    }

    async function prepararCambioSerie(ventaId, detalleId, serieId, serieNumero, productoId) {
        document.getElementById('inputVentaIdForSerie').value = ventaId;
        document.getElementById('inputDetalleId').value = detalleId;
        document.getElementById('inputSerieIdActual').value = serieId;
        document.getElementById('inputProductoId').value = productoId;
        document.getElementById('lblSerieActual').textContent = serieNumero;

        const select = document.getElementById('selectNuevaSerie');
        select.innerHTML = '<option value="">Cargando...</option>';

        const modal = document.getElementById('modalCambiarSerie');
        modal.classList.remove('hidden');
        modal.classList.add('flex');

        try {
            const res = await fetch(`/inventario/productos/${productoId}/series-disponibles`);
            const data = await res.json();
            
            if (data.success || Array.isArray(data)) { // Handle if returns array directly or wrapped
                const list = data.data || data; // Adapt based on controller response
                select.innerHTML = '<option value="">Seleccione nueva serie...</option>';
                list.forEach(s => {
                    select.innerHTML += `<option value="${s.serie_id}">${s.serie_numero_serie || s.serie_numero}</option>`;
                });
            } else {
                 select.innerHTML = '<option value="">Error al cargar series</option>';
            }
        } catch (e) {
            console.error(e);
            select.innerHTML = '<option value="">Error de conexión</option>';
        }
    }

    async function guardarCambioSerie() {
        const ventaId = document.getElementById('inputVentaIdForSerie').value;
        const detalleId = document.getElementById('inputDetalleId').value;
        const oldSerieId = document.getElementById('inputSerieIdActual').value;
        const newSerieId = document.getElementById('selectNuevaSerie').value;
        const productoId = document.getElementById('inputProductoId').value;
        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        if (!newSerieId) {
            alert('Seleccione una nueva serie');
            return;
        }

        try {
            const res = await fetch('/ventas/update-editable', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token
                },
                body: JSON.stringify({
                    ven_id: ventaId,
                    cambios: [{
                        det_id: detalleId,
                        producto_id: productoId,
                        old_serie_id: oldSerieId,
                        new_serie_id: newSerieId
                    }]
                })
            });

            const data = await res.json();

            if (data.success) {
                alert('Serie cambiada exitosamente');
                cerrarModalSerie();
                // Reload details in the main modal
                abrirModalEditarVenta(ventaId);
            } else {
                alert('Error: ' + (data.message || 'Error desconocido'));
            }
        } catch (e) {
            console.error(e);
            alert('Error al guardar cambio');
        }
    }
</script>
