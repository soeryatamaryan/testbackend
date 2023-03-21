<?php

namespace Tests\Feature;

// Library
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use App\Http\Resources\DebitCardTransactionResource;

// Test
use Tests\TestCase;

// Model
use App\Models\DebitCard;
use App\Models\User;
use App\Models\DebitCardTransaction;

class DebitCardTransactionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected DebitCard $debitCard;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->debitCard = DebitCard::factory()->create([
            'user_id' => $this->user->id
        ]);
        Passport::actingAs($this->user);
    }

    public function testCustomerCanSeeAListOfDebitCardTransactions()
    {
        // get /debit-card-transactions
        $request=[
            "debit_card_id" => $this->debitCard->id
        ];

        DebitCardTransaction::factory()->create($request);

        $this->json('GET','api/debit-card-transactions', $request)
            ->assertOk()
            ->assertJsonStructure([
                '*' => ['amount','currency_code']
            ]);
    }

    public function testCustomerCannotSeeAListOfDebitCardTransactionsOfOtherCustomerDebitCard()
    {
        // get /debit-card-transactions
        $request=[
            "debit_card_id" => $this->debitCard->id
        ];

        DebitCardTransaction::factory()->create($request);

        $getDebitCard = DebitCard::find($this->debitCard->id)
            ->debitCardTransactions()
            ->get();

        $otherUser = User::factory()->create();

        $createDebitCard = DebitCard::factory()->create(['user_id' => $otherUser->id]);

        DebitCardTransaction::factory()->create(['debit_card_id' => $createDebitCard->id]);

        $this->json('GET','api/debit-card-transactions', $request)
            ->assertOk()
            ->assertJson(json_decode(json_encode(DebitCardTransactionResource::collection($getDebitCard)),true));
    }

    public function testCustomerCanCreateADebitCardTransaction()
    {
        // post /debit-card-transactions
        $currencyCode = DebitCardTransaction::CURRENCIES[array_rand(DebitCardTransaction::CURRENCIES)];
        $request=[
            "debit_card_id"     =>  $this->debitCard->id,
            "amount"            =>  rand(10000, 999999),
            "currency_code"     =>  $currencyCode
        ];

        $this->postJson('api/debit-card-transactions', $request)
            ->assertCreated()
            ->assertJsonStructure(['amount','currency_code']);

        $this->assertDatabaseHas('debit_card_transactions', [
            'id'            => $this->debitCard->debitCardTransactions->first()->id,
            'debit_card_id' => $this->user->id,
        ]);
    }

    public function testCustomerCannotCreateADebitCardTransactionToOtherCustomerDebitCard()
    {
        // post /debit-card-transactions
        $otherUser          = User::factory()->create();

        $createDebitCard    = DebitCard::factory()->create(['user_id' => $otherUser->id]);

        $currencyCode       = DebitCardTransaction::CURRENCIES[array_rand(DebitCardTransaction::CURRENCIES)];

        $request=[
            "debit_card_id"     =>  $createDebitCard->id,
            "amount"            =>  rand(10000, 999999),
            "currency_code"     =>  $currencyCode
        ];

        $this->postJson('api/debit-card-transactions', $request)
            ->assertStatus(403);
    }

    public function testCustomerCanSeeADebitCardTransaction()
    {
        // get /debit-card-transactions/{debitCardTransaction}
        $createDebitCard = DebitCardTransaction::factory()->create(
            [
                'debit_card_id' => $this->debitCard->id
            ]
        );

        $this->getJson('api/debit-card-transactions/' . $createDebitCard->id)
            ->assertOk()
            ->assertJsonStructure(['amount','currency_code']);
    }

    public function testCustomerCannotSeeADebitCardTransactionAttachedToOtherCustomerDebitCard()
    {
        // get /debit-card-transactions/{debitCardTransaction}
        $otherUser = User::factory()->create();
        $otherDebitCard = DebitCard::factory()->create([
            'user_id' => $otherUser->id
        ]);
        $otherDebitCardTransaction = DebitCardTransaction::factory()->create(['debit_card_id' => $otherDebitCard->id]);

        $this->getJson('api/debit-card-transactions/' . $this->debitCard->id)
        ->assertStatus(403);
    }

    // Extra bonus for extra tests :)
}
