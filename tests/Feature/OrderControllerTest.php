<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OrderControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test per il metodo index senza filtro.
     *
     * Questo test:
     * - Crea un prodotto e un ordine (con un articolo associato).
     * - Effettua una richiesta GET a /api/v1/orders.
     * - Verifica che la risposta sia 200 e che la struttura JSON includa
     *   i campi attesi (ordine e, all'interno, gli items con il relativo prodotto).
     */
    public function testIndexReturnsAllOrders()
    {
        // Creiamo un prodotto di esempio.
        $product = Product::factory()->create([
            'stock_quantity' => 50,
            'price'          => 100,
        ]);

        // Creiamo un ordine con status 'pending'.
        $order = Order::factory()->create(['status' => 'pending']);

        // Creiamo un articolo per l'ordine.
        $order->items()->create([
            'product_id' => $product->id,
            'quantity'   => 2,
            'price'      => $product->price,
        ]);

        $response = $this->getJson('/api/v1/orders');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         '*' => [
                             'id',
                             'order_number',
                             'status',
                             'items' => [
                                 '*' => [
                                     'id',
                                     'product_id',
                                     'quantity',
                                     'price',
                                     'product' => [
                                         'id',
                                         // eventuali altri campi del prodotto
                                     ],
                                 ],
                             ],
                         ],
                     ],
                 ]);
    }

    /**
     * Test per il metodo index con filtro sullo status.
     *
     * Il test crea due ordini (uno 'pending' e uno 'completed') e
     * invia una richiesta GET con ?status=pending, verificando che tutti gli
     * ordini restituiti abbiano status 'pending'.
     */
    public function testIndexFiltersOrdersByStatus()
    {
        // Creiamo due ordini con status diversi.
        $pendingOrder = Order::factory()->create(['status' => 'pending']);
        $completedOrder = Order::factory()->create(['status' => 'completed']);

        // Creiamo un prodotto e associamo un articolo a ciascun ordine.
        $product = Product::factory()->create([
            'stock_quantity' => 100,
            'price'          => 50,
        ]);
        $pendingOrder->items()->create([
            'product_id' => $product->id,
            'quantity'   => 1,
            'price'      => $product->price,
        ]);
        $completedOrder->items()->create([
            'product_id' => $product->id,
            'quantity'   => 2,
            'price'      => $product->price,
        ]);

        // Richiesta con filtro: status=pending
        $response = $this->getJson('/api/v1/orders?status=pending');

        $response->assertStatus(200);
        $data = $response->json('data');

        // Controlliamo che tutti gli ordini ritornati abbiano status 'pending'
        foreach ($data as $order) {
            $this->assertEquals('pending', $order['status']);
        }
    }

    /**
     * Test per la validazione del metodo index.
     *
     * Se viene passato un valore non valido per 'status', il controller
     * deve restituire un errore di validazione (422).
     */
    public function testIndexValidationFailsWithInvalidStatus()
    {
        $response = $this->getJson('/api/v1/orders?status=invalid');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('status');
    }

    /**
     * Test per il metodo store che crea un nuovo ordine.
     *
     * Il test:
     * - Crea un prodotto con una quantità in magazzino nota.
     * - Invia una richiesta POST a /api/v1/orders con un item che fa riferimento
     *   al prodotto.
     * - Verifica che la risposta contenga i dati dell'ordine creato e che nel DB
     *   l'ordine sia presente con status 'pending'.
     * - Verifica anche che lo stock del prodotto sia decrementato della quantità richiesta.
     */
    public function testStoreCreatesNewOrder()
    {
        // Creiamo un prodotto con stock iniziale 20.
        $product = Product::factory()->create([
            'stock_quantity' => 20,
            'price'          => 150,
        ]);

        $data = [
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity'   => 3,
                ],
            ],
        ];

        $response = $this->postJson('/api/v1/orders', $data);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         'id',
                         'order_number',
                         'status',
                         'items' => [
                             '*' => [
                                 'id',
                                 'product_id',
                                 'quantity',
                                 'price',
                                 'product' => [
                                     'id',
                                 ],
                             ],
                         ],
                     ],
                 ]);

        // Verifichiamo che l'ordine sia stato creato con status 'pending'.
        $this->assertDatabaseHas('orders', [
            'status' => 'pending',
        ]);

        // Il prodotto dovrebbe avere il magazzino decrementato di 3.
        $expectedStock = $product->stock_quantity - 3;
        $this->assertDatabaseHas('products', [
            'id'             => $product->id,
            'stock_quantity' => $expectedStock,
        ]);
    }

    /**
     * Test per la validazione del metodo store.
     *
     * Inviando dati non validi (ad esempio, senza la chiave 'items'),
     * il controller deve restituire un errore di validazione.
     */
    public function testStoreValidationFailsWithInvalidData()
    {
        $response = $this->postJson('/api/v1/orders', []);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('items');
    }

    /**
     * Test per il metodo update che modifica un ordine esistente.
     *
     * Il test simula un ordine già esistente con un item:
     * - Dopo la creazione, si simula il decremento dello stock (come farebbe il metodo store).
     * - Invia una richiesta PUT a /api/v1/orders/{order} per aggiornare la quantità dell'item.
     * - Verifica che lo stock del prodotto sia ulteriormente decrementato in base alla differenza.
     */
    public function testUpdateModifiesOrderItems()
    {
        // Creiamo un prodotto con stock iniziale 100.
        $product = Product::factory()->create([
            'stock_quantity' => 100,
            'price'          => 200,
        ]);

        // Creiamo un ordine con status 'pending' e aggiungiamo un item con quantità 2.
        $order = Order::factory()->create(['status' => 'pending']);
        $order->items()->create([
            'product_id' => $product->id,
            'quantity'   => 2,
            'price'      => $product->price,
        ]);
        // Simuliamo il decremento dello stock che sarebbe avvenuto durante la creazione dell'ordine.
        $product->decrement('stock_quantity', 2);

        // A questo punto, lo stock del prodotto deve essere 100 - 2 = 98.
        $product->refresh();
        $this->assertEquals(98, $product->stock_quantity);

        // Inviamo una richiesta di update per modificare la quantità dell'item a 5.
        // La differenza è +3, dunque il metodo update decrementerà ulteriormente lo stock di 3.
        $updateData = [
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity'   => 5,
                ],
            ],
        ];

        $response = $this->putJson("/api/v1/orders/{$order->id}", $updateData);

        $response->assertStatus(200)
                 ->assertJsonPath('data.items.0.quantity', 5);

        // Il nuovo stock atteso: 98 - (5 - 2) = 95.
        $this->assertDatabaseHas('products', [
            'id'             => $product->id,
            'stock_quantity' => 95,
        ]);
    }

    /**
     * Test per il metodo updateStatus che cambia lo stato di un ordine.
     *
     * Il test:
     * - Crea un ordine con stato 'pending'.
     * - Invia una richiesta PATCH a /api/v1/orders/{order}/status per aggiornare
     *   lo stato a 'completed'.
     * - Verifica che lo stato dell'ordine sia aggiornato nel DB e nella risposta.
     */
    public function testUpdateStatusChangesOrderStatus()
    {
        $order = Order::factory()->create(['status' => 'pending']);

        $data = ['status' => 'completed'];
        $response = $this->patchJson("/api/v1/orders/{$order->id}/status", $data);

        $response->assertStatus(200)
                 ->assertJsonPath('data.status', 'completed');

        $this->assertDatabaseHas('orders', [
            'id'     => $order->id,
            'status' => 'completed',
        ]);
    }

    /**
     * Test per il metodo destroy che elimina un ordine e ripristina il magazzino.
     */
    public function testDestroyDeletesOrderAndRestoresStock()
    {
        // Creiamo un prodotto con stock iniziale 50.
        $product = Product::factory()->create([
            'stock_quantity' => 50,
            'price'          => 300,
        ]);

        // Creiamo un ordine e aggiungiamo un item con quantità 4.
        $order = Order::factory()->create(['status' => 'pending']);
        $order->items()->create([
            'product_id' => $product->id,
            'quantity'   => 4,
            'price'      => $product->price,
        ]);

        // Simuliamo il decremento dello stock al momento della creazione.
        $product->decrement('stock_quantity', 4);

        // Verifichiamo che l'ordine sia stato creato
        $this->assertDatabaseHas('orders', ['id' => $order->id]);

        // Inviamo la richiesta DELETE per eliminare l'ordine.
        $response = $this->deleteJson("/api/v1/orders/{$order->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Ordine eliminato con successo',
                 ]);

        // Verifichiamo che l'ordine non esista più nel database.
        $this->assertDatabaseMissing('orders', [
            'id' => $order->id,
        ]);

        // Verifichiamo che lo stock sia stato ripristinato
        $this->assertDatabaseHas('products', [
            'id'             => $product->id,
            'stock_quantity' => 50,
        ]);
    }
}