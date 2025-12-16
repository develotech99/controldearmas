<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Subcategoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CategoriasController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Categoria::with(['subcategorias' => function($query) {
            $query->orderBy('subcategoria_nombre');
        }]);

        // Filtros
        if ($request->filled('buscar')) {
            $query->buscar($request->buscar);
        }

        if ($request->filled('estado')) {
            $query->where('categoria_situacion', $request->estado);
        }

        $categorias = $query->orderBy('categoria_nombre')->paginate(10);
        
        // Para uso en JavaScript
        $categoriasData = $categorias->getCollection()->map(function($categoria) {
            return [
                'categoria_id' => $categoria->categoria_id,
                'categoria_nombre' => $categoria->categoria_nombre,
                'categoria_situacion' => $categoria->categoria_situacion,
                'subcategorias_count' => $categoria->subcategorias->count(),
                'subcategorias_activas' => $categoria->subcategorias_activas,
                'created_at' => $categoria->created_at,
                'subcategorias' => $categoria->subcategorias->map(function($sub) {
                    return [
                        'subcategoria_id' => $sub->subcategoria_id,
                        'subcategoria_nombre' => $sub->subcategoria_nombre,
                        'subcategoria_situacion' => $sub->subcategoria_situacion,
                        'created_at' => $sub->created_at
                    ];
                })
            ];
        });

        return view('categorias.index', compact('categorias', 'categoriasData'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'categoria_nombre' => 'required|string|max:100',
          
        ], [
            'categoria_nombre.required' => 'El nombre de la categoría es obligatorio',
            'categoria_nombre.max' => 'El nombre no puede exceder 100 caracteres',
          
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $categoria = Categoria::create($request->only(['categoria_nombre', 'categoria_situacion']));
            
            return response()->json([
                'success' => true,
                'message' => 'Categoría creada correctamente',
                'data' => $categoria
            ]);
        } catch (\Exception $e) {
            $msg = 'Error al crear la categoría';
            if (str_contains($e->getMessage(), 'Data too long')) {
                $msg = 'Uno de los campos excede la longitud permitida. Verifique los datos.';
            } elseif (str_contains($e->getMessage(), 'Duplicate entry')) {
                $msg = 'Ya existe una categoría con ese nombre.';
            } elseif (config('app.debug')) {
                $msg .= ': ' . $e->getMessage();
            }

            return response()->json([
                'success' => false,
                'message' => $msg
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Categoria $categoria)
    {
        $validator = Validator::make($request->all(), [
            'categoria_nombre' => 'required|string|max:100',

        ], [
            'categoria_nombre.required' => 'El nombre de la categoría es obligatorio',
            'categoria_nombre.max' => 'El nombre no puede exceder 100 caracteres',
          
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $categoria->update($request->only(['categoria_nombre', 'categoria_situacion']));
            
            return response()->json([
                'success' => true,
                'message' => 'Categoría actualizada correctamente',
                'data' => $categoria
            ]);
        } catch (\Exception $e) {
            $msg = 'Error al actualizar la categoría';
            if (str_contains($e->getMessage(), 'Data too long')) {
                $msg = 'Uno de los campos excede la longitud permitida. Verifique los datos.';
            } elseif (str_contains($e->getMessage(), 'Duplicate entry')) {
                $msg = 'Ya existe una categoría con ese nombre.';
            } elseif (config('app.debug')) {
                $msg .= ': ' . $e->getMessage();
            }

            return response()->json([
                'success' => false,
                'message' => $msg
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Categoria $categoria)
    {
        try {
            // Verificar si tiene subcategorías
            if ($categoria->subcategorias()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar la categoría porque tiene subcategorías asociadas'
                ], 400);
            }

            $categoria->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Categoría eliminada correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la categoría'
            ], 500);
        }
    }

    /**
     * Store a newly created subcategory.
     */
    public function storeSubcategoria(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'subcategoria_nombre' => 'required|string|max:100',
            'subcategoria_idcategoria' => 'required|exists:pro_categorias,categoria_id',
        ], [
            'subcategoria_nombre.required' => 'El nombre de la subcategoría es obligatorio',
            'subcategoria_nombre.max' => 'El nombre no puede exceder 100 caracteres',
            'subcategoria_idcategoria.required' => 'La categoría es obligatoria',
            'subcategoria_idcategoria.exists' => 'La categoría seleccionada no existe',
        ]);

        // Validar unicidad por categoría
        $exists = Subcategoria::where('subcategoria_nombre', $request->subcategoria_nombre)
                             ->where('subcategoria_idcategoria', $request->subcategoria_idcategoria)
                             ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Ya existe una subcategoría con ese nombre en esta categoría'
            ], 422);
        }

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $subcategoria = Subcategoria::create($request->only([
                'subcategoria_nombre', 
                'subcategoria_idcategoria', 
                'subcategoria_situacion'
            ]));
            
            $subcategoria->load('categoria');
            
            return response()->json([
                'success' => true,
                'message' => 'Subcategoría creada correctamente',
                'data' => $subcategoria
            ]);
        } catch (\Exception $e) {
            $msg = 'Error al crear la subcategoría';
            if (str_contains($e->getMessage(), 'Data too long')) {
                $msg = 'Uno de los campos excede la longitud permitida. Verifique los datos.';
            } elseif (str_contains($e->getMessage(), 'Duplicate entry')) {
                $msg = 'Ya existe una subcategoría con ese nombre en esta categoría.';
            } elseif (config('app.debug')) {
                $msg .= ': ' . $e->getMessage();
            }

            return response()->json([
                'success' => false,
                'message' => $msg
            ], 500);
        }
    }

    /**
     * Update the specified subcategory.
     */
    public function updateSubcategoria(Request $request, Subcategoria $subcategoria)
    {
        $validator = Validator::make($request->all(), [
            'subcategoria_nombre' => 'required|string|max:100',
            'subcategoria_idcategoria' => 'required|exists:pro_categorias,categoria_id',
        ], [
            'subcategoria_nombre.required' => 'El nombre de la subcategoría es obligatorio',
            'subcategoria_nombre.max' => 'El nombre no puede exceder 100 caracteres',
            'subcategoria_idcategoria.required' => 'La categoría es obligatoria',
            'subcategoria_idcategoria.exists' => 'La categoría seleccionada no existe',
        ]);

        // Validar unicidad por categoría excluyendo el actual
        $exists = Subcategoria::where('subcategoria_nombre', $request->subcategoria_nombre)
                             ->where('subcategoria_idcategoria', $request->subcategoria_idcategoria)
                             ->where('subcategoria_id', '!=', $subcategoria->subcategoria_id)
                             ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Ya existe una subcategoría con ese nombre en esta categoría'
            ], 422);
        }

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $subcategoria->update($request->only([
                'subcategoria_nombre', 
                'subcategoria_idcategoria', 
                'subcategoria_situacion'
            ]));
            
            $subcategoria->load('categoria');
            
            return response()->json([
                'success' => true,
                'message' => 'Subcategoría actualizada correctamente',
                'data' => $subcategoria
            ]);
        } catch (\Exception $e) {
            $msg = 'Error al actualizar la subcategoría';
            if (str_contains($e->getMessage(), 'Data too long')) {
                $msg = 'Uno de los campos excede la longitud permitida. Verifique los datos.';
            } elseif (str_contains($e->getMessage(), 'Duplicate entry')) {
                $msg = 'Ya existe una subcategoría con ese nombre en esta categoría.';
            } elseif (config('app.debug')) {
                $msg .= ': ' . $e->getMessage();
            }

            return response()->json([
                'success' => false,
                'message' => $msg
            ], 500);
        }
    }

    /**
     * Remove the specified subcategory.
     */
    public function destroySubcategoria(Subcategoria $subcategoria)
    {
        try {
            // Verificar si hay productos asociados
            if ($subcategoria->productos()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar la subcategoría porque tiene productos asociados'
                ], 400);
            }

            $subcategoria->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Subcategoría eliminada correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la subcategoría: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get categories for select options
     */
    public function getCategoriasActivas()
    {
        $categorias = Categoria::activas()
                              ->orderBy('categoria_nombre')
                              ->get(['categoria_id', 'categoria_nombre']);
        
        return response()->json([
            'success' => true,
            'data' => $categorias
        ]);
    }
}