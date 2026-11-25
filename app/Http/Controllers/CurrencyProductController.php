<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CurrencyProductController extends Controller
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

        // Se obtiene el producto junto con su divisa base
        $product = Product::with('currency')->find($id);

        // Se pagina la relación ordenando por el símbolo de la divisa
        $prices = $product->currencies()
            ->orderBy('currencies.symbol', 'asc')
            ->paginate($request->paginate);


        if ($prices->total() === 0) {
            return response()->json([
                'message' => 'Este producto aún no tiene lista de precios multimoneda asignado.',
                'base_price' => [
                    'product_id' => $product->id,
                    'price'      => (float) $product->price,
                    'currency'   => $product->currency,
                ],
                'data' => []
            ], 200);
        }

        if ($prices->currentPage() > $prices->lastPage()) {
            return response()->json([
                'message' => 'La página solicitada no existe. La última página disponible es la ' . $prices->lastPage() . '.'
            ], 400);
        }


        // Se transforma cada registro paginado para darle un formato más limpio
        $prices->through(function ($currency) use ($product) {
            return [
                'id'            => $currency->pivot->id,
                'product_id'    => $product->id,
                'currency_id'   => $currency->id,
                'currency_name' => $currency->name,
                'symbol'        => $currency->symbol,
                'exchange_rate' => (float) $currency->exchange_rate,
                'price'         => (float) $currency->pivot->price,
                'created_at'    => $currency->pivot->created_at,
                'updated_at'    => $currency->pivot->updated_at,
            ];
        });

        // Se devuelve el precio base aparte y la lista paginada limpia
        return response()->json([
            'base_price' => [
                'product_id'    => $product->id,
                'product_name'  => $product->name,
                'price'         => (float) $product->price,
                'currency_id'   => $product->currency->id,
                'currency_name' => $product->currency->name,
                'symbol'        => $product->currency->symbol,
            ],
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

        $currencyId = (int) $request->currency_id;
        $product = Product::find($id);

        // 1. Se verifica si coincide con la moneda base original del producto
        if ($product->currency_id === $currencyId) {
            return response()->json([
                'message' => 'No se puede registrar este precio. La divisa seleccionada coincide con la moneda base original del producto.'
            ], 400);
        }

        // 2. Se verifica si ya existe en la tabla pivote usando la relación
        $priceExists = $product->currencies()
            ->where('currency_id', $currencyId)
            ->exists();

        if ($priceExists) {
            return response()->json([
                'message' => 'Ya existe un precio registrado para este producto en la divisa seleccionada.'
            ], 400);
        }

        // 3. Se guarda en la tabla pivote usando attach()
        $product->currencies()->attach($currencyId, [
            'price' => $request->price
        ]);

        // 4. Se recupera el registro recién creado con su información de divisa y pivote
        $createdCurrency = $product->currencies()
            ->where('currency_id', $currencyId)
            ->first();

        return response()->json([
            'message' => '¡Precio multimoneda asignado exitosamente al producto!',
            'data' => [
                'id'            => $createdCurrency->pivot->id,
                'product_id'    => $product->id,
                'currency_id'   => $createdCurrency->id,
                'currency_name' => $createdCurrency->name,
                'symbol'        => $createdCurrency->symbol,
                'exchange_rate' => (float) $createdCurrency->exchange_rate,
                'price'         => (float) $createdCurrency->pivot->price,
                'created_at'    => $createdCurrency->pivot->created_at,
                'updated_at'    => $createdCurrency->pivot->updated_at,
            ]
        ], 201);
    }
}
