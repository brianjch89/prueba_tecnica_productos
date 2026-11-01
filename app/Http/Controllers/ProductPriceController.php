<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductPrice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductPriceController extends Controller
{

    public function getPricesByProducts(Request $request, string $id)
    {
        $messages = [
            'paginate.required' => 'Debe ingresar un valor de paginación para poder paginar la lista de precios.',
            'paginate.integer'  => 'El valor de la paginación debe ser un número entero.',
            'paginate.min'      => 'El valor de la paginación debe ser un número entero positivo mayor a cero.',

            'id.required' => 'Debe ingresar un id para poder realizar la búsqueda de precios.',
            'id.integer'  => 'El valor del id debe ser un número entero.',
            'id.min'      => 'El valor del id debe ser un número entero positivo mayor a cero.',
            'id.exists'   => 'El producto solicitado no existe en nuestra base de datos.',
        ];

        $validator = Validator::make(['id' => $id, 'paginate' => $request->paginate], [
            'id'       => 'bail|required|integer|min:1|exists:products,id',
            'paginate' => 'bail|required|integer|min:1',
        ], $messages);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first()
            ], 400);
        }

        // $prices = ProductPrice::with(['currency', 'product.currency'])
        //     ->select('products_prices.*')
        //     ->join('currencies as c1', 'products_prices.currency_id', '=', 'c1.id')
        //     ->where('products_prices.product_id', (int) $id)
        //     ->orderBy('c1.symbol', 'asc')
        //     ->get();

        // Con paginación
        $prices = ProductPrice::with(['currency', 'product.currency'])
            ->select('products_prices.*') // Evita que los IDs de los joins sobreescriban el ID de "product_prices"
            ->join('currencies as c1', 'products_prices.currency_id', '=', 'c1.id')
            ->where('products_prices.product_id', (int) $id)
            ->orderBy('c1.symbol', 'asc') // Alfabéticamente por el símbolo de la moneda del precio
            ->paginate($request->paginate);

        if ($prices->total() === 0) {
            return response()->json([
                'message' => 'Este producto aún no tiene lista de precios multimoneda asignado.',
                'data' => []
            ], 200);
        }

        if ($prices->currentPage() > $prices->lastPage()) {
            return response()->json([
                'message' => 'La página solicitada no existe. La última página disponible es la ' . $prices->lastPage() . '.'
            ], 400);
        }

        return response()->json([
            'data' => $prices
        ], 200);
    }

    public function storePriceForAProduct(Request $request, string $id)
    {
        $messages = [
            'required' => 'Por favor, ingrese todos los campos necesarios para poder almacenar el nuevo precio para el producto.',

            'product_id.exists'  => 'El producto seleccionado no existe en nuestra base de datos.',
            'currency_id.exists' => 'La divisa seleccionada no existe en nuestra base de datos.',

            'min'      => 'Los identificadores deben ser números enteros positivos mayores que cero.',
            'integer'  => 'Los identificadores deben ser números enteros.',

            'price.numeric' => 'El precio debe ser un número decimal o entero.',
            'price.min'     => 'El precio del producto no puede ser negativo.',
            'price.max'     => 'El importe del precio no puede ser mayor a 999,999.99.',
            'price.decimal' => 'El precio debe tener como máximo 2 decimales.',
        ];

        $validator = Validator::make([
            'product_id'  => $id,
            'currency_id' => $request->currency_id,
            'price'       => $request->price
        ], [
            'product_id'  => 'bail|required|integer|min:1|exists:products,id',
            'currency_id' => 'bail|required|integer|min:1|exists:currencies,id',
            'price'       => 'bail|required|numeric|min:0|max:999999.99|decimal:0,2',
        ], $messages);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first()
            ], 400);
        }

        $productId = (int) $id;
        $currencyId = (int) $request->currency_id;

        // 1. Se verifica si esa combinación de "product_id" y "currency_id" existe en un registro de la tabla "products_prices"
        $priceExistsInList = ProductPrice::where('product_id', $productId)
            ->where('currency_id', $currencyId)
            ->exists();

        if ($priceExistsInList) {
            return response()->json([
                'message' => 'Ya existe un precio registrado para este producto en la divisa seleccionada.'
            ], 400);
        }

        // 2. Se verifica si el producto ya tiene una divisa original de nacimiento
        $productBaseExistInList = Product::where('id', $productId)
            ->where('currency_id', $currencyId)
            ->exists();

        if ($productBaseExistInList) {
            return response()->json([
                'message' => 'No se puede registrar este precio. La divisa seleccionada coincide con la moneda base original del producto.'
            ], 400);
        }

        // Si supera ambas verificaciones, guardamos de forma 100% segura
        $ProductPrice = ProductPrice::create([
            'product_id' => $productId,
            'currency_id' => $currencyId,
            'price' => $request->price,
        ]);

        return response()->json([
            'message' => '¡Precio multimoneda asignado exitosamente al producto!',
            'data' => $ProductPrice
        ], 201);
    }
}
