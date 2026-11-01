<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductPrice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $messages = [
            'paginate.required' => 'Debe ingresar un valor de paginación para poder paginar la lista de precios.',
            'paginate.integer'  => 'El valor de la paginación debe ser un número entero.',
            'paginate.min'      => 'El valor de la paginación debe ser un número entero positivo mayor a cero.',
        ];

        $validator = Validator::make(['paginate' => $request->paginate], [
            'paginate' => 'bail|required|integer|min:1',
        ], $messages);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first()
            ], 400);
        }

        // $products = Product::with('currency')
        //     ->select('products.*')
        //     ->orderBy('products.name', 'asc')
        //     ->get();

        // Con paginación
        $products = Product::with('currency')
            ->select('products.*')
            ->orderBy('products.name', 'asc')
            ->paginate($request->paginate);

        if ($products->total() === 0) {
            return response()->json([
                'message' => 'No se encontraron productos almacenados en el sistema.',
                'data' => []
            ], 200);
        }

        if ($products->currentPage() > $products->lastPage()) {
            return response()->json([
                'message' => 'La página solicitada no existe. La última página disponible es la ' . $products->lastPage() . '.'
            ], 400);
        }

        return response()->json([
            'data' => $products
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $messages = [
            'required' => 'Por favor, llene los campos necesarios para poder ingresar el producto.',

            'currency_id.integer' => 'El valor del campo "currency_id" debe ser un número entero.',
            'currency_id.exists'  => 'La divisa seleccionada no existe en nuestra base de datos.',

            'numeric' => 'Los valores de costos y precios deben ser números decimales o enteros.',
            'min'     => 'Los valores económicos no pueden ser negativos.',
            'max'     => 'Ninguno de los importes puede ser mayor a 999,999.99.',
            'decimal' => 'Los valores financieros deben tener como máximo 2 decimales.',
        ];

        $validator = Validator::make($request->all(), [
            'name'               => 'bail|required|string|max:255',
            'price'              => 'bail|required|numeric|min:0|max:999999.99|decimal:0,2',
            'currency_id'        => 'bail|required|integer|min:1|exists:currencies,id',
            'tax_cost'           => 'bail|required|numeric|min:0|max:999999.99|decimal:0,2',
            'manufacturing_cost' => 'bail|required|numeric|min:0|max:999999.99|decimal:0,2',
            'description'        => 'nullable',
        ], $messages);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first()
            ], 400);
        }

        // VERIFICACIÓN DE LA EXISTENCIA DEL NOMBRE DEL PRODUCTO A INGRESAR EN LA BASE DE DATOS
        $inputName = trim((string) $request->input('name'));

        // Función de para limpiar las tildes de la palabra o las palabras
        $stringClean = str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'Á', 'É', 'Í', 'Ó', 'Ú'],
            ['a', 'e', 'i', 'o', 'u', 'a', 'e', 'i', 'o', 'u'],
            $inputName
        );

        // En SQLite, anidamos REPLACE() para limpiar las tildes en la columna de la base de datos
        $nameExists = Product::whereRaw("
            REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(name, 'á', 'a'), 'é', 'e'), 'í', 'i'), 'ó', 'o'), 'ú', 'u') 
            LIKE ?
        ", [strtolower($stringClean)])
            ->exists();

        if ($nameExists) {
            return response()->json([
                'message' => 'Este producto ya se encuentra almacenado.'
            ], 400);
        }
        // FIN VERIFICACIÓN DE LA EXISTENCIA DEL NOMBRE DEL PRODUCTO A INGRESAR EN LA BASE DE DATOS

        $product = Product::create($validator->validated());

        return response()->json([
            'message' => '¡Producto almacenado exitosamente!',
            'data' => $product
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $messages = [
            'id.required' => 'Debe ingresar un id para poder realizar la búsqueda del producto.',
            'id.integer'  => 'El valor del id debe ser un número entero.',
            'id.min'      => 'El valor del id debe ser un número entero positivo mayor a cero.',
            'id.exists'   => 'El producto solicitado no existe en nuestra base de datos.',
        ];

        $validator = Validator::make(['id' => $id], [
            'id' => 'bail|required|integer|min:1|exists:products,id',
        ], $messages);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first()
            ], 400);
        }

        $product = Product::with('currency')->find($id);

        return response()->json([
            'data' => $product
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'message' => 'El producto que intenta actualizar no existe.'
            ], 404);
        }

        $messages = [
            'required'            => 'Por favor, llene los campos necesarios para poder ingresar el producto.',

            'currency_id.integer' => 'El valor del campo "currency_id" debe ser un número entero.',
            'currency_id.exists'  => 'La divisa seleccionada no existe en nuestra base de datos.',

            'numeric'             => 'Los valores de costos y precios deben ser números decimales o enteros.',
            'min'                 => 'Los valores económicos no pueden ser negativos.',
            'max'                 => 'Ninguno de los importes puede ser mayor a 999,999.99.',
            'decimal'             => 'Los valores financieros deben tener como máximo 2 decimales.',
        ];

        $validator = Validator::make($request->all(), [
            'name'               => 'bail|required|string|max:255',
            'price'              => 'bail|required|numeric|min:0|max:999999.99|decimal:0,2',
            'currency_id'        => 'bail|required|integer|min:1|exists:currencies,id',
            'tax_cost'           => 'bail|required|numeric|min:0|max:999999.99|decimal:0,2',
            'manufacturing_cost' => 'bail|required|numeric|min:0|max:999999.99|decimal:0,2',
            'description'        => 'nullable',
        ], $messages);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first()
            ], 400);
        }

        // VERIFICACIÓN DE LA EXISTENCIA DEL NOMBRE DEL PRODUCTO INGRESADO EN LA BASE DE DATOS
        $inputName = trim((string) $request->input('name'));

        // Función de para limpiar las tildes de la palabra o las palabras
        $stringClean = str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'Á', 'É', 'Í', 'Ó', 'Ú'],
            ['a', 'e', 'i', 'o', 'u', 'a', 'e', 'i', 'o', 'u'],
            $inputName
        );

        // En SQLite, anidamos REPLACE() para limpiar las tildes en la columna de la base de datos
        $nameTakenByOther = Product::whereRaw("
            REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(name, 'á', 'a'), 'é', 'e'), 'í', 'i'), 'ó', 'o'), 'ú', 'u') 
            LIKE ?
        ", [strtolower($stringClean)])
            ->where('id', '!=', (int) $id) // Se excluye el propio registro de la búsqueda
            ->exists();

        if ($nameTakenByOther) {
            return response()->json([
                'message' => 'No se puede actualizar. El nombre ya pertenece a otro producto almacenado.'
            ], 400);
        }
        // FIN VERIFICACIÓN DE LA EXISTENCIA DEL NOMBRE DEL PRODUCTO INGRESADO EN LA BASE DE DATOS

        $product->update($validator->validated());

        return response()->json([
            'message' => '¡Producto actualizado exitosamente!',
            'data' => $product
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $messages = [
            'id.required' => 'Debe ingresar un id para poder realizar la eliminación del producto.',
            'id.integer'  => 'El valor del id debe ser un número entero.',
            'id.min'      => 'El valor del id debe ser un número entero positivo mayor a cero.',
            'id.exists'   => 'El producto solicitado no existe en nuestra base de datos.',
        ];

        $validator = Validator::make(['id' => $id], [
            'id' => 'bail|required|integer|min:1|exists:products,id',
        ], $messages);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first()
            ], 400);
        }

        $product = Product::find($id);

        try {
            DB::transaction(function () use ($id, $product) {
                // Primero se elimina ese producto asociado a otras divisas en la tabla "products_prices"
                ProductPrice::where('product_id', (int) $id)
                    ->delete();

                // Luego se elimina el producto en sí en la tabla "products"
                $product->delete();
            });

            return response()->json([
                'message' => '¡Producto eliminado exitosamente!'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'No se pudo realizar la eliminación del producto debido a un error interno en el sistema.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
