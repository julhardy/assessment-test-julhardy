<?php

namespace Tests\Feature;

use App\Models\DebitCard;
use App\Models\DebitCardTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

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
        DebitCardTransaction::factory()->create([
            'debit_card_id' => $this->debitCard->id,
            'amount' => 100000,
            'currency_code' => 'idr'
        ]);
        $response = $this->getJson("/debit-card-transactions");
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['amount', 'currency_code']
                ]
            ]);
    }

    public function testCustomerCannotSeeAListOfDebitCardTransactionsOfOtherCustomerDebitCard()
    {
        // get /debit-card-transactions
        $otheruser = User::factory()->create();
        $otherdc = DebitCard::factory()->create([
            'user_id' => $otheruser->id
        ]);
        DebitCardTransaction::factory()->create([
            'debit_card_id' => $otherdc->id
        ]);

        $response = $this->getJson("/debit-card-transactions");
        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    public function testCustomerCanCreateADebitCardTransaction()
    {
        // post /debit-card-transactions
        $req = ['amount' => 100000, 'currency_code' => 'idr'];
        $response = $this->postJson('/debit-card-transactions', $req);
        $response->assertStatus(201)
            ->assertJsonFragment($req)
            ->assertJsonStructure(['amount', 'currency_code']);

        $this->assertDatabaseHas(
            'debit_card_transactions',
            array_merge($req, ['debit_card_id' => $this->debitCard->id])
        );
    }

    public function testCustomerCannotCreateADebitCardTransactionToOtherCustomerDebitCard()
    {
        // post /debit-card-transactions
        $otheruser = User::factory()->create();
        $otherdc = DebitCard::factory()->create([
            'user_id' => $otheruser->id
        ]);
        DebitCardTransaction::factory()->create([
            'debit_card_id' => $otherdc->id
        ]);
        $req = ['amount' => 100000, 'currency_code' => 'idr'];
        $response = $this->postJson('/debit-card-transactions', $req);
        $response->assertStatus(403);

        $this->assertDatabaseMissing('debit_card_transactions',
            array_merge($req, ['debit_card_id' => $otherdc->id])
        );
    }

    public function testCustomerCanSeeADebitCardTransaction()
    {
        // get /debit-card-transactions/{debitCardTransaction}
        $dcTrans = DebitCardTransaction::factory()->create([
            'debit_card_id'=>$this->debitCard->id,
            'amount'=>100000,
            'currency_code'=>"idr"
        ]);

        $response = $this->getJson("/debit-card-transactions"."/".$dcTrans->id);
        $response->assertStatus(200)
        ->assertJsonStructure(['*' => ['amount', 'currency_code']]);
    }

    public function testCustomerCannotSeeADebitCardTransactionAttachedToOtherCustomerDebitCard()
    {
        // get /debit-card-transactions/{debitCardTransaction}
        $otherUser = User::factory()->create();
        $otherdc = DebitCard::factory()->create([
            'user_id'=>$otherUser->id
        ]);
        $dcTrans = DebitCardTransaction::factory()->create([
            'debit_card_id'=>$otherdc->id,
            'amount'=>100000,
            'currency_code'=>"idr"
        ]);

        $response = $this->getJson("/debit-card-transactions"."/".$dcTrans->id);
        $response->assertStatus(403);
    }

    // Extra bonus for extra tests :)
}
