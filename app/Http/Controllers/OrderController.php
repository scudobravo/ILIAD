<?php

// declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

final class OrderController extends Controller
{
    // Metodo per ottenere la lista degli ordini, con filtro opzionale per stato
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'status' => ['nullable', Rule::in(['pending', 'completed', 'cancelled'])],
        ]);

        $ordersQuery = Order::query();
        
        if ($request->has('status')) {
            $ordersQuery->where('status', $request->status);
        }

        $orders = $ordersQuery->with('items.product')->get();

        return response()->json(['data' => $orders]);
    }

    // Metodo per creare un nuovo ordine
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $order = DB::transaction(function () use ($request) {
                // Creiamo l'ordine
                $order = Order::create([
                    'order_number' => 'ORD-' . time(),
                    'status' => 'pending'
                ]);

                // Aggiungiamo gli articoli e aggiorniamo il magazzino
                foreach ($request->items as $item) {
                    $product = Product::findOrFail($item['product_id']);
                    $product->decrement('stock_quantity', $item['quantity']);
                    
                    $order->items()->create([
                        'product_id' => $product->id,
                        'quantity' => $item['quantity'],
                        'price' => $product->price
                    ]);
                }

                return $order->load('items.product');
            });

            return response()->json(['data' => $order]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    // Metodo per aggiornare un ordine esistente
    public function update(Request $request, Order $order): JsonResponse
    {
        $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $updatedOrder = DB::transaction(function () use ($request, $order) {
                // Creiamo un array associativo degli item richiesti per facile accesso
                $requestedItems = collect($request->items)->keyBy('product_id');
                
                // Gestiamo gli articoli esistenti
                foreach ($order->items as $existingItem) {
                    if ($requestedItems->has($existingItem->product_id)) {
                        // L'articolo esiste ancora nella richiesta, aggiorniamo la quantità
                        $requestedQuantity = $requestedItems->get($existingItem->product_id)['quantity'];
                        $quantityDiff = $requestedQuantity - $existingItem->quantity;
                        
                        if ($quantityDiff !== 0) {
                            // Aggiorniamo il magazzino solo se la quantità è cambiata
                            $existingItem->product->decrement('stock_quantity', $quantityDiff);
                            $existingItem->update(['quantity' => $requestedQuantity]);
                        }
                        
                        // Rimuoviamo l'item dalla collection per tenere traccia dei nuovi
                        $requestedItems->forget($existingItem->product_id);
                    } else {
                        // L'articolo non è più presente, ripristiniamo il magazzino e lo eliminiamo
                        $existingItem->product->increment('stock_quantity', $existingItem->quantity);
                        $existingItem->delete();
                    }
                }
                
                // Aggiungiamo i nuovi articoli
                foreach ($requestedItems as $item) {
                    $product = Product::findOrFail($item['product_id']);
                    $product->decrement('stock_quantity', $item['quantity']);
                    
                    $order->items()->create([
                        'product_id' => $product->id,
                        'quantity' => $item['quantity'],
                        'price' => $product->price
                    ]);
                }

                return $order->load('items.product');
            });

            return response()->json(['data' => $updatedOrder]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    // Metodo per aggiornare lo stato di un ordine
    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        $request->validate([
            'status' => ['required', Rule::in(['pending', 'completed', 'cancelled'])],
        ]);

        $order->update(['status' => $request->status]);
        
        return response()->json(['data' => $order->load('items.product')]);
    }

    // Metodo per eliminare un ordine
    public function destroy(Order $order): JsonResponse
    {
        try {
            DB::transaction(function () use ($order) {
                // Ripristiniamo il magazzino
                foreach ($order->items as $item) {
                    $item->product->increment('stock_quantity', $item->quantity);
                }

                $order->delete();
            });

            return response()->json(['message' => 'Ordine eliminato con successo']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
} 