<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ========================
        // TABLAS BASE
        // ========================

        Schema::create('roles', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nombre', 50)->unique();
            $table->string('descripcion', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id('user_id');
            $table->string('user_primer_nombre', 100)->nullable();
            $table->string('user_segundo_nombre', 100)->nullable();
            $table->string('user_primer_apellido', 100)->nullable();
            $table->string('user_segundo_apellido', 100)->nullable();
            $table->string('email', 100)->unique()->nullable();
            $table->string('password', 255)->nullable();
            $table->string('remember_token', 100)->nullable();
            $table->string('user_dpi_dni', 20)->nullable();
            $table->unsignedInteger('user_rol')->nullable();
            $table->timestamp('user_fecha_creacion')->useCurrent();
            $table->dateTime('user_fecha_contrasena')->nullable();
            $table->string('user_foto', 250)->nullable();
            $table->string('user_token', 250)->nullable();
            $table->dateTime('user_fecha_verificacion')->nullable();
            $table->tinyInteger('user_situacion')->default(1);
            $table->string('user_empresa', 255)->nullable();
            
            $table->foreign('user_rol')->references('id')->on('roles')->onDelete('set null');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email', 255)->primary();
            $table->string('token', 255);
            $table->timestamp('created_at')->nullable();
        });

        // ========================
        // CATÁLOGOS
        // ========================

        Schema::create('pro_metodos_pago', function (Blueprint $table) {
            $table->id('metpago_id');
            $table->string('metpago_descripcion', 50);
            $table->integer('metpago_situacion')->default(1);
            $table->timestamps();
        });

        Schema::create('pro_paises', function (Blueprint $table) {
            $table->id('pais_id');
            $table->string('pais_descripcion', 50)->nullable();
            $table->integer('pais_situacion')->default(1);
            $table->timestamps();
        });

        Schema::create('pro_marcas', function (Blueprint $table) {
            $table->id('marca_id');
            $table->string('marca_descripcion', 50)->nullable();
            $table->integer('marca_situacion')->default(1);
            $table->timestamps();
            $table->index('marca_situacion');
        });

        Schema::create('pro_modelo', function (Blueprint $table) {
            $table->id('modelo_id');
            $table->string('modelo_descripcion', 50)->nullable();
            $table->integer('modelo_situacion')->default(1);
            $table->unsignedBigInteger('modelo_marca_id')->nullable();
            
            $table->index('modelo_marca_id');
            $table->index('modelo_situacion');
            $table->foreign('modelo_marca_id')->references('marca_id')->on('pro_marcas')->onDelete('set null');
        });

        Schema::create('pro_unidades_medida', function (Blueprint $table) {
            $table->id('unidad_id');
            $table->string('unidad_nombre', 50);
            $table->string('unidad_abreviacion', 10);
            $table->string('unidad_tipo', 20)->default('longitud');
            $table->integer('unidad_situacion')->default(1);
            $table->timestamps();
        });

        Schema::create('pro_calibres', function (Blueprint $table) {
            $table->id('calibre_id');
            $table->string('calibre_nombre', 20);
            $table->unsignedBigInteger('calibre_unidad_id');
            $table->decimal('calibre_equivalente_mm', 6, 2)->nullable();
            $table->integer('calibre_situacion')->default(1);
            $table->timestamps();
            
            $table->foreign('calibre_unidad_id')->references('unidad_id')->on('pro_unidades_medida');
        });

        Schema::create('pro_categorias', function (Blueprint $table) {
            $table->id('categoria_id');
            $table->string('categoria_nombre', 100);
            $table->integer('categoria_situacion')->default(1);
            $table->timestamps();
        });

        Schema::create('pro_subcategorias', function (Blueprint $table) {
            $table->id('subcategoria_id');
            $table->string('subcategoria_nombre', 100);
            $table->unsignedBigInteger('subcategoria_idcategoria');
            $table->integer('subcategoria_situacion')->default(1);
            $table->timestamps();
            
            $table->foreign('subcategoria_idcategoria')->references('categoria_id')->on('pro_categorias')->onDelete('cascade');
        });

        // ========================
        // EMPRESAS Y LICENCIAS
        // ========================

        Schema::create('pro_empresas_de_importacion', function (Blueprint $table) {
            $table->id('empresaimp_id');
            $table->unsignedBigInteger('empresaimp_pais');
            $table->string('empresaimp_descripcion', 50)->nullable();
            $table->integer('empresaimp_situacion')->default(1);
            $table->timestamps();
            
            $table->foreign('empresaimp_pais')->references('pais_id')->on('pro_paises');
        });

        Schema::create('pro_licencias_para_importacion', function (Blueprint $table) {
            $table->unsignedBigInteger('lipaimp_id')->primary();
            $table->integer('lipaimp_poliza')->nullable();
            $table->string('lipaimp_descripcion', 255)->nullable();
            $table->date('lipaimp_fecha_emision')->nullable();
            $table->date('lipaimp_fecha_vencimiento')->nullable();
            $table->text('lipaimp_observaciones')->nullable();
            $table->integer('lipaimp_situacion')->default(1);
            $table->timestamps();
        });

        Schema::create('pro_armas_licenciadas', function (Blueprint $table) {
            $table->id('arma_lic_id');
            $table->unsignedBigInteger('arma_num_licencia');
            $table->unsignedBigInteger('arma_sub_cat');
            $table->unsignedBigInteger('arma_modelo');
            $table->unsignedBigInteger('arma_empresa');
            $table->decimal('arma_largo_canon', 10, 2);
            $table->integer('arma_cantidad')->default(1);
            $table->unsignedBigInteger('arma_calibre')->nullable();
            
            $table->index('arma_num_licencia');
            $table->index('arma_sub_cat');
            $table->index('arma_modelo');
            $table->index('arma_empresa');
            
            $table->foreign('arma_empresa')->references('empresaimp_id')->on('pro_empresas_de_importacion')->onDelete('restrict');
            $table->foreign('arma_num_licencia')->references('lipaimp_id')->on('pro_licencias_para_importacion')->onDelete('cascade');
            $table->foreign('arma_modelo')->references('modelo_id')->on('pro_modelo')->onDelete('restrict');
            $table->foreign('arma_sub_cat')->references('subcategoria_id')->on('pro_subcategorias')->onDelete('restrict');
            $table->foreign('arma_calibre')->references('calibre_id')->on('pro_calibres')->onDelete('restrict');
        });

        Schema::create('pro_documentacion_lic_import', function (Blueprint $table) {
            $table->id('doclicimport_id');
            $table->string('doclicimport_ruta', 255);
            $table->string('doclicimport_nombre_original', 255)->nullable();
            $table->unsignedBigInteger('doclicimport_size_bytes')->default(0);
            $table->string('doclicimport_mime', 100)->nullable();
            $table->unsignedBigInteger('doclicimport_num_lic');
            $table->tinyInteger('doclicimport_situacion')->default(1);
            $table->timestamps();
            
            $table->foreign('doclicimport_num_lic')->references('lipaimp_id')->on('pro_licencias_para_importacion')->onDelete('cascade');
        });

        // ========================
        // PAGOS DE LICENCIAS
        // ========================

        Schema::create('pro_pagos_licencias', function (Blueprint $table) {
            $table->id('pago_lic_id');
            $table->unsignedBigInteger('pago_lic_licencia_id');
            $table->decimal('pago_lic_total', 12, 2)->default(0);
            $table->tinyInteger('pago_lic_situacion')->default(1);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            
            $table->index('pago_lic_licencia_id');
            $table->foreign('pago_lic_licencia_id')->references('lipaimp_id')->on('pro_licencias_para_importacion')->onDelete('cascade');
        });

        Schema::create('pro_pagos_lic_metodos', function (Blueprint $table) {
            $table->id('pagomet_id');
            $table->unsignedBigInteger('pagomet_pago_lic');
            $table->unsignedBigInteger('pagomet_metodo');
            $table->decimal('pagomet_monto', 12, 2)->default(0);
            $table->char('pagomet_moneda', 3)->default('GTQ');
            $table->string('pagomet_referencia', 100)->nullable();
            $table->string('pagomet_banco', 100)->nullable();
            $table->string('pagomet_nota', 255)->nullable();
            $table->tinyInteger('pagomet_situacion')->default(1);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            
            $table->index('pagomet_pago_lic');
            $table->index('pagomet_metodo');
            $table->index(['pagomet_situacion', 'pagomet_pago_lic']);
            
            $table->foreign('pagomet_pago_lic')->references('pago_lic_id')->on('pro_pagos_licencias')->onDelete('cascade');
            $table->foreign('pagomet_metodo')->references('metpago_id')->on('pro_metodos_pago');
        });

        Schema::create('pro_comprobantes_pago_licencias', function (Blueprint $table) {
            $table->id('comprob_id');
            $table->string('comprob_ruta', 255);
            $table->string('comprob_nombre_original', 255)->nullable();
            $table->unsignedBigInteger('comprob_size_bytes')->default(0);
            $table->string('comprob_mime', 100)->nullable();
            $table->unsignedBigInteger('comprob_pagomet_id');
            $table->tinyInteger('comprob_situacion')->default(1);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            
            $table->index('comprob_pagomet_id');
            $table->index('comprob_situacion');
            
            $table->foreign('comprob_pagomet_id')->references('pagomet_id')->on('pro_pagos_lic_metodos')->onDelete('cascade');
        });

        // ========================
        // INVENTARIO VIEJO (si aún lo usas)
        // ========================

        Schema::create('pro_inventario_modelos', function (Blueprint $table) {
            $table->id('inv_modelo_id');
            $table->unsignedBigInteger('modelo_licencia');
            $table->integer('modelo_poliza');
            $table->date('modelo_fecha_ingreso');
            $table->unsignedBigInteger('modelo_marca');
            $table->unsignedBigInteger('modelo_modelo');
            $table->unsignedBigInteger('modelo_calibre')->nullable();
            $table->integer('modelo_cantidad')->default(0);
            $table->integer('modelo_disponible')->default(0);
            $table->integer('modelo_situacion')->default(1);
            $table->timestamps();
            
            $table->foreign('modelo_licencia')->references('lipaimp_id')->on('pro_licencias_para_importacion');
            $table->foreign('modelo_marca')->references('marca_id')->on('pro_marcas');
            $table->foreign('modelo_modelo')->references('modelo_id')->on('pro_modelo');
            $table->foreign('modelo_calibre')->references('calibre_id')->on('pro_calibres');
        });

        Schema::create('pro_inventario_armas', function (Blueprint $table) {
            $table->id('arma_id');
            $table->unsignedBigInteger('arma_modelo_id');
            $table->string('arma_numero_serie', 200)->unique()->nullable();
            $table->enum('arma_estado', ['disponible', 'vendida', 'reservada', 'baja'])->default('disponible');
            $table->timestamps();
            
            $table->foreign('arma_modelo_id')->references('inv_modelo_id')->on('pro_inventario_modelos');
        });

        // ========================
        // PRODUCTOS NUEVO
        // ========================

        Schema::create('pro_productos', function (Blueprint $table) {
            $table->id('producto_id');
            $table->string('producto_nombre', 100);
            $table->text('producto_descripcion')->nullable();
            $table->string('pro_codigo_sku', 100)->unique();
            $table->string('producto_codigo_barra', 100)->unique()->nullable();
            $table->unsignedBigInteger('producto_categoria_id');
            $table->unsignedBigInteger('producto_subcategoria_id');
            $table->unsignedBigInteger('producto_marca_id');
            $table->unsignedBigInteger('producto_modelo_id')->nullable();
            $table->unsignedBigInteger('producto_calibre_id')->nullable();
            $table->unsignedBigInteger('producto_madein')->nullable();
            $table->boolean('producto_requiere_serie')->default(false);
            $table->integer('producto_stock_minimo')->default(0);
            $table->integer('producto_stock_maximo')->default(0);
            $table->integer('producto_situacion')->default(1);
            $table->integer('producto_requiere_stock')->default(1);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            
            $table->index('producto_categoria_id');
            $table->index('producto_subcategoria_id');
            $table->index('producto_marca_id');
            $table->index('producto_modelo_id');
            $table->index('producto_calibre_id');
            $table->index('producto_situacion');
            
            $table->foreign('producto_categoria_id')->references('categoria_id')->on('pro_categorias')->onDelete('restrict');
            $table->foreign('producto_subcategoria_id')->references('subcategoria_id')->on('pro_subcategorias')->onDelete('restrict');
            $table->foreign('producto_marca_id')->references('marca_id')->on('pro_marcas')->onDelete('restrict');
            $table->foreign('producto_modelo_id')->references('modelo_id')->on('pro_modelo')->onDelete('set null');
            $table->foreign('producto_calibre_id')->references('calibre_id')->on('pro_calibres')->onDelete('set null');
            $table->foreign('producto_madein')->references('pais_id')->on('pro_paises')->onDelete('set null');
        });

        Schema::create('pro_licencia_asignacion_producto', function (Blueprint $table) {
            $table->id('asignacion_id');
            $table->unsignedBigInteger('asignacion_producto_id');
            $table->unsignedBigInteger('asignacion_licencia_id');
            $table->integer('asignacion_cantidad');
            $table->unsignedBigInteger('asignacion_serie_id')->nullable();
            $table->text('asignacion_observaciones')->nullable();
            $table->integer('asignacion_situacion')->default(1);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            
            $table->index('asignacion_producto_id');
            $table->index('asignacion_licencia_id');
            $table->index('asignacion_serie_id');
            
            $table->foreign('asignacion_producto_id')->references('producto_id')->on('pro_productos')->onDelete('cascade');
            $table->foreign('asignacion_licencia_id')->references('lipaimp_id')->on('pro_licencias_para_importacion')->onDelete('cascade');
        });

        Schema::create('pro_productos_fotos', function (Blueprint $table) {
            $table->id('foto_id');
            $table->unsignedBigInteger('foto_producto_id');
            $table->string('foto_url', 255);
            $table->string('foto_alt_text', 255)->nullable();
            $table->boolean('foto_principal')->default(false);
            $table->integer('foto_orden')->default(0);
            $table->integer('foto_situacion')->default(1);
            $table->timestamp('created_at')->useCurrent();
            
            $table->index('foto_producto_id');
            $table->index('foto_principal');
            $table->index('foto_orden');
            
            $table->foreign('foto_producto_id')->references('producto_id')->on('pro_productos')->onDelete('cascade');
        });

          Schema::create('factura_abonos', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('factura_id');
        $table->unsignedInteger('numero'); 
        $table->date('fecha_vencimiento');
        $table->decimal('monto', 14, 2);
        $table->timestamps();

        $table->foreign('factura_id')
              ->references('fac_id')->on('facturacion')
              ->onDelete('cascade');
    });

        Schema::create('pro_series_productos', function (Blueprint $table) {
            $table->id('serie_id');
            $table->unsignedBigInteger('serie_producto_id');
            $table->string('serie_numero_serie', 200);
            $table->string('serie_estado', 25)->default('disponible');
            $table->timestamp('serie_fecha_ingreso')->useCurrent();
            $table->string('serie_observaciones', 255)->nullable();
            $table->integer('serie_situacion')->default(1);
            $table->boolean('serie_tiene_tenencia')->default(false);
            $table->decimal('serie_monto_tenencia', 8, 2)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->unsignedBigInteger('serie_asignacion_id')->nullable();
            
            $table->unique(['serie_producto_id', 'serie_numero_serie'], 'unique_serie_per_product');
            $table->index('serie_producto_id');
            $table->index('serie_estado');
            $table->index('serie_numero_serie');
            $table->index('serie_asignacion_id');
            
            $table->foreign('serie_producto_id')->references('producto_id')->on('pro_productos')->onDelete('cascade');
            $table->foreign('serie_asignacion_id')->references('asignacion_id')->on('pro_licencia_asignacion_producto')->onDelete('set null');
        });

        Schema::create('pro_lotes', function (Blueprint $table) {
            $table->id('lote_id');
            $table->string('lote_codigo', 100)->unique();
            $table->unsignedBigInteger('lote_producto_id');
            $table->timestamp('lote_fecha')->useCurrent();
            $table->string('lote_descripcion', 255)->nullable();
            $table->integer('lote_cantidad_total')->default(0);
            $table->integer('lote_cantidad_disponible')->default(0);
            $table->unsignedBigInteger('lote_usuario_id')->nullable();
            $table->integer('lote_situacion')->default(1);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            
            $table->index('lote_codigo');
            $table->index('lote_fecha');
            $table->index('lote_usuario_id');
            $table->index('lote_producto_id');
            $table->index('lote_cantidad_total');
            $table->index('lote_cantidad_disponible');
            
            $table->foreign('lote_producto_id')->references('producto_id')->on('pro_productos')->onDelete('cascade');
            $table->foreign('lote_usuario_id')->references('user_id')->on('users')->onDelete('set null');
        });

        Schema::create('pro_precios', function (Blueprint $table) {
            $table->id('precio_id');
            $table->unsignedBigInteger('precio_producto_id');
            $table->decimal('precio_costo', 10, 2);
            $table->decimal('precio_venta', 10, 2);
            $table->decimal('precio_venta_empresa', 10, 2)->nullable();
            $table->decimal('precio_margen', 5, 2)->nullable();
            $table->decimal('precio_margen_empresa', 5, 2)->nullable();
            $table->decimal('precio_especial', 10, 2)->nullable();
            $table->string('precio_moneda', 3)->default('GTQ');
            $table->string('precio_justificacion', 255)->nullable();
            $table->date('precio_fecha_asignacion');
            $table->unsignedBigInteger('precio_usuario_id')->nullable();
            $table->integer('precio_situacion')->default(1);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            
            $table->index(['precio_producto_id', 'precio_fecha_asignacion']);
            $table->index('precio_situacion');
            $table->index('precio_usuario_id');
            $table->index('precio_venta_empresa');
            $table->index('precio_margen_empresa');
            
            $table->foreign('precio_producto_id')->references('producto_id')->on('pro_productos')->onDelete('cascade');
            $table->foreign('precio_usuario_id')->references('user_id')->on('users')->onDelete('set null');
        });

        Schema::create('pro_promociones', function (Blueprint $table) {
            $table->id('promo_id');
            $table->unsignedBigInteger('promo_producto_id');
            $table->string('promo_nombre', 100);
            $table->string('promo_tipo', 20)->default('porcentaje');
            $table->decimal('promo_valor', 10, 2);
            $table->decimal('promo_precio_original', 10, 2)->nullable();
            $table->decimal('promo_precio_descuento', 10, 2)->nullable();
            $table->date('promo_fecha_inicio');
            $table->date('promo_fecha_fin');
            $table->string('promo_justificacion', 255)->nullable();
            $table->unsignedBigInteger('promo_usuario_id')->nullable();
            $table->integer('promo_situacion')->default(1);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            
            $table->index('promo_producto_id');
            $table->index(['promo_fecha_inicio', 'promo_fecha_fin']);
            $table->index('promo_situacion');
            
            $table->foreign('promo_producto_id')->references('producto_id')->on('pro_productos')->onDelete('cascade');
            $table->foreign('promo_usuario_id')->references('user_id')->on('users')->onDelete('set null');
        });

        Schema::create('pro_movimientos', function (Blueprint $table) {
            $table->increments('mov_id');
            $table->unsignedBigInteger('mov_producto_id');
            $table->string('mov_tipo', 50);
            $table->string('mov_origen', 100)->nullable();
            $table->string('mov_destino', 100)->nullable();
            $table->integer('mov_cantidad');
            $table->decimal('mov_precio_unitario', 10, 2)->nullable();
            $table->decimal('mov_valor_total', 10, 2)->nullable();
            $table->timestamp('mov_fecha')->useCurrent();
            $table->unsignedBigInteger('mov_usuario_id');
            $table->unsignedBigInteger('mov_lote_id')->nullable();
            $table->unsignedBigInteger('mov_serie_id')->nullable();
            $table->string('mov_documento_referencia', 100)->nullable();
            $table->string('mov_observaciones', 250)->nullable();
            $table->string('mov_licencia_anterior', 250)->nullable(); 
            $table->string('mov_licencia_nueva', 250)->nullable(); 
            $table->integer('mov_situacion')->default(1);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            
            $table->index(['mov_producto_id', 'mov_fecha']);
            $table->index(['mov_tipo', 'mov_fecha']);
            $table->index(['mov_usuario_id', 'mov_fecha']);
            $table->index('mov_lote_id');
            $table->index('mov_serie_id');
            $table->index('mov_situacion');
            
            $table->foreign('mov_producto_id')->references('producto_id')->on('pro_productos')->onDelete('restrict');
            $table->foreign('mov_usuario_id')->references('user_id')->on('users')->onDelete('restrict');
            $table->foreign('mov_lote_id')->references('lote_id')->on('pro_lotes')->onDelete('set null');
            $table->foreign('mov_serie_id')->references('serie_id')->on('pro_series_productos')->onDelete('set null');
        });



        Schema::create('pro_stock_actual', function (Blueprint $table) {
            $table->id('stock_id');
            $table->unsignedBigInteger('stock_producto_id')->unique();
            $table->integer('stock_cantidad_total')->default(0);
            $table->integer('stock_cantidad_disponible')->default(0);
            $table->integer('stock_cantidad_reservada')->default(0);
            $table->integer('stock_cantidad_reservada2')->default(0);
            $table->decimal('stock_valor_total', 12, 2)->default(0);
            $table->timestamp('stock_ultimo_movimiento')->useCurrent()->useCurrentOnUpdate();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            
            $table->index('stock_producto_id');
            $table->index('stock_cantidad_disponible');
            
            $table->foreign('stock_producto_id')->references('producto_id')->on('pro_productos')->onDelete('cascade');
        });

        // ========================
        // ALERTAS
        // ========================

        Schema::create('pro_alertas', function (Blueprint $table) {
            $table->id('alerta_id');
            $table->string('alerta_tipo', 50);
            $table->string('alerta_titulo', 100);
            $table->text('alerta_mensaje');
            $table->string('alerta_prioridad', 20)->default('media');
            $table->unsignedBigInteger('alerta_producto_id')->nullable();
            $table->unsignedBigInteger('alerta_usuario_id')->nullable();
            $table->boolean('alerta_para_todos')->default(false);
            $table->boolean('alerta_vista')->default(false);
            $table->boolean('alerta_resuelta')->default(false);
            $table->timestamp('alerta_fecha')->useCurrent();
            $table->boolean('email_enviado')->default(false);
            $table->timestamp('email_fecha_envio')->nullable();
            
            $table->index('alerta_tipo');
            $table->index('alerta_vista');
            $table->index('alerta_producto_id');
            $table->index('alerta_para_todos');
            $table->index('alerta_resuelta');
            
            $table->foreign('alerta_producto_id')->references('producto_id')->on('pro_productos')->onDelete('cascade');
            $table->foreign('alerta_usuario_id')->references('user_id')->on('users')->onDelete('set null');
        });

        Schema::create('pro_alertas_roles', function (Blueprint $table) {
            $table->id('alerta_rol_id');
            $table->unsignedBigInteger('alerta_id');
            $table->unsignedInteger('rol_id'); // IMPORTANTE: unsignedInteger porque roles.id es INT
            
            $table->unique(['alerta_id', 'rol_id'], 'unique_alerta_rol');
            
            $table->foreign('alerta_id')->references('alerta_id')->on('pro_alertas')->onDelete('cascade');
            $table->foreign('rol_id')->references('id')->on('roles')->onDelete('cascade');
        });

        // ========================
        // CLIENTES
        // ========================

        Schema::create('pro_clientes', function (Blueprint $table) {
            $table->increments('cliente_id');
            $table->string('cliente_nombre1', 50);
            $table->string('cliente_nombre2', 50)->nullable();
            $table->string('cliente_apellido1', 50);
            $table->string('cliente_apellido2', 50)->nullable();
            $table->string('cliente_dpi', 20)->unique()->nullable();
            $table->string('cliente_nit', 20)->unique()->nullable();
            $table->string('cliente_direccion', 255)->nullable();
            $table->string('cliente_telefono', 30)->nullable();
            $table->string('cliente_correo', 150)->nullable();
            $table->string('cliente_nom_empresa', 250)->nullable(); 
            $table->string('cliente_nom_vendedor', 250)->nullable(); 
            $table->string('cliente_cel_vendedor', 250)->nullable(); 
            $table->string('cliente_ubicacion', 250)->nullable(); 
            $table->string('cliente_pdf_licencia', 250)->nullable(); 
            $table->unsignedBigInteger('cliente_user_id')->unique()->nullable();
            $table->integer('cliente_tipo');
            $table->integer('cliente_situacion')->default(1);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            
            $table->foreign('cliente_user_id')->references('user_id')->on('users')->onDelete('set null');
        });

        // ========================
        // VENTAS
        // ========================

        Schema::create('pro_ventas', function (Blueprint $table) {
            $table->increments('ven_id');
            $table->unsignedBigInteger('ven_user');
            $table->date('ven_fecha');
            $table->unsignedInteger('ven_cliente')->nullable();
            $table->decimal('ven_total_vendido', 10, 2);
            $table->decimal('ven_descuento', 10, 2)->default(0);
            $table->enum('ven_situacion', ['ACTIVA', 'ANULADA', 'PENDIENTE','RESERVADA'])->default('ACTIVA');
            $table->string('ven_observaciones', 200)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            
            $table->foreign('ven_user')->references('user_id')->on('users');
        });

        Schema::create('pro_detalle_ventas', function (Blueprint $table) {
            $table->increments('det_id');
            $table->unsignedInteger('det_ven_id');
            $table->unsignedBigInteger('det_producto_id');
            $table->integer('det_cantidad');
            $table->decimal('det_precio', 10, 2);
            $table->decimal('det_descuento', 10, 2)->default(0);
            $table->enum('det_situacion', ['ACTIVO', 'ANULADA', 'PENDIENTE'])->default('ACTIVO');
            $table->timestamp('created_at')->useCurrent();
            
            $table->foreign('det_ven_id')->references('ven_id')->on('pro_ventas')->onDelete('cascade');
            $table->foreign('det_producto_id')->references('producto_id')->on('pro_productos');
        });

        // ========================
        // PAGOS Y CUOTAS
        // ========================

        Schema::create('pro_pagos', function (Blueprint $table) {
            $table->increments('pago_id');
            $table->unsignedInteger('pago_venta_id')->unique();
            $table->decimal('pago_monto_total', 10, 2);
            $table->decimal('pago_monto_pagado', 10, 2)->default(0);
            $table->decimal('pago_monto_pendiente', 10, 2);
            $table->enum('pago_tipo_pago', ['UNICO', 'CUOTAS']);
            $table->integer('pago_cantidad_cuotas')->nullable();
            $table->decimal('pago_abono_inicial', 10, 2)->default(0);
            $table->enum('pago_estado', ['PENDIENTE', 'PARCIAL', 'COMPLETADO', 'VENCIDO'])->default('PENDIENTE');
            $table->date('pago_fecha_inicio');
            $table->date('pago_fecha_completado')->nullable();
            $table->text('pago_observaciones')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            
            $table->foreign('pago_venta_id')->references('ven_id')->on('pro_ventas')->onDelete('cascade');
        });

        Schema::create('pro_cuotas', function (Blueprint $table) {
            $table->increments('cuota_id');
            $table->unsignedInteger('cuota_control_id');
            $table->integer('cuota_numero');
            $table->decimal('cuota_monto', 10, 2);
            $table->date('cuota_fecha_vencimiento');
            $table->enum('cuota_estado', ['PENDIENTE', 'PAGADA', 'VENCIDA'])->default('PENDIENTE');
            $table->date('cuota_fecha_pago')->nullable();
            $table->string('cuota_observaciones', 200)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            
            $table->foreign('cuota_control_id')->references('pago_id')->on('pro_pagos')->onDelete('cascade');
        });

        Schema::create('pro_detalle_pagos', function (Blueprint $table) {
            $table->increments('det_pago_id');
            $table->unsignedInteger('det_pago_pago_id');
            $table->unsignedInteger('det_pago_cuota_id')->nullable();
            $table->date('det_pago_fecha');
            $table->decimal('det_pago_monto', 10, 2);
            $table->unsignedBigInteger('det_pago_metodo_pago');
            $table->unsignedBigInteger('det_pago_banco_id')->nullable();
            $table->string('det_pago_numero_autorizacion', 100)->nullable();
            $table->string('det_pago_imagen_boucher', 255)->nullable();
            $table->enum('det_pago_tipo_pago', ['ABONO_INICIAL', 'CUOTA', 'PAGO_UNICO', 'PAGO_ADELANTADO']);
            $table->enum('det_pago_estado', ['VALIDO', 'ANULADO', 'PENDIENTE_VALIDACION'])->default('VALIDO');
            $table->text('det_pago_observaciones')->nullable();
            $table->unsignedBigInteger('det_pago_usuario_registro');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            
            $table->foreign('det_pago_pago_id')->references('pago_id')->on('pro_pagos')->onDelete('cascade');
            $table->foreign('det_pago_cuota_id')->references('cuota_id')->on('pro_cuotas')->onDelete('set null');
            $table->foreign('det_pago_metodo_pago')->references('metpago_id')->on('pro_metodos_pago');
            $table->foreign('det_pago_usuario_registro')->references('user_id')->on('users');
        });

        Schema::create('pro_pagos_subidos', function (Blueprint $table) {
            $table->id('ps_id');
            $table->unsignedInteger('ps_venta_id');
            $table->unsignedBigInteger('ps_cliente_user_id')->nullable();
            $table->enum('ps_estado', ['PENDIENTE', 'PENDIENTE_VALIDACION', 'APROBADO', 'RECHAZADO'])->default('PENDIENTE');
            $table->enum('ps_canal', ['WEB', 'CSV', 'MANUAL'])->default('WEB');
            $table->dateTime('ps_fecha_comprobante')->nullable();
            $table->decimal('ps_monto_comprobante', 10, 2);
            $table->decimal('ps_monto_total_cuotas_front', 10, 2)->nullable();
            $table->decimal('ps_diferencia', 10, 2)->nullable();
            $table->unsignedBigInteger('ps_banco_id')->nullable();
            $table->string('ps_banco_nombre', 64)->nullable();
            $table->string('ps_referencia', 64)->nullable();
            $table->string('ps_concepto', 255)->nullable();
            $table->json('ps_cuotas_json')->nullable();
            $table->string('ps_imagen_path', 255)->nullable();
            $table->unsignedBigInteger('ps_validado_por')->nullable();
            $table->unsignedBigInteger('ps_revisado_por')->nullable();
            $table->dateTime('ps_revisado_en')->nullable();
            $table->string('ps_notas_revision', 300)->nullable();
            $table->dateTime('ps_fecha_validacion')->nullable();
            $table->text('ps_observaciones')->nullable();
            $table->string('ps_checksum', 64)->unique()->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            
            $table->index(['ps_venta_id', 'ps_estado']);
            $table->index('ps_referencia');
            $table->index('ps_banco_id');
            
            $table->foreign('ps_venta_id')->references('ven_id')->on('pro_ventas')->onDelete('cascade');
            $table->foreign('ps_cliente_user_id')->references('user_id')->on('users')->onDelete('set null');
            $table->foreign('ps_validado_por')->references('user_id')->on('users')->onDelete('set null');
        });

        // ========================
        // COMISIONES
        // ========================

        Schema::create('pro_porcentaje_vendedor', function (Blueprint $table) {
            $table->increments('porc_vend_id');
            $table->unsignedBigInteger('porc_vend_user_id');
            $table->unsignedInteger('porc_vend_ven_id');
            $table->decimal('porc_vend_porcentaje', 5, 2);
            $table->decimal('porc_vend_cantidad_ganancia', 10, 2);
            $table->decimal('porc_vend_monto_base', 10, 2);
            $table->date('porc_vend_fecha_asignacion');
            $table->enum('porc_vend_estado', ['PENDIENTE', 'PAGADO', 'CANCELADO'])->default('PENDIENTE');
            $table->date('porc_vend_fecha_pago')->nullable();
            $table->enum('porc_vend_situacion', ['ACTIVO', 'INACTIVO'])->default('ACTIVO');
            $table->string('porc_vend_observaciones', 200)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            
            $table->unique(['porc_vend_user_id', 'porc_vend_ven_id'], 'unique_vendedor_venta');
            
            $table->foreign('porc_vend_user_id')->references('user_id')->on('users');
            $table->foreign('porc_vend_ven_id')->references('ven_id')->on('pro_ventas')->onDelete('cascade');
        });

        // ========================
        // CAJA
        // ========================

        Schema::create('caja_saldos', function (Blueprint $table) {
            $table->increments('caja_saldo_id');
            $table->unsignedBigInteger('caja_saldo_metodo_pago');
            $table->char('caja_saldo_moneda', 3)->default('GTQ');
            $table->decimal('caja_saldo_monto_actual', 14, 2)->default(0);
            $table->dateTime('caja_saldo_actualizado')->useCurrent()->useCurrentOnUpdate();
            
            $table->unique(['caja_saldo_metodo_pago', 'caja_saldo_moneda'], 'uk_caja_saldo');
            
            $table->foreign('caja_saldo_metodo_pago')->references('metpago_id')->on('pro_metodos_pago');
        });

        Schema::create('cja_historial', function (Blueprint $table) {
            $table->increments('cja_id');
            $table->enum('cja_tipo', ['VENTA', 'IMPORTACION', 'EGRESO', 'DEPOSITO', 'AJUSTE_POS']);
            $table->unsignedInteger('cja_id_venta')->nullable();
            $table->integer('cja_id_import')->nullable();
            $table->unsignedBigInteger('cja_usuario');
            $table->decimal('cja_monto', 10, 2);
            $table->date('cja_fecha');
            $table->unsignedBigInteger('cja_metodo_pago');
            $table->unsignedBigInteger('cja_tipo_banco')->nullable();
            $table->string('cja_no_referencia', 100)->nullable();
            $table->enum('cja_situacion', ['ACTIVO', 'ANULADA', 'PENDIENTE'])->default('ACTIVO');
            $table->string('cja_observaciones', 200)->nullable();
            $table->timestamp('created_at')->useCurrent();
            
            $table->foreign('cja_usuario')->references('user_id')->on('users');
            $table->foreign('cja_id_venta')->references('ven_id')->on('pro_ventas')->onDelete('set null');
            $table->foreign('cja_metodo_pago')->references('metpago_id')->on('pro_metodos_pago');
        });

        Schema::create('pro_estados_cuenta', function (Blueprint $table) {
            $table->increments('ec_id');
            $table->unsignedBigInteger('ec_banco_id')->nullable();
            $table->string('ec_archivo', 255);
            $table->json('ec_headers')->nullable();
            $table->longText('ec_rows')->nullable();
            $table->date('ec_fecha_ini')->nullable();
            $table->date('ec_fecha_fin')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        // ========================
        // USUARIOS - UBICACIONES Y VISITAS
        // ========================

        Schema::create('users_ubicaciones', function (Blueprint $table) {
            $table->id('ubi_id');
            $table->unsignedInteger('ubi_user');
            $table->decimal('ubi_latitud', 9, 6);
            $table->decimal('ubi_longitud', 9, 6);
            $table->string('ubi_descripcion', 255)->nullable();
            $table->string('ubi_foto', 255)->nullable();
            $table->timestamps();
            
            $table->foreign('ubi_user')->references('cliente_id')->on('pro_clientes')->onDelete('cascade');
        });

        Schema::create('users_visitas', function (Blueprint $table) {
            $table->id('visita_id');
            $table->unsignedInteger('visita_user');
            $table->dateTime('visita_fecha')->nullable();
            $table->integer('visita_estado');
            $table->decimal('visita_venta', 10, 2);
            $table->text('visita_descripcion')->nullable();
            $table->timestamps();
            
            $table->foreign('visita_user')->references('cliente_id')->on('pro_clientes')->onDelete('cascade');
        });

        Schema::create('users_historial_visitas', function (Blueprint $table) {
            $table->id('hist_id');
            $table->unsignedBigInteger('hist_visita_id');
            $table->dateTime('hist_fecha_actualizacion');
            $table->integer('hist_estado_anterior')->nullable();
            $table->integer('hist_estado_nuevo');
            $table->decimal('hist_total_venta_anterior', 10, 2)->nullable();
            $table->decimal('hist_total_venta_nuevo', 10, 2)->nullable();
            $table->text('hist_descripcion')->nullable();
            $table->timestamps();
            
            $table->foreign('hist_visita_id')->references('visita_id')->on('users_visitas');
        });
    }

    public function down(): void
    {
        // Orden inverso para eliminar correctamente
        Schema::dropIfExists('users_historial_visitas');
        Schema::dropIfExists('users_visitas');
        Schema::dropIfExists('users_ubicaciones');
        Schema::dropIfExists('pro_estados_cuenta');
        Schema::dropIfExists('cja_historial');
        Schema::dropIfExists('caja_saldos');
        Schema::dropIfExists('pro_porcentaje_vendedor');
        Schema::dropIfExists('pro_pagos_subidos');
        Schema::dropIfExists('pro_detalle_pagos');
        Schema::dropIfExists('pro_cuotas');
        Schema::dropIfExists('pro_pagos');
        Schema::dropIfExists('pro_detalle_ventas');
        Schema::dropIfExists('pro_ventas');
        Schema::dropIfExists('pro_clientes');
        Schema::dropIfExists('pro_alertas_roles');
        Schema::dropIfExists('pro_alertas');
        Schema::dropIfExists('pro_stock_actual');
        Schema::dropIfExists('pro_movimientos');
        Schema::dropIfExists('pro_promociones');
        Schema::dropIfExists('pro_precios');
        Schema::dropIfExists('pro_lotes');
        Schema::dropIfExists('pro_series_productos');
        Schema::dropIfExists('pro_productos_fotos');
        Schema::dropIfExists('pro_licencia_asignacion_producto');
        Schema::dropIfExists('pro_productos');
        Schema::dropIfExists('pro_inventario_armas');
        Schema::dropIfExists('pro_inventario_modelos');
        Schema::dropIfExists('pro_documentacion_lic_import');
        Schema::dropIfExists('pro_comprobantes_pago_licencias');
        Schema::dropIfExists('pro_pagos_lic_metodos');
        Schema::dropIfExists('pro_pagos_licencias');
        Schema::dropIfExists('pro_armas_licenciadas');
        Schema::dropIfExists('pro_licencias_para_importacion');
        Schema::dropIfExists('pro_empresas_de_importacion');
        Schema::dropIfExists('pro_subcategorias');
        Schema::dropIfExists('pro_categorias');
        Schema::dropIfExists('pro_calibres');
        Schema::dropIfExists('pro_modelo');
        Schema::dropIfExists('pro_marcas');
        Schema::dropIfExists('pro_unidades_medida');
        Schema::dropIfExists('pro_paises');
        Schema::dropIfExists('pro_metodos_pago');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
        Schema::dropIfExists('roles');
    }
};